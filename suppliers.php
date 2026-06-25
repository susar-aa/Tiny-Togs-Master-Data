<?php
require_once __DIR__ . '/config/bootstrap.php';

use Models\Supplier;

// Route AJAX requests
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $supModel = new Supplier();
        $res = $supModel->delete($id);
        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'Supplier deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete Supplier.']);
        }
        exit;
    }

    if ($_GET['action'] === 'save') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Supplier name is required.']);
            exit;
        }

        $supModel = new Supplier();
        if ($id > 0) {
            $res = $supModel->update($id, $name);
            $msg = 'Supplier updated successfully.';
        } else {
            $res = $supModel->save($name);
            $msg = 'Supplier created successfully.';
        }

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save Supplier.']);
        }
        exit;
    }

    if ($_GET['action'] === 'transfer') {
        header('Content-Type: application/json');
        $from_sup = trim($_POST['from_supplier'] ?? '');
        $to_sup = trim($_POST['to_supplier'] ?? '');

        if (empty($from_sup) || empty($to_sup) || $from_sup === $to_sup) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid supplier parameters.']);
            exit;
        }

        $supModel = new Supplier();
        $count = $supModel->transferProducts($from_sup, $to_sup);

        if ($count !== false) {
            echo json_encode(['status' => 'success', 'message' => "Successfully transferred {$count} products."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error during transfer.']);
        }
        exit;
    }

    if ($_GET['action'] === 'get_products') {
        header('Content-Type: application/json');
        $supplier_name = $_GET['supplier'] ?? '';

        $supModel = new Supplier();
        $products = $supModel->getProductsBySupplier($supplier_name);

        echo json_encode(['status' => 'success', 'products' => $products]);
        exit;
    }

    if ($_GET['action'] === 'bulk_update_products_supplier') {
        header('Content-Type: application/json');
        $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
        $supplier_name = trim($_POST['supplier_name'] ?? '');

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No products selected.']);
            exit;
        }

        $supModel = new Supplier();
        $res = $supModel->bulkUpdateProductsSupplier($ids, $supplier_name);

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'Supplier updated for selected products successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update supplier for products.']);
        }
        exit;
    }
}

// Fetch suppliers for display
$supModel = new Supplier();
$suppliers = $supModel->getAll();

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
        background: linear-gradient(135deg, #ff9500, #ffb340);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(255, 149, 0, 0.35);
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
    .ios-btn-secondary { background: var(--ios-gray-5); color: var(--ios-label); }
    .ios-btn-secondary:hover { background: var(--ios-gray-4); color: var(--ios-label); }
    .ios-btn-info {
        background: var(--ios-teal);
        color: #fff;
        box-shadow: 0 4px 14px rgba(48, 176, 199, 0.3);
    }
    .ios-btn-info:hover { background: #2698ac; color: #fff; transform: translateY(-1px); }

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
    .ios-card-body { padding: 1.5rem; }

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

    /* ---------- Inputs ---------- */
    .ios-input,
    .ios-select {
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
    .ios-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.4rem;
        cursor: pointer;
    }
    .ios-input::placeholder { color: var(--ios-gray-2); }
    .ios-input:focus,
    .ios-select:focus {
        background: #fff;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
    }
    .ios-form-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--ios-label);
        margin-bottom: 0.45rem;
        display: block;
    }

    /* Search box */
    .ios-search-box { position: relative; display: flex; align-items: center; }
    .ios-search-box .search-icon {
        position: absolute; left: 14px; color: var(--ios-gray-2); font-size: 0.85rem; pointer-events: none;
    }
    .ios-search-box .ios-input { padding-left: 2.4rem; }

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
        padding: 0.9rem 1rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: var(--ios-label);
    }
    .ios-table tbody tr { transition: background 0.15s ease; }
    .ios-table tbody tr:hover { background: var(--ios-gray-6); }
    .ios-table tbody tr:last-child td { border-bottom: none; }
    .ios-table tbody tr.table-primary { background: rgba(0,122,255,0.07) !important; }

    .supplier-name {
        font-weight: 600;
        color: var(--ios-orange);
    }
    .ios-count-green { color: var(--ios-green); font-weight: 700; font-size: 0.95rem; }

    /* Product count chip */
    .ios-count-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 0.25rem 0.7rem;
        background: rgba(52,199,89,0.12);
        color: #1f8f3f;
        border-radius: 980px;
        font-weight: 700;
        font-size: 0.85rem;
    }

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
            <span class="icon-badge"><i class="fa-solid fa-truck"></i></span>
            Suppliers
        </h1>
        <p class="ios-page-subtitle">Manage active suppliers, view product association counts, and transfer products between suppliers.</p>
    </div>

    <!-- ============ Layout Grid ============ -->
    <div class="row" id="supplierLayoutRow">
        <!-- Suppliers Card -->
        <div class="col-lg-12 transition-width" id="supplierCardContainer">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h5 class="ios-card-title">Active Suppliers</h5>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="ios-search-box" style="width: 270px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchSupplierName" class="ios-input" placeholder="Search supplier...">
                        </div>
                        <span class="ios-pill ios-pill-orange"><?= count($suppliers) ?> Suppliers</span>
                        <button class="ios-btn ios-btn-primary ios-btn-sm" id="addSupplierBtn">
                            <i class="fa-solid fa-plus"></i>Add Supplier
                        </button>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="table-responsive">
                        <table class="ios-table" id="suppliersTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Supplier Name</th>
                                    <th class="text-center" style="width: 30%">Products Count</th>
                                    <th class="text-center" style="width: 30%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No suppliers created yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers as $sup): ?>
                                        <tr data-id="<?= $sup['id'] ?>" data-name="<?= htmlspecialchars($sup['name']) ?>">
                                            <td>
                                                <span class="supplier-name"><i class="fa-regular fa-building me-2"></i><?= htmlspecialchars($sup['name']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="ios-count-chip"><?= (int)$sup['product_count'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <button class="ios-icon-btn info transfer-supplier-btn" title="Transfer Products">
                                                    <i class="fa-solid fa-right-left"></i>
                                                </button>
                                                <button class="ios-icon-btn edit edit-supplier-btn" title="Edit Supplier">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </button>
                                                <button class="ios-icon-btn danger delete-supplier-btn" title="Delete Supplier">
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
        <div class="col-lg-7 d-none transition-width" id="supplierProductsCardContainer">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h5 class="ios-card-title text-truncate" id="selectedSupplierTitle" style="max-width: 85%;">Products by Supplier</h5>
                    <button class="ios-close" id="closeProductsCardBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="ios-card-body">
                    <div id="productsLoading" class="text-center py-5 d-none">
                        <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--ios-blue);"></i>
                        <p class="text-muted small mt-2">Fetching products...</p>
                    </div>
                    <div id="productsContent">
                        <div class="table-responsive scroll-area">
                            <table class="ios-table" id="supplierProductsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%">Code</th>
                                        <th>Product Name</th>
                                        <th style="width: 20%">Price</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody id="supplierProductsBody">
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

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ios-modal">
            <form id="supplierForm">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="supplierModalLabel">Add Supplier</h5>
                    <button type="button" class="ios-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="supplier_id" value="">
                    <div class="mb-3">
                        <label for="supplier_name" class="ios-form-label">Supplier Name</label>
                        <input type="text" class="ios-input" id="supplier_name" name="name" required placeholder="e.g. Baby Sleep Corp">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ios-btn ios-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ios-btn ios-btn-primary"><i class="fa-solid fa-save"></i>Save Supplier</button>
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
                    <p class="text-muted small">Move all products from the current supplier to a new destination supplier.</p>
                    <div class="mb-3">
                        <label class="ios-form-label">From Supplier</label>
                        <input type="text" class="ios-input readonly" id="transfer_from_name" readonly>
                        <input type="hidden" id="transfer_from" name="from_supplier">
                    </div>
                    <div class="mb-3">
                        <label for="transfer_to" class="ios-form-label">To Supplier</label>
                        <select class="ios-select" id="transfer_to" name="to_supplier" required>
                            <option value="">Select Destination Supplier...</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></option>
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
    // Search Supplier
    $('#searchSupplierName').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('#suppliersTable tbody tr').each(function() {
            const tr = $(this);
            if (tr.find('td').length <= 1) return;
            const name = tr.data('name') ? tr.data('name').toLowerCase() : '';
            if (name.includes(query)) {
                tr.show();
            } else {
                tr.hide();
            }
        });
    });

    // Add Supplier Modal
    $('#addSupplierBtn').on('click', function() {
        $('#supplierForm')[0].reset();
        $('#supplier_id').val('');
        $('#supplierModalLabel').text('Add Supplier');
        $('#supplierModal').modal('show');
    });

    // Edit Supplier Modal
    $(document).on('click', '.edit-supplier-btn', function() {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');

        $('#supplier_id').val(id);
        $('#supplier_name').val(name);
        $('#supplierModalLabel').text('Edit Supplier');
        $('#supplierModal').modal('show');
    });

    // Submit Supplier Form
    $('#supplierForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'suppliers.php?action=save',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#supplierModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Server error saving Supplier.');
            }
        });
    });

    // Delete Supplier
    $(document).on('click', '.delete-supplier-btn', function() {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');

        if (!confirm('Are you sure you want to delete Supplier "' + name + '"? Products referencing this supplier will have their supplier set to empty.')) {
            return;
        }

        $.ajax({
            url: 'suppliers.php?action=delete',
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
                alert('Server error deleting Supplier.');
            }
        });
    });

    // Transfer Products Modal
    $(document).on('click', '.transfer-supplier-btn', function() {
        const tr = $(this).closest('tr');
        const name = tr.data('name');

        $('#transferForm')[0].reset();
        $('#transfer_from_name').val(name);
        $('#transfer_from').val(name);
        // Remove the current supplier from the destination options
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
            url: 'suppliers.php?action=transfer',
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

    // Click supplier name to show products
    $(document).on('click', '.supplier-name', function(e) {
        e.preventDefault();
        const tr = $(this).closest('tr');
        const supplierName = tr.data('name');

        // Highlight selected row
        $('#suppliersTable tbody tr').removeClass('table-primary');
        tr.addClass('table-primary');

        // Transition layout
        $('#supplierCardContainer').removeClass('col-lg-12').addClass('col-lg-5');
        $('#supplierProductsCardContainer').removeClass('d-none');

        $('#selectedSupplierTitle').text('Products from "' + supplierName + '"');
        $('#productsLoading').removeClass('d-none');
        $('#productsContent').addClass('d-none');

        // Load Products via AJAX
        $.ajax({
            url: 'suppliers.php?action=get_products&supplier=' + encodeURIComponent(supplierName),
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let html = '';
                    if (res.products.length === 0) {
                        html = '<tr><td colspan="4" class="text-center text-muted py-4">No products found for this supplier.</td></tr>';
                    } else {
                        res.products.forEach(function(p) {
                            html += '<tr>' +
                                    '  <td><code>' + p.product_code + '</code></td>' +
                                    '  <td style="font-weight:600;">' + p.product_name + '</td>' +
                                    '  <td style="color: var(--ios-green); font-weight:600;">Rs. ' + parseFloat(p.selling_price).toFixed(2) + '</td>' +
                                    '  <td class="small text-muted">' + (p.current_category ? p.current_category : 'Not set') + '</td>' +
                                    '</tr>';
                        });
                    }
                    $('#supplierProductsBody').html(html);
                    $('#productsLoading').addClass('d-none');
                    $('#productsContent').removeClass('d-none');
                } else {
                    $('#supplierProductsBody').html('<tr><td colspan="4" class="text-danger text-center py-4">Failed to load products.</td></tr>');
                    $('#productsLoading').addClass('d-none');
                    $('#productsContent').removeClass('d-none');
                }
            },
            error: function() {
                $('#supplierProductsBody').html('<tr><td colspan="4" class="text-danger text-center py-4">Error loading data.</td></tr>');
                $('#productsLoading').addClass('d-none');
                $('#productsContent').removeClass('d-none');
            }
        });
    });

    // Close Products Card
    $('#closeProductsCardBtn').on('click', function() {
        $('#supplierProductsCardContainer').addClass('d-none');
        $('#supplierCardContainer').removeClass('col-lg-5').addClass('col-lg-12');
        $('#suppliersTable tbody tr').removeClass('table-primary');
    });
});
</script>