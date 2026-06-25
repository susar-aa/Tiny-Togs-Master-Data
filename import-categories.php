<?php
require_once __DIR__ . '/config/bootstrap.php';

use Controllers\ImportController;
use Models\Category;

// Route AJAX requests
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_products') {
        header('Content-Type: application/json');
        $category_name = $_GET['category'] ?? '';
        
        $db = \Config\Database::getConnection();
        $stmt = $db->prepare("SELECT product_code, product_name, price, supplier FROM products WHERE current_category = :cat ORDER BY product_name ASC");
        $stmt->execute([':cat' => $category_name]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'products' => $products]);
        exit;
    }

    if ($_GET['action'] === 'delete') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $catModel = new Category();
        $res = $catModel->deleteCategory($id);
        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete category.']);
        }
        exit;
    }

    if ($_GET['action'] === 'save') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['category_name'] ?? '');
        $main_cat = trim($_POST['main_category'] ?? '');
        $items = trim($_POST['including_items'] ?? '');
        
        $catModel = new Category();
        if ($id > 0) {
            $res = $catModel->updateCategory($id, $name, $items, $main_cat);
            $msg = 'Category updated successfully.';
        } else {
            $res = $catModel->importCategory($name, $items, $main_cat);
            $msg = 'Category created successfully.';
        }
        
        if ($res) {
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save category.']);
        }
        exit;
    }

    if ($_GET['action'] === 'transfer') {
        header('Content-Type: application/json');
        $from_cat = trim($_POST['from_category'] ?? '');
        $to_cat = trim($_POST['to_category'] ?? '');

        if (empty($from_cat) || empty($to_cat) || $from_cat === $to_cat) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid category parameters.']);
            exit;
        }

        $db = \Config\Database::getConnection();
        $stmt = $db->prepare("UPDATE products SET current_category = :to_cat WHERE current_category = :from_cat");
        $res = $stmt->execute([
            ':to_cat' => $to_cat,
            ':from_cat' => $from_cat
        ]);

        if ($res) {
            $count = $stmt->rowCount();
            echo json_encode(['status' => 'success', 'message' => "Successfully transferred {$count} products."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error during transfer.']);
        }
        exit;
    }

    if ($_GET['action'] === 'bulk_update_main_category') {
        header('Content-Type: application/json');
        $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
        $main_category = trim($_POST['main_category'] ?? '');

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No categories selected.']);
            exit;
        }

        $catModel = new Category();
        $res = $catModel->bulkUpdateMainCategory($ids, $main_category);

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'Main Category updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update Main Category.']);
        }
        exit;
    }
}

$message = null;
$message_type = 'success';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['category_file'])) {
    $file = $_FILES['category_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload failed. Please try again.";
        $message_type = 'danger';
    } else {
        $importCtrl = new ImportController();
        $res = $importCtrl->importCategories($file['tmp_name']);
        
        $message = $res['message'];
        $message_type = ($res['status'] === 'success') ? 'success' : 'danger';
    }
}

// Fetch categories for display
$categoryModel = new Category();
$categories = $categoryModel->getAll();
$mainCategoryModel = new \Models\MainCategory();
$mainCategories = $mainCategoryModel->getAll();

include __DIR__ . '/views/layout/header.php';
?>

<!-- ============================================ -->
<!-- iOS-Inspired Light Theme Styling             -->
<!-- ============================================ -->
<style>
    :root {
        --ios-bg: #f2f2f7;
        --ios-card: #ffffff;
        --ios-blue: #007aff;
        --ios-blue-hover: #0066d6;
        --ios-green: #34c759;
        --ios-red: #ff3b30;
        --ios-orange: #ff9500;
        --ios-teal: #30b0c7;
        --ios-purple: #af52de;
        --ios-gray: #8e8e93;
        --ios-gray-2: #aeaeb2;
        --ios-gray-3: #c7c7cc;
        --ios-gray-4: #d1d1d6;
        --ios-gray-5: #e5e5ea;
        --ios-gray-6: #f2f2f7;
        --ios-label: #1c1c1e;
        --ios-secondary-label: #6b6b70;
        --ios-radius: 18px;
        --ios-radius-sm: 12px;
        --ios-radius-xs: 10px;
        --ios-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        --ios-shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.04);
        --ios-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    body {
        background-color: var(--ios-bg) !important;
        font-family: var(--ios-font);
        color: var(--ios-label);
        -webkit-font-smoothing: antialiased;
    }

    .ios-wrap {
        max-width: 1500px;
        margin: 0 auto;
        padding: 1.5rem 1rem 3rem;
    }

    /* ---------- Page Header ---------- */
    .ios-page-header { margin-bottom: 1.75rem; }
    .ios-page-title {
        font-size: 1.85rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--ios-label);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .ios-page-title .icon-badge {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #007aff, #4aa3ff);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.35);
    }
    .ios-page-subtitle {
        color: var(--ios-secondary-label);
        font-size: 0.92rem;
        margin: 0.4rem 0 0 0;
    }

    /* ---------- Buttons ---------- */
    .ios-btn {
        border: none;
        border-radius: 980px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.55rem 1.15rem;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        line-height: 1;
    }
    .ios-btn-sm { padding: 0.45rem 0.9rem; font-size: 0.8rem; }
    .ios-btn-primary {
        background: var(--ios-blue);
        color: #fff;
        box-shadow: 0 4px 14px rgba(0, 122, 255, 0.3);
    }
    .ios-btn-primary:hover {
        background: var(--ios-blue-hover);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(0, 122, 255, 0.4);
    }
    .ios-btn-secondary {
        background: var(--ios-gray-5);
        color: var(--ios-label);
    }
    .ios-btn-secondary:hover { background: var(--ios-gray-4); color: var(--ios-label); }
    .ios-btn-info {
        background: var(--ios-teal);
        color: #fff;
        box-shadow: 0 4px 14px rgba(48, 176, 199, 0.3);
    }
    .ios-btn-info:hover { background: #2698ac; color: #fff; transform: translateY(-1px); }
    .ios-btn-outline-soft {
        background: var(--ios-gray-5);
        color: var(--ios-secondary-label);
    }
    .ios-btn-outline-soft:hover { background: var(--ios-gray-4); color: var(--ios-label); }

    /* Icon action buttons in table */
    .ios-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.18s ease;
        margin: 0 1px;
    }
    .ios-icon-btn.info    { background: rgba(48,176,199,0.12);  color: var(--ios-teal); }
    .ios-icon-btn.info:hover    { background: var(--ios-teal);  color: #fff; }
    .ios-icon-btn.edit    { background: rgba(0,122,255,0.12);   color: var(--ios-blue); }
    .ios-icon-btn.edit:hover    { background: var(--ios-blue);  color: #fff; }
    .ios-icon-btn.danger  { background: rgba(255,59,48,0.12);   color: var(--ios-red); }
    .ios-icon-btn.danger:hover  { background: var(--ios-red);   color: #fff; }

    /* ---------- Cards ---------- */
    .ios-card {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        box-shadow: var(--ios-shadow);
        border: none;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .ios-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--ios-gray-6);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .ios-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        margin: 0;
        color: var(--ios-label);
    }
    .ios-card-subtitle {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--ios-secondary-label);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .ios-card-body { padding: 1.5rem; }

    /* ---------- Alert ---------- */
    .ios-alert {
        border: none;
        border-radius: var(--ios-radius-sm);
        padding: 0.9rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        box-shadow: var(--ios-shadow-sm);
    }
    .ios-alert.success { background: rgba(52,199,89,0.12);  color: #1f8f3f; }
    .ios-alert.danger  { background: rgba(255,59,48,0.12);  color: #c4291f; }
    .ios-alert .btn-close { margin-left: auto; }

    /* ---------- Badges / Pills ---------- */
    .ios-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 980px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.35rem 0.8rem;
    }
    .ios-pill-blue   { background: rgba(0,122,255,0.12);  color: var(--ios-blue); }
    .ios-pill-orange { background: rgba(255,149,0,0.15);  color: #c47700; }
    .ios-keyword-chip {
        display: inline-block;
        background: var(--ios-gray-6);
        color: var(--ios-secondary-label);
        border: 1px solid var(--ios-gray-5);
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.2rem 0.6rem;
        border-radius: 980px;
        margin: 0.15rem 0.2rem 0.15rem 0;
    }

    /* ---------- Inputs / Selects ---------- */
    .ios-input,
    .ios-select,
    .ios-textarea {
        width: 100%;
        background: var(--ios-gray-6);
        border: 1.5px solid transparent;
        border-radius: var(--ios-radius-xs);
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        color: var(--ios-label);
        transition: all 0.2s ease;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        font-family: var(--ios-font);
    }
    .ios-textarea { resize: vertical; min-height: 90px; }
    .ios-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.4rem;
        cursor: pointer;
    }
    .ios-input::placeholder, .ios-textarea::placeholder { color: var(--ios-gray-2); }
    .ios-input:focus,
    .ios-select:focus,
    .ios-textarea:focus {
        background: #fff;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
    }
    .ios-input.readonly { background: var(--ios-gray-5); color: var(--ios-secondary-label); }
    .ios-form-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--ios-label);
        margin-bottom: 0.45rem;
        display: block;
    }
    .ios-form-help { font-size: 0.78rem; color: var(--ios-gray); margin-top: 0.4rem; }

    /* Search box */
    .ios-search-box { position: relative; display: flex; align-items: center; }
    .ios-search-box .search-icon {
        position: absolute; left: 14px; color: var(--ios-gray-2); font-size: 0.85rem; pointer-events: none;
    }
    .ios-search-box .ios-input { padding-left: 2.4rem; }

    /* ---------- Dropzone ---------- */
    .ios-dropzone {
        border: 2px dashed var(--ios-gray-3);
        border-radius: var(--ios-radius-sm);
        background: var(--ios-gray-6);
        padding: 2.25rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .ios-dropzone:hover { border-color: var(--ios-blue); background: rgba(0,122,255,0.04); }
    .ios-dropzone.border-primary { border-color: var(--ios-blue) !important; background: rgba(0,122,255,0.06); }
    .ios-dropzone i {
        font-size: 2.4rem;
        color: var(--ios-blue);
        margin-bottom: 0.6rem;
        display: block;
    }
    .ios-dropzone h6 { font-weight: 600; color: var(--ios-label); margin-bottom: 0.25rem; }
    .ios-dropzone p { color: var(--ios-gray); font-size: 0.82rem; margin: 0; }

    /* Expected columns table */
    .ios-mini-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: var(--ios-radius-xs);
        overflow: hidden;
        font-size: 0.8rem;
        box-shadow: 0 0 0 1px var(--ios-gray-5);
    }
    .ios-mini-table th {
        background: var(--ios-gray-6);
        color: var(--ios-secondary-label);
        font-weight: 600;
        padding: 0.55rem 0.7rem;
        text-align: center;
        border-bottom: 1px solid var(--ios-gray-5);
    }
    .ios-mini-table td {
        padding: 0.5rem 0.7rem;
        border-bottom: 1px solid var(--ios-gray-6);
        color: var(--ios-label);
    }
    .ios-mini-table tr:last-child td { border-bottom: none; }

    /* ---------- Tables ---------- */
    .ios-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }
    .ios-table thead th {
        background: transparent;
        border: none;
        border-bottom: 1.5px solid var(--ios-gray-5);
        color: var(--ios-gray);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.8rem 1rem;
        vertical-align: middle;
    }
    .ios-table tbody td {
        border: none;
        border-bottom: 1px solid var(--ios-gray-6);
        padding: 0.85rem 1rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: var(--ios-label);
    }
    .ios-table tbody tr { transition: background 0.15s ease; }
    .ios-table tbody tr:hover { background: var(--ios-gray-6); }
    .ios-table tbody tr.table-primary { background: rgba(0,122,255,0.07) !important; }

    .clickable-category {
        font-weight: 600;
        color: var(--ios-blue) !important;
        text-decoration: none;
    }
    .clickable-category:hover { text-decoration: underline; }

    .ios-count-blue  { color: var(--ios-blue); font-weight: 700; }
    .ios-count-green { color: var(--ios-green); font-weight: 700; }

    code, .ios-code {
        background: var(--ios-gray-6);
        color: var(--ios-secondary-label);
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
        font-size: 0.8rem;
    }

    /* ---------- iOS Checkboxes ---------- */
    .form-check-input,
    input[type="checkbox"].form-check-input {
        width: 20px !important;
        height: 20px !important;
        border-radius: 6px !important;
        border: 1.5px solid var(--ios-gray-3) !important;
        background-color: #fff !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        margin: 0 !important;
        vertical-align: middle !important;
        background-image: none !important;
        -webkit-print-color-adjust: unset !important;
        print-color-adjust: unset !important;
    }
    .form-check-input:checked { 
        background: var(--ios-blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 12 10 16 18 8'%3E%3C/polyline%3E%3C/svg%3E") center center / 14px 14px no-repeat !important; 
        border-color: var(--ios-blue) !important; 
    }
    .form-check-input:focus { box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15) !important; }

    /* ---------- Bulk Panel ---------- */
    .ios-bulk-panel {
        background: linear-gradient(135deg, rgba(0,122,255,0.08), rgba(74,163,255,0.05));
        border: 1.5px solid rgba(0,122,255,0.2);
        border-radius: var(--ios-radius);
        margin-bottom: 1.5rem;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .ios-bulk-info { display: flex; align-items: center; gap: 0.6rem; color: var(--ios-blue); font-weight: 600; font-size: 0.9rem; }
    .ios-bulk-info i { font-size: 1.1rem; }
    .ios-bulk-controls { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .ios-bulk-controls .label-txt { font-size: 0.85rem; font-weight: 600; color: var(--ios-secondary-label); }
    .ios-bulk-controls .ios-select { width: auto; min-width: 200px; background-color: #fff; }

    /* ---------- Modals ---------- */
    .modal-content.ios-modal {
        border: none;
        border-radius: var(--ios-radius);
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        overflow: hidden;
    }
    .ios-modal .modal-header { border: none; padding: 1.4rem 1.5rem 0.5rem; }
    .ios-modal .modal-title { font-weight: 700; font-size: 1.2rem; letter-spacing: -0.01em; }
    .ios-modal .modal-title.text-primary { color: var(--ios-blue) !important; }
    .ios-modal .modal-title.text-info { color: var(--ios-teal) !important; }
    .ios-modal .modal-body { padding: 1rem 1.5rem; }
    .ios-modal .modal-footer { border: none; padding: 0.75rem 1.5rem 1.4rem; gap: 0.5rem; }

    /* ---------- Close button ---------- */
    .ios-close {
        width: 30px; height: 30px;
        border-radius: 50%;
        border: none;
        background: var(--ios-gray-5);
        color: var(--ios-secondary-label);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.15s ease;
        opacity: 1;
    }
    .ios-close:hover { background: var(--ios-gray-4); color: var(--ios-label); }

    /* spinner */
    .text-primary, .fa-spin.text-primary { color: var(--ios-blue) !important; }

    .transition-width { transition: all 0.4s ease-in-out; }
    .scroll-area { max-height: 480px; overflow-y: auto; }
    .scroll-area::-webkit-scrollbar { width: 8px; }
    .scroll-area::-webkit-scrollbar-thumb { background: var(--ios-gray-4); border-radius: 8px; }

    .d-none { display: none !important; }

    @media (max-width: 768px) {
        .ios-page-title { font-size: 1.45rem; }
        .ios-bulk-panel { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="ios-wrap">

    <!-- ============ Page Header ============ -->
    <div class="ios-page-header">
        <h1 class="ios-page-title">
            <span class="icon-badge"><i class="fa-solid fa-folder-tree"></i></span>
            Categories
        </h1>
        <p class="ios-page-subtitle">Manage active categories, keyword rules, and products assigned to them.</p>
    </div>

    <?php if ($message): ?>
        <div class="ios-alert <?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ============ Collapsible Import Section ============ -->
    <div class="ios-card">
        <div class="ios-card-header">
            <h6 class="ios-card-subtitle"><i class="fa-solid fa-file-excel" style="color: var(--ios-green);"></i>Import Categories Spreadsheet</h6>
            <button class="ios-btn ios-btn-outline-soft ios-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#importSection" aria-expanded="false" aria-controls="importSection">
                <i class="fa-solid fa-chevron-down"></i> Toggle Import Form
            </button>
        </div>
        <div class="collapse" id="importSection">
            <div class="ios-card-body" style="border-top: 1px solid var(--ios-gray-6);">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <form action="import-categories.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="ios-dropzone" id="dropzone">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <h6>Drag &amp; drop your Excel file here</h6>
                                <p>or click to browse from files (.xlsx, .xls, .csv)</p>
                                <input type="file" name="category_file" id="fileInput" class="d-none" accept=".xlsx, .xls, .csv" required>
                                <div class="mt-2 small" style="color: var(--ios-blue); font-weight: 600;" id="fileNameDisplay"></div>
                            </div>
                            <button type="submit" class="ios-btn ios-btn-primary w-100 mt-3" style="justify-content:center; padding: 0.7rem;" id="submitBtn" disabled>
                                <i class="fa-solid fa-file-arrow-up"></i>Import Categories
                            </button>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="ios-form-label" style="margin-bottom: 0.75rem;">Expected Columns Structure:</h6>
                        <table class="ios-mini-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Main Category</th>
                                    <th>Including Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Baby Bedding Sets &amp; Pillows</td>
                                    <td>Nursery</td>
                                    <td>pillow, quilt, bedding, pillow case</td>
                                </tr>
                                <tr>
                                    <td>Baby Clothing</td>
                                    <td>Apparel</td>
                                    <td>romper, bodysuit, shirt, socks, bib</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="ios-form-help">
                            Note: The system matches keywords using comma separation. Empty rows are skipped.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Bulk Edit Panel ============ -->
    <div class="ios-bulk-panel d-none" id="bulkCategoryActionsPanel">
        <div class="ios-bulk-info">
            <i class="fa-solid fa-square-check"></i>
            <span id="selectedCatCountText">0 categories selected</span>
        </div>
        <div class="ios-bulk-controls">
            <span class="label-txt">Change Main Category To:</span>
            <select id="bulkMainCategorySelect" class="ios-select">
                <option value="">None (Clear Main Category)</option>
                <?php foreach ($mainCategories as $mc): ?>
                    <option value="<?= htmlspecialchars($mc['name']) ?>"><?= htmlspecialchars($mc['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ios-btn ios-btn-primary ios-btn-sm" id="bulkCatApplyBtn">
                <i class="fa-solid fa-circle-check"></i>Apply Change
            </button>
        </div>
    </div>

    <!-- ============ Category Layout Grid ============ -->
    <div class="row" id="categoryLayoutRow">
        <!-- Categories Card -->
        <div class="col-lg-12 transition-width" id="categoryCardContainer">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h5 class="ios-card-title">Active Categories &amp; Keywords</h5>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="ios-search-box" style="width: 270px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchCategoryName" class="ios-input" placeholder="Search category, main or keyword...">
                        </div>
                        <span class="ios-pill ios-pill-blue"><?= count($categories) ?> Categories</span>
                        <button class="ios-btn ios-btn-primary ios-btn-sm" id="addCategoryBtn">
                            <i class="fa-solid fa-plus"></i>Add Category
                        </button>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="table-responsive">
                        <table class="ios-table" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" id="selectAllCatCheckbox" class="form-check-input">
                                    </th>
                                    <th style="width: 25%">Category Name</th>
                                    <th style="width: 15%">Main Category</th>
                                    <th>Keywords</th>
                                    <th class="text-center" style="width: 8%">Keywords</th>
                                    <th class="text-center" style="width: 8%">Products</th>
                                    <th class="text-center" style="width: 18%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No categories imported yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['category_name']) ?>" data-main="<?= htmlspecialchars($cat['main_category'] ?? '') ?>" data-items="<?= htmlspecialchars($cat['including_items']) ?>">
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input cat-checkbox" value="<?= $cat['id'] ?>">
                                            </td>
                                            <td>
                                                <a href="#" class="clickable-category">
                                                    <i class="fa-regular fa-folder me-2"></i><?= htmlspecialchars($cat['category_name']) ?>
                                                </a>
                                                <?php if ($cat['is_auto_created']): ?>
                                                    <br><span class="ios-pill ios-pill-orange mt-1"><i class="fa-solid fa-wand-magic-sparkles"></i>Auto-Created</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color: var(--ios-secondary-label); font-size: 0.85rem; font-weight: 500;">
                                                <?= htmlspecialchars($cat['main_category'] ?? 'None') ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $kws = explode(',', $cat['including_items']);
                                                $kwCount = 0;
                                                foreach ($kws as $kw): 
                                                    if (empty(trim($kw))) continue;
                                                    $kwCount++;
                                                ?>
                                                    <span class="ios-keyword-chip"><?= htmlspecialchars(trim($kw)) ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="text-center ios-count-blue">
                                                <?= $kwCount ?>
                                            </td>
                                            <td class="text-center ios-count-green">
                                                <?= (int)($cat['product_count'] ?? 0) ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="ios-icon-btn info transfer-category-btn" title="Transfer Products">
                                                    <i class="fa-solid fa-right-left"></i>
                                                </button>
                                                <button class="ios-icon-btn edit edit-category-btn" title="Edit Category">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </button>
                                                <button class="ios-icon-btn danger delete-category-btn" title="Delete Category">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products List Card -->
        <div class="col-lg-7 d-none transition-width" id="categoryProductsCardContainer">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h5 class="ios-card-title text-truncate" id="selectedCategoryTitle" style="max-width: 85%;">Products in Category</h5>
                    <button class="ios-close" id="closeProductsCardBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="ios-card-body">
                    <div id="productsLoading" class="text-center py-5 d-none">
                        <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--ios-blue);"></i>
                        <p class="text-muted small mt-2">Fetching products...</p>
                    </div>
                    <div id="productsContent">
                        <div class="table-responsive scroll-area">
                            <table class="ios-table" id="categoryProductsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%">Code</th>
                                        <th>Product Name</th>
                                        <th style="width: 20%">Price</th>
                                        <th>Supplier</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryProductsBody">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ios-modal">
            <form id="categoryForm">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="categoryModalLabel">Add Category</h5>
                    <button type="button" class="ios-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="category_id" value="">
                    <div class="mb-3">
                        <label for="category_name" class="ios-form-label">Category Name</label>
                        <input type="text" class="ios-input" id="category_name" name="category_name" required placeholder="e.g. Baby Clothing">
                    </div>
                    <div class="mb-3">
                        <label for="main_category" class="ios-form-label">Main Category</label>
                        <select class="ios-select" id="main_category" name="main_category">
                            <option value="">None (Optional)</option>
                            <?php foreach ($mainCategories as $mc): ?>
                                <option value="<?= htmlspecialchars($mc['name']) ?>"><?= htmlspecialchars($mc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="including_items" class="ios-form-label">Keywords (Comma separated)</label>
                        <textarea class="ios-textarea" id="including_items" name="including_items" rows="4" placeholder="romper, bodysuit, socks, bib, frock"></textarea>
                        <div class="ios-form-help">Items matching these keywords will be classified into this category.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ios-btn ios-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ios-btn ios-btn-primary"><i class="fa-solid fa-save"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transfer Products Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ios-modal">
            <form id="transferForm">
                <div class="modal-header">
                    <h5 class="modal-title text-info" id="transferModalLabel">Transfer Products</h5>
                    <button type="button" class="ios-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Move all products from the current category to a new destination category.</p>
                    <div class="mb-3">
                        <label class="ios-form-label">From Category</label>
                        <input type="text" class="ios-input readonly" id="transfer_from_name" readonly>
                        <input type="hidden" id="transfer_from" name="from_category">
                    </div>
                    <div class="mb-3">
                        <label for="transfer_to" class="ios-form-label">To Category</label>
                        <select class="ios-select" id="transfer_to" name="to_category" required>
                            <option value="">Select Destination Category...</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['category_name']) ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ios-btn ios-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ios-btn ios-btn-info"><i class="fa-solid fa-right-left"></i>Transfer Products</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/views/layout/footer.php';
?>

<script>
$(document).ready(function() {
    // Drag & Drop file upload handler
    const dropzone = $('#dropzone');
    const fileInput = $('#fileInput');
    const display = $('#fileNameDisplay');
    const submitBtn = $('#submitBtn');

    dropzone.on('click', function() {
        fileInput.click();
    });

    fileInput.on('change', function(e) {
        handleFileSelect(e.target.files);
    });

    dropzone.on('dragover', function(e) {
        e.preventDefault();
        dropzone.addClass('border-primary');
    });

    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        dropzone.removeClass('border-primary');
    });

    dropzone.on('drop', function(e) {
        e.preventDefault();
        dropzone.removeClass('border-primary');
        const files = e.originalEvent.dataTransfer.files;
        fileInput[0].files = files;
        handleFileSelect(files);
    });

    function handleFileSelect(files) {
        if (files.length > 0) {
            display.text(files[0].name + " (" + formatBytes(files[0].size) + ")");
            submitBtn.prop('disabled', false);
        } else {
            display.text('');
            submitBtn.prop('disabled', true);
        }
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Modal Control: Add Category
    $('#addCategoryBtn').on('click', function() {
        $('#categoryForm')[0].reset();
        $('#category_id').val('');
        $('#categoryModalLabel').text('Add Category');
        $('#categoryModal').modal('show');
    });

    // Modal Control: Edit Category
    $(document).on('click', '.edit-category-btn', function(e) {
        e.stopPropagation();
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');
        const main = tr.data('main');
        const items = tr.data('items');

        $('#category_id').val(id);
        $('#category_name').val(name);
        $('#main_category').val(main);
        $('#including_items').val(items);
        $('#categoryModalLabel').text('Edit Category');
        $('#categoryModal').modal('show');
    });

    // Submit Category Save
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'import-categories.php?action=save',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#categoryModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Error communication with server.');
            }
        });
    });

    // Delete Category
    $(document).on('click', '.delete-category-btn', function(e) {
        e.stopPropagation();
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');

        if (confirm('Are you sure you want to delete category "' + name + '"? This will remove all its validation keyword rules too.')) {
            $.ajax({
                url: 'import-categories.php?action=delete',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Error communicating with server.');
                }
            });
        }
    });

    // Modal Control: Transfer Products
    $(document).on('click', '.transfer-category-btn', function(e) {
        e.stopPropagation();
        const tr = $(this).closest('tr');
        const name = tr.data('name');

        $('#transferForm')[0].reset();
        $('#transfer_from_name').val(name);
        $('#transfer_from').val(name);
        // Remove the current category from the destination options
        $('#transfer_to option').show();
        $('#transfer_to option[value="' + name + '"]').hide();
        $('#transferModal').modal('show');
    });

    // Submit Transfer
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        if (!confirm('Are you sure you want to move all products? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: 'import-categories.php?action=transfer',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#transferModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Error communication with server.');
            }
        });
    });

    // Tap Category Item: show products
    $(document).on('click', '.clickable-category', function(e) {
        e.preventDefault();
        const tr = $(this).closest('tr');
        const categoryName = tr.data('name');

        // Highlight selected row
        $('#categoriesTable tbody tr').removeClass('table-primary');
        tr.addClass('table-primary');

        // Transition layout
        $('#categoryCardContainer').removeClass('col-lg-12').addClass('col-lg-5');
        $('#categoryProductsCardContainer').removeClass('d-none');
        
        $('#selectedCategoryTitle').text('Products in "' + categoryName + '"');
        $('#productsLoading').removeClass('d-none');
        $('#productsContent').addClass('d-none');

        // Load Products via AJAX
        $.ajax({
            url: 'import-categories.php?action=get_products&category=' + encodeURIComponent(categoryName),
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let html = '';
                    if (res.products.length === 0) {
                        html = '<tr><td colspan="4" class="text-center text-muted py-4">No products found in this category.</td></tr>';
                    } else {
                        res.products.forEach(function(p) {
                            html += '<tr>' +
                                    '  <td><code>' + p.product_code + '</code></td>' +
                                    '  <td style="font-weight:600;">' + p.product_name + '</td>' +
                                    '  <td style="color: var(--ios-green); font-weight:600;">Rs. ' + parseFloat(p.price).toFixed(2) + '</td>' +
                                    '  <td class="small text-muted">' + (p.supplier ? p.supplier : 'Not set') + '</td>' +
                                    '</tr>';
                        });
                    }
                    $('#categoryProductsBody').html(html);
                    $('#productsLoading').addClass('d-none');
                    $('#productsContent').removeClass('d-none');
                } else {
                    $('#categoryProductsBody').html('<tr><td colspan="4" class="text-danger text-center py-4">Failed to load products.</td></tr>');
                    $('#productsLoading').addClass('d-none');
                    $('#productsContent').removeClass('d-none');
                }
            },
            error: function() {
                $('#categoryProductsBody').html('<tr><td colspan="4" class="text-danger text-center py-4">Error loading data.</td></tr>');
                $('#productsLoading').addClass('d-none');
                $('#productsContent').removeClass('d-none');
            }
        });
    });

    // Close Products Card
    $('#closeProductsCardBtn').on('click', function() {
        $('#categoryProductsCardContainer').addClass('d-none');
        $('#categoryCardContainer').removeClass('col-lg-5').addClass('col-lg-12');
        $('#categoriesTable tbody tr').removeClass('table-primary');
    });

    // Real-time search for Category table
    $('#searchCategoryName').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('#categoriesTable tbody tr').each(function() {
            const tr = $(this);
            // Skip empty placeholder row if any
            if (tr.find('td').length <= 1) return;

            const name = tr.data('name') ? tr.data('name').toLowerCase() : '';
            const main = tr.data('main') ? tr.data('main').toLowerCase() : '';
            const items = tr.data('items') ? tr.data('items').toLowerCase() : '';

            if (name.includes(query) || main.includes(query) || items.includes(query)) {
                tr.show();
            } else {
                tr.hide();
            }
        });
    });

    // Bulk category actions selection
    let selectedCatIds = [];

    function updateBulkCatPanel() {
        selectedCatIds = [];
        $('.cat-checkbox:checked').each(function() {
            selectedCatIds.push(parseInt($(this).val()));
        });

        if (selectedCatIds.length > 0) {
            $('#selectedCatCountText').text(selectedCatIds.length + ' category/categories selected');
            $('#bulkCategoryActionsPanel').removeClass('d-none');
        } else {
            $('#bulkCategoryActionsPanel').addClass('d-none');
        }
    }

    // Select all categories checkbox click handler
    $('#selectAllCatCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.cat-checkbox').prop('checked', isChecked);
        updateBulkCatPanel();
    });

    // Individual category checkbox click handler
    $(document).on('change', '.cat-checkbox', function() {
        updateBulkCatPanel();
        // Update main checkbox state
        const allChecked = $('.cat-checkbox').length === $('.cat-checkbox:checked').length;
        $('#selectAllCatCheckbox').prop('checked', allChecked);
    });

    // Apply Bulk Change
    $('#bulkCatApplyBtn').on('click', function() {
        const mainCat = $('#bulkMainCategorySelect').val();
        if (selectedCatIds.length === 0) {
            alert('Please select at least one category.');
            return;
        }

        if (!confirm('Are you sure you want to change the Main Category of ' + selectedCatIds.length + ' categories to "' + (mainCat || 'None') + '"?')) {
            return;
        }

        $.ajax({
            url: 'import-categories.php?action=bulk_update_main_category',
            type: 'POST',
            data: {
                ids: selectedCatIds,
                main_category: mainCat
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function() {
                alert('An error occurred while bulk updating the main category.');
            }
        });
    });
});
</script>