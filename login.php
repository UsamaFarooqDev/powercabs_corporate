<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Corporate Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="global.css" />
</head>
<body>
    <div class="container-fluid vh-100 g-0">
        <div class="row h-100 g-0">
            <div class="col-lg-6 d-flex flex-column justify-content-center px-5">
                <div class="mx-lg-5 px-lg-5">
                    <div class="mb-4">
                        <img src="assets/powercabs-logo-black.svg" alt="PowerCabs Logo" class="mb-3" />
                    </div>
                    <h2 class="mb-3" style="font-size: 36px; font-weight: 700">Corporate Sign in</h2>
                    <p class="mb-4 text-muted">Please login to continue to your corporate account.</p>

                    <form id="loginForm" method="POST" novalidate>
                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="yourcompany@example.com" required />
                        </div>

                        <div class="form-group position-relative mb-3">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required style="padding-right: 40px;" />
                            <span onclick="togglePasswordVisibility('password')" style="position: absolute; right: 12px; top: 70%; transform: translateY(-50%); cursor: pointer; z-index: 10; color: #6c757d;">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>

                        <div class="mb-3 text-end">
                            <a href="forgot-password.php" style="font-weight: 400; color:#f37a20; text-decoration:none;">Forgot Password?</a>
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

    <!-- Toast Container -->
    <div id="toastContainer" aria-live="polite" aria-atomic="true"
         style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showToast(message, type) {
      type = type || 'error';
      const bg = type === 'success' ? '#28a745' : '#dc3545';
      const icon = type === 'success'
        ? '<i class="bi bi-check-circle-fill me-2"></i>'
        : '<i class="bi bi-x-circle-fill me-2"></i>';
      const id = 'toast-' + Date.now();
      const html = `
        <div id="${id}" class="toast align-items-center border-0 mb-2 show"
             role="alert" aria-live="assertive" aria-atomic="true"
             style="background:${bg}; color:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
          <div class="d-flex">
            <div class="toast-body d-flex align-items-center" style="font-size:14px; font-weight:500;">
              ${icon}${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>`;
      const container = document.getElementById('toastContainer');
      container.insertAdjacentHTML('beforeend', html);
      const toastEl = document.getElementById(id);
      const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
      bsToast.show();
      toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
    </script>
    <script>
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

        (async function checkSession() {
            try {
                const res = await fetch('auth/session.php');
                const json = await res.json();
                if (json.loggedIn) {
                    window.location.href = 'home.php';
                }
            } catch (err) {
                console.error(err);
            }
        })();

        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const resp = await fetch('auth/login.php', { method: 'POST', body: formData });
                const json = await resp.json();
                if (json.success) {
                    window.location.href = 'home.php';
                } else {
                    showToast(json.message || 'Invalid credentials.', 'error');
                }
            } catch (err) {
                showToast('Network error, please try again.', 'error');
            }
        });
    </script>
</body>
</html>

