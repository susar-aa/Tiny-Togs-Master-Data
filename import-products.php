<?php
require_once __DIR__ . '/config/bootstrap.php';

use Controllers\ImportController;
use Models\Product;

// Route AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $importCtrl = new ImportController();

    if ($action === 'upload' && isset($_FILES['product_file'])) {
        $res = $importCtrl->initProductImport($_FILES['product_file']);
        echo json_encode($res);
        exit;
    }
    
    if ($action === 'process_batch') {
        $file_path = $_POST['file_path'] ?? '';
        $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 500;
        
        $res = $importCtrl->processProductBatch($file_path, $offset, $limit);
        echo json_encode($res);
        exit;
    }
    
    if ($action === 'finalize') {
        $file_path = $_POST['file_path'] ?? '';
        $total_imported = isset($_POST['total_imported']) ? (int)$_POST['total_imported'] : 0;
        
        $res = $importCtrl->finalizeProductImport($file_path, $total_imported);
        echo json_encode($res);
        exit;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

$productModel = new Product();
$total_products = $productModel->getCount();

include __DIR__ . '/views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 font-weight-700"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Import Products</h1>
            <p class="text-muted">Import high-volume product databases. The system handles 10,000+ rows through batched uploads.</p>
        </div>
    </div>

    <div class="row">
        <!-- Upload & Process Card -->
        <div class="col-lg-6 mb-4">
            <!-- Upload Panel -->
            <div class="card card-glass shadow-sm mb-4" id="uploadPanel">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="card-title font-weight-600 mb-0">Upload Product Catalog</h5>
                </div>
                <div class="card-body p-4">
                    <form id="productUploadForm">
                        <div class="dropzone-container" id="dropzone">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <h6 class="font-weight-600">Drag & drop your product catalog here</h6>
                            <p class="text-muted small">or click to browse from files (.xlsx, .xls, .csv)</p>
                            <input type="file" name="product_file" id="fileInput" class="d-none" accept=".xlsx, .xls, .csv" required>
                            <div class="text-primary font-weight-600 mt-2 small" id="fileNameDisplay"></div>
                        </div>

                        <div class="mt-4">
                            <h6 class="font-weight-600 mb-2">Supported Headers:</h6>
                            <p class="text-muted small mb-2">The system will dynamically look for and map columns with these terms:</p>
                            <ul class="text-muted small ps-3">
                                <li><strong>Code</strong> (optional - separate product code)</li>
                                <li><strong>SKU</strong> (required - unique product identifier)</li>
                                <li><strong>Product Name / Title</strong> (required)</li>
                                <li><strong>Product Category / Category</strong></li>
                                <li><strong>Cost Price</strong></li>
                                <li><strong>Selling Price / Price</strong></li>
                                <li><strong>Supplier Name / Vendor</strong></li>
                                <li><em>All other custom columns will be saved under additional attributes JSON.</em></li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-4 py-2" id="submitBtn" disabled>
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload File
                        </button>
                    </form>
                </div>
            </div>

            <!-- Progress Panel -->
            <div class="card card-glass shadow-sm mb-4 d-none" id="progressPanel">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="card-title font-weight-600 mb-0">Processing Imports...</h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-spinner fa-spin text-primary fa-3x mb-3"></i>
                        <h6 class="font-weight-600" id="progressStatus">Reading Excel data rows...</h6>
                        <p class="text-muted small" id="progressSubstatus">Preparing rows for database batch processing.</p>
                    </div>

                    <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="importProgressBar"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small font-weight-500">
                        <span id="progressPercentage">0%</span>
                        <span id="progressCount">0 / 0 products</span>
                    </div>
                </div>
            </div>

            <!-- Result Panel -->
            <div class="card card-glass border-0 shadow-sm bg-success text-white mb-4 d-none" id="resultPanel">
                <div class="card-body p-4 text-center">
                    <i class="fa-solid fa-circle-check fa-4x mb-3"></i>
                    <h4 class="font-weight-700">Import Complete!</h4>
                    <p class="mb-4" id="resultMessage">Product file uploaded and processed successfully.</p>
                    <button class="btn btn-light text-success font-weight-600 px-4" onclick="location.reload()">
                        <i class="fa-solid fa-rotate-left me-2"></i>Import Another File
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-6 mb-4">
            <div class="card card-glass shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="card-title font-weight-600 mb-0">Database Summary</h5>
                    <i class="fa-solid fa-database text-muted"></i>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4 p-3 bg-light rounded border">
                        <h6 class="font-weight-600 text-muted small text-uppercase mb-2">Total Stored Products</h6>
                        <h2 class="font-weight-700 m-0 text-primary"><?= number_format($total_products) ?></h2>
                    </div>

                    <div class="alert alert-info card-glass" role="alert">
                        <h6 class="font-weight-600 alert-heading mb-2"><i class="fa-solid fa-circle-info me-2"></i>Import Guidelines</h6>
                        <ul class="small mb-0 ps-3">
                            <li>Keep sheet formulas and images clear to speed up uploads.</li>
                            <li>The first row is always processed as headers.</li>
                            <li>Existing product codes (SKUs) will have their names, categories, and prices updated; new codes will be added.</li>
                            <li>JSON formats are automatically handled for additional fields.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/views/layout/footer.php';
?>

<script>
$(document).ready(function() {
    const dropzone = $('#dropzone');
    const fileInput = $('#fileInput');
    const display = $('#fileNameDisplay');
    const submitBtn = $('#submitBtn');
    const uploadForm = $('#productUploadForm');

    // Drag and Drop triggers
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
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Ajax-driven upload & batching loop
    uploadForm.on('submit', function(e) {
        e.preventDefault();

        const file = fileInput[0].files[0];
        if (!file) return;

        // Transition panels
        $('#uploadPanel').addClass('d-none');
        $('#progressPanel').removeClass('d-none');
        $('#progressStatus').text("Uploading Excel file to server...");

        const formData = new FormData();
        formData.append('product_file', file);

        $.ajax({
            url: 'import-products.php?action=upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Start processing batches
                    processBatch(response.file_path, 0, response.total_rows, 500, 0);
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                showError("Uploading error: " + error);
            }
        });
    });

    function processBatch(filePath, offset, totalRows, batchSize, totalImported) {
        $('#progressStatus').text("Processing spreadsheet database...");
        $('#progressSubstatus').text("Importing rows " + (offset + 1) + " to " + Math.min(offset + batchSize, totalRows) + "...");
        
        $.ajax({
            url: 'import-products.php?action=process_batch',
            type: 'POST',
            data: {
                file_path: filePath,
                offset: offset,
                limit: batchSize
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const importedThisBatch = response.imported;
                    const nextOffset = offset + batchSize;
                    const runningImported = totalImported + importedThisBatch;
                    
                    // Update progress bar
                    const percent = Math.min(100, Math.round((nextOffset / totalRows) * 100));
                    $('#importProgressBar').css('width', percent + '%').attr('aria-valuenow', percent);
                    $('#progressPercentage').text(percent + '%');
                    $('#progressCount').text(Math.min(nextOffset, totalRows) + " / " + totalRows + " products");

                    if (nextOffset < totalRows && importedThisBatch > 0) {
                        // Continue loop
                        processBatch(filePath, nextOffset, totalRows, batchSize, runningImported);
                    } else {
                        // Finalize import
                        finalizeImport(filePath, runningImported);
                    }
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                showError("Batch processing failed: " + error);
            }
        });
    }

    function finalizeImport(filePath, totalImported) {
        $('#progressStatus').text("Finalizing imports and cleaning tmp file...");
        $('#progressSubstatus').text("Wrapping up processes.");
        
        $.ajax({
            url: 'import-products.php?action=finalize',
            type: 'POST',
            data: {
                file_path: filePath,
                total_imported: totalImported
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#progressPanel').addClass('d-none');
                    $('#resultPanel').removeClass('d-none');
                    $('#resultMessage').text("Successfully processed spreadsheet. Total imported/updated records: " + totalImported);
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                showError("Finalization failed: " + error);
            }
        });
    }

    function showError(message) {
        $('#progressPanel').addClass('d-none');
        $('#uploadPanel').removeClass('d-none');
        alert("Import Error: " + message);
        location.reload();
    }
});
</script>
