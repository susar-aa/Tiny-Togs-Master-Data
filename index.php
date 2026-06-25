<?php
require_once __DIR__ . '/config/bootstrap.php';

use Controllers\ProductController;
use Models\Category;

// Route AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $prodCtrl = new ProductController();

    if ($action === 'datatable') {
        $prodCtrl->handleDataTableRequest();
    }
    
    if ($action === 'details') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $prodCtrl->getDetails($id);
    }
    
    if ($action === 'update_category') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $cat = $_POST['category_name'] ?? '';
        $prodCtrl->updateCategory($id, $cat);
    }
    
    if ($action === 'bulk_update_category') {
        $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
        $cat = $_POST['category_name'] ?? '';
        $prodCtrl->bulkUpdateCategory($ids, $cat);
    }
    
    exit;
}

// Fetch categories for display and filter dropdowns
$categoryModel = new Category();
$categories = $categoryModel->getAll();
$productModel = new \Models\Product();
$suppliers = $productModel->getUniqueSuppliers();

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
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem 1rem 3rem;
    }

    /* ---------- Page Header ---------- */
    .ios-page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
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
        max-width: 600px;
    }
    .ios-header-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    /* ---------- iOS Buttons ---------- */
    .ios-btn {
        border: none;
        border-radius: 980px;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.6rem 1.2rem;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        line-height: 1;
    }
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
    .ios-btn-outline {
        background: rgba(0, 122, 255, 0.08);
        color: var(--ios-blue);
    }
    .ios-btn-outline:hover {
        background: rgba(0, 122, 255, 0.16);
        color: var(--ios-blue);
    }
    .ios-pill-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--ios-gray-5);
        color: var(--ios-secondary-label);
        border-radius: 980px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.55rem 1rem;
    }

    /* ---------- iOS Cards ---------- */
    .ios-card {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        box-shadow: var(--ios-shadow);
        border: none;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .ios-card-body {
        padding: 1.5rem;
    }

    /* ---------- Filters ---------- */
    .ios-filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ios-gray);
        margin-bottom: 0.5rem;
        display: block;
    }
    .ios-search-box {
        position: relative;
        display: flex;
        align-items: center;
    }
    .ios-search-box .search-icon {
        position: absolute;
        left: 14px;
        color: var(--ios-gray-2);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .ios-input,
    .ios-select {
        width: 100%;
        background: var(--ios-gray-6);
        border: 1.5px solid transparent;
        border-radius: var(--ios-radius-xs);
        padding: 0.65rem 1rem;
        font-size: 0.9rem;
        color: var(--ios-label);
        transition: all 0.2s ease;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
    }
    .ios-input {
        padding-left: 2.4rem;
    }
    .ios-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.4rem;
        cursor: pointer;
    }
    .ios-input::placeholder {
        color: var(--ios-gray-2);
    }
    .ios-input:focus,
    .ios-select:focus {
        background: #fff;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
    }

    /* ---------- Bulk Actions Panel ---------- */
    .ios-bulk-panel {
        background: linear-gradient(135deg, rgba(0, 122, 255, 0.08), rgba(74, 163, 255, 0.05));
        border: 1.5px solid rgba(0, 122, 255, 0.2);
        border-radius: var(--ios-radius);
        margin-bottom: 1.5rem;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .ios-bulk-info {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: var(--ios-blue);
        font-weight: 600;
        font-size: 0.9rem;
    }
    .ios-bulk-info i {
        font-size: 1.1rem;
    }
    .ios-bulk-controls {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .ios-bulk-controls .label-txt {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--ios-secondary-label);
    }
    .ios-bulk-controls .ios-select {
        width: auto;
        min-width: 160px;
        background-color: #fff;
    }

    /* ---------- iOS Table ---------- */
    #productsTable {
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
    }
    #productsTable thead th {
        background: transparent;
        border: none;
        border-bottom: 1.5px solid var(--ios-gray-5);
        color: var(--ios-gray);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.85rem 1rem;
        vertical-align: middle;
    }
    #productsTable tbody td {
        border: none;
        border-bottom: 1px solid var(--ios-gray-6);
        padding: 0.85rem 1rem;
        vertical-align: middle;
        font-size: 0.92rem;
        color: var(--ios-label);
    }
    #productsTable tbody tr {
        transition: background 0.15s ease;
    }
    #productsTable tbody tr:hover {
        background: var(--ios-gray-6);
    }
    #productsTable tbody tr.shown {
        background: rgba(0, 122, 255, 0.04);
    }

    /* Product name cell */
    .product-name-toggle {
        font-weight: 600;
        color: var(--ios-label) !important;
        font-size: 0.93rem;
    }
    .row-toggle-icon {
        color: var(--ios-gray-2) !important;
        transition: transform 0.2s ease;
        width: 16px;
        text-align: center;
    }
    .cursor-pointer { cursor: pointer; }
    tr.shown .row-toggle-icon { color: var(--ios-blue) !important; }

    /* Inline category select inside table */
    .category-select {
        width: 100%;
        background: var(--ios-gray-6);
        border: 1.5px solid var(--ios-gray-5);
        border-radius: var(--ios-radius-xs);
        padding: 0.5rem 2.2rem 0.5rem 0.9rem;
        font-size: 0.85rem;
        color: var(--ios-label);
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.9rem center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .category-select:focus {
        background-color: #fff;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    }
    .category-select.border-warning { border-color: var(--ios-orange) !important; box-shadow: 0 0 0 3px rgba(255,149,0,0.12); }
    .category-select.border-success { border-color: var(--ios-green) !important; box-shadow: 0 0 0 3px rgba(52,199,89,0.12); }
    .category-select.border-danger  { border-color: var(--ios-red) !important;  box-shadow: 0 0 0 3px rgba(255,59,48,0.12); }

    /* ---------- iOS Checkboxes ---------- */
    .ios-checkbox {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1.5px solid var(--ios-gray-3);
        background-color: #fff;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        position: relative;
        transition: all 0.15s ease;
        margin: 0;
        vertical-align: middle;
        outline: none;
        display: inline-block;
    }
    .ios-checkbox:checked {
        background-color: var(--ios-blue) !important;
        border-color: var(--ios-blue) !important;
    }
    .ios-checkbox:checked::after {
        content: "";
        position: absolute;
        left: 6px;
        top: 2px;
        width: 6px;
        height: 11px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        display: block !important;
    }
    .ios-checkbox:focus {
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }

    /* ---------- Expanded Detail Row ---------- */
    .ios-detail-card {
        background: var(--ios-gray-6);
        border-radius: var(--ios-radius-sm);
        padding: 1.25rem;
        margin: 0.5rem 0;
    }
    .ios-detail-item .detail-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--ios-gray);
        display: block;
        margin-bottom: 0.25rem;
    }
    .ios-detail-item .detail-value {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--ios-label);
    }
    .ios-detail-item .detail-value.price { color: var(--ios-green); }
    .ios-cat-chip {
        display: inline-block;
        background: rgba(0, 122, 255, 0.12);
        color: var(--ios-blue);
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.7rem;
        border-radius: 980px;
    }

    /* ---------- DataTables overrides ---------- */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 0.85rem;
        color: var(--ios-secondary-label);
        padding-top: 1rem;
    }
    .dataTables_wrapper .dataTables_length select {
        background: var(--ios-gray-6);
        border: 1.5px solid var(--ios-gray-5);
        border-radius: 8px;
        padding: 0.3rem 1.6rem 0.3rem 0.6rem;
        margin: 0 0.4rem;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        cursor: pointer;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        border: none !important;
        padding: 0.35rem 0.8rem !important;
        margin: 0 2px;
        color: var(--ios-blue) !important;
        background: transparent !important;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--ios-blue) !important;
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(0,122,255,0.3);
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--ios-gray-5) !important;
        color: var(--ios-blue) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: var(--ios-gray-3) !important;
        background: transparent !important;
    }
    .dataTables_processing {
        background: rgba(255,255,255,0.92) !important;
        border-radius: var(--ios-radius-sm);
        box-shadow: var(--ios-shadow-sm);
        color: var(--ios-blue) !important;
        font-weight: 600;
        border: none !important;
    }
    /* Sort indicators */
    #productsTable thead th.sorting,
    #productsTable thead th.sorting_asc,
    #productsTable thead th.sorting_desc {
        cursor: pointer;
    }

    .d-none { display: none !important; }

    @media (max-width: 768px) {
        .ios-page-title { font-size: 1.45rem; }
        .ios-bulk-panel { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="ios-wrap">

    <!-- ============ Page Header ============ -->
    <div class="ios-page-header">
        <div>
            <h1 class="ios-page-title">
                <span class="icon-badge"><i class="fa-solid fa-table-list"></i></span>
                Product List
            </h1>
            <p class="ios-page-subtitle">
                Manage the master catalog, modify product classifications in real-time, and run bulk category updates.
            </p>
        </div>
        <div class="ios-header-actions">
            <a href="export-products.php" class="ios-btn ios-btn-outline" style="background: rgba(52, 199, 89, 0.08); color: var(--ios-green);">
                <i class="fa-solid fa-cloud-arrow-down"></i>Export Products
            </a>
            <a href="import-products.php" class="ios-btn ios-btn-outline">
                <i class="fa-solid fa-cloud-arrow-up"></i>Import Products
            </a>
            <span class="ios-pill-badge"><i class="fa-solid fa-boxes-stacked"></i>Master Catalog</span>
        </div>
    </div>

    <!-- ============ Filters Panel ============ -->
    <div class="ios-card">
        <div class="ios-card-body">
            <div class="row g-3 align-items-end">
                <!-- Search Product Name -->
                <div class="col-md-4">
                    <label for="searchProductName" class="ios-filter-label">Search Product Name</label>
                    <div class="ios-search-box">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="searchProductName" class="ios-input" placeholder="Type product name to search...">
                    </div>
                </div>
                <!-- Category Filter -->
                <div class="col-md-4">
                    <label for="filterCategory" class="ios-filter-label">Filter By Category</label>
                    <select id="filterCategory" class="ios-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Supplier Filter -->
                <div class="col-md-4">
                    <label for="filterSupplier" class="ios-filter-label">Filter By Supplier</label>
                    <select id="filterSupplier" class="ios-select">
                        <option value="">All Suppliers</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= htmlspecialchars($sup) ?>"><?= htmlspecialchars($sup) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Bulk Edit Panel ============ -->
    <div class="ios-bulk-panel d-none" id="bulkActionsPanel">
        <div class="ios-bulk-info">
            <i class="fa-solid fa-square-check"></i>
            <span id="selectedCountText">0 items selected</span>
        </div>
        <div class="ios-bulk-controls">
            <span class="label-txt">Change Category To:</span>
            <select id="bulkCategorySelect" class="ios-select">
                <option value="">Uncategorized</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ios-btn ios-btn-primary" id="bulkApplyBtn">
                <i class="fa-solid fa-circle-check"></i>Apply Change
            </button>
        </div>
    </div>

    <!-- ============ DataTable Card ============ -->
    <div class="ios-card">
        <div class="ios-card-body">
            <div class="table-responsive">
                <table class="table w-100" id="productsTable">
                    <thead>
                        <tr>
                            <th class="no-sort text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllCheckbox" class="ios-checkbox">
                            </th>
                            <th style="width: 100px;">Code</th>
                            <th style="width: 100px;">SKU</th>
                            <th>Product Name</th>
                            <th style="width: 250px;">Product Category</th>
                            <th style="width: 100px;">Cost Price</th>
                            <th style="width: 110px;">Selling Price</th>
                            <th style="width: 180px;">Supplier Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data populated dynamically via server-side DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/views/layout/footer.php';
?>

<script>
$(document).ready(function() {
    let selectedIds = [];
    const categoriesList = <?= json_encode($categories) ?>;

    // Initialize Server-side DataTable for Products
    const table = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 100,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        dom: 'lrtip', // Hide default search bar to use our custom realtime search and category/supplier filters
        ajax: {
            url: 'index.php?action=datatable',
            type: 'POST',
            data: function(d) {
                d.category_filter = $('#filterCategory').val();
                d.product_name_filter = $('#searchProductName').val();
                d.supplier_filter = $('#filterSupplier').val();
            }
        },
        columns: [
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const isChecked = selectedIds.includes(parseInt(row.id)) ? 'checked' : '';
                    return '<input type="checkbox" class="ios-checkbox row-checkbox" value="' + row.id + '" ' + isChecked + '>';
                }
            },
            { 
                data: 'product_code',
                orderable: true,
                render: function(data, type, row) {
                    return '<code>' + (data || 'N/A') + '</code>';
                }
            },
            { 
                data: 'product_code',
                orderable: true,
                render: function(data, type, row) {
                    return '<code>' + (data || 'N/A') + '</code>';
                }
            },
            { 
                data: 'product_name',
                className: 'dt-control-toggle',
                orderable: true,
                render: function(data, type, row) {
                    return '<div class="d-flex align-items-center gap-2 cursor-pointer">' +
                           '  <i class="fa-solid fa-chevron-right row-toggle-icon" style="font-size: 0.8rem;"></i>' +
                           '  <span class="product-name-toggle">' + data + '</span>' +
                           '</div>';
                }
            },
            { 
                data: 'current_category',
                orderable: true,
                render: function(data, type, row) {
                    let selectHtml = '<select class="category-select" data-id="' + row.id + '">';
                    selectHtml += '<option value="">Uncategorized</option>';
                    categoriesList.forEach(function(cat) {
                        const selected = (cat.category_name === data) ? 'selected' : '';
                        selectHtml += '<option value="' + cat.category_name + '" ' + selected + '>' + cat.category_name + '</option>';
                    });
                    selectHtml += '</select>';
                    return selectHtml;
                }
            },
            { 
                data: 'price',
                orderable: true,
                render: function(data, type, row) {
                    return '<span style="font-weight: 600; color: var(--ios-orange);">Rs. ' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                data: 'selling_price',
                orderable: true,
                render: function(data, type, row) {
                    return '<span style="font-weight: 600; color: var(--ios-green);">Rs. ' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                data: 'supplier',
                orderable: true,
                render: function(data, type, row) {
                    return data ? '<span style="color: var(--ios-secondary-label);">' + data + '</span>' : '<span class="text-muted" style="font-size:0.8rem;">Not set</span>';
                }
            }
        ],
        order: [[3, 'asc']], // Default sort by product name (column index 3)
        columnDefs: [
            { targets: 'no-sort', orderable: false }
        ],
        drawCallback: function() {
            updateSelectAllCheckboxState();
        }
    });

    // Row expansion formatter
    function formatDetailsRow(d) {
        let extraRows = '';
        if (d.other_fields_json) {
            try {
                const extra = JSON.parse(d.other_fields_json);
                if (extra && typeof extra === 'object' && Object.keys(extra).length > 0) {
                    for (const [key, value] of Object.entries(extra)) {
                        extraRows += `
                            <div class="col-md-4 mb-2 ios-detail-item">
                                <span class="detail-label">${key}:</span>
                                <span class="detail-value">${value}</span>
                            </div>`;
                    }
                }
            } catch (e) {}
        }

        return `
            <div class="ios-detail-card">
                <div class="row g-3">
                    <div class="col-md-2 ios-detail-item">
                        <span class="detail-label">Product Code / SKU</span>
                        <span class="detail-value">${d.product_code || 'N/A'}</span>
                    </div>
                    <div class="col-md-2 ios-detail-item">
                        <span class="detail-label">Cost Price</span>
                        <span class="detail-value price">Rs. ${parseFloat(d.price).toFixed(2)}</span>
                    </div>
                    <div class="col-md-2 ios-detail-item">
                        <span class="detail-label">Selling Price</span>
                        <span class="detail-value" style="color: var(--ios-green); font-weight: 600;">Rs. ${parseFloat(d.selling_price).toFixed(2)}</span>
                    </div>
                    <div class="col-md-3 ios-detail-item">
                        <span class="detail-label">Supplier</span>
                        <span class="detail-value">${d.supplier || 'Not set'}</span>
                    </div>
                    <div class="col-md-3 ios-detail-item">
                        <span class="detail-label">Category</span>
                        <span class="ios-cat-chip mt-1">${d.current_category || 'Uncategorized'}</span>
                    </div>
                    ${extraRows ? '<div class="col-12"><hr class="my-2" style="border-color: var(--ios-gray-4);"></div>' + extraRows : ''}
                </div>
            </div>`;
    }

    // Row click toggle listener
    $('#productsTable tbody').on('click', 'td.dt-control-toggle, span.product-name-toggle, i.row-toggle-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const tr = $(this).closest('tr');
        const row = table.row(tr);
        const icon = tr.find('.row-toggle-icon');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        } else {
            row.child(formatDetailsRow(row.data())).show();
            tr.addClass('shown');
            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        }
    });

    // Real-time product name search handler with debounce
    let searchTimeout = null;
    $('#searchProductName').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            selectedIds = [];
            $('#selectAllCheckbox').prop('checked', false);
            $('#bulkActionsPanel').addClass('d-none');
            table.ajax.reload();
        }, 250);
    });

    // Redraw table when dropdown filters change
    $('#filterCategory, #filterSupplier').on('change', function() {
        selectedIds = [];
        $('#selectAllCheckbox').prop('checked', false);
        $('#bulkActionsPanel').addClass('d-none');
        table.ajax.reload();
    });

    // Handle Inline Dropdown Category Changes
    $(document).on('change', '.category-select', function() {
        const id = $(this).data('id');
        const categoryName = $(this).val();
        const selectEl = $(this);
        
        selectEl.removeClass('border-success border-danger').addClass('border-warning');
        
        $.ajax({
            url: 'index.php?action=update_category',
            type: 'POST',
            data: {
                id: id,
                category_name: categoryName
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    selectEl.removeClass('border-warning').addClass('border-success');
                    setTimeout(function() {
                        selectEl.removeClass('border-success');
                    }, 1200);
                } else {
                    selectEl.removeClass('border-warning').addClass('border-danger');
                    alert("Error updating category: " + response.message);
                }
            },
            error: function() {
                selectEl.removeClass('border-warning').addClass('border-danger');
                alert("Communication error when saving category.");
            }
        });
    });

    // Checkbox selections
    $('#productsTable tbody').on('change', '.row-checkbox', function() {
        const id = parseInt($(this).val());
        if ($(this).is(':checked')) {
            if (!selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        } else {
            selectedIds = selectedIds.filter(val => val !== id);
        }
        updateBulkPanel();
        updateSelectAllCheckboxState();
    });

    // Select All
    $('#selectAllCheckbox').on('click', function() {
        const checked = $(this).is(':checked');
        $('.row-checkbox').each(function() {
            const id = parseInt($(this).val());
            $(this).prop('checked', checked);
            if (checked) {
                if (!selectedIds.includes(id)) {
                    selectedIds.push(id);
                }
            } else {
                selectedIds = selectedIds.filter(val => val !== id);
            }
        });
        updateBulkPanel();
    });

    function updateSelectAllCheckboxState() {
        const rowsCount = $('.row-checkbox').length;
        const checkedCount = $('.row-checkbox:checked').length;
        
        if (rowsCount > 0 && rowsCount === checkedCount) {
            $('#selectAllCheckbox').prop('checked', true);
        } else {
            $('#selectAllCheckbox').prop('checked', false);
        }
    }

    function updateBulkPanel() {
        if (selectedIds.length > 0) {
            $('#selectedCountText').text(selectedIds.length + " products selected");
            $('#bulkActionsPanel').removeClass('d-none');
        } else {
            $('#bulkActionsPanel').addClass('d-none');
        }
    }

    // Bulk Apply Click
    $('#bulkApplyBtn').on('click', function() {
        const targetCategory = $('#bulkCategorySelect').val();
        if (selectedIds.length === 0) return;

        if (confirm("Are you sure you want to update category of " + selectedIds.length + " selected products to '" + (targetCategory ? targetCategory : 'Uncategorized') + "'?")) {
            $.ajax({
                url: 'index.php?action=bulk_update_category',
                type: 'POST',
                data: {
                    ids: selectedIds,
                    category_name: targetCategory
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        selectedIds = [];
                        updateBulkPanel();
                        $('#selectAllCheckbox').prop('checked', false);
                        table.ajax.reload();
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("Server error when running bulk update.");
                }
            });
        }
    });


});
</script>