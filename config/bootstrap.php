<?php
// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (Display during development, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define Root Path
define('ROOT_PATH', dirname(__DIR__) . '/');

// Custom PSR-4-like Autoloader
spl_autoload_register(function ($class) {
    $base_dir = ROOT_PATH;
    
    // Split the class path
    $parts = explode('\\', $class);
    
    // Convert first part to lowercase (folders are lowercase: config, controllers, models)
    if (count($parts) > 1) {
        $parts[0] = strtolower($parts[0]);
    }
    
    $file = $base_dir . implode('/', $parts) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load Composer Autoloader for PhpSpreadsheet
$composer_autoload = ROOT_PATH . 'vendor/autoload.php';
if (!file_exists($composer_autoload)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Composer Setup Required - Tiny Togs Validation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: #0f172a;
                color: #f8fafc;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .setup-card {
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 0.75rem;
                max-width: 580px;
                padding: 2.5rem;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
            }
            code {
                background: #0f172a;
                color: #38bdf8;
                padding: 0.2rem 0.4rem;
                border-radius: 0.25rem;
                font-family: monospace;
            }
            pre {
                background: #0f172a;
                color: #38bdf8;
                padding: 1rem;
                border-radius: 0.5rem;
                font-family: monospace;
                text-align: left;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <div class="setup-card text-center">
            <i class="fa-solid fa-triangle-exclamation fa-4x text-warning mb-4"></i>
            <h3 class="font-weight-700 mb-3 text-white">Composer Dependencies Missing</h3>
            <p class="text-muted mb-4">
                The validation application depends on the third-party <strong>PhpSpreadsheet</strong> library to process spreadsheet data, which is currently not installed.
            </p>
            <div class="mb-4">
                <p class="mb-2 text-start font-weight-600 text-white-50">To fix this, run the following commands in your command line:</p>
                <pre class="m-0"><code>cd "C:\xampp\htdocs\Tiny Togs Master Data"
composer install</code></pre>
            </div>
            <p class="small text-muted mb-0">
                If you haven't installed Composer yet, download it from <a href="https://getcomposer.org/" target="_blank" class="text-info text-decoration-none">getcomposer.org</a>. Once the installation is complete, reload this page to access the application.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
} else {
    require_once $composer_autoload;
}

