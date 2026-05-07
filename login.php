<?php
session_start();
$flashSuccess = $_SESSION['success'] ?? '';
$flashError   = $_SESSION['error']   ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" href="favicon.ico" type="image/x-icon"/>
  <title>PowerCabs Corporate — Sign In</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

  <style>
    :root {
      --orange:    #f37a20;
      --orange-dk: #e06910;
      --orange-soft: #fff4eb;
      --dark:      #0c0d11;
      --dark-2:    #14161c;
      --dark-3:    #1c1f27;
      --line:      #1f232c;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-500: #64748b;
      --slate-700: #334155;
      --slate-900: #0f172a;
    }

    *, *::before, *::after { box-sizing: border-box; }
    html, body {
      height: 100vh;
      overflow: hidden;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      color: var(--slate-900);
      background: #ffffff;
    }

    /* ── Page shell ── */
    .login-page {
      height: 100vh;
      display: flex; flex-direction: column;
      overflow: hidden;
    }
    .login-shell {
      flex: 1;
      display: grid;
      grid-template-columns: minmax(420px, 1fr) 1.45fr;
      height: 100vh;
      overflow: hidden;
    }
    @media (max-width: 1100px) {
      .login-shell { grid-template-columns: 1fr; }
      .login-panel-right { display: none !important; }
    }

    /* ── Left panel ── */
    .login-panel-left {
      display: flex; flex-direction: column;
      justify-content: center;
      padding: 28px 56px 28px 88px;
      background: #ffffff;
      overflow: hidden;
    }
    .login-inner { width: 100%; max-width: 420px; }
    @media (max-width: 1300px) {
      .login-panel-left { padding-left: 56px; }
    }
    @media (max-width: 640px) {
      .login-panel-left { padding: 28px 24px; }
    }

    .login-logo { height: 70px; margin-bottom: 32px; display: block; }

    .login-title {
      font-size: 1.85rem; font-weight: 700;
      color: var(--slate-900);
      letter-spacing: -.02em; line-height: 1.15;
      margin-bottom: 10px;
    }
    .login-secure {
      display: flex; align-items: center; gap: 10px;
      font-size: .88rem; color: var(--slate-500);
      margin-bottom: 22px;
    }
    .login-secure .ic {
      width: 28px; height: 28px; border-radius: 8px;
      background: var(--slate-100); color: var(--slate-700);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: .9rem;
    }

    .field-label {
      font-size: 11px; font-weight: 600;
      letter-spacing: .08em; text-transform: uppercase;
      color: var(--slate-700); margin-bottom: 6px; display: block;
    }
    .field-wrap {
      position: relative;
      display: flex; align-items: center;
      border: 1px solid var(--slate-200);
      border-radius: 9px;
      background: #ffffff;
      transition: border-color .15s, box-shadow .15s;
    }
    .field-wrap:focus-within {
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(243,122,32,.15);
    }
    .field-wrap .ic-prefix {
      flex-shrink: 0; padding: 0 11px;
      color: var(--slate-500); font-size: .9rem;
    }
    .field-wrap .field-input {
      flex: 1; min-width: 0;
      border: none; outline: none; background: transparent;
      padding: .5rem 0;
      font-size: .9rem; color: var(--slate-900);
    }
    .field-wrap .field-input::placeholder { color: #94a3b8; }
    .field-wrap .pw-toggle {
      flex-shrink: 0; padding: 0 12px;
      background: none; border: none;
      color: var(--slate-500); cursor: pointer; font-size: .95rem;
      display: flex; align-items: center;
    }
    .field-wrap .pw-toggle:hover { color: var(--slate-900); }

    .mb-3 { margin-bottom: 14px !important; }

    .reset-row {
      display: flex; justify-content: flex-end;
      margin: 6px 0 14px;
    }
    .reset-row a {
      font-size: .82rem; font-weight: 500;
      color: var(--orange); text-decoration: none;
    }
    .reset-row a:hover { text-decoration: underline; }

    .btn-signin {
      width: 100%;
      background: var(--orange); color: #fff;
      border: 1px solid var(--orange);
      border-radius: 9px;
      padding: .55rem 1rem;
      font-size: .9rem; font-weight: 600;
      letter-spacing: .01em;
      display: flex; align-items: center; justify-content: center; gap: .4rem;
      cursor: pointer;
      transition: background .15s, box-shadow .15s, border-color .15s;
    }
    .btn-signin:hover,
    .btn-signin:focus {
      background: var(--orange-dk); border-color: var(--orange-dk);
      box-shadow: 0 6px 18px rgba(243,122,32,.28);
    }
    .btn-signin:disabled { opacity: .7; cursor: not-allowed; }

    .help-row {
      margin-top: 18px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .help-card {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px;
      border: 1px solid var(--slate-200);
      border-radius: 10px;
      background: #ffffff;
      text-decoration: none; color: inherit;
      transition: border-color .15s, box-shadow .15s, background .15s, transform .12s;
      min-width: 0;
    }
    .help-card:hover {
      border-color: var(--orange); background: var(--orange-soft);
      box-shadow: 0 4px 14px rgba(243,122,32,.12);
      transform: translateY(-1px);
    }
    .help-card .ic {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--slate-100); color: var(--slate-700);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: .95rem;
    }
    .help-card:hover .ic { background: #fff; color: var(--orange); }
    .help-card.help-whatsapp .ic { background: #ecfdf5; color: #15803d; }
    .help-card.help-whatsapp:hover { border-color: #16a34a; background: #f0fdf4; box-shadow: 0 4px 14px rgba(22,163,74,.12); }
    .help-card.help-whatsapp:hover .ic { background: #fff; color: #16a34a; }
    .help-card .info { flex: 1; min-width: 0; line-height: 1.25; }
    .help-card .ttl {
      font-size: 10px; font-weight: 600; letter-spacing: .07em;
      text-transform: uppercase; color: var(--slate-500);
    }
    .help-card .num {
      font-size: .92rem; font-weight: 600; color: var(--slate-900);
      margin-top: 2px; white-space: nowrap;
      overflow: hidden; text-overflow: ellipsis;
    }
    @media (max-width: 480px) {
      .help-row { grid-template-columns: 1fr; }
    }

    .login-foot-note {
      font-size: .75rem; color: var(--slate-500);
      margin-top: 16px;
    }

    /* ── Right panel (dark hero) ── */
    .login-panel-right {
      position: relative; overflow: hidden;
      background: var(--dark);
      color: #fff;
      display: flex; flex-direction: column;
      padding: 28px 44px 40px;
    }
    .login-panel-right::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
      background-size: 48px 48px;
      pointer-events: none;
    }
    .rp-glow-1 {
      position: absolute; width: 480px; height: 480px; border-radius: 50%;
      background: radial-gradient(circle, rgba(243,122,32,.15) 0%, transparent 70%);
      top: -140px; right: -160px; pointer-events: none;
    }
    .rp-glow-2 {
      position: absolute; width: 320px; height: 320px; border-radius: 50%;
      background: radial-gradient(circle, rgba(243,122,32,.07) 0%, transparent 70%);
      bottom: 80px; left: -80px; pointer-events: none;
    }

    .rp-content { position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column; min-height: 0; }

    /* Illustration block with floating cards */
    .rp-illu {
      position: relative;
      width: 100%; max-width: 780px;
      align-self: center;
      margin-bottom: 24px;
      max-height: 48vh;
    }
    .rp-illu svg { width: 100%; height: 100%; max-height: 48vh; display: block; }

    .float-card {
      position: absolute;
      background: #181c25;
      border: 1px solid #2a2f3a;
      border-radius: 11px;
      padding: 10px 14px;
      box-shadow: 0 14px 32px rgba(0,0,0,.5), 0 2px 6px rgba(0,0,0,.4);
      min-width: 175px;
      backdrop-filter: blur(4px);
    }
    .float-card .fc-row {
      display: flex; align-items: center; gap: 9px;
      font-size: .76rem; font-weight: 600;
      color: #ffffff; letter-spacing: .005em;
      margin-bottom: 8px;
    }
    .float-card .fc-icon {
      width: 22px; height: 22px;
      border-radius: 6px;
      background: rgba(243,122,32,.15);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      color: var(--orange);
      font-size: .78rem;
    }
    .float-card .fc-bar {
      height: 5px; border-radius: 2.5px;
      background: rgba(255,255,255,.08);
      margin-bottom: 5px;
    }
    .float-card .fc-bar:last-child { margin-bottom: 0; }
    .float-card .fc-bar.fill {
      background: linear-gradient(90deg, var(--orange) 60%, rgba(243,122,32,.25));
    }
    .float-card .fc-bar.short { width: 65%; }

    .float-card.fc-tracking  { top: 4%;  left: 32%; }
    .float-card.fc-ride      { top: 40%; left: 3%;  }
    .float-card.fc-analytics { top: 22%; right: 3%; }

    .float-card.fc-analytics .fc-icon {
      background: rgba(243,122,32,.10);
      padding: 2px;
    }
    .float-card.fc-analytics .fc-donut { width: 18px; height: 18px; }

    .rp-headline {
      font-size: 1.55rem; font-weight: 700;
      color: #ffffff; letter-spacing: -.01em;
      line-height: 1.25; margin: 6px 0 8px;
    }
    .rp-lede {
      font-size: .9rem; color: rgba(255,255,255,.55);
      line-height: 1.55; margin-bottom: 22px;
      max-width: 600px;
    }

    /* Features row — divided into 5 equal columns with vertical separators */
    .rp-features {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 0;
      margin-bottom: 22px;
    }
    .rp-feat {
      padding: 0 18px;
      border-left: 1px solid var(--line);
    }
    .rp-feat:first-child { border-left: none; padding-left: 0; }
    .rp-feat:last-child  { padding-right: 0; }
    .rp-feat .ic {
      width: 38px; height: 38px; border-radius: 9px;
      background: rgba(243,122,32,.13); color: var(--orange);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.15rem; margin-bottom: 10px;
    }
    .rp-feat .ttl {
      font-size: .92rem; font-weight: 600; color: #ffffff;
      margin-bottom: 4px; line-height: 1.3;
    }
    .rp-feat .sub {
      font-size: .78rem; color: rgba(255,255,255,.55);
      line-height: 1.45;
    }

    /* Security row — 3 stats left-aligned with vertical separators, help card right */
    .rp-security {
      display: flex; align-items: center; gap: 0;
      padding-top: 16px;
      border-top: 1px solid var(--line);
      margin-top: auto;
    }
    .rp-sec-item {
      display: flex; align-items: center; gap: 12px;
      padding: 0 22px;
      border-left: 1px solid var(--line);
    }
    .rp-sec-item:first-child { border-left: none; padding-left: 0; }
    .rp-sec-item .ic {
      width: 38px; height: 38px; border-radius: 9px;
      background: rgba(255,255,255,.05); color: rgba(255,255,255,.8);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.05rem;
    }
    .rp-sec-item .ttl  { font-size: .88rem; font-weight: 600; color: rgba(255,255,255,.95); line-height: 1.2; }
    .rp-sec-item .sub  { font-size: .76rem; color: rgba(255,255,255,.5);  line-height: 1.2; margin-top: 2px; }

    .rp-help {
      margin-left: auto;
      display: flex; align-items: center; gap: 12px;
      padding: 10px 14px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: rgba(255,255,255,.02);
      text-decoration: none; color: inherit;
      transition: border-color .15s, background .15s;
    }
    .rp-help:hover { border-color: var(--orange); background: rgba(243,122,32,.08); }
    .rp-help .ic {
      width: 30px; height: 30px; border-radius: 7px;
      background: rgba(243,122,32,.12); color: var(--orange);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .rp-help .ttl { font-size: .8rem; font-weight: 600; color: #ffffff; line-height: 1.2; }
    .rp-help .sub { font-size: .72rem; color: rgba(255,255,255,.5); line-height: 1.2; margin-top: 2px; }

    /* ── Toast ── */
    #toastContainer {
      position: fixed; top: 20px; right: 20px;
      z-index: 9999; min-width: 300px;
    }

    /* ── Fade-in ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .login-inner > * { animation: fadeUp .4s ease both; }
    .login-inner > *:nth-child(2) { animation-delay: .05s; }
    .login-inner > *:nth-child(3) { animation-delay: .10s; }
    .login-inner > *:nth-child(4) { animation-delay: .15s; }

    /* Smaller screens */
    @media (max-width: 640px) {
      .rp-features { grid-template-columns: repeat(2, 1fr); }
      .rp-feat { border-left: none; padding: 0; }
    }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-shell">

    <!-- ───────────── LEFT: Form ───────────── -->
    <div class="login-panel-left">
      <div class="login-inner">

        <img src="assets/powercabs-logo-black.svg" alt="PowerCabs" class="login-logo"/>

        <h1 class="login-title">Corporate Fleet Portal</h1>
        <div class="login-secure">
          <span class="ic"><i class="bi bi-shield-check"></i></span>
          <span>Secure access for authorised company administrators.</span>
        </div>

        <form id="loginForm" method="POST" novalidate>

          <div class="mb-3">
            <label for="email" class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="ic-prefix"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" id="email"
                     class="field-input"
                     placeholder="name@company.com" autocomplete="email" required/>
            </div>
          </div>

          <div>
            <label for="password" class="field-label">Password</label>
            <div class="field-wrap">
              <span class="ic-prefix"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" id="password"
                     class="field-input"
                     placeholder="Enter your password" autocomplete="current-password" required/>
              <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password">
                <i class="bi bi-eye-slash" id="pwIcon"></i>
              </button>
            </div>
            <div class="reset-row">
              <a href="forgot-password.php">Reset password</a>
            </div>
          </div>

          <button type="submit" id="btnSignIn" class="btn-signin">
            Sign in to dashboard
          </button>
        </form>

        <div class="help-row">
          <a href="tel:+35312030727" class="help-card help-call">
            <span class="ic"><i class="bi bi-telephone-fill"></i></span>
            <span class="info">
              <span class="ttl d-block">Call us</span>
              <span class="num d-block">01 2030 727</span>
            </span>
          </a>
          <a href="https://wa.me/353899728089" target="_blank" rel="noopener" class="help-card help-whatsapp">
            <span class="ic"><i class="bi bi-whatsapp"></i></span>
            <span class="info">
              <span class="ttl d-block">WhatsApp</span>
              <span class="num d-block">+353 89 972 8089</span>
            </span>
          </a>
        </div>

        <div class="login-foot-note">
          For registered PowerCabs corporate clients and fleet partners.
        </div>

      </div>
    </div>

    <!-- ───────────── RIGHT: Hero ───────────── -->
    <div class="login-panel-right">
      <div class="rp-glow-1"></div>
      <div class="rp-glow-2"></div>

      <div class="rp-content">

        <!-- Illustration with floating cards -->
        <div class="rp-illu">
          <div class="float-card fc-tracking">
            <div class="fc-row">
              <span class="fc-icon"><i class="bi bi-geo-alt-fill"></i></span>
              Live Tracking
            </div>
            <div class="fc-bar fill"></div>
            <div class="fc-bar short"></div>
          </div>

          <div class="float-card fc-ride">
            <div class="fc-row">
              <span class="fc-icon"><i class="bi bi-car-front-fill"></i></span>
              Ride Management
            </div>
            <div class="fc-bar fill"></div>
            <div class="fc-bar short"></div>
          </div>

          <div class="float-card fc-analytics">
            <div class="fc-row">
              <span class="fc-icon">
                <svg class="fc-donut" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="14" fill="none" stroke="#2a2f3a" stroke-width="7"/>
                  <circle cx="18" cy="18" r="14" fill="none" stroke="#f37a20" stroke-width="7"
                          stroke-dasharray="34 88" transform="rotate(-90 18 18)"/>
                  <circle cx="18" cy="18" r="14" fill="none" stroke="#fbbf24" stroke-width="7"
                          stroke-dasharray="20 88" stroke-dashoffset="-34" transform="rotate(-90 18 18)"/>
                </svg>
              </span>
              Analytics
            </div>
            <div class="fc-bar fill"></div>
            <div class="fc-bar short"></div>
          </div>

          <svg viewBox="0 0 800 320" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
            <defs>
              <radialGradient id="warmGlow" cx="78%" cy="18%" r="55%">
                <stop offset="0%"  stop-color="#f37a20" stop-opacity=".14"/>
                <stop offset="55%" stop-color="#f37a20" stop-opacity="0"/>
              </radialGradient>
              <linearGradient id="gg" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%"   stop-color="#0c0d11" stop-opacity="0"/>
                <stop offset="100%" stop-color="#0c0d11" stop-opacity=".95"/>
              </linearGradient>
              <linearGradient id="lampGlow" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f37a20" stop-opacity=".55"/>
                <stop offset="100%" stop-color="#f37a20" stop-opacity="0"/>
              </linearGradient>
            </defs>

            <!-- Warm atmospheric glow upper right -->
            <rect x="0" y="0" width="800" height="320" fill="url(#warmGlow)"/>

            <!-- Clouds (low opacity) -->
            <g opacity=".09" fill="#ffffff">
              <ellipse cx="170" cy="60"  rx="44" ry="11"/>
              <ellipse cx="208" cy="56"  rx="28" ry="9"/>
              <ellipse cx="540" cy="46"  rx="42" ry="10"/>
              <ellipse cx="572" cy="42"  rx="26" ry="8"/>
            </g>

            <!-- Wifi top-right -->
            <g transform="translate(740, 30)" opacity=".7">
              <path d="M0 16 C 7 6, 21 6, 28 16"  stroke="#f37a20" stroke-width="2.2" stroke-linecap="round" fill="none"/>
              <path d="M5 21 C 10 14, 18 14, 23 21" stroke="#f37a20" stroke-width="2.2" stroke-linecap="round" fill="none"/>
              <circle cx="14" cy="26" r="2.4" fill="#f37a20"/>
            </g>

            <!-- Ground baseline -->
            <rect x="0" y="288" width="800" height="2.5" rx="1.5" fill="#1e2433"/>

            <!-- Buildings + windows wrapped at lower opacity so they don't overpower the cards -->
            <g opacity=".72">

              <!-- Far-back filler buildings (low contrast) -->
              <rect x="455" y="180" width="80"  height="105" rx="3" fill="#15181f" stroke="#1e2229"/>
              <rect x="650" y="195" width="60"  height="90"  rx="3" fill="#15181f" stroke="#1e2229"/>

              <!-- Building A: small left -->
              <rect x="115" y="195" width="70"  height="95"  rx="4" fill="#181c25" stroke="#232837"/>
              <!-- Building B: TALL focal centre-left -->
              <rect x="210" y="115" width="120" height="175" rx="4" fill="#1a1f2c" stroke="#232837"/>
              <!-- Building C: medium-low between B and lamp -->
              <rect x="350" y="180" width="50"  height="110" rx="4" fill="#181c25" stroke="#232837"/>
              <!-- Building D: low-wide right of lamp -->
              <rect x="445" y="210" width="100" height="80"  rx="4" fill="#1a1f2c" stroke="#232837"/>
              <!-- Building E: tall right -->
              <rect x="555" y="150" width="80"  height="140" rx="4" fill="#181c25" stroke="#232837"/>
              <!-- Building F: medium far right -->
              <rect x="645" y="190" width="60"  height="100" rx="4" fill="#1a1f2c" stroke="#232837"/>

              <?php
                // Slightly smaller buildings — fewer, smaller window boxes, lower-opacity orange
                $clusters = [
                  // [x, y, w, h, cols, rows, winW, winH]
                  [115, 195, 70,  95,  3, 3, 11, 9],
                  [210, 115, 120, 175, 4, 5, 14, 12],   // focal tower
                  [350, 180, 50,  110, 2, 3, 11, 10],
                  [445, 210, 100, 80,  3, 2, 14, 11],
                  [555, 150, 80,  140, 3, 4, 13, 11],
                  [645, 190, 60,  100, 2, 3, 12, 9],
                ];
                foreach ($clusters as $i => $c) {
                  [$bx,$by,$bw,$bh,$cols,$rows,$ww,$wh] = $c;
                  $isFocal = $i === 1;
                  $padX = ($bw - $cols * $ww) / ($cols + 1);
                  $padY = ($bh - $rows * $wh) / ($rows + 1);
                  for ($r = 0; $r < $rows; $r++) {
                    for ($cc = 0; $cc < $cols; $cc++) {
                      $xx = $bx + $padX + $cc * ($ww + $padX);
                      $yy = $by + $padY + $r * ($wh + $padY);
                      $on = $isFocal ? (rand(0,2) > 0) : (rand(0,3) > 0);
                      $opOn = ['.4','.55','.7','.85'][rand(0,3)];
                      $fill = $on ? "fill='#f37a20' opacity='$opOn'" : "fill='#2a3040'";
                      echo "<rect x='" . round($xx,1) . "' y='" . round($yy,1) . "' width='$ww' height='$wh' rx='2' $fill/>";
                    }
                  }
                }
              ?>
            </g>

            <!-- Roof antenna on focal building -->
            <rect x="268" y="98"  width="3" height="18" fill="#232837" opacity=".7"/>
            <circle cx="270" cy="96" r="2.5" fill="#f37a20" opacity=".75"/>

            <!-- Tree LEFT (cleaner silhouette) -->
            <g>
              <rect x="38" y="272" width="6" height="18" rx="1" fill="#15181f"/>
              <circle cx="32"  cy="258" r="16" fill="#1a1f2c"/>
              <circle cx="48"  cy="258" r="16" fill="#1a1f2c"/>
              <circle cx="40"  cy="248" r="16" fill="#1c2230"/>
            </g>

            <!-- Tree RIGHT -->
            <g>
              <rect x="754" y="272" width="6" height="18" rx="1" fill="#15181f"/>
              <circle cx="748" cy="258" r="16" fill="#1a1f2c"/>
              <circle cx="764" cy="258" r="16" fill="#1a1f2c"/>
              <circle cx="756" cy="248" r="16" fill="#1c2230"/>
            </g>

            <!-- Two dashed connectors fanning out from the lamp light -->
            <g stroke="#f37a20" stroke-width="1.6" stroke-dasharray="5 5" fill="none"
               stroke-linecap="round" stroke-linejoin="round" opacity=".55">
              <!-- Lamp light → Live Tracking card (smooth flowing wave) -->
              <path d="M 420 184
                       Q 380 168 380 130
                       Q 380 105 398 105
                       Q 398 80  372 80
                       L 380 80"/>
              <!-- Lamp light → Analytics card (up → right) -->
              <path d="M 428 184 V 149 Q 428 144 433 144 H 586"/>
            </g>

            <!-- Street lamp (between buildings C and D) — refined -->
            <g>
              <!-- soft halo -->
              <ellipse cx="424" cy="200" rx="34" ry="34" fill="url(#lampGlow)" opacity=".55"/>
              <!-- lamp head body -->
              <rect x="416" y="184" width="16" height="28" rx="4" fill="#1a1f2c" stroke="#2a3040"/>
              <!-- lamp light (orange core) -->
              <rect x="419" y="190" width="10" height="18" rx="2" fill="#f37a20"/>
              <rect x="419" y="190" width="10" height="6"  rx="2" fill="#ffae5e" opacity=".9"/>
              <!-- pole -->
              <rect x="423" y="212" width="3"  height="78" fill="#232837"/>
              <!-- base -->
              <rect x="418" y="286" width="13" height="4" rx="1" fill="#15181f"/>
            </g>

            <!-- Road centre dashes (drawn before cars so cars sit on top) -->
            <line x1="40" y1="296" x2="760" y2="296"
                  stroke="#3a4054" stroke-width="2.5"
                  stroke-dasharray="16 12" stroke-linecap="round" opacity=".5"/>

            <!-- Big orange delivery van (left foreground) -->
            <g transform="translate(245, 260)">
              <!-- shadow -->
              <ellipse cx="46" cy="52" rx="48" ry="3" fill="#000" opacity=".35"/>
              <!-- body -->
              <rect x="2"  y="14" width="92" height="34" rx="6" fill="#f37a20"/>
              <!-- cabin (front) -->
              <rect x="62" y="6"  width="32" height="20" rx="5" fill="#e06910"/>
              <!-- side cargo panels (windows) -->
              <rect x="10" y="20" width="22" height="14" rx="2" fill="rgba(255,255,255,.16)"/>
              <rect x="36" y="20" width="22" height="14" rx="2" fill="rgba(255,255,255,.16)"/>
              <!-- cabin window -->
              <rect x="68" y="10" width="22" height="12" rx="2" fill="rgba(255,255,255,.22)"/>
              <!-- bumpers -->
              <rect x="0"  y="40" width="8"  height="6" rx="2" fill="#c5550c"/>
              <rect x="93" y="22" width="4"  height="10" rx="1" fill="#fff" opacity=".7"/>
              <!-- wheels -->
              <circle cx="20" cy="48" r="9" fill="#0f1117"/>
              <circle cx="20" cy="48" r="4" fill="#2a3040"/>
              <circle cx="76" cy="48" r="9" fill="#0f1117"/>
              <circle cx="76" cy="48" r="4" fill="#2a3040"/>
            </g>

            <!-- Small dark car centre-right -->
            <g transform="translate(580, 274)">
              <ellipse cx="32" cy="32" rx="34" ry="2.5" fill="#000" opacity=".3"/>
              <rect x="2"  y="6"  width="60" height="20" rx="6" fill="#232837"/>
              <rect x="11" y="0"  width="42" height="16" rx="4" fill="#1a1f2c"/>
              <rect x="14" y="3"  width="14" height="11" rx="2" fill="rgba(243,122,32,.22)"/>
              <rect x="34" y="3"  width="14" height="11" rx="2" fill="rgba(243,122,32,.22)"/>
              <circle cx="14" cy="28" r="6" fill="#0f1117"/><circle cx="14" cy="28" r="2.4" fill="#2a3040"/>
              <circle cx="50" cy="28" r="6" fill="#0f1117"/><circle cx="50" cy="28" r="2.4" fill="#2a3040"/>
              <rect x="60" y="12" width="4" height="6" rx="1" fill="#f37a20" opacity=".8"/>
            </g>

            <!-- Small dark car far right -->
            <g transform="translate(680, 280)">
              <ellipse cx="22" cy="26" rx="24" ry="2" fill="#000" opacity=".3"/>
              <rect x="0" y="4" width="44" height="16" rx="5" fill="#232837"/>
              <rect x="6" y="0" width="32" height="13" rx="3" fill="#1a1f2c"/>
              <circle cx="9"  cy="22" r="5" fill="#0f1117"/>
              <circle cx="35" cy="22" r="5" fill="#0f1117"/>
            </g>

            <!-- Ground gradient for depth -->
            <rect x="0" y="270" width="800" height="50" fill="url(#gg)"/>
          </svg>
        </div>

        <!-- Headline -->
        <h2 class="rp-headline">Control your company transport in one place</h2>
        <p class="rp-lede">
          PowerCabs Corporate Portal helps you manage, monitor, and optimise
          your fleet and employee rides with ease.
        </p>

        <!-- Features -->
        <div class="rp-features">
          <div class="rp-feat">
            <div class="ic"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="ttl">Real-time tracking</div>
            <div class="sub">Track rides and fleet in real time.</div>
          </div>
          <div class="rp-feat">
            <div class="ic"><i class="bi bi-receipt"></i></div>
            <div class="ttl">Centralised billing</div>
            <div class="sub">View invoices and manage payments.</div>
          </div>
          <div class="rp-feat">
            <div class="ic"><i class="bi bi-people-fill"></i></div>
            <div class="ttl">Employee management</div>
            <div class="sub">Manage employees, rides and permissions.</div>
          </div>
          <div class="rp-feat">
            <div class="ic"><i class="bi bi-bar-chart-fill"></i></div>
            <div class="ttl">Analytics &amp; reports</div>
            <div class="sub">Get insights and export reports.</div>
          </div>
          <div class="rp-feat">
            <div class="ic"><i class="bi bi-headset"></i></div>
            <div class="ttl">Dedicated support</div>
            <div class="sub">Priority support for corporate clients.</div>
          </div>
        </div>

        <!-- Security row -->
        <div class="rp-security">
          <div class="rp-sec-item">
            <div class="ic"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
              <div class="ttl">256-bit SSL</div>
              <div class="sub">Secured</div>
            </div>
          </div>
          <div class="rp-sec-item">
            <div class="ic"><i class="bi bi-shield-check"></i></div>
            <div>
              <div class="ttl">GDPR</div>
              <div class="sub">Compliant</div>
            </div>
          </div>
          <div class="rp-sec-item">
            <div class="ic"><i class="bi bi-people"></i></div>
            <div>
              <div class="ttl">Role-based access</div>
              <div class="sub">For your team</div>
            </div>
          </div>

          <a href="https://wa.me/353899728089" target="_blank" rel="noopener" class="rp-help">
            <span class="ic"><i class="bi bi-headset"></i></span>
            <div>
              <div class="ttl">Need help?</div>
              <div class="sub">Contact corporate support</div>
            </div>
            <i class="bi bi-chevron-right" style="color:rgba(255,255,255,.4)"></i>
          </a>
        </div>

      </div>
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

  /* Flash messages from PHP session (e.g. after password reset) */
  <?php if ($flashSuccess): ?>
    showToast(<?= json_encode(strip_tags($flashSuccess)) ?>, 'success');
  <?php endif; ?>
  <?php if ($flashError): ?>
    showToast(<?= json_encode(strip_tags($flashError)) ?>, 'error');
  <?php endif; ?>

  /* Auto-redirect if already logged in */
  (async () => {
    try {
      const r = await fetch('auth/session.php');
      const j = await r.json();
      if (j.loggedIn) window.location.href = 'home.php';
    } catch {}
  })();

  /* Login submit */
  const defaultBtnHtml = 'Sign in to dashboard';
  const loadingBtnHtml = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in…';

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
