<?php
$sidebarLinks = [
  ['href' => 'home.php',        'icon' => 'bi-house-door-fill',  'label' => 'Home',     'match' => 'Dashboard'],
  ['href' => 'employee.php',    'icon' => 'bi-people-fill',      'label' => 'Employees',     'match' => 'Employee Directory'],
  ['href' => 'rideHistory.php', 'icon' => 'bi-clock-history',    'label' => 'Rides History',  'match' => 'Ride History'],
  ['href' => 'invoice.php',     'icon' => 'bi-receipt',          'label' => 'Invoices',  'match' => 'Invoices'],
];
$currentPage = $pageTitle ?? '';
?>
<style>
  .sn-nav-link:hover { background: rgba(255,255,255,.08) !important; }
  .sn-nav-link.active { background: rgba(243,122,32,.18) !important; font-weight: 600; border-left: 3px solid #f37a20; }
</style>

<div class="d-flex flex-column h-100 px-1 py-3">

  <div class="d-flex justify-content-center align-items-center mb-4 px-1" style="height:48px; width:100%">
    <img
      src="assets/powercabs-logo.svg"
      alt="Navigation Logo"
    />
  </div>

  <p class="text-uppercase text-white text-opacity-50 fw-semibold px-1 mb-1 lh-1"
     style="font-size:var(--fs-label); letter-spacing:.09em">
    Menu
  </p>

  <ul class="nav flex-column gap-1 mt-1">
    <?php foreach ($sidebarLinks as $link):
      $isActive = ($currentPage === $link['match']);
    ?>
    <li class="nav-item">
      <a href="<?= $link['href'] ?>"
         class="sn-nav-link nav-link text-white d-flex align-items-center gap-2 rounded-2 px-3 py-2 fw-medium<?= $isActive ? ' active' : '' ?>"
         style="font-size:var(--fs-body)">
        <i class="bi <?= $link['icon'] ?> opacity-75" style="font-size:var(--fs-body)"></i>
        <?= $link['label'] ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>

  <div class="mt-auto border-top border-white border-opacity-10 pt-2">
    <span class="fw-medium flex-grow-1 text-truncate"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></span>
  </div>

</div>
