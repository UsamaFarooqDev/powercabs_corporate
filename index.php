<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="global.css" />
    <style>
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            color: #6c757d;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        .forgot-password a {
            color: #f37a20;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container-fluid vh-100 g-0">
        <div class="row h-100 g-0">
            <div class="col-lg-6 d-flex flex-column justify-content-center px-5">
                <div class="mx-lg-5 px-lg-5">
                    <div class="mb-4">
                        <img src="assets/powercabs-logo-black.svg" alt="PowerCabs Logo" class="mb-3" />
                    </div>
                    <h2 class="mb-3" style="font-size: 36px; font-weight: 700">Sign in</h2>
                    <p class="mb-4 text-muted">Please login to continue to your account.</p>

                    <!-- Toast Container for Success Messages -->
                    <div class="toast-container">
                        <?php if (isset($_SESSION['success'])): ?>
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                            <div class="toast-header bg-success text-white">
                                <strong class="me-auto">Success</strong>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="errorAlert" class="alert alert-danger d-none"></div>

                    <form id="loginForm" method="POST" action="php/login.php">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Johndoe@gmail.com" required />
                        </div>

<div class="form-group position-relative">
  <label for="password">Password</label>
  <input 
    type="password" 
    name="pass" 
    id="password" 
    class="form-control" 
    placeholder="Password" 
    required 
    style="padding-right: 40px;"
  />
  <span 
    onclick="togglePasswordVisibility('password')" 
    style="
      position: absolute;
      right: 12px;
      top: 70%;
      transform: translateY(-50%);
      cursor: pointer;
      z-index: 10;
      color: #6c757d;
    "
  >
    <i class="fas fa-eye-slash"></i>
  </span>
</div>

                        <div class="forgot-password mb-3">
                            <a href="forgot-password.php" style="font-weight: 400">Forgot Password?</a>
                        </div>

                        <input type="submit" class="btn w-100" value="Sign in" style="background-color: #f37a20; color: #fff" />
                    </form>
                </div>
            </div>

            <div class="col-lg-6 d-none d-lg-block position-relative h-100 overflow-hidden">
                <div class="h-100 w-100">
                    <img src="assets/right-column.jpg" alt="Login Visual" class="login-img" style="object-fit: cover; width: 100%; height: 100%;" />
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password visibility toggle function
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = event.currentTarget.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }

        // Initialize toasts
        document.addEventListener('DOMContentLoaded', function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                new bootstrap.Toast(toast).show();
            });
        });
    </script>
</body>
</html>