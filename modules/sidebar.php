<style>
  .sn-nav-link:hover { background: rgba(255,255,255,.08) !important; }
  .sn-nav-link.active { background: rgba(255,255,255,.14) !important; font-weight: 600; }
</style>

<div class="d-flex flex-column h-100 px-1 py-3">

  <div class="d-flex justify-content-center align-items-center mb-4 px-1" style="height:52px">
    <img
      src="assets/powercabs-logo.svg"
      alt="Navigation Logo"
      class="img-fluid"
      style="max-height:56px"
    />
  </div>

  <p class="text-uppercase text-white text-opacity-50 fw-semibold px-1 mb-1 lh-1"
     style="font-size:.72rem; letter-spacing:.09em">
    Menu
  </p>

  <ul class="nav flex-column gap-1 mt-1">

    <li class="nav-item">
      <a href="home.php"
         class="sn-nav-link nav-link text-white d-flex align-items-center gap-2 rounded-2 px-3 py-2 fw-medium transition"
         style="font-size:.9425rem">
        <i class="bi bi-house-door-fill opacity-75" style="font-size:.9rem"></i>
        Dashboard
      </a>
    </li>

    <li class="nav-item">
      <a href="employee.php"
         class="sn-nav-link nav-link text-white d-flex align-items-center gap-2 rounded-2 px-3 py-2 fw-medium"
         style="font-size:.9425rem">
        <i class="bi bi-people-fill opacity-75" style="font-size:.9rem"></i>
        Employees
      </a>
    </li>

    <li class="nav-item">
      <a href="rideHistory.php"
         class="sn-nav-link nav-link text-white d-flex align-items-center gap-2 rounded-2 px-3 py-2 fw-medium"
         style="font-size:.9425rem">
        <i class="bi bi-clock-history opacity-75" style="font-size:.9rem"></i>
        Rides History
      </a>
    </li>

  </ul>

  <div class="mt-auto border-top border-white border-opacity-10 pt-2">
        <span class="fw-medium flex-grow-1 text-truncate"><?= htmlspecialchars($user['name']) ?></span>
  </div>

</div>