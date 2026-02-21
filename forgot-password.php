<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - PowerCabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="global.css" />
    <style>
        .back-to-login {
            color: #f37a20;
            text-decoration: none;
            font-size: 14px;
        }
        .back-to-login:hover {
            text-decoration: underline;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
        }
        .step.active {
            background-color: #f37a20;
            color: white;
        }
        .step.completed {
            background-color: #28a745;
            color: white;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background-color: #e9ecef;
            margin: auto 0;
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

                    <!-- Step Indicator -->
                    <div class="step-indicator mb-4">
                        <div class="step active" id="step1">1</div>
                        <div class="step-line"></div>
                        <div class="step" id="step2">2</div>
                        <div class="step-line"></div>
                        <div class="step" id="step3">3</div>
                    </div>

                    <!-- Error/Success Messages -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Step 1: Email Form -->
                    <div id="step1-form">
                        <h2 class="mb-3" style="font-size: 32px; font-weight: 700">Forgot Password?</h2>
                        <p class="mb-4 text-muted">Enter your email address and we'll send you an OTP to reset your password.</p>

                        <form id="emailForm" method="POST" action="php/send-otp.php">
                            <div class="form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Johndoe@gmail.com" required />
                            </div>
                            <button type="submit" class="btn w-100 mb-3" style="background-color: #f37a20; color: #fff">Send OTP</button>
                            <div class="text-center">
                                <a href="index.php" class="back-to-login"><i class="fas fa-arrow-left"></i> Back to Login</a>
                            </div>
                        </form>
                    </div>

<!-- Step 2: OTP Verification Form -->
<div id="step2-form" style="display: none;">
    <h2 class="mb-3" style="font-size: 32px; font-weight: 700">Verify OTP</h2>
    <p class="mb-4 text-muted">Enter the 6-digit OTP sent to your email.</p>
    
    <!-- Display OTP from session for testing -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <form id="otpForm" method="POST" action="php/verify-otp.php">
        <div class="form-group mb-3">
            <label for="otp">Enter OTP</label>
            <input 
                type="text" 
                name="otp" 
                id="otp" 
                class="form-control" 
                placeholder="******" 
                maxlength="6" 
                pattern="\d{6}" 
                title="Please enter exactly 6 digits"
                required 
            />
        </div>
        <input type="hidden" name="email" id="otp_email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" />
        <button type="submit" class="btn w-100 mb-3" style="background-color: #f37a20; color: #fff">Verify OTP</button>
        <div class="text-center">
            <a href="#" onclick="resendOTP()" class="back-to-login">Resend OTP</a>
        </div>
    </form>
</div>

                    <!-- Step 3: Reset Password Form (Initially Hidden) -->
                    <div id="step3-form" style="display: none;">
                        <h2 class="mb-3" style="font-size: 32px; font-weight: 700">Reset Password</h2>
                        <p class="mb-4 text-muted">Enter your new password.</p>

                        <form id="resetPasswordForm" method="POST" action="php/reset-password.php">
                            <div class="form-group mb-3 position-relative">
                                <label for="new_password">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required />
                                <span class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                            </div>
                            <div class="form-group mb-3 position-relative">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required />
                                <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                            </div>
                            <input type="hidden" name="email" id="reset_email" value="" />
                            <button type="submit" class="btn w-100 mb-3" style="background-color: #f37a20; color: #fff">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 d-none d-lg-block position-relative h-100 overflow-hidden">
                <div class="h-100 w-100">
                    <img src="assets/right-column.jpg" alt="Forgot Password Visual" class="login-img" style="object-fit: cover; width: 100%; height: 100%;" />
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

        // Function to switch to OTP step
        function showOTPStep(email) {
            document.getElementById('step1-form').style.display = 'none';
            document.getElementById('step2-form').style.display = 'block';
            document.getElementById('step3-form').style.display = 'none';
            
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
            
            document.getElementById('otp_email').value = email;
        }

        // Function to switch to Reset Password step
        function showResetStep(email) {
            document.getElementById('step1-form').style.display = 'none';
            document.getElementById('step2-form').style.display = 'none';
            document.getElementById('step3-form').style.display = 'block';
            
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step3').classList.add('active');
            
            document.getElementById('reset_email').value = email;
        }

        // Resend OTP function
        function resendOTP() {
            const email = document.getElementById('otp_email').value;
            
            fetch('php/resend-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('OTP resent successfully!');
                } else {
                    alert('Failed to resend OTP. Please try again.');
                }
            });
        }

        // Check URL parameters to determine which step to show
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step');
            const email = urlParams.get('email');

            if (step === 'otp' && email) {
                showOTPStep(email);
            } else if (step === 'reset' && email) {
                showResetStep(email);
            }
        });
    </script>
</body>
</html>