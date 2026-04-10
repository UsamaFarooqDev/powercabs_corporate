<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate — Sign In</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

  <style>
    :root {
      --orange:    #f37a20;
      --orange-dk: #e06910;
      --dark:      #0f1117;
      --dark-2:    #181c25;
      --dark-3:    #232837;
    }

    html, body { height: 100%; font-family: 'Segoe UI', system-ui, sans-serif; }

    /* Two-column layout */
    .login-shell {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
    }
    @media (max-width: 991px) {
      .login-shell { grid-template-columns: 1fr; }
      .login-panel-right { display: none !important; }
    }

    /* Form input focus */
    .login-input:focus {
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(243,122,32,.12);
    }

    /* Password toggle */
    .pw-toggle {
      position: absolute; right: .85rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; padding: 0;
      color: #6b7280; cursor: pointer; font-size: 1rem;
    }
    .pw-toggle:hover { color: #111827; }

    /* Submit button */
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

    /* Right panel */
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

    .rp-illustration {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
    }
    .rp-illustration svg { width: 88%; max-width: 520px; }

    /* Toast */
    #toastContainer {
      position: fixed; top: 20px; right: 20px;
      z-index: 9999; min-width: 300px;
    }

    /* Fade-in */
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
  </style>
</head>
<body>

<div class="login-shell">

  <!-- ───────────── LEFT: Form ───────────── -->
  <div class="d-flex flex-column justify-content-center bg-white px-4 px-lg-5 ps-lg-5 ms-lg-4">
    <div class="login-inner" style="max-width:420px; width:100%;">

      <!-- Logo -->
      <img src="assets/powercabs-logo-black.svg" alt="PowerCabs" class="mb-4" style="height:62px;"/>

      <!-- Heading -->
      <!-- <div class="fw-semibold text-uppercase mb-2" style="font-size:.8rem; letter-spacing:.1em; color:var(--orange);">Corporate Portal</div> -->
      <h1 class="fw-bold mb-2" style="font-size:2rem; color:#111827; line-height:1.2;">Welcome back</h1>
      <p class="text-secondary mb-4" style="font-size:.95rem; line-height:1.6;">Sign in to manage your fleet, rides, and employees.</p>

      <!-- Form -->
      <form id="loginForm" method="POST" novalidate>

        <div class="mb-3">
          <label for="email" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">Email Address</label>
          <input type="email" name="email" id="email"
                 class="form-control login-input py-2 px-3"
                 style="font-size:.95rem; border-radius:8px;"
                 placeholder="yourcompany@example.com" required/>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label fw-semibold text-uppercase" style="font-size:.8rem; letter-spacing:.05em; color:#374151;">Password</label>
          <div class="position-relative">
            <input type="password" name="password" id="password"
                   class="form-control login-input py-2 px-3 pe-5"
                   style="font-size:.95rem; border-radius:8px;"
                   placeholder="Enter your password" required/>
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password">
              <i class="bi bi-eye-slash" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" id="btnSignIn" class="btn btn-signin w-100 text-white fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mt-3" style="font-size:.95rem; border-radius:8px;">
          Sign In <i style="font-size:.75rem;" class="bi bi-chevron-right"></i>
        </button>

      </form>

      <div class="text-center text-secondary mt-4 pt-3 border-top" style="font-size:.8rem;">
        &copy; <?= date('Y') ?> PowerCabs. Corporate use only.
      </div>

    </div>
  </div>

  <!-- ───────────── RIGHT: Illustration ───────────── -->
  <div class="login-panel-right d-flex flex-column justify-content-end p-4">
    <div class="rp-glow"></div>
    <div class="rp-glow-2"></div>

    <div class="rp-illustration">
      <svg viewBox="0 0 520 480" fill="none" xmlns="http://www.w3.org/2000/svg">

        <!-- Road grid -->
        <rect x="0" y="300" width="520" height="6" rx="3" fill="#1e2433"/>
        <rect x="0" y="360" width="520" height="4" rx="2" fill="#1a1f2c"/>
        <rect x="180" y="200" width="6" height="280" rx="3" fill="#1e2433"/>
        <rect x="340" y="200" width="6" height="280" rx="3" fill="#1a1f2c"/>

        <!-- Road dashes -->
        <rect x="50"  y="301" width="40" height="4" rx="2" fill="#2a3040"/>
        <rect x="130" y="301" width="40" height="4" rx="2" fill="#2a3040"/>
        <rect x="230" y="301" width="40" height="4" rx="2" fill="#2a3040"/>
        <rect x="310" y="301" width="40" height="4" rx="2" fill="#2a3040"/>
        <rect x="400" y="301" width="40" height="4" rx="2" fill="#2a3040"/>
        <rect x="460" y="301" width="40" height="4" rx="2" fill="#2a3040"/>

        <!-- Buildings -->
        <rect x="20"  y="190" width="60" height="110" rx="4" fill="#181c25"/>
        <rect x="20"  y="190" width="60" height="110" rx="4" stroke="#232837" stroke-width="1"/>
        <rect x="30" y="202" width="12" height="10" rx="2" fill="#f37a20" opacity=".7"/>
        <rect x="50" y="202" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="30" y="222" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="50" y="222" width="12" height="10" rx="2" fill="#f37a20" opacity=".5"/>
        <rect x="30" y="242" width="12" height="10" rx="2" fill="#f37a20" opacity=".3"/>
        <rect x="50" y="242" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="30" y="262" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="50" y="262" width="12" height="10" rx="2" fill="#f37a20" opacity=".6"/>

        <!-- Tall tower -->
        <rect x="90"  y="120" width="80" height="180" rx="4" fill="#1a1f2c"/>
        <rect x="90"  y="120" width="80" height="180" rx="4" stroke="#232837" stroke-width="1"/>
        <rect x="128" y="100" width="4" height="22" rx="2" fill="#232837"/>
        <circle cx="130" cy="98" r="4" fill="#f37a20" opacity=".8"/>
        <?php
          $wx = [100, 118, 136, 154];
          $wy = [132, 152, 172, 192, 212, 232, 252];
          foreach ($wy as $y) {
            foreach ($wx as $x) {
              $op = (rand(0,1)) ? '.6' : '.15';
              echo "<rect x='$x' y='$y' width='10' height='8' rx='1' fill='#f37a20' opacity='$op'/>";
            }
          }
        ?>

        <!-- Wide low block -->
        <rect x="200" y="220" width="130" height="80" rx="4" fill="#181c25"/>
        <rect x="200" y="220" width="130" height="80" rx="4" stroke="#232837" stroke-width="1"/>
        <rect x="212" y="232" width="14" height="12" rx="2" fill="#f37a20" opacity=".55"/>
        <rect x="234" y="232" width="14" height="12" rx="2" fill="#2a3040"/>
        <rect x="256" y="232" width="14" height="12" rx="2" fill="#f37a20" opacity=".4"/>
        <rect x="278" y="232" width="14" height="12" rx="2" fill="#2a3040"/>
        <rect x="300" y="232" width="14" height="12" rx="2" fill="#f37a20" opacity=".7"/>
        <rect x="212" y="254" width="14" height="12" rx="2" fill="#2a3040"/>
        <rect x="234" y="254" width="14" height="12" rx="2" fill="#f37a20" opacity=".4"/>
        <rect x="256" y="254" width="14" height="12" rx="2" fill="#2a3040"/>
        <rect x="278" y="254" width="14" height="12" rx="2" fill="#f37a20" opacity=".35"/>
        <rect x="300" y="254" width="14" height="12" rx="2" fill="#2a3040"/>

        <!-- Block D -->
        <rect x="360" y="160" width="70" height="140" rx="4" fill="#1a1f2c"/>
        <rect x="360" y="160" width="70" height="140" rx="4" stroke="#232837" stroke-width="1"/>
        <rect x="370" y="172" width="12" height="10" rx="2" fill="#f37a20" opacity=".65"/>
        <rect x="390" y="172" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="410" y="172" width="12" height="10" rx="2" fill="#f37a20" opacity=".3"/>
        <rect x="370" y="192" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="390" y="192" width="12" height="10" rx="2" fill="#f37a20" opacity=".5"/>
        <rect x="410" y="192" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="370" y="212" width="12" height="10" rx="2" fill="#f37a20" opacity=".4"/>
        <rect x="390" y="212" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="410" y="212" width="12" height="10" rx="2" fill="#f37a20" opacity=".7"/>
        <rect x="370" y="232" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="390" y="232" width="12" height="10" rx="2" fill="#f37a20" opacity=".3"/>
        <rect x="410" y="232" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="370" y="252" width="12" height="10" rx="2" fill="#f37a20" opacity=".55"/>
        <rect x="390" y="252" width="12" height="10" rx="2" fill="#2a3040"/>
        <rect x="410" y="252" width="12" height="10" rx="2" fill="#f37a20" opacity=".4"/>

        <!-- Block E -->
        <rect x="445" y="200" width="55" height="100" rx="4" fill="#181c25"/>
        <rect x="455" y="212" width="10" height="8" rx="1" fill="#f37a20" opacity=".5"/>
        <rect x="474" y="212" width="10" height="8" rx="1" fill="#2a3040"/>
        <rect x="455" y="230" width="10" height="8" rx="1" fill="#2a3040"/>
        <rect x="474" y="230" width="10" height="8" rx="1" fill="#f37a20" opacity=".65"/>
        <rect x="455" y="248" width="10" height="8" rx="1" fill="#f37a20" opacity=".35"/>
        <rect x="474" y="248" width="10" height="8" rx="1" fill="#2a3040"/>
        <rect x="455" y="266" width="10" height="8" rx="1" fill="#2a3040"/>
        <rect x="474" y="266" width="10" height="8" rx="1" fill="#f37a20" opacity=".5"/>

        <!-- Car 1 -->
        <g transform="translate(58, 312)">
          <rect x="0" y="4" width="72" height="22" rx="5" fill="#f37a20"/>
          <rect x="8" y="0" width="52" height="18" rx="4" fill="#e06910"/>
          <rect x="12" y="3" width="16" height="10" rx="2" fill="rgba(255,255,255,.18)"/>
          <rect x="34" y="3" width="16" height="10" rx="2" fill="rgba(255,255,255,.18)"/>
          <circle cx="14" cy="26" r="7" fill="#0f1117"/>
          <circle cx="14" cy="26" r="3" fill="#2a3040"/>
          <circle cx="58" cy="26" r="7" fill="#0f1117"/>
          <circle cx="58" cy="26" r="3" fill="#2a3040"/>
          <rect x="64" y="10" width="8" height="5" rx="2" fill="#fff" opacity=".7"/>
          <rect x="0" y="10" width="6" height="5" rx="2" fill="#ff4500" opacity=".6"/>
        </g>

        <!-- Car 2 -->
        <g transform="translate(310, 314) scale(-1,1) translate(-72, 0)">
          <rect x="0" y="4" width="72" height="22" rx="5" fill="#232837"/>
          <rect x="8" y="0" width="52" height="18" rx="4" fill="#1a1f2c"/>
          <rect x="12" y="3" width="16" height="10" rx="2" fill="rgba(243,122,32,.2)"/>
          <rect x="34" y="3" width="16" height="10" rx="2" fill="rgba(243,122,32,.2)"/>
          <circle cx="14" cy="26" r="7" fill="#0f1117"/>
          <circle cx="14" cy="26" r="3" fill="#2a3040"/>
          <circle cx="58" cy="26" r="7" fill="#0f1117"/>
          <circle cx="58" cy="26" r="3" fill="#2a3040"/>
          <rect x="64" y="10" width="8" height="5" rx="2" fill="#f37a20" opacity=".8"/>
        </g>

        <!-- Car 3 top-down -->
        <g transform="translate(186, 220)">
          <rect x="0" y="0" width="18" height="32" rx="4" fill="#f37a20"/>
          <rect x="3" y="3" width="12" height="10" rx="2" fill="rgba(255,255,255,.15)"/>
          <circle cx="4"  cy="36" r="5" fill="#0f1117"/>
          <circle cx="14" cy="36" r="5" fill="#0f1117"/>
          <circle cx="4"  cy="0"  r="4" fill="#0f1117"/>
          <circle cx="14" cy="0"  r="4" fill="#0f1117"/>
        </g>

        <!-- Location pin -->
        <g transform="translate(118, 56)">
          <circle cx="12" cy="12" r="14" fill="#f37a20" opacity=".15"/>
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#f37a20"/>
          <circle cx="12" cy="9" r="18" stroke="#f37a20" stroke-width="1.5" opacity=".2">
            <animate attributeName="r" values="14;22;14" dur="2.4s" repeatCount="indefinite"/>
            <animate attributeName="opacity" values=".3;0;.3" dur="2.4s" repeatCount="indefinite"/>
          </circle>
        </g>

        <!-- Route dashed line -->
        <path d="M130 80 C160 80, 160 200, 205 220"
              stroke="#f37a20" stroke-width="1.5" stroke-dasharray="5 4"
              fill="none" opacity=".35"/>

        <!-- Signal icon -->
        <g transform="translate(448, 50)" opacity=".5">
          <path d="M8 0C3.58 0 0 3.58 0 8" stroke="#f37a20" stroke-width="2" stroke-linecap="round" fill="none"/>
          <path d="M16 8C16 3.58 19.58 0 24 0" stroke="#f37a20" stroke-width="2" stroke-linecap="round" fill="none"/>
          <path d="M11 8C11 6.34 12.34 5 14 5s3 1.34 3 3" stroke="#f37a20" stroke-width="2" stroke-linecap="round" fill="none"/>
          <circle cx="14" cy="12" r="2" fill="#f37a20"/>
        </g>

        <!-- Stat cards -->
        <g transform="translate(26, 60)">
          <rect width="110" height="44" rx="8" fill="#181c25" stroke="#232837" stroke-width="1"/>
          <rect x="10" y="10" width="8" height="8" rx="2" fill="#f37a20" opacity=".8"/>
          <rect x="26" y="12" width="50" height="6" rx="3" fill="#2a3040"/>
          <rect x="26" y="24" width="32" height="5" rx="2.5" fill="#f37a20" opacity=".4"/>
          <rect x="82" y="10" width="18" height="24" rx="4" fill="#f37a20" opacity=".1"/>
          <rect x="86" y="20" width="10" height="10" rx="2" fill="#f37a20" opacity=".5"/>
        </g>
        <g transform="translate(370, 90)">
          <rect width="120" height="44" rx="8" fill="#181c25" stroke="#232837" stroke-width="1"/>
          <rect x="10" y="10" width="8" height="8" rx="2" fill="#f37a20" opacity=".6"/>
          <rect x="26" y="12" width="60" height="6" rx="3" fill="#2a3040"/>
          <rect x="26" y="24" width="40" height="5" rx="2.5" fill="#f37a20" opacity=".35"/>
          <rect x="92" y="28" width="5" height="8"  rx="1" fill="#f37a20" opacity=".3"/>
          <rect x="100" y="20" width="5" height="16" rx="1" fill="#f37a20" opacity=".5"/>
          <rect x="108" y="24" width="5" height="12" rx="1" fill="#f37a20" opacity=".4"/>
        </g>

        <!-- Ground gradient -->
        <rect x="0" y="295" width="520" height="80" fill="url(#groundGrad)"/>
        <defs>
          <linearGradient id="groundGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="#0f1117" stop-opacity="0"/>
            <stop offset="100%" stop-color="#0f1117" stop-opacity=".9"/>
          </linearGradient>
        </defs>

      </svg>
    </div>

    <!-- Caption -->
    <div class="position-relative" style="z-index:2;">
      <div class="d-inline-flex align-items-center gap-2 rounded-pill px-3 py-1 mb-3 fw-semibold text-uppercase"
           style="font-size:.78rem; letter-spacing:.06em; color:var(--orange); background:rgba(243,122,32,.15); border:1px solid rgba(243,122,32,.25);">
        <i class="bi bi-shield-check"></i> Enterprise Grade
      </div>
      <h2 class="fw-bold text-white mb-2" style="font-size:1.6rem; line-height:1.3;">Smarter corporate<br/>mobility, at scale.</h2>
      <p class="mb-0" style="font-size:.88rem; color:rgba(255,255,255,.45); line-height:1.6; max-width:320px;">Manage rides, track expenses, and control your fleet — all from one unified dashboard.</p>
    </div>

  </div>
</div>

<!-- Toast container -->
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
    new bootstrap.Toast(el, { delay: 4000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  /* Password toggle */
  document.getElementById('pwToggle').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
  });

  /* Auto-redirect if already logged in */
  (async () => {
    try {
      const r = await fetch('auth/session.php');
      const j = await r.json();
      if (j.loggedIn) window.location.href = 'home.php';
    } catch {}
  })();

  /* Login submit */
  const defaultBtnHtml = 'Sign In <i class="bi bi-chevron-right"></i>';
  const loadingBtnHtml = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in\u2026';

  document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btnSignIn');
    btn.disabled = true;
    btn.innerHTML = loadingBtnHtml;
    try {
      const resp = await fetch('auth/login.php', { method: 'POST', body: new FormData(this) });
      const json = await resp.json();
      if (json.success) {
        window.location.href = 'home.php';
      } else {
        showToast(json.message || 'Invalid credentials.', 'error');
        btn.disabled = false;
        btn.innerHTML = defaultBtnHtml;
      }
    } catch {
      showToast('Network error, please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = defaultBtnHtml;
    }
  });
</script>
</body>
</html>