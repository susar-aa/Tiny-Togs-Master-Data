            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global App Scripts -->
    <script>
        $(document).ready(function () {
            // Sidebar Toggle
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('show');
            });

            // Theme Toggle
            const themeToggler = $('#themeToggler');
            const icon = themeToggler.find('i');
            
            // Set initial icon based on theme
            function updateThemeIcon(theme) {
                if (theme === 'dark') {
                    icon.removeClass('fa-moon').addClass('fa-sun');
                } else {
                    icon.removeClass('fa-sun').addClass('fa-moon');
                }
            }

            let currentTheme = localStorage.getItem('theme') || 'light';
            updateThemeIcon(currentTheme);

            themeToggler.on('click', function () {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        });
    </script>
</body>
</html>
