<?php
require_once __DIR__ . '/config/bootstrap.php';

use Models\MainCategory;

// Route AJAX requests
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $mcModel = new MainCategory();
        $res = $mcModel->delete($id);
        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'Main Category deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete Main Category.']);
        }
        exit;
    }

    if ($_GET['action'] === 'save') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Main Category name is required.']);
            exit;
        }

        $mcModel = new MainCategory();
        if ($id > 0) {
            $res = $mcModel->update($id, $name);
            $msg = 'Main Category updated successfully.';
        } else {
            $res = $mcModel->save($name);
            $msg = 'Main Category created successfully.';
        }
        
        if ($res) {
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save Main Category.']);
        }
        exit;
    }
}

// Fetch main categories for display
$mcModel = new MainCategory();
$mainCategories = $mcModel->getAll();

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
    .ios-btn-secondary { background: var(--ios-gray-5); color: var(--ios-label); }
    .ios-btn-secondary:hover { background: var(--ios-gray-4); color: var(--ios-label); }

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
    .ios-icon-btn.edit   { background: rgba(0,122,255,0.12); color: var(--ios-blue); }
    .ios-icon-btn.edit:hover   { background: var(--ios-blue); color: #fff; }
    .ios-icon-btn.danger { background: rgba(255,59,48,0.12); color: var(--ios-red); }
    .ios-icon-btn.danger:hover { background: var(--ios-red); color: #fff; }

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
    .ios-pill-blue { background: rgba(0,122,255,0.12); color: var(--ios-blue); }

    /* ---------- Inputs ---------- */
    .ios-input {
        width: 100%;
        background: var(--ios-gray-6);
        border: 1.5px solid transparent;
        border-radius: var(--ios-radius-xs);
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        color: var(--ios-label);
        transition: all 0.2s ease;
        outline: none;
        font-family: var(--ios-font);
    }
    .ios-input::placeholder { color: var(--ios-gray-2); }
    .ios-input:focus {
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

    .main-cat-name {
        font-weight: 600;
        color: var(--ios-blue);
    }
    .ios-count-green { color: var(--ios-green); font-weight: 700; font-size: 0.95rem; }

    /* Sub-category count chip */
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
    }
</style>

<div class="ios-wrap">

    <!-- ============ Page Header ============ -->
    <div class="ios-page-header">
        <h1 class="ios-page-title">
            <span class="icon-badge"><i class="fa-solid fa-folder"></i></span>
            Main Categories
        </h1>
        <p class="ios-page-subtitle">Manage active main categories and view sub-category association counts.</p>
    </div>

    <!-- ============ Layout Grid ============ -->
    <div class="row">
        <!-- Main Categories Card -->
        <div class="col-lg-12">
            <div class="ios-card">
                <div class="ios-card-header">
                    <h5 class="ios-card-title">Active Main Categories</h5>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="ios-search-box" style="width: 270px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchMainCategoryName" class="ios-input" placeholder="Search main category...">
                        </div>
                        <span class="ios-pill ios-pill-blue"><?= count($mainCategories) ?> Main Categories</span>
                        <button class="ios-btn ios-btn-primary ios-btn-sm" id="addMainCategoryBtn">
                            <i class="fa-solid fa-plus"></i>Add Main Category
                        </button>
                    </div>
                </div>
                <div class="ios-card-body">
                    <div class="table-responsive">
                        <table class="ios-table" id="mainCategoriesTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Main Category Name</th>
                                    <th class="text-center" style="width: 30%">Sub-Categories Count</th>
                                    <th class="text-center" style="width: 30%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mainCategories)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No main categories created yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mainCategories as $mc): ?>
                                        <tr data-id="<?= $mc['id'] ?>" data-name="<?= htmlspecialchars($mc['name']) ?>">
                                            <td>
                                                <span class="main-cat-name"><i class="fa-regular fa-folder-open me-2"></i><?= htmlspecialchars($mc['name']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="ios-count-chip"><?= (int)$mc['sub_category_count'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <button class="ios-icon-btn edit edit-main-category-btn" title="Edit Main Category">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </button>
                                                <button class="ios-icon-btn danger delete-main-category-btn" title="Delete Main Category">
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
    </div>
</div>

<!-- Add/Edit Main Category Modal -->
<div class="modal fade" id="mainCategoryModal" tabindex="-1" aria-labelledby="mainCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ios-modal">
            <form id="mainCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="mainCategoryModalLabel">Add Main Category</h5>
                    <button type="button" class="ios-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="main_category_id" value="">
                    <div class="mb-3">
                        <label for="main_category_name" class="ios-form-label">Main Category Name</label>
                        <input type="text" class="ios-input" id="main_category_name" name="name" required placeholder="e.g. Apparel">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ios-btn ios-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ios-btn ios-btn-primary"><i class="fa-solid fa-save"></i>Save Main Category</button>
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
    // Search Main Category
    $('#searchMainCategoryName').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('#mainCategoriesTable tbody tr').each(function() {
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

    // Add Main Category Modal
    $('#addMainCategoryBtn').on('click', function() {
        $('#mainCategoryForm')[0].reset();
        $('#main_category_id').val('');
        $('#mainCategoryModalLabel').text('Add Main Category');
        $('#mainCategoryModal').modal('show');
    });

    // Edit Main Category Modal
    $(document).on('click', '.edit-main-category-btn', function() {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');

        $('#main_category_id').val(id);
        $('#main_category_name').val(name);
        $('#mainCategoryModalLabel').text('Edit Main Category');
        $('#mainCategoryModal').modal('show');
    });

    // Submit Main Category Form
    $('#mainCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'main-categories.php?action=save',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#mainCategoryModal').modal('hide');
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Server error saving Main Category.');
            }
        });
    });

    // Delete Main Category
    $(document).on('click', '.delete-main-category-btn', function() {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const name = tr.data('name');

        if (!confirm('Are you sure you want to delete Main Category "' + name + '"? Any sub-categories referencing it will be unassigned.')) {
            return;
        }

        $.ajax({
            url: 'main-categories.php?action=delete',
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
                alert('Server error deleting Main Category.');
            }
        });
    });
});
</script>