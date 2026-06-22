# Product Category Validation System

A local web application built using **PHP**, **MySQL**, **Bootstrap 5**, **jQuery**, and **DataTables** to import category structures and product catalogs, automatically detect misclassified items using advanced/fuzzy text scanning, and review/approve corrections.

## System Features
- **Excel & CSV Import**: Handles large data imports (10,000+ products) using memory-efficient batched AJAX uploads.
- **Categorization Engine**: Automatically flags mismatches by mapping product name substrings to category inclusion keywords.
- **Advanced Text Cleaning**: Normalizes terms by removing hyphens, special characters, double spaces, and casing differences.
- **Fuzzy Word Matching**: Employs Levenshtein distance calculations to match plural/singular or slight spelling variations (e.g., *pillow* &rarr; *pillows*, *towel* &rarr; *towels*).
- **Interactive Review Dashboard**: Features server-side pagination, details modal, single/bulk approvals, and ignoring suggestions.
- **Charts & Statistics**: Uses **Chart.js** to render category distributions and confidence score frequencies.
- **Report Exports**: Generates filtered correction sheets in both Excel (.xlsx) and CSV formats.
- **Audit Logging**: Keeps track of file uploads, scans executed, single approvals, and bulk updates.
- **Dark Mode Support**: Seamless toggle styling for modern UI looks.

---

## Directory Structure
```text
Tiny Togs Master Data/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom CSS layouts and theme variables
│   ├── js/
│   └── images/
├── config/
│   ├── database.php             # PDO database connection configuration
│   └── bootstrap.php            # Custom autoloader, session, error configs
├── controllers/
│   ├── DashboardController.php  # Handles stats calculations
│   ├── ImportController.php     # Handlers for Category/Product spreadsheets
│   ├── ScanController.php       # Cleaning & matching text scanning engine
│   └── SuggestionController.php # DataTables bindings and spreadsheet exports
├── models/
│   ├── Category.php             # Categories database operations
│   ├── Keyword.php              # Category keywords relations
│   ├── Log.php                  # Audit logs recorder
│   ├── Product.php              # Products list, updates, and batch imports
│   ├── Setting.php              # System configuration configurations
│   └── Suggestion.php           # Scanned suggestion corrections logic
├── uploads/                     # Storage for processed temporary spreadsheets
├── views/
│   └── layout/
│       ├── header.php           # Sidebar navigation, topbar, and meta assets
│       └── footer.php           # JS libraries loading (Bootstrap, jQuery, etc.)
├── database.sql                 # Database table schema and initial seeding
├── composer.json                # Composer configurations for libraries
├── index.php                    # Dashboard view / Home
├── import-categories.php        # Categories upload panel
├── import-products.php          # Products batch-upload panel
├── scan-products.php            # AJAX batch scanner trigger page
├── suspicious-products.php      # Main grid list review board
├── settings.php                 # Scanner parameters configuration form
├── export-report.php            # Spreadsheet export options form
└── README.md                    # Installation documentation
```

---

## Installation & Setup Instructions

### 1. Prerequisite Environments
- Install **XAMPP** (or WampServer / Laragon) with **PHP 8.0+** and **MySQL**.
- Install **Composer** (PHP package manager) globally on your system path.

### 2. Project Placement
Clone or move this project folder into your XAMPP local server root directory:
`C:\xampp\htdocs\Tiny Togs Master Data`

### 3. Install PHP Dependencies (PhpSpreadsheet)
Since Excel imports rely on the `PhpSpreadsheet` library, you must download the project dependencies using Composer:
1. Open terminal, command prompt, or PowerShell.
2. Navigate to the project root:
   ```cmd
   cd "C:\xampp\htdocs\Tiny Togs Master Data"
   ```
3. Run the installation command:
   ```cmd
   composer install
   ```
   *Note: This command generates the `vendor/` directory and creates `composer.lock`.*

### 4. Create and Import the Database
1. Launch the XAMPP Control Panel and start the **Apache** and **MySQL** services.
2. Open your web browser and navigate to **phpMyAdmin**: `http://localhost/phpmyadmin/`.
3. Create a new database named: `product_category_val`.
4. Click on the newly created database, go to the **Import** tab.
5. Choose the `database.sql` file located in the root of the project folder.
6. Click **Go** (or Import) to run the schema setups and insert the sample dataset.

### 5. Running the Application
Open your web browser and access the local server path:
`http://localhost/Tiny%20Togs%20Master%20Data/index.php`

---

## Verification Testing (Manual)
1. **Initial View**: The dashboard should display the default 5 categories, 5 products, and 31 keywords pre-loaded from `database.sql`.
2. **Import Categories**: Go to *Import Categories*, drag or select a category Excel file, and verify keywords are parsed.
3. **Import Products**: Go to *Import Products*, drag or select a product Excel file, and verify it uploads through AJAX and updates the database without timeouts.
4. **Scan Products**: Go to *Scan Products* and run the scanner. Once completed, it will calculate mismatches.
5. **Review Dashboard**: Go to *Suspicious Products*, search for products, view details in the modal, and test individual/bulk approve or ignore actions.
6. **Export Report**: Export findings as CSV or Excel files in *Reports* to verify data matching.
7. **Settings**: Modify settings like fuzzy matching or thresholds in *Settings* and rerun scans to check outputs.
