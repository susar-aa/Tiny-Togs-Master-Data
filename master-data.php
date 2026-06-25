<?php
require_once __DIR__ . '/config/bootstrap.php';

use Config\Database;

// AJAX endpoint for fetching data
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    header('Content-Type: application/json');
    $db = Database::getConnection();
    $sql = "SELECT p.id, p.code, p.product_code, p.product_name, p.current_category, c.main_category, p.cost_price, p.selling_price, p.supplier
            FROM products p
            LEFT JOIN categories c ON p.current_category = c.category_name
            ORDER BY p.id ASC";
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $products, 'total' => count($products)]);
    exit;
}

include __DIR__ . '/views/layout/header.php';
?>

<style>
    :root {
        --ios-bg: #f2f2f7; --ios-card: #ffffff; --ios-blue: #007aff; --ios-blue-hover: #0066d6;
        --ios-green: #34c759; --ios-red: #ff3b30; --ios-orange: #ff9500;
        --ios-gray: #8e8e93; --ios-gray-2: #aeaeb2; --ios-gray-3: #c7c7cc;
        --ios-gray-4: #d1d1d6; --ios-gray-5: #e5e5ea; --ios-gray-6: #f2f2f7;
        --ios-label: #1c1c1e; --ios-secondary-label: #6b6b70;
        --ios-radius: 18px; --ios-radius-sm: 12px; --ios-radius-xs: 10px;
        --ios-shadow: 0 4px 24px rgba(0,0,0,0.06);
        --ios-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
    }
    body { background: var(--ios-bg) !important; font-family: var(--ios-font); color: var(--ios-label); -webkit-font-smoothing: antialiased; }
    .ios-wrap { max-width: 1600px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
    .ios-page-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.75rem; }
    .ios-page-title { font-size: 1.85rem; font-weight: 700; letter-spacing: -0.02em; margin: 0; display: flex; align-items: center; gap: 0.65rem; }
    .ios-page-title .icon-badge { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #34c759, #30d158); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(52,199,89,0.35); }
    .ios-page-subtitle { color: var(--ios-secondary-label); font-size: 0.92rem; margin: 0.4rem 0 0 0; max-width: 600px; }
    .ios-header-actions { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    .ios-btn { border: none; border-radius: 980px; font-weight: 600; font-size: 0.88rem; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; gap: 0.45rem; cursor: pointer; transition: all 0.2s ease; text-decoration: none; line-height: 1; }
    .ios-btn-primary { background: var(--ios-blue); color: #fff; box-shadow: 0 4px 14px rgba(0,122,255,0.3); }
    .ios-btn-primary:hover { background: var(--ios-blue-hover); color: #fff; transform: translateY(-1px); }
    .ios-btn-green { background: var(--ios-green); color: #fff; box-shadow: 0 4px 14px rgba(52,199,89,0.3); }
    .ios-btn-green:hover { background: #2db84e; color: #fff; transform: translateY(-1px); }
    .ios-pill-badge { display: inline-flex; align-items: center; gap: 0.4rem; background: var(--ios-gray-5); color: var(--ios-secondary-label); border-radius: 980px; font-size: 0.8rem; font-weight: 600; padding: 0.55rem 1rem; }
    .ios-card { background: var(--ios-card); border-radius: var(--ios-radius); box-shadow: var(--ios-shadow); border: none; overflow: hidden; }

    /* ---- Excel Spreadsheet Styles ---- */
    .excel-toolbar { padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--ios-gray-5); display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%); }
    .excel-toolbar .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 350px; }
    .excel-toolbar .search-wrap input { width: 100%; background: #fff; border: 1.5px solid var(--ios-gray-4); border-radius: 8px; padding: 0.5rem 0.9rem 0.5rem 2.2rem; font-size: 0.85rem; outline: none; transition: all 0.2s ease; }
    .excel-toolbar .search-wrap input:focus { border-color: var(--ios-blue); box-shadow: 0 0 0 3px rgba(0,122,255,0.1); }
    .excel-toolbar .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--ios-gray-2); font-size: 0.8rem; }
    .excel-stat { font-size: 0.82rem; font-weight: 600; color: var(--ios-secondary-label); }
    .excel-stat strong { color: var(--ios-blue); }

    .excel-container { overflow: auto; max-height: calc(100vh - 240px); }
    .excel-container::-webkit-scrollbar { width: 10px; height: 10px; }
    .excel-container::-webkit-scrollbar-thumb { background: var(--ios-gray-3); border-radius: 6px; }
    .excel-container::-webkit-scrollbar-track { background: var(--ios-gray-6); }

    .excel-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; table-layout: fixed; }
    .excel-table thead { position: sticky; top: 0; z-index: 10; }
    .excel-table thead th {
        background: linear-gradient(180deg, #f0f0f0 0%, #e8e8e8 100%);
        border: 1px solid var(--ios-gray-4);
        padding: 0.55rem 0.75rem;
        font-weight: 700; font-size: 0.73rem;
        text-transform: uppercase; letter-spacing: 0.04em;
        color: var(--ios-secondary-label);
        text-align: left; white-space: nowrap;
        user-select: none; cursor: pointer;
        position: relative;
    }
    .excel-table thead th:hover { background: linear-gradient(180deg, #e8e8e8 0%, #ddd 100%); }
    .excel-table thead th.sorted-asc::after { content: ' ▲'; font-size: 0.6rem; color: var(--ios-blue); }
    .excel-table thead th.sorted-desc::after { content: ' ▼'; font-size: 0.6rem; color: var(--ios-blue); }
    .excel-table thead th.row-num-col { width: 50px; text-align: center; cursor: default; background: #e0e0e0; }

    .excel-table tbody td {
        border: 1px solid var(--ios-gray-5);
        padding: 0.45rem 0.75rem;
        color: var(--ios-label);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        transition: background 0.1s ease;
    }
    .excel-table tbody tr:hover td { background: rgba(0,122,255,0.04); }
    .excel-table tbody tr:nth-child(even) td { background: #fafbfc; }
    .excel-table tbody tr:nth-child(even):hover td { background: rgba(0,122,255,0.06); }
    .excel-table tbody td.row-num { background: #f0f0f0; color: var(--ios-gray); font-weight: 600; text-align: center; font-size: 0.78rem; border-right: 2px solid var(--ios-gray-4); }
    .excel-table tbody td.price-cell { text-align: right; font-variant-numeric: tabular-nums; color: var(--ios-green); font-weight: 600; }
    .excel-table tbody td.code-cell { font-family: 'SF Mono', 'Menlo', 'Consolas', monospace; font-size: 0.82rem; color: var(--ios-blue); }
    .excel-table tbody td.empty-cell { color: var(--ios-gray-3); font-style: italic; }

    /* Column widths */
    .col-rownum { width: 50px; }
    .col-code { width: 100px; }
    .col-sku { width: 110px; }
    .col-name { width: 280px; }
    .col-maincat { width: 160px; }
    .col-subcat { width: 200px; }
    .col-cost { width: 120px; }
    .col-sell { width: 120px; }
    .col-supplier { width: 180px; }

    /* Loading overlay */
    .excel-loading { display: flex; align-items: center; justify-content: center; padding: 4rem 2rem; flex-direction: column; gap: 1rem; }
    .excel-loading i { font-size: 2rem; color: var(--ios-blue); }
    .excel-loading p { color: var(--ios-secondary-label); font-weight: 500; font-size: 0.9rem; margin: 0; }

    /* Empty state */
    .excel-empty { text-align: center; padding: 4rem 2rem; color: var(--ios-gray-2); }
    .excel-empty i { font-size: 3rem; margin-bottom: 1rem; display: block; }

    @media (max-width: 768px) {
        .ios-page-title { font-size: 1.45rem; }
        .excel-container { max-height: calc(100vh - 280px); }
    }
</style>

<div class="ios-wrap">
    <!-- Page Header -->
    <div class="ios-page-header">
        <div>
            <h1 class="ios-page-title">
                <span class="icon-badge"><i class="fa-solid fa-table-cells"></i></span>
                Master Data View
            </h1>
            <p class="ios-page-subtitle">
                View the complete product master data in a spreadsheet-like interface. Click column headers to sort.
            </p>
        </div>
        <div class="ios-header-actions">
            <a href="export-products.php" class="ios-btn ios-btn-green">
                <i class="fa-solid fa-file-excel"></i>Export Excel
            </a>
            <a href="import-products.php" class="ios-btn ios-btn-primary">
                <i class="fa-solid fa-cloud-arrow-up"></i>Import Products
            </a>
            <span class="ios-pill-badge" id="totalBadge"><i class="fa-solid fa-database"></i>Loading...</span>
        </div>
    </div>

    <!-- Excel Card -->
    <div class="ios-card">
        <!-- Toolbar -->
        <div class="excel-toolbar">
            <div class="search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="excelSearch" placeholder="Search across all columns...">
            </div>
            <div class="excel-stat" id="shownStat">Showing <strong>0</strong> of <strong>0</strong> rows</div>
        </div>

        <!-- Spreadsheet Container -->
        <div class="excel-container" id="excelContainer">
            <div class="excel-loading" id="excelLoading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p>Loading master data...</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/layout/footer.php'; ?>

<script>
$(document).ready(function() {
    let allData = [];
    let filteredData = [];
    let sortCol = null;
    let sortDir = 'asc';

    const columns = [
        { key: 'code', label: 'Code', cls: 'col-code', cellCls: 'code-cell' },
        { key: 'product_code', label: 'SKU', cls: 'col-sku', cellCls: 'code-cell' },
        { key: 'product_name', label: 'Product Name', cls: 'col-name', cellCls: '' },
        { key: 'main_category', label: 'Main Category', cls: 'col-maincat', cellCls: '' },
        { key: 'current_category', label: 'Sub Category', cls: 'col-subcat', cellCls: '' },
        { key: 'cost_price', label: 'Cost Price', cls: 'col-cost', cellCls: 'price-cell', isPrice: true },
        { key: 'selling_price', label: 'Selling Price', cls: 'col-sell', cellCls: 'price-cell', isPrice: true },
        { key: 'supplier', label: 'Supplier Name', cls: 'col-supplier', cellCls: '' }
    ];

    // Fetch data
    $.ajax({
        url: 'master-data.php?action=fetch_data',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                allData = res.data;
                filteredData = [...allData];
                $('#totalBadge').html('<i class="fa-solid fa-database"></i>' + allData.length.toLocaleString() + ' Products');
                renderTable();
            }
        },
        error: function() {
            $('#excelContainer').html('<div class="excel-empty"><i class="fa-solid fa-circle-exclamation"></i><p>Failed to load data</p></div>');
        }
    });

    function renderTable() {
        if (filteredData.length === 0 && allData.length > 0) {
            $('#excelContainer').html('<div class="excel-empty"><i class="fa-solid fa-filter-circle-xmark"></i><p>No products match your search</p></div>');
            updateStats();
            return;
        }
        if (allData.length === 0) {
            $('#excelContainer').html('<div class="excel-empty"><i class="fa-solid fa-box-open"></i><p>No products in the database yet</p></div>');
            updateStats();
            return;
        }

        let html = '<table class="excel-table"><thead><tr>';
        html += '<th class="row-num-col col-rownum">#</th>';
        columns.forEach(function(col, idx) {
            let sortCls = '';
            if (sortCol === idx) sortCls = sortDir === 'asc' ? ' sorted-asc' : ' sorted-desc';
            html += '<th class="' + col.cls + sortCls + '" data-col="' + idx + '">' + col.label + '</th>';
        });
        html += '</tr></thead><tbody>';

        filteredData.forEach(function(row, i) {
            html += '<tr>';
            html += '<td class="row-num">' + (i + 1) + '</td>';
            columns.forEach(function(col) {
                let val = row[col.key] ?? '';
                let cellCls = col.cellCls || '';
                if (!val && val !== 0 && val !== '0') {
                    cellCls += ' empty-cell';
                    val = '—';
                } else if (col.isPrice) {
                    val = 'Rs. ' + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                html += '<td class="' + cellCls + '" title="' + String(row[col.key] ?? '').replace(/"/g, '&quot;') + '">' + val + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#excelContainer').html(html);
        updateStats();

        // Bind sort click
        $('.excel-table thead th[data-col]').on('click', function() {
            const colIdx = parseInt($(this).data('col'));
            if (sortCol === colIdx) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = colIdx;
                sortDir = 'asc';
            }
            sortData();
            renderTable();
        });
    }

    function sortData() {
        if (sortCol === null) return;
        const key = columns[sortCol].key;
        const isPrice = columns[sortCol].isPrice;

        filteredData.sort(function(a, b) {
            let valA = a[key] ?? '';
            let valB = b[key] ?? '';
            if (isPrice) {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else {
                valA = String(valA).toLowerCase();
                valB = String(valB).toLowerCase();
            }
            if (valA < valB) return sortDir === 'asc' ? -1 : 1;
            if (valA > valB) return sortDir === 'asc' ? 1 : -1;
            return 0;
        });
    }

    function updateStats() {
        $('#shownStat').html('Showing <strong>' + filteredData.length.toLocaleString() + '</strong> of <strong>' + allData.length.toLocaleString() + '</strong> rows');
    }

    // Search with debounce
    let searchTimer = null;
    $('#excelSearch').on('input', function() {
        clearTimeout(searchTimer);
        const q = $(this).val().toLowerCase().trim();
        searchTimer = setTimeout(function() {
            if (!q) {
                filteredData = [...allData];
            } else {
                filteredData = allData.filter(function(row) {
                    return columns.some(function(col) {
                        return String(row[col.key] ?? '').toLowerCase().includes(q);
                    });
                });
            }
            if (sortCol !== null) sortData();
            renderTable();
        }, 200);
    });
});
</script>
