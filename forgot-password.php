<?php
session_start();

$step = $_GET['step'] ?? 'email';
$email = $_GET['email'] ?? ($_SESSION['reset_email'] ?? '');

// Only allow reset step if verified
if ($step === 'reset' && empty($_SESSION['reset_verified'])) {
  $step = 'email';
}

$errorMsg = $_SESSION['error']   ?? '';
$successMsg = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate — Forgot Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    :root {
      --orange:    #f37a20;
      --orange-dk: #e06910;
      --dark:      #0f1117;
    }

    html, body { height: 100%; font-family: 'Segoe UI', system-ui, sans-serif; }

    .login-shell {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
    }
    @media (max-width: 991px) {
      .login-shell { grid-template-columns: 1fr; }
      .login-panel-right { display: none !important; }
    }

    .login-input:focus {
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(243,122,32,.12);
    }

    .pw-toggle {
      position: absolute; right: .85rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; padding: 0;
      color: #6b7280; cursor: pointer; font-size: 1rem;
    }
    .pw-toggle:hover { color: #111827; }

    .btn-signin {
      background: var(--orange) !important; border: none;
      color: #fff !important;
      transition: background .15s, box-shadow .15s;
    }
    .btn-signin:hover,
    .btn-signin:focus,
    .btn-signin:active,
    .btn-signin:disabled {
      background: var(--orange-dk) !important;
      color: #fff !important;
      box-shadow: 0 4px 16px rgba(243,122,32,.3);
    }

    .login-panel-right {
      background: var(--dark);
      position: relative; overflow: hidden;
    }
    .login-panel-right::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(243,122,32,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(243,122,32,.04) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
    }

    .rp-glow {
      position: absolute; width: 520px; height: 520px; border-radius: 50%;
      background: radial-gradient(circle, rgba(243,122,32,.18) 0%, transparent 70%);
      top: -80px; right: -120px; pointer-events: none;
    }
    .rp-glow-2 {
      position: absolute; width: 320px; height: 320px; border-radius: 50%;
      background: radial-gradient(circle, rgba(243,122,32,.1) 0%, transparent 70%);
      bottom: 80px; left: -60px; pointer-events: none;
    }

    /* Step indicator */
    .step-indicator {
      display: flex; align-items: center; gap: .5rem;
      margin-bottom: 1.5rem;
    }
    .step-dot {
      width: 32px; height: 32px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      background: #f3f4f6; color: #9ca3af;
      font-size: .8rem; font-weight: 700;
      border: 2px solid transparent;
      transition: all .2s;
    }
    .step-dot.active {
      background: var(--orange); color: #fff;
      box-shadow: 0 0 0 4px rgba(243,122,32,.15);
    }
    .step-dot.done {
      background: #dcfce7; color: #16a34a;
    }
    .step-line {
      flex: 1; height: 2px; background: #e5e7eb;
      max-width: 40px;
    }
    .step-line.done { background: #16a34a; }

    /* OTP hero icon */
    .reset-hero {
      width: 60px; height: 60px; border-radius: 14px;
      background: #fff4eb;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 1.25rem;
    }
    .reset-hero i { color: var(--orange); font-size: 1.75rem; }

    #toastContainer {
      position: fixed; top: 20px; right: 20px;
      z-index: 9999; min-width: 300px;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .login-inner > * { animation: fadeUp .4s ease both; }
    .login-inner > *:nth-child(1) { animation-delay: .05s; }
    .login-inner > *:nth-child(2) { animation-delay: .10s; }
    .login-inner > *:nth-child(3) { animation-delay: .13s; }
    .login-inner > *:nth-child(4) { animation-delay: .16s; }
    .login-inner > *:nth-child(5) { animation-delay: .19s; }
    .login-inner > *:nth-child(6) { animation-delay: .22s; }
    .login-inner > *:nth-child(7) { animation-delay: .25s; }

    /* OTP input boxes */
    .otp-input {
      text-align: center;
      letter-spacing: .5rem;
      font-size: 1.4rem !important;
      font-weight: 700;
      font-family: 'SF Mono', Consolas, monospace;
    }
  </style>
</head>
<body>

<div class="login-shell">

  <!-- LEFT: Form -->
  <div class="d-flex flex-column justify-content-center bg-white px-4 px-lg-5 ps-lg-5 ms-lg-4">
    <div class="login-inner" style="max-width:420px; width:100%;">

      <!-- Logo -->
      <img src="assets/powercabs-logo-black.svg" alt="PowerCabs" class="mb-4" style="height:62px;"/>

      <!-- Step indicator -->
      <div class="step-indicator">
        <div class="step-dot <?= $step === 'email' ? 'active' : 'done' ?>">
          <?= $step === 'email' ? '1' : '<i class="bi bi-check-lg"></i>' ?>
        </div>
        <div class="step-line <?= in_array($step, ['otp','reset']) ? 'done' : '' ?>"></div>
        <div class="step-dot <?= $step === 'otp' ? 'active' : ($step === 'reset' ? 'done' : '') ?>">
          <?= $step === 'reset' ? '<i class="bi bi-check-lg"></i>' : '2' ?>
        </div>
        <div class="step-line <?= $step === 'reset' ? 'done' : '' ?>"></div>
        <div class="step-dot <?= $step === 'reset' ? 'active' : '' ?>">3</div>
      </div>

      <?php if ($step === 'email'): ?>
        <!-- ── STEP 1: Email ── -->
        <h1 class="fw-bold mb-2" style="font-size:2rem; color:#111827; line-height:1.2;">Forgot password?</h1>
        <p class="text-secondary mb-4" style="font-size:.95rem; line-height:1.6;">Enter the email linked to your corporate account and we'll send you a verification code.</p>

        <form action="php/send-otp.php" method="POST" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">Email Address</label>
            <input type="email" name="email" id="email"
                   class="form-control login-input py-2 px-3"
                   style="font-size:.95rem; border-radius:8px;"
                   placeholder="yourcompany@example.com" required autofocus
                   value="<?= htmlspecialchars($email) ?>"/>
          </div>

          <button type="submit" id="btnSubmit" class="btn btn-signin w-100 text-white fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mt-3" style="font-size:.95rem; border-radius:8px;">
            Send verification code <i style="font-size:.75rem;" class="bi bi-chevron-right"></i>
          </button>
        </form>

      <?php elseif ($step === 'otp'): ?>
        <!-- ── STEP 2: OTP ── -->
        <h1 class="fw-bold mb-2" style="font-size:2rem; color:#111827; line-height:1.2;">Enter verification code</h1>
        <p class="text-secondary mb-4" style="font-size:.95rem; line-height:1.6;">
          We sent a 6-digit code to <span class="fw-semibold text-dark"><?= htmlspecialchars($email) ?></span>. Enter it below to continue.
        </p>

        <form action="php/verify-otp.php" method="POST" novalidate>
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>"/>

          <div class="mb-3">
            <label for="otp" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">Verification Code</label>
            <input type="text" name="otp" id="otp"
                   class="form-control login-input otp-input py-2 px-3"
                   style="border-radius:8px;"
                   placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"/>
          </div>

          <button type="submit" id="btnSubmit" class="btn btn-signin w-100 text-white fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mt-3" style="font-size:.95rem; border-radius:8px;">
            Verify code <i style="font-size:.75rem;" class="bi bi-chevron-right"></i>
          </button>
        </form>

        <div class="text-center mt-3 d-flex flex-column gap-2 align-items-center">
          <form action="php/resend-otp.php" method="POST" class="d-inline" id="resendOtpForm">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>"/>
            <button type="submit" id="btnResendOtp" class="btn btn-link text-decoration-none p-0 border-0" style="font-size:.88rem; color:var(--orange);">
              <i class="bi bi-arrow-clockwise me-1"></i><span id="resendOtpLabel">Resend code</span>
            </button>
          </form>
          <a href="forgot-password.php" class="text-decoration-none" style="font-size:.88rem; color:#6b7280;">
            <i class="bi bi-arrow-left me-1"></i>Use a different email
          </a>
        </div>

      <?php else: ?>
        <!-- ── STEP 3: Reset password ── -->
        <h1 class="fw-bold mb-2" style="font-size:2rem; color:#111827; line-height:1.2;">Set new password</h1>
        <p class="text-secondary mb-4" style="font-size:.95rem; line-height:1.6;">Create a strong password for your corporate account.</p>

        <form action="php/reset-password.php" method="POST" novalidate id="resetForm">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>"/>

          <div class="mb-3">
            <label for="new_password" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">New Password</label>
            <div class="position-relative">
              <input type="password" name="new_password" id="new_password"
                     class="form-control login-input py-2 px-3 pe-5"
                     style="font-size:.95rem; border-radius:8px;"
                     placeholder="Enter new password" required minlength="6" autofocus/>
              <button type="button" class="pw-toggle" data-target="new_password" aria-label="Toggle password">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">Confirm Password</label>
            <div class="position-relative">
              <input type="password" name="confirm_password" id="confirm_password"
                     class="form-control login-input py-2 px-3 pe-5"
                     style="font-size:.95rem; border-radius:8px;"
                     placeholder="Re-enter new password" required minlength="6"/>
              <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Toggle password">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </div>

          <button type="submit" id="btnSubmit" class="btn btn-signin w-100 text-white fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mt-3" style="font-size:.95rem; border-radius:8px;">
            Reset password <i style="font-size:.75rem;" class="bi bi-chevron-right"></i>
          </button>
        </form>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="login.php" class="text-decoration-none fw-medium" style="font-size:.88rem; color:var(--orange);">
          <i class="bi bi-arrow-left me-1"></i>Back to sign in
        </a>
      </div>

      <div class="text-center text-secondary mt-4 pt-3 border-top" style="font-size:.8rem;">
        &copy; <?= date('Y') ?> PowerCabs. Corporate use only.
      </div>

    </div>
  </div>

  <!-- RIGHT: Illustration (same as login) -->
  <div class="login-panel-right d-flex flex-column justify-content-center align-items-center p-4">
    <div class="rp-glow"></div>
    <div class="rp-glow-2"></div>

    <div class="position-relative text-center" style="z-index:2; max-width:380px;">
      <div class="reset-hero mx-auto">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      <div class="d-inline-flex align-items-center gap-2 rounded-pill px-3 py-1 mb-3 fw-semibold text-uppercase"
           style="font-size:.78rem; letter-spacing:.06em; color:var(--orange); background:rgba(243,122,32,.15); border:1px solid rgba(243,122,32,.25);">
        <i class="bi bi-lock-fill"></i> Secure Recovery
      </div>
      <h2 class="fw-bold text-white mb-3" style="font-size:1.8rem; line-height:1.3;">Your security,<br/>our priority.</h2>
      <p class="mb-0" style="font-size:.95rem; color:rgba(255,255,255,.55); line-height:1.6;">
        We use time-limited verification codes to protect your corporate account. Your password is encrypted and never shared.
      </p>

      <div class="mt-5 d-flex flex-column gap-3">
        <div class="d-flex align-items-start gap-3 text-start">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px; background:rgba(243,122,32,.15);">
            <i class="bi bi-envelope-check-fill" style="color:var(--orange); font-size:1rem;"></i>
          </div>
          <div>
            <div class="text-white fw-semibold" style="font-size:.9rem;">Email verification</div>
            <div style="font-size:.82rem; color:rgba(255,255,255,.45);">6-digit code valid for 10 minutes</div>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 text-start">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px; background:rgba(243,122,32,.15);">
            <i class="bi bi-shield-check" style="color:var(--orange); font-size:1rem;"></i>
          </div>
          <div>
            <div class="text-white fw-semibold" style="font-size:.9rem;">Encrypted storage</div>
            <div style="font-size:.82rem; color:rgba(255,255,255,.45);">Passwords hashed with bcrypt</div>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 text-start">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px; background:rgba(243,122,32,.15);">
            <i class="bi bi-clock-history" style="color:var(--orange); font-size:1rem;"></i>
          </div>
          <div>
            <div class="text-white fw-semibold" style="font-size:.9rem;">Audit trail</div>
            <div style="font-size:.82rem; color:rgba(255,255,255,.45);">All reset attempts are logged</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  /* Toast */
  function showToast(message, type) {
    type = type || 'error';
    const bg   = type === 'success' ? '#16a34a' : '#dc2626';
    const icon = type === 'success'
      ? '<i class="bi bi-check-circle-fill me-2"></i>'
      : '<i class="bi bi-x-circle-fill me-2"></i>';
    const id   = 'toast-' + Date.now();
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', `
      <div id="${id}" class="toast align-items-center border-0 mb-2 show"
           role="alert" style="background:${bg}; color:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.15);">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center" style="font-size:.88rem; font-weight:500;">
            ${icon}${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 6000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  /* Show PHP session messages */
  <?php if ($errorMsg): ?>
    showToast(<?= json_encode(strip_tags($errorMsg)) ?>, 'error');
  <?php endif; ?>
  <?php if ($successMsg): ?>
    showToast(<?= json_encode(strip_tags($successMsg)) ?>, 'success');
  <?php endif; ?>

  /* Password visibility toggles */
  document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target || 'password');
      const icon  = btn.querySelector('i');
      const show  = input.type === 'password';
      input.type  = show ? 'text' : 'password';
      icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  });

  /* OTP input: only digits */
  const otpInput = document.getElementById('otp');
  if (otpInput) {
    otpInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '');
    });
  }

  /* Resend OTP: cooldown (45s) after each successful resend */
  <?php if ($step === 'otp' && $email !== ''): ?>
  (function () {
    const btn = document.getElementById('btnResendOtp');
    const label = document.getElementById('resendOtpLabel');
    if (!btn || !label) return;
    const email = <?= json_encode($email) ?>;
    const key = 'resendOtpUntil:' + email;
    const COOLDOWN_MS = 45000;
    const url = new URL(window.location.href);
    if (url.searchParams.get('rc') === '1') {
      sessionStorage.setItem(key, String(Date.now() + COOLDOWN_MS));
      url.searchParams.delete('rc');
      history.replaceState({}, '', url.pathname + url.search + url.hash);
    }
    function tick() {
      const until = parseInt(sessionStorage.getItem(key) || '0', 10);
      const left = until - Date.now();
      if (left <= 0) {
        btn.disabled = false;
        label.textContent = 'Resend code';
        return;
      }
      btn.disabled = true;
      label.textContent = 'Resend in ' + Math.ceil(left / 1000) + 's';
      setTimeout(tick, 400);
    }
    tick();
  })();
  <?php endif; ?>

  /* Reset password: match check */
  const resetForm = document.getElementById('resetForm');
  if (resetForm) {
    resetForm.addEventListener('submit', (e) => {
      const np = document.getElementById('new_password').value;
      const cp = document.getElementById('confirm_password').value;
      if (np !== cp) {
        e.preventDefault();
        showToast('Passwords do not match.', 'error');
        return;
      }
      if (np.length < 6) {
        e.preventDefault();
        showToast('Password must be at least 6 characters.', 'error');
        return;
      }
    });
  }

  /* Submit button loader */
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
      const btn = form.querySelector('button[type="submit"]');
      if (!btn || btn.disabled) return;
      if (form.id === 'resendOtpForm') {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
        return;
      }
      // Let native validation stop it if invalid
      if (!form.checkValidity()) return;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait…';
    });
  });
</script>
</body>
</html>
