<?php
namespace Controllers;

use Config\Database;
use Models\Product;
use Models\Keyword;
use Models\Suggestion;
use Models\Setting;
use Models\Log;
use PDO;

class ScanController {
    
    /**
     * Helper to clean text according to Advanced Matching rules (Feature 8)
     */
    private function cleanText($text) {
        // Lowercase
        $text = mb_strtolower($text, 'UTF-8');
        // Replace hyphens and other word-breaking punctuation with spaces
        $text = str_replace(['-','/','&','_',',','.'], ' ', $text);
        // Remove special characters (keep alphanumeric, spaces)
        $text = preg_replace('/[^a-z0-9\s]/u', '', $text);
        // Replace multiple spaces with single space
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Checks if keyword matches product name with optional fuzzy matching (Feature 9)
     */
    private function isKeywordMatch($product_name_cleaned, $keyword_cleaned, $enable_fuzzy) {
        if (empty($keyword_cleaned) || empty($product_name_cleaned)) {
            return false;
        }

        // 1. Exact Match via Word Boundaries (to avoid matching substrings inside words, e.g. "cot" in "cotton")
        // Also support basic singular/plural matches (e.g. pillow vs pillows)
        $pattern = '/\b' . preg_quote($keyword_cleaned, '/') . '(s|es)?\b/u';
        if (preg_match($pattern, $product_name_cleaned)) {
            return true;
        }

        // 2. Concatenated/Sub-word matches (e.g., "tshirt" for "shirt", "babyromper" for "romper")
        // Only run for keywords of length >= 4 to avoid false positive substring matches (like "cot" in "cotton")
        if (strlen($keyword_cleaned) >= 4) {
            $product_words = explode(' ', $product_name_cleaned);
            foreach ($product_words as $pw) {
                if (str_ends_with($pw, $keyword_cleaned) || str_starts_with($pw, $keyword_cleaned)) {
                    return true;
                }
            }
        }

        if (!$enable_fuzzy) {
            return false;
        }

        // 2. Fuzzy word-level comparison
        $product_words = explode(' ', $product_name_cleaned);
        $keyword_words = explode(' ', $keyword_cleaned);
        
        if (count($keyword_words) === 1) {
            $kw = $keyword_words[0];
            if (strlen($kw) < 3) return false;

            foreach ($product_words as $pw) {
                if (strlen($pw) < 3) continue;

                $dist = levenshtein($kw, $pw);
                $max_dist = strlen($kw) > 5 ? 2 : 1;

                if ($dist <= $max_dist) {
                    return true;
                }
            }
        } else {
            // Multi-word keyword fuzzy comparison
            $kw_count = count($keyword_words);
            $pw_count = count($product_words);
            
            for ($i = 0; $i <= $pw_count - $kw_count; $i++) {
                $match = true;
                for ($j = 0; $j < $kw_count; $j++) {
                    $pw = $product_words[$i + $j];
                    $kw = $keyword_words[$j];
                    
                    if (strlen($pw) < 3 || strlen($kw) < 3) {
                        if ($pw !== $kw) {
                            $match = false;
                            break;
                        }
                        continue;
                    }

                    $dist = levenshtein($kw, $pw);
                    $max_dist = strlen($kw) > 5 ? 2 : 1;

                    if ($dist > $max_dist) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Initialize scan: clear pending suggestions and return total products to scan
     */
    public function initScan() {
        $db = Database::getConnection();
        
        // Clear all pending suggestions
        $sql = "DELETE FROM category_suggestions WHERE status = 'pending'";
        $db->exec($sql);

        // Get total products count
        $productModel = new Product();
        $total_products = $productModel->getCount();

        return [
            'status' => 'success',
            'total_products' => $total_products
        ];
    }

    /**
     * Scan a batch of products and generate suggestions
     */
    public function scanBatch($offset, $limit) {
        $db = Database::getConnection();
        
        // Load settings
        $settingModel = new Setting();
        $min_confidence = (int)$settingModel->get('min_confidence_threshold', 70);
        $enable_fuzzy = (bool)$settingModel->get('enable_fuzzy_matching', 1);
        $max_suggestions = (int)$settingModel->get('max_suggestions_per_product', 1);
        $min_kw_len = (int)$settingModel->get('keyword_min_length', 3);

        // Load all keywords with categories
        $keywordModel = new Keyword();
        $all_keywords = $keywordModel->getAllWithCategories();

        // Load products batch
        $productModel = new Product();
        $products = $productModel->getBatch($offset, $limit);
        
        if (empty($products)) {
            return [
                'status' => 'success',
                'scanned' => 0,
                'suggestions_created' => 0,
                'done' => true
            ];
        }

        $suggestionModel = new Suggestion();
        $suggestions_created = 0;

        foreach ($products as $p) {
            $product_id = $p['id'];
            $product_name = $p['product_name'];
            $current_cat = $p['current_category'];

            $product_name_cleaned = $this->cleanText($product_name);
            
            // Group keyword matches by category name
            $category_matches = [];

            foreach ($all_keywords as $kw) {
                $raw_kw = $kw['keyword'];
                if (strlen($raw_kw) < $min_kw_len) {
                    continue; // Skip short keywords based on setting
                }

                $kw_cleaned = $this->cleanText($raw_kw);
                
                if ($this->isKeywordMatch($product_name_cleaned, $kw_cleaned, $enable_fuzzy)) {
                    $cat_name = $kw['category_name'];
                    if (!isset($category_matches[$cat_name])) {
                        $category_matches[$cat_name] = [];
                    }
                    $category_matches[$cat_name][] = $raw_kw;
                }
            }

            // Create suggestion candidates
            $candidates = [];

            foreach ($category_matches as $cat_name => $matched_kws) {
                // Determine confidence score based on matches count
                $match_count = count(array_unique($matched_kws));
                
                if ($match_count === 1) {
                    $confidence = 80;
                } elseif ($match_count === 2) {
                    $confidence = 90;
                } else {
                    $confidence = 98;
                }

                // Skip suggestion if category is same as product current category
                // (It's already classified correctly!)
                if ($this->cleanText($cat_name) === $this->cleanText($current_cat)) {
                    continue;
                }

                // Skip if below settings confidence threshold
                if ($confidence < $min_confidence) {
                    continue;
                }

                $candidates[] = [
                    'suggested_category' => $cat_name,
                    'matched_keywords' => implode(', ', array_unique($matched_kws)),
                    'confidence_score' => $confidence,
                    'match_count' => $match_count
                ];
            }

            if (empty($candidates)) {
                continue;
            }

            // Sort candidates by:
            // 1. Confidence Score (descending)
            // 2. Match Count (descending)
            usort($candidates, function($a, $b) {
                if ($b['confidence_score'] !== $a['confidence_score']) {
                    return $b['confidence_score'] <=> $a['confidence_score'];
                }
                return $b['match_count'] <=> $a['match_count'];
            });

            // Write suggestions up to max suggestions per product
            $limit_suggestions = min(count($candidates), $max_suggestions);
            for ($i = 0; $i < $limit_suggestions; $i++) {
                $cand = $candidates[$i];
                $success = $suggestionModel->addOrUpdateSuggestion(
                    $product_id,
                    $current_cat,
                    $cand['suggested_category'],
                    $cand['matched_keywords'],
                    $cand['confidence_score']
                );
                if ($success) {
                    $suggestions_created++;
                }
            }
        }

        return [
            'status' => 'success',
            'scanned' => count($products),
            'suggestions_created' => $suggestions_created,
            'done' => false
        ];
    }

    /**
     * Finalize scanning: log action and save scanned products count
     */
    public function finalizeScan($total_scanned, $total_suggestions) {
        // Save total scanned in settings so we can display it on the Dashboard card
        $settingModel = new Setting();
        $settingModel->set('scanned_products_count', $total_scanned);

        $logModel = new Log();
        $logModel->record('Scan Execution', "Scanned {$total_scanned} products. Generated {$total_suggestions} new suspicious category suggestions.");

        return [
            'status' => 'success',
            'message' => "Successfully scanned {$total_scanned} products. Found {$total_suggestions} issues."
        ];
    }
}
