<?php
namespace Controllers;

use Models\Product;
use Models\Category;
use Models\Suggestion;
use Models\Setting;
use Models\Log;

class DashboardController {
    private $productModel;
    private $categoryModel;
    private $suggestionModel;
    private $settingModel;
    private $logModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->suggestionModel = new Suggestion();
        $this->settingModel = new Setting();
        $this->logModel = new Log();
    }

    /**
     * Get all statistics needed for the cards
     */
    public function getCardMetrics() {
        $total_products = $this->productModel->getCount();
        $total_categories = $this->categoryModel->getCount();
        
        // Fetch scanned products count from settings
        $scanned_products = (int)$this->settingModel->get('scanned_products_count', 0);
        
        // Fetch suggestion status counts
        $sugg_metrics = $this->suggestionModel->getMetrics();

        return [
            'total_products' => $total_products,
            'total_categories' => $total_categories,
            'scanned_products' => $scanned_products,
            'suspicious_products' => $sugg_metrics['pending'],
            'approved_corrections' => $sugg_metrics['approved'],
            'ignored_suggestions' => $sugg_metrics['ignored']
        ];
    }

    /**
     * Get data for the Category Distribution chart
     */
    public function getCategoryDistributionData() {
        return $this->categoryModel->getCategoryDistribution();
    }

    /**
     * Get data for the Confidence Distribution chart
     */
    public function getConfidenceDistributionData() {
        return $this->suggestionModel->getConfidenceDistribution();
    }

    /**
     * Get latest logs for the audit trail
     */
    public function getRecentLogs($limit = 5) {
        return $this->logModel->getLatestLogs($limit);
    }
}
