<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a2e;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            position: relative;
            overflow: hidden;
        }

        /* Animated background circles */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.06;
            background: #FFD700;
        }
        body::before {
            width: 600px;
            height: 600px;
            top: -200px;
            right: -150px;
            animation: float 8s ease-in-out infinite;
        }
        body::after {
            width: 400px;
            height: 400px;
            bottom: -100px;
            left: -100px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            background: #FFD700;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: #fff;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15));
        }

        .login-header h4 {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            font-size: 0.85rem;
            color: #333;
            opacity: 0.75;
            margin: 0;
        }

        .login-body {
            padding: 2rem 2.5rem 2.5rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: #444;
            margin-bottom: 0.4rem;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .input-group-custom .form-control:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.15);
            background: #fff;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1.1rem;
            z-index: 4;
            transition: color 0.3s;
        }

        .input-group-custom .form-control:focus ~ .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: #C7A600;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            font-size: 1.1rem;
            z-index: 4;
            transition: color 0.2s;
        }
        .password-toggle:hover { color: #333; }

        .btn-login {
            background: linear-gradient(135deg, #FFD700 0%, #E6C300 100%);
            border: none;
            color: #1a1a2e;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.85rem;
            border-radius: 12px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #E6C300 0%, #C7A600 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
            color: #1a1a2e;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-link {
            color: #888;
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: #C7A600; }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f0f0f0;
        }
        .login-footer small {
            color: #aaa;
            font-size: 0.8rem;
        }

        .alert {
            border: none;
            border-radius: 10px;
            font-size: 0.88rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container { padding: 1rem; }
            .login-body { padding: 1.5rem 1.5rem 2rem; }
            .login-header { padding: 2rem 1.5rem 1.5rem; }
            .login-header img { width: 65px; height: 65px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM">
                <h4>Sistem Surat Menyurat</h4>
                <p>Fakultas Pertanian - Universitas Lambung Mangkurat</p>
            </div>
            <div class="login-body">
                
                <?php 
                if (isset($_GET['error'])) {
                    if ($_GET['error'] == 'access_denied') {
                        echo '<div class="alert alert-danger"><i class="bi bi-shield-exclamation me-2"></i>Akses ditolak. Silakan login terlebih dahulu.</div>';
                    } else {
                        echo '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Username atau password salah!</div>';
                    }
                }
                if (isset($_GET['status']) && $_GET['status'] == 'logout_success') {
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Anda telah berhasil logout.</div>';
                }
                if (isset($_GET['status']) && $_GET['status'] == 'register_success') {
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Registrasi berhasil! Silakan login.</div>';
                }
                ?>

                <form action="proses_login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username / NIM</label>
                        <div class="input-group-custom">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan NIM atau Username" required autocomplete="username">
                            <i class="bi bi-person input-icon"></i>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group-custom">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" style="padding-right: 2.8rem;">
                            <i class="bi bi-lock input-icon"></i>
                            <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="text-end mb-4">
                        <a href="#" class="forgot-link" data-bs-toggle="popover" data-bs-trigger="focus" title="Lupa Password?" data-bs-content="Silakan hubungi admin fakultas untuk reset password.">
                            <i class="bi bi-question-circle me-1"></i>Lupa Password?
                        </a>
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    </button>
                </form>

                <div class="login-footer">
                    <small>&copy; <?= date('Y'); ?> Fakultas Pertanian - Universitas Lambung Mangkurat</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pw = document.getElementById('password');
            const type = pw.getAttribute('type') === 'password' ? 'text' : 'password';
            pw.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Popover
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (el) { return new bootstrap.Popover(el); });
    </script>
</body>
</html>
