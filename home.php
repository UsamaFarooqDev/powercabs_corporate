<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user = $_SESSION['user'];
$cid  = $user['cid'];

$total_ride    = 0;
$pending_rides = 0;
$employees     = 0;
$expense       = 0.0;
$rides         = [];
$ridesFetchError = '';
$pageTitle     = 'Dashboard';

try {
  $supabase = new SupabaseClient(true);
  $rides    = $supabase->select('corporate_rides', ['cid' => $cid], '*', 'date.desc', 100);
  $total_ride = count($rides);
  foreach ($rides as $row) {
    if (($row['status'] ?? '') === 'Pending') $pending_rides++;
    $expense += floatval($row['fare'] ?? 0);
  }
} catch (Throwable $e) {
  $rides = [];
  $ridesFetchError = $e->getMessage();
  error_log('home.php rides fetch error: ' . $ridesFetchError);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — PowerCabs</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <!-- DataTables (Bootstrap 5 skin) -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <!-- Your global sheet (sidebar layout, etc.) -->
  <link href="global.css" rel="stylesheet"/>

  <style>
    body { background: #f5f7fa; }

    .stat-card {
      border-radius: 16px;
      border: 1px solid #eeeff2;
      transition: box-shadow .15s ease, transform .15s ease;
    }
    .stat-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.07) !important; transform: translateY(-1px); }

    .stat-icon {
      width: 52px; height: 52px;
      border-radius: 12px;
      background: #fff4eb;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .stat-icon i { color: #f37a20; font-size: 1.35rem; }

    .stat-label { font-size: .72rem; font-weight: 500; color: #9ca3af; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 2px; }
    .stat-value { font-size: 1.45rem; font-weight: 700; color: #111827; line-height: 1; }

    .btn-book {
      background: #f37a20;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: .9925rem;
      font-weight: 600;
      padding: .45rem 1rem;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      transition: background .15s ease, box-shadow .15s ease;
    }
    .btn-book:hover { background: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.35); }

    .rides-card { border-radius: 16px; border: 1px solid #eeeff2; }

    .rides-search {
      font-size: .9rem;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: .38rem .75rem;
      background: #fff;
      max-width: 610px;
      outline: none;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .rides-search:focus { border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12); }

    .rides-table thead th {
      font-size: .72rem;
      font-weight: 600;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: .05em;
      border-bottom: 1px solid #e5e7eb !important;
      padding-bottom: .65rem;
      white-space: nowrap;
    }
    .rides-table tbody td {
      font-size: .8125rem;
      color: #374151;
      padding: .75rem .5rem;
      border-bottom: 1px solid #f3f4f6 !important;
      vertical-align: middle;
    }
    .rides-table tbody tr:last-child td { border-bottom: none !important; }
    .rides-table tbody tr:hover td { background: #fafafa; }

    .badge-status {
      display: inline-flex;
      align-items: center;
      gap: .28rem;
      font-size: .7rem;
      font-weight: 600;
      padding: .22rem .6rem;
      border-radius: 99px;
      letter-spacing: .02em;
    }
    .badge-status::before {
      content: '';
      width: 5px; height: 5px;
      border-radius: 50%;
      background: currentColor;
      opacity: .7;
    }
    .badge-completed  { background: #f0fdf4; color: #16a34a; }
    .badge-inprogress { background: #fffbeb; color: #d97706; }
    .badge-pending    { background: #eff6ff; color: #2563eb; }
    .badge-cancelled  { background: #fef2f2; color: #dc2626; }

    div.dataTables_wrapper div.dataTables_filter,
    div.dataTables_wrapper div.dataTables_length { display: none; }
    div.dataTables_wrapper div.dataTables_info  { font-size: .75rem; color: #9ca3af; }
    div.dataTables_wrapper div.dataTables_paginate .paginate_button {
      font-size: .78rem !important;
      border-radius: 6px !important;
      padding: .25rem .6rem !important;
    }
    div.dataTables_wrapper div.dataTables_paginate .paginate_button.current {
      background: #f37a20 !important;
      border-color: #f37a20 !important;
      color: #fff !important;
    }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">

    <div class="mb-3">
      <a href="bookRide.php" class="btn-book text-decoration-none">
        Book New Ride <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <div class="row g-3 mb-4">

      <?php
        $stats = [
          ['icon' => 'bi-car-front-fill',    'label' => 'Total Rides',         'value' => $total_ride],
          ['icon' => 'bi-person-badge-fill', 'label' => 'Active Employees',    'value' => $employees],
          ['icon' => 'bi-cash-coin',         'label' => 'Total Expenditure',   'value' => '€' . number_format($expense, 2)],
          ['icon' => 'bi-calendar-event',    'label' => 'Upcoming Rides',      'value' => $pending_rides],
        ];
        foreach ($stats as $s):
      ?>
      <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3 p-3">
            <div class="stat-icon">
              <i class="bi <?= $s['icon'] ?>"></i>
            </div>
            <div>
              <div class="stat-label"><?= $s['label'] ?></div>
              <div class="stat-value"><?= $s['value'] ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <div class="card rides-card border-0 shadow-sm">
      <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
          <div>
            <h6 class="fw-semibold mb-0" style="font-size:.9rem; color:#111827">Recent Rides</h6>
            <span class="d-block mt-1" style="font-size:.75rem; color:#f37a20; font-weight:500">This Month</span>
          </div>
          <input
            type="text"
            id="ridesSearch"
            class="rides-search"
            placeholder="Search rides…"
          />
        </div>

        <div class="table-responsive">
          <table class="table rides-table datatable w-100">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Date & Time</th>
                <th>Cab #</th>
                <th>Cost</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="rides-body">
              <?php foreach ($rides as $r):
                $status = $r['status'] ?? '';
                $badgeClass = match($status) {
                  'Completed'   => 'badge-completed',
                  'In Progress' => 'badge-inprogress',
                  'Pending'     => 'badge-pending',
                  'Cancelled'   => 'badge-cancelled',
                  default       => 'badge-pending',
                };
              ?>
              <tr>
                <td><?= htmlspecialchars($r['employee'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['pickup'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['destination'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['pickupTime'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['vehicle_number'] ?? 'N/A') ?></td>
                <td>€<?= htmlspecialchars($r['fare'] ?? '0') ?></td>
                <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

  <script>
    // Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
    document.addEventListener('click', e => {
      if (window.innerWidth < 768
          && !e.target.closest('.sidebar')
          && !e.target.closest('#sidebarToggle')) {
        document.querySelector('.sidebar')?.classList.remove('active');
      }
    });
  </script>

  <script>
    window.RIDES_REALTIME_CONFIG = {
      cid:           <?= json_encode($cid) ?>,
      supabaseUrl:   <?= json_encode(SUPABASE_URL) ?>,
      supabaseAnonKey: <?= json_encode(SUPABASE_ANON_KEY) ?>,
    };
  </script>
  <script src="js/script.js"></script>
  <script src="js/realtime-rides.js"></script>

</body>
</html>