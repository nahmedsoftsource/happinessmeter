/**
 * Admin Panel JavaScript
 */

(function() {
    'use strict';

    // =============================================
    // Sidebar Toggle
    // =============================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Close sidebar on overlay click (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // =============================================
    // Alert Dismiss
    // =============================================
    document.querySelectorAll('.alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.parentElement.style.opacity = '0';
            setTimeout(() => {
                this.parentElement.remove();
            }, 300);
        });
    });

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentElement) {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    });

    // =============================================
    // File Upload Drag & Drop
    // =============================================
    document.querySelectorAll('.file-upload').forEach(function(upload) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            upload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            upload.addEventListener(eventName, () => {
                upload.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            upload.addEventListener(eventName, () => {
                upload.classList.remove('dragover');
            }, false);
        });

        upload.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const input = upload.querySelector('input[type="file"]');

            if (input && files.length) {
                input.files = files;
                // Trigger change event
                input.dispatchEvent(new Event('change'));
            }
        }, false);
    });

    // =============================================
    // Confirm Delete
    // =============================================
    document.querySelectorAll('[data-confirm]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // =============================================
    // Form Validation Feedback
    // =============================================
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            }
        });
    });

})();
