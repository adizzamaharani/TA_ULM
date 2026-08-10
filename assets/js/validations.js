/**
 * Global Validations & UX Enhancements
 * Sistem Surat ULM
 */

document.addEventListener('DOMContentLoaded', function() {

    // =============================================
    // 1. KONFIRMASI LOGOUT
    // =============================================
    document.querySelectorAll('a[href*="logout.php"]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var logoutUrl = this.href;

            // Cek apakah SweetAlert2 tersedia
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Yakin ingin logout?',
                    text: 'Anda akan keluar dari sistem.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-box-arrow-right"></i> Ya, Logout',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        window.location.href = logoutUrl;
                    }
                });
            } else {
                // Fallback ke confirm bawaan browser
                if (confirm('Yakin ingin logout dari sistem?')) {
                    window.location.href = logoutUrl;
                }
            }
        });
    });

    // =============================================
    // 2. KONFIRMASI SUBMIT FORM PENGAJUAN
    // =============================================
    document.querySelectorAll('form[action*="proses_ajukan.php"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formEl = this;

            // Validasi HTML5 dulu
            if (!formEl.checkValidity()) {
                formEl.reportValidity();
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Konfirmasi Pengajuan',
                    text: 'Pastikan semua data sudah benar. Ajukan surat ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#FFD700',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-send"></i> Ya, Ajukan!',
                    cancelButtonText: 'Cek Kembali',
                    reverseButtons: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        formEl.submit();
                    }
                });
            } else {
                if (confirm('Pastikan semua data sudah benar. Ajukan surat ini?')) {
                    formEl.submit();
                }
            }
        });
    });

    // =============================================
    // 3. VALIDASI TANGGAL (mulai < selesai)
    // =============================================
    var tglMulai = document.getElementById('tanggal_mulai');
    var tglSelesai = document.getElementById('tanggal_selesai');

    if (tglMulai && tglSelesai) {
        // Set minimum tanggal ke hari ini
        var today = new Date().toISOString().split('T')[0];
        tglMulai.setAttribute('min', today);

        tglMulai.addEventListener('change', function() {
            tglSelesai.setAttribute('min', this.value);
            if (tglSelesai.value && tglSelesai.value < this.value) {
                tglSelesai.value = '';
            }
        });

        tglSelesai.addEventListener('change', function() {
            if (tglMulai.value && this.value < tglMulai.value) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai!',
                        confirmButtonColor: '#FFD700'
                    });
                } else {
                    alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai!');
                }
                this.value = '';
            }
        });
    }

    // =============================================
    // 4. VALIDASI SEMESTER (1-14)
    // =============================================
    var semesterInput = document.getElementById('semester');
    if (semesterInput) {
        semesterInput.addEventListener('change', function() {
            var val = parseInt(this.value);
            if (val < 1 || val > 14 || isNaN(val)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Semester Tidak Valid',
                        text: 'Semester harus antara 1 sampai 14.',
                        confirmButtonColor: '#FFD700'
                    });
                }
                this.value = '';
            }
        });
    }

    // =============================================
    // 5. KONFIRMASI UPDATE STATUS (Admin)
    // =============================================
    document.querySelectorAll('form[action*="index.php"] button[type="submit"]').forEach(function(btn) {
        var form = btn.closest('form');
        if (form && form.querySelector('[name="status_baru"]')) {
            form.addEventListener('submit', function(e) {
                var statusSelect = this.querySelector('[name="status_baru"]');
                if (statusSelect) {
                    var newStatus = statusSelect.value;
                    if (newStatus === 'Ditolak') {
                        e.preventDefault();
                        var formEl = this;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Tolak Surat Ini?',
                                text: 'Surat yang ditolak tidak bisa dikembalikan. Lanjutkan?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Ya, Tolak',
                                cancelButtonText: 'Batal'
                            }).then(function(result) {
                                if (result.isConfirmed) formEl.submit();
                            });
                        } else {
                            if (confirm('Tolak surat ini?')) formEl.submit();
                        }
                    }
                }
            });
        }
    });

    // =============================================
    // 6. SIDEBAR MOBILE & DESKTOP TOGGLE
    // =============================================
    var sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        // Buat overlay jika belum ada
        var overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 576) {
                document.body.classList.toggle('sidebar-open');
                document.body.classList.remove('sidebar-toggled');
            } else {
                document.body.classList.toggle('sidebar-toggled');
                document.body.classList.remove('sidebar-open');
            }
        });

        overlay.addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
        });

        // Close sidebar saat link diklik di mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 576) {
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
    }

    // =============================================
    // 7. TEXTAREA CHAR COUNTER (keperluan)
    // =============================================
    var keperluanField = document.getElementById('keperluan');
    if (keperluanField) {
        keperluanField.setAttribute('maxlength', '500');
        var counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.id = 'keperluan-counter';
        counter.textContent = '0 / 500 karakter';
        keperluanField.parentNode.appendChild(counter);

        keperluanField.addEventListener('input', function() {
            var len = this.value.length;
            counter.textContent = len + ' / 500 karakter';
            counter.style.color = len > 450 ? '#dc3545' : '#6c757d';
        });
    }

    // =============================================
    // 8. PREVENT DOUBLE SUBMIT
    // =============================================
    document.querySelectorAll('form').forEach(function(form) {
        var submitted = false;
        form.addEventListener('submit', function() {
            if (submitted) {
                event.preventDefault();
                return;
            }
            submitted = true;
            // Disable tombol submit
            var btn = form.querySelector('button[type="submit"], .btn-submit');
            if (btn) {
                btn.disabled = true;
                var originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
                // Re-enable after 5 seconds (fallback)
                setTimeout(function() {
                    submitted = false;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }, 5000);
            }
        });
    });

    // =============================================
    // 9. AUTO-DISMISS ALERTS
    // =============================================
    document.querySelectorAll('.alert-success, .alert-info').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4000);
    });

});
