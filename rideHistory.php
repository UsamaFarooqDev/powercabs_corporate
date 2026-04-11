<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user      = $_SESSION['user'];
$cid       = $user['cid'];
$pageTitle = 'Ride History';
$rides     = [];
try {
  $supabase = new SupabaseClient(true);
  $rides    = $supabase->select('corporate_rides', ['cid' => $cid], '*', 'date.desc');
} catch (Throwable $e) {
  $rides = [];
}

// Pre-compute quick stats for the summary chips
$total      = count($rides);
$completed  = 0; $inprogress = 0; $cancelled = 0; $totalFare = 0.0;
foreach ($rides as $r) {
  $s = $r['status'] ?? '';
  if ($s === 'Completed')   $completed++;
  if ($s === 'In Progress') $inprogress++;
  if ($s === 'Cancelled')   $cancelled++;
  $totalFare += floatval($r['fare'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate - Ride History</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    body { background: #f5f7fa; }

    /* ── Summary chips ── */
    .rh-chip {
      border-radius: 10px;
      border: 1px solid #eeeff2;
      background: #fff;
      padding: .65rem 1rem;
      display: flex;
      align-items: center;
      gap: .65rem;
      min-width: 130px;
    }
    .rh-chip-icon {
      width: 32px; height: 32px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: var(--fs-body);
    }
    .rh-chip-label { font-size: var(--fs-label); font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; line-height: 1; }
    .rh-chip-value { font-size: 1.05rem; font-weight: 700; color: #111827; line-height: 1.2; margin-top: 2px; }

    /* ── Table card ── */
    .rh-card { border-radius: 16px; border: 1px solid #eeeff2; }

    /* ── Book-ride button ── */
    .btn-book {
      background: #f37a20; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .42rem .95rem;
      display: inline-flex; align-items: center; gap: .35rem;
      text-decoration: none;
      transition: background .15s, box-shadow .15s;
    }
    .btn-book:hover { background: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.3); }

    /* ── Search ── */
    .rh-search {
      font-size: var(--fs-input); border: 1px solid #e5e7eb;
      border-radius: 8px; padding: .38rem .75rem;
      background: #fff; max-width: 210px;
      transition: border-color .15s, box-shadow .15s;
    }
    .rh-search:focus { outline: none; border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12); }

    /* ── Location cell truncation ── */
    .rh-loc { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ── Date & Time cell ── */
    .rh-dt-date { color: #111827; line-height: 1.2; }
    .rh-dt-time { font-size: var(--fs-label); color: #6b7280; line-height: 1.2; margin-top: 2px; }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">

    <!-- ── Summary chips ── -->
    <div class="d-flex flex-wrap gap-2 mb-4">

      <div class="rh-chip shadow-sm">
        <div class="rh-chip-icon" style="background:#fff4eb">
          <i class="bi bi-car-front-fill" style="color:#f37a20"></i>
        </div>
        <div>
          <div class="rh-chip-label">Total</div>
          <div class="rh-chip-value"><?= $total ?></div>
        </div>
      </div>

      <div class="rh-chip shadow-sm">
        <div class="rh-chip-icon" style="background:#f0fdf4">
          <i class="bi bi-check-circle-fill" style="color:#16a34a"></i>
        </div>
        <div>
          <div class="rh-chip-label">Completed</div>
          <div class="rh-chip-value"><?= $completed ?></div>
        </div>
      </div>

      <div class="rh-chip shadow-sm">
        <div class="rh-chip-icon" style="background:#fffbeb">
          <i class="bi bi-arrow-repeat" style="color:#d97706"></i>
        </div>
        <div>
          <div class="rh-chip-label">In Progress</div>
          <div class="rh-chip-value"><?= $inprogress ?></div>
        </div>
      </div>

      <div class="rh-chip shadow-sm">
        <div class="rh-chip-icon" style="background:#fef2f2">
          <i class="bi bi-x-circle-fill" style="color:#dc2626"></i>
        </div>
        <div>
          <div class="rh-chip-label">Cancelled</div>
          <div class="rh-chip-value"><?= $cancelled ?></div>
        </div>
      </div>

      <div class="rh-chip shadow-sm ms-auto">
        <div class="rh-chip-icon" style="background:#f5f3ff">
          <i class="bi bi-cash-coin" style="color:#7c3aed"></i>
        </div>
        <div>
          <div class="rh-chip-label">Total Spend</div>
          <div class="rh-chip-value">€<?= number_format($totalFare, 2) ?></div>
        </div>
      </div>

    </div>

    <!-- ── Table card ── -->
    <div class="card rh-card border-0 shadow-sm">
      <div class="card-body p-4">

        <!-- Card header -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
          <div>
            <h6 class="fw-semibold mb-0" style="font-size:var(--fs-card-heading); color:#111827">All Rides</h6>
            <span class="d-block mt-1" style="font-size:var(--fs-card-sub); color:#9ca3af">
              <?= $total ?> record<?= $total !== 1 ? 's' : '' ?>
            </span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <input type="text" id="ridesSearch" class="rh-search" placeholder="Search rides…"/>
            <a href="bookRide.php" class="btn-book">
              <i class="bi bi-plus-lg"></i> Book Ride
            </a>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table pc-table w-100" id="rhTable">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Date &amp; Time</th>
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
                $badgeIcon = match($status) {
                  'Completed'   => 'bi-check-lg',
                  'In Progress' => 'bi-arrow-repeat',
                  'Pending'     => 'bi-clock',
                  'Cancelled'   => 'bi-x-lg',
                  default       => 'bi-clock',
                };
              ?>
              <tr>
                <td><?= htmlspecialchars($r['employee']    ?? '') ?></td>
                <td><span class="rh-loc" title="<?= htmlspecialchars($r['pickup'] ?? '') ?>"><?= htmlspecialchars($r['pickup'] ?? '') ?></span></td>
                <td><span class="rh-loc" title="<?= htmlspecialchars($r['destination'] ?? '') ?>"><?= htmlspecialchars($r['destination'] ?? '') ?></span></td>
                <td style="white-space:nowrap">
                  <?php
                    $pt = $r['pickupTime'] ?? '';
                    $ts = $pt ? strtotime($pt) : false;
                  ?>
                  <?php if ($ts): ?>
                    <div class="rh-dt-date"><?= date('d-m-y', $ts) ?></div>
                    <div class="rh-dt-time"><?= date('h:i A', $ts) ?></div>
                  <?php else: ?>
                    <?= htmlspecialchars($pt) ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['vehicle_number'] ?? 'N/A') ?></td>
                <td>€<?= htmlspecialchars($r['fare'] ?? '0') ?></td>
                <td><span class="badge-status <?= $badgeClass ?>" title="<?= htmlspecialchars($status) ?>"><i class="bi <?= $badgeIcon ?>"></i></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>

  <!-- Scripts — order preserved -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

  <script>
    // DataTable + custom search
    const rhTable = $('#rhTable').DataTable({ pageLength: 10, order: [] });
    document.getElementById('ridesSearch').addEventListener('input', function () {
      rhTable.search(this.value).draw();
    });

    // Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
    document.addEventListener('click', e => {
      if (window.innerWidth < 768
        && !e.target.closest('.sidebar')
        && !e.target.closest('#sidebarToggle'))
        document.querySelector('.sidebar')?.classList.remove('active');
    });
  </script>

  <script>
    window.RIDES_REALTIME_CONFIG = {
      cid:            <?= json_encode($cid) ?>,
      supabaseUrl:    <?= json_encode(SUPABASE_URL) ?>,
      supabaseAnonKey:<?= json_encode(SUPABASE_ANON_KEY) ?>,
    };
  </script>
  <script src="js/script.js"></script>
  <script src="js/realtime-rides.js"></script>

</body>
</html>