<?php
// Determine current script name for active menu highlight
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Category Validation System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <script>
        // Inline script to prevent theme flashing on load
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="brand">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Tiny Togs Admin</span>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-table-list"></i>
                        <span>PRODUCT LIST</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="master-data.php" class="nav-link <?= ($current_page == 'master-data.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-table-cells"></i>
                        <span>MASTER DATA</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="import-categories.php" class="nav-link <?= ($current_page == 'import-categories.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-folder-tree"></i>
                        <span>CATEGORIES</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="main-categories.php" class="nav-link <?= ($current_page == 'main-categories.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-folder"></i>
                        <span>MAIN CATEGORIES</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="suppliers.php" class="nav-link <?= ($current_page == 'suppliers.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-truck"></i>
                        <span>SUPPLIERS</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <span>Version 1.0.0</span>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div id="content-wrapper">
            <!-- Main Content Container -->
            <main class="main-content">
