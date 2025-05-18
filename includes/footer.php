    </div><!-- /.container -->
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0"><i class="bi bi-check2-square"></i> MyTasker &copy; <?php echo date('Y'); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">Personal Data Protection Act Compliant</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Only handle task status toggles (no hover effects)
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.task-status-toggle');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            });
        });
    </script>
</body>
</html> 