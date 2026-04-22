<?php
$pageTitle = $pageTitle ?? 'Dashboard';
// Inject favicon into <head> from this shared include
echo '<script>if(!document.querySelector("link[rel=icon]")){var l=document.createElement("link");l.rel="icon";l.href="favicon.ico";l.type="image/x-icon";document.head.appendChild(l);}</script>';


session_start_once:
if (session_status() === PHP_SESSION_NONE) session_start();
$_navCid = $_SESSION['user']['cid'] ?? '';
echo '<script>window.PC_USER_CID = ' . json_encode((string)$_navCid) . ';</script>';
$userName = $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'User';
$parts    = array_filter(explode(' ', trim($userName)));
$initials = strtoupper(
    count($parts) >= 2
        ? substr($parts[0], 0, 1) . substr(end($parts), 0, 1)
        : substr($parts[0] ?? 'U', 0, 2)
);
?>

<style>
  :root {
    --nav-height:      60px;
    --nav-bg:          #ffffff;
    --nav-border:      #e5e7eb;
    --nav-text:        #111827;
    --nav-muted:       #6b7280;
    --nav-radius:      6px;
    --avatar-bg:       #18181b;
    --avatar-fg:       #ffffff;
    --avatar-size:     32px;
    --avatar-font:     12px;
    --ring-color:      rgba(0,0,0,.08);
    --dd-shadow:       0 4px 16px rgba(0,0,0,.10);
    --dd-border:       #e5e7eb;
    --dd-hover:        #f4f4f5;
    --transition:      140ms ease;
  }

  .sn-navbar {
    height:          var(--nav-height);
    background:      var(--nav-bg);
    border-bottom:   1px solid var(--nav-border);
    display:         flex;
    align-items:     center;
    justify-content: space-between;
    padding:         0 1.1rem;
  }

  .sn-left {
    display:     flex;
    align-items: center;
    gap:         .55rem;
  }

  .sn-toggler {
    display:         none;    
    align-items:     center;
    justify-content: center;
    width:           30px;
    height:          30px;
    border:          1px solid var(--nav-border);
    border-radius:   var(--nav-radius);
    background:      transparent;
    cursor:          pointer;
    color:           var(--nav-muted);
    transition:      background var(--transition), color var(--transition);
  }
  .sn-toggler:hover { background: #f4f4f5; color: var(--nav-text); }
  .sn-toggler svg   { width: 15px; height: 15px; }

  .sn-page-title {
    font-size:   var(--fs-page-title);
    font-weight: 700;
    color:       var(--nav-text);
    letter-spacing: .01em;
    margin:      0;
    line-height: 1;
  }

  .sn-right {
    display:     flex;
    align-items: center;
  }

  .sn-avatar-btn {
    width:           var(--avatar-size);
    height:          var(--avatar-size);
    border-radius:   50%;
    background:      var(--avatar-bg);
    color:           var(--avatar-fg);
    font-size:       var(--avatar-font);
    font-weight:     600;
    letter-spacing:  .03em;
    border:          none;
    cursor:          pointer;
    display:         flex;
    align-items:     center;
    justify-content: center;
    transition:      box-shadow var(--transition), opacity var(--transition);
    outline:         none;
    user-select:     none;
  }
  .sn-avatar-btn:hover,
  .sn-avatar-btn:focus-visible {
    box-shadow: 0 0 0 3px var(--ring-color);
    opacity:    .88;
  }

  .sn-dropdown {
    min-width:     150px;
    border:        1px solid var(--dd-border);
    border-radius: var(--nav-radius);
    box-shadow:    var(--dd-shadow);
    padding:       4px;
    background:    #fff;
    margin-top:    6px !important;
  }

  .sn-dropdown .dropdown-item {
    border-radius:  4px;
    font-size:      var(--fs-body);
    padding:        .38rem .65rem;
    color:          var(--nav-text);
    transition:     background var(--transition);
    line-height:    1.3;
  }
  .sn-dropdown .dropdown-item:hover { background: var(--dd-hover); }

  .sn-dropdown .sn-dd-label {
    font-size:     var(--fs-small);
    color:         var(--nav-muted);
    padding:       .35rem .65rem .1rem;
    font-weight:   500;
    letter-spacing:.03em;
    text-transform: uppercase;
    display:       block;
  }

  .sn-dropdown .dropdown-divider {
    margin: 3px 0;
    border-color: var(--dd-border);
  }

  .sn-dropdown .sn-logout {
    color: #dc2626;
  }
  .sn-dropdown .sn-logout:hover { background: #fef2f2; }

  @media (max-width: 991.98px) {
    .sn-toggler { display: flex; }
  }
</style>

<nav class="sn-navbar">

  <div class="sn-left">
    <button class="sn-toggler" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <h1 class="sn-page-title" id="pageTitle"><?= htmlspecialchars($pageTitle) ?></h1>
  </div>

  <div class="sn-right">
    <div class="dropdown">
      <button
        class="sn-avatar-btn"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        aria-label="User menu"
      ><?= htmlspecialchars($initials) ?></button>

      <ul class="dropdown-menu dropdown-menu-end sn-dropdown">
        <li><span class="sn-dd-label"><?= htmlspecialchars($userName) ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
        <li><a class="dropdown-item sn-logout" href="auth/logout.php">Log out</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="sidebar text-white p-3">
  <?php require __DIR__ . '/sidebar.php'; ?>
</div>

<?php require __DIR__ . '/toast.php'; ?>
<?php require __DIR__ . '/fab-bookride.php'; ?>