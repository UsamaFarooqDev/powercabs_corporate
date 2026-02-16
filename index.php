<?php 
session_start();

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="global.css" />
  </head>
  <body>
    <div class="container-fluid vh-100 g-0">
      <div class="row h-100 g-0">
        <div class="col-lg-6 d-flex flex-column justify-content-center px-5">
          <div class="mx-lg-5 px-lg-5">
            <div class="mb-4">
              <img
                src="assets/powercabs-logo-black.svg"
                alt="PowerCabs Logo"
                class="mb-3"
              />
            </div>
            <h2 class="mb-3" style="font-size: 42px; font-weight: 700">
              Sign in
            </h2>
            <p class="mb-4 text-muted">
              Please login to continue to your account.
            </p>

            <div id="errorAlert" class="alert alert-danger d-none"></div>

            <form id="loginForm" method="POST" action="php/login.php">
            <?php

if (isset($_SESSION['error'])) {
    echo "<p style='color: red;'>" . htmlspecialchars($_SESSION['error']) . "</p>";
    unset($_SESSION['error']); // Clear the error message after displaying it
}
?>
              <div class="form-group mb-3">
                <label for="email">Email</label>
                <input
                  type="email"
                  name="email"
                  class="form-control"
                  placeholder="Johndoe@gmail.com"
                  required
                />
              </div>
              <div class="form-group mb-3 position-relative">
                <label for="password">Password</label>
                <input
                  type="password"
                  name="pass"
                  class="form-control"
                  placeholder="Password"
                  required
                />
                <span
                  class="password-toggle"
                  onclick="togglePasswordVisibility()"
                >
                  <i class="fas fa-eye-slash"></i>
                </span>
              </div>
              <input
                type="submit"
              
                class="btn w-100"
                value="Sign in"
                style="background-color: #f37a20; color: #fff"
              />
             
             
            </form>
          </div>
        </div>

        <div
          class="col-lg-6 d-none d-lg-block position-relative h-100 overflow-hidden"
        >
          <div class="h-100 w-100">
            <img
              src="assets/right-column.jpg"
              alt="Login Visual"
              class="login-img"
            />
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.password-toggle i');

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

    

     
    </script>
  </body>
</html>
