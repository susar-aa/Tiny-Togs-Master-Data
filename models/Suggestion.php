<?php
namespace Models;

use Config\Database;
use PDO;

class Suggestion {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get counts for dashboard metrics
     * @return array
     */
    public function getMetrics() {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'ignored' THEN 1 ELSE 0 END) as ignored_count
                FROM category_suggestions";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return [
            'pending' => (int)($row['pending_count'] ?? 0),
            'approved' => (int)($row['approved_count'] ?? 0),
            'ignored' => (int)($row['ignored_count'] ?? 0)
        ];
    }

    /**
     * Create or update a suggestion for a product
     */
    public function addOrUpdateSuggestion($product_id, $current_category, $suggested_category, $matched_keyword, $confidence_score) {
        // Check if suggestion already exists
        $sql = "SELECT id, status FROM category_suggestions WHERE product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Only update if it is currently pending
            if ($existing['status'] === 'pending') {
                $update_sql = "UPDATE category_suggestions 
                               SET current_category = :current,
                                   suggested_category = :suggested,
                                   matched_keyword = :keyword,
                                   confidence_score = :score,
                                   created_at = CURRENT_TIMESTAMP
                               WHERE id = :id";
                $update_stmt = $this->db->prepare($update_sql);
                return $update_stmt->execute([
                    ':current' => $current_category,
                    ':suggested' => $suggested_category,
                    ':keyword' => $matched_keyword,
                    ':score' => $confidence_score,
                    ':id' => $existing['id']
                ]);
            }
            // If already approved or ignored, we preserve that decision
            return true;
        }

        // Create new suggestion
        $insert_sql = "INSERT INTO category_suggestions (product_id, current_category, suggested_category, matched_keyword, confidence_score, status)
                       VALUES (:product_id, :current, :suggested, :keyword, :score, 'pending')";
        $insert_stmt = $this->db->prepare($insert_sql);
        return $insert_stmt->execute([
            ':product_id' => $product_id,
            ':current' => $current_category,
            ':suggested' => $suggested_category,
            ':keyword' => $matched_keyword,
            ':score' => $confidence_score
        ]);
    }

    /**
     * DataTables Server-Side Search/Filter/Pagination Query
     */
    public function getFilteredSuggestions($filters, $search, $start, $length, $order_column, $order_dir) {
        $where_clauses = [];
        $params = [];

        // Base query
        $sql = "SELECT s.id, s.product_id, p.product_code, p.product_name, s.current_category, 
                       s.suggested_category, s.matched_keyword, s.confidence_score, s.status, s.created_at
                FROM category_suggestions s
                JOIN products p ON s.product_id = p.id";

        // Filtering
        if (!empty($filters['status'])) {
            $where_clauses[] = "s.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['current_category'])) {
            $where_clauses[] = "s.current_category = :current_cat";
            $params[':current_cat'] = $filters['current_category'];
        }
        if (!empty($filters['suggested_category'])) {
            $where_clauses[] = "s.suggested_category = :suggested_cat";
            $params[':suggested_cat'] = $filters['suggested_category'];
        }
        if (isset($filters['min_confidence']) && $filters['min_confidence'] !== '') {
            $where_clauses[] = "s.confidence_score >= :min_conf";
            $params[':min_conf'] = (int)$filters['min_confidence'];
        }

        // Global Search
        if (!empty($search)) {
            $where_clauses[] = "(p.product_name LIKE :search 
                                OR p.product_code LIKE :search2 
                                OR s.current_category LIKE :search3 
                                OR s.suggested_category LIKE :search4 
                                OR s.matched_keyword LIKE :search5)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
            $params[':search4'] = "%{$search}%";
            $params[':search5'] = "%{$search}%";
        }

        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        // Sorting mapping
        $columns_map = [
            0 => 's.id',
            1 => 'p.product_code',
            2 => 'p.product_name',
            3 => 's.current_category',
            4 => 's.suggested_category',
            5 => 's.matched_keyword',
            6 => 's.confidence_score',
            7 => 's.status',
            8 => 's.created_at'
        ];

        $sort_col = $columns_map[$order_column] ?? 's.created_at';
        $sort_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$sort_col} {$sort_dir}";

        // Limit & Offset
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind params
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$length, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$start, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get filtered count for DataTables
     */
    public function getFilteredSuggestionsCount($filters, $search) {
        $where_clauses = [];
        $params = [];

        $sql = "SELECT COUNT(*) as cnt
                FROM category_suggestions s
                JOIN products p ON s.product_id = p.id";

        if (!empty($filters['status'])) {
            $where_clauses[] = "s.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['current_category'])) {
            $where_clauses[] = "s.current_category = :current_cat";
            $params[':current_cat'] = $filters['current_category'];
        }
        if (!empty($filters['suggested_category'])) {
            $where_clauses[] = "s.suggested_category = :suggested_cat";
            $params[':suggested_cat'] = $filters['suggested_category'];
        }
        if (isset($filters['min_confidence']) && $filters['min_confidence'] !== '') {
            $where_clauses[] = "s.confidence_score >= :min_conf";
            $params[':min_conf'] = (int)$filters['min_confidence'];
        }

        if (!empty($search)) {
            $where_clauses[] = "(p.product_name LIKE :search 
                                OR p.product_code LIKE :search2 
                                OR s.current_category LIKE :search3 
                                OR s.suggested_category LIKE :search4 
                                OR s.matched_keyword LIKE :search5)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
            $params[':search4'] = "%{$search}%";
            $params[':search5'] = "%{$search}%";
        }

        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Get total suggestions count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as cnt FROM category_suggestions";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Get single suggestion detail by ID
     */
    public function getById($id) {
        $sql = "SELECT s.id, s.product_id, p.product_code, p.product_name, p.selling_price, p.supplier,
                       s.current_category, s.suggested_category, s.matched_keyword, s.confidence_score, s.status
                FROM category_suggestions s
                JOIN products p ON s.product_id = p.id
                WHERE s.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Approve a suggestion: update category in products table and status to 'approved'
     */
    public function approve($id) {
        $suggestion = $this->getById($id);
        if (!$suggestion) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Update product category
            $prod = new Product();
            $prod->updateCategory($suggestion['product_id'], $suggestion['suggested_category']);

            // Update suggestion status
            $sql = "UPDATE category_suggestions SET status = 'approved' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            // Log it
            $log = new Log();
            $log->record('Approvals', "Approved category change for product code '{$suggestion['product_code']}': '{$suggestion['current_category']}' -> '{$suggestion['suggested_category']}' (matched: '{$suggestion['matched_keyword']}', confidence: {$suggestion['confidence_score']}%)");

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ignore a suggestion
     */
    public function ignore($id) {
        $suggestion = $this->getById($id);
        if (!$suggestion) {
            return false;
        }

        $sql = "UPDATE category_suggestions SET status = 'ignored' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        if ($result) {
            $log = new Log();
            $log->record('Approvals', "Ignored category suggestion for product code '{$suggestion['product_code']}': Suggested '{$suggestion['suggested_category']}'");
        }

        return $result;
    }

    /**
     * Bulk approve multiple suggestions
     */
    public function bulkApprove($ids) {
        if (empty($ids)) return 0;
        
        $success_count = 0;
        foreach ($ids as $id) {
            if ($this->approve($id)) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            $log = new Log();
            $log->record('Bulk Updates', "Bulk approved {$success_count} category suggestions");
        }

        return $success_count;
    }

    /**
     * Bulk ignore multiple suggestions
     */
    public function bulkIgnore($ids) {
        if (empty($ids)) return 0;

        $in_clause = implode(',', array_map('intval', $ids));
        
        $sql = "UPDATE category_suggestions SET status = 'ignored' WHERE id IN ({$in_clause}) AND status = 'pending'";
        $stmt = $this->db->query($sql);
        $affected = $stmt->rowCount();

        if ($affected > 0) {
            $log = new Log();
            $log->record('Bulk Updates', "Bulk ignored {$affected} category suggestions");
        }

        return $affected;
    }

    /**
     * Confidence score distribution (for dashboard chart)
     */
    public function getConfidenceDistribution() {
        $sql = "SELECT confidence_score, COUNT(*) as cnt 
                FROM category_suggestions 
                GROUP BY confidence_score 
                ORDER BY confidence_score ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieve all suggestions matching filter for report export
     */
    public function getSuggestionsForExport($filters) {
        $where_clauses = [];
        $params = [];

        $sql = "SELECT p.product_name, p.product_code, s.current_category, s.suggested_category, 
                       c.main_category, s.confidence_score, s.matched_keyword, s.status
                FROM category_suggestions s
                JOIN products p ON s.product_id = p.id
                LEFT JOIN categories c ON s.suggested_category = c.category_name";

        if (!empty($filters['status'])) {
            $where_clauses[] = "s.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['current_category'])) {
            $where_clauses[] = "s.current_category = :current_cat";
            $params[':current_cat'] = $filters['current_category'];
        }
        if (!empty($filters['suggested_category'])) {
            $where_clauses[] = "s.suggested_category = :suggested_cat";
            $params[':suggested_cat'] = $filters['suggested_category'];
        }
        if (isset($filters['min_confidence']) && $filters['min_confidence'] !== '') {
            $where_clauses[] = "s.confidence_score >= :min_conf";
            $params[':min_conf'] = (int)$filters['min_confidence'];
        }

        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql .= " ORDER BY s.confidence_score DESC, p.product_name ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
