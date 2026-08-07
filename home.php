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

function normalizeCorporateRide(array $row): array {
  $statusRaw = (string)($row['status'] ?? '');
  $status = trim($statusRaw) === '' ? 'Pending' : ucwords(str_replace('_', ' ', strtolower($statusRaw)));
  $source = strtolower(trim((string)($row['source'] ?? '')));
  $category = $source === 'corporate meet_and_greet' ? 'Meet & Greet' : 'Corporate';
  return [
    'id' => (string)($row['id'] ?? ''),
    'employee' => $row['employee'] ?? '',
    'pickup' => $row['pickup_addr'] ?? '',
    'destination' => $row['dest_addr'] ?? '',
    'pickupTime' => $row['enroute_at'] ?? ($row['created_at'] ?? ''),
    'vehicle_number' => $row['vehicle_number'] ?? 'N/A',
    'fare' => $row['fare_eur'] ?? 0,
    'status' => $status,
    'category' => $category,
  ];
}
function isCorporateSource(array $row): bool {
  $source = strtolower(trim((string)($row['source'] ?? '')));
  return $source === 'corporate' || $source === 'corporate meet_and_greet';
}

$creditInfo = ['is_revenue' => false, 'balance' => 0.0, 'credits_earned' => 0.0, 'credits_used' => 0.0, 'month_label' => '', 'reset_date' => ''];

try {
  $supabase = new SupabaseClient(true);
  $rows     = $supabase->select('rides', ['cid' => $cid], '*', 'created_at.desc', 100);
  $rows     = array_values(array_filter($rows, 'isCorporateSource'));
  $rides    = array_map('normalizeCorporateRide', $rows);
  $total_ride = count($rides);
  foreach ($rides as $row) {
    if (in_array($row['status'] ?? '', ['Pending', 'Scheduled'])) $pending_rides++;
    if (($row['status'] ?? '') === 'Completed') $expense += floatval($row['fare'] ?? 0);
  }
  try {
    $empRows   = $supabase->select('corporate_employees', ['cid' => $cid], '*');
    $employees = count($empRows);
  } catch (Throwable $empErr) {
    $employees = 0;
  }
  try {
    $billing    = corporate_billing_config($supabase, $user);
    $creditInfo = compute_credit_balance($supabase, $user, $billing);
  } catch (Throwable $creditErr) {
    // silently ignore — credit balance is non-critical
  }
} catch (Throwable $e) {
  $rides = [];
  $ridesFetchError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title> PowerCabs Corporate - Dashboard</title>

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
    .stat-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.07) !important; }
    .stat-card .card-body { padding: 1.35rem 1.25rem !important; }

    .stat-icon {
      width: 56px; height: 56px;
      border-radius: 14px;
      background: #fff4eb;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .stat-icon i { color: #f37a20; font-size: 1.45rem; }

    .stat-label { font-size: var(--fs-label); font-weight: 500; color: #9ca3af; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 1.55rem; font-weight: 700; color: #111827; line-height: 1.1; }



    .rides-card { border-radius: 16px; border: 1px solid #eeeff2; }

    .rides-search {
      font-size: var(--fs-input);
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: .38rem .75rem;
      background: #fff;
      max-width: 610px;
      outline: none;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .rides-search:focus { border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12); }

  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">

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

      <?php if ($creditInfo['is_revenue']): ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-left:4px solid #16a34a!important">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:#16a34a;display:flex;align-items:center;justify-content:center">
                  <i class="bi bi-gift-fill" style="color:#fff;font-size:1.25rem"></i>
                </div>
                <div>
                  <div style="font-size:11px;font-weight:600;color:#15803d;text-transform:uppercase;letter-spacing:.06em">Loyalty Credits — <?= htmlspecialchars($creditInfo['month_label']) ?></div>
                  <div style="font-size:1.4rem;font-weight:800;color:#15803d;line-height:1.1">€<?= number_format($creditInfo['balance'], 2) ?> available</div>
                </div>
              </div>
              <div class="d-flex gap-4 flex-wrap">
                <div style="text-align:center">
                  <div style="font-size:11px;color:#166534">Earned this month</div>
                  <div style="font-size:1rem;font-weight:700;color:#15803d">€<?= number_format($creditInfo['credits_earned'], 2) ?></div>
                </div>
                <?php if ($creditInfo['credits_used'] > 0): ?>
                <div style="text-align:center">
                  <div style="font-size:11px;color:#166534">Used this month</div>
                  <div style="font-size:1rem;font-weight:700;color:#dc2626">-€<?= number_format($creditInfo['credits_used'], 2) ?></div>
                </div>
                <?php endif; ?>
                <div style="text-align:center">
                  <div style="font-size:11px;color:#166534">Resets on</div>
                  <div style="font-size:.9rem;font-weight:600;color:#15803d"><?= htmlspecialchars($creditInfo['reset_date']) ?></div>
                </div>
              </div>
              <a href="bookRide.php" class="btn btn-sm" style="background:#16a34a;color:#fff;border-radius:8px;padding:8px 18px;font-weight:600;white-space:nowrap">
                <i class="bi bi-lightning-charge-fill me-1"></i>Use Credits
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <div class="card rides-card border-0 shadow-sm">
      <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
          <div>
            <h6 class="fw-semibold mb-0" style="font-size:var(--fs-card-heading); color:#111827">Recent Rides</h6>
            <span class="d-block mt-1" style="font-size:var(--fs-card-sub); color:#f37a20; font-weight:500">This Month</span>
          </div>
          <input
            type="text"
            id="ridesSearch"
            class="rides-search"
            placeholder="Search rides…"
          />
        </div>

        <div class="table-responsive">
          <table class="table pc-table datatable w-100">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Date & Time</th>
                <th>Cab #</th>
                <th>Cost</th>
                <th>Category</th>
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
                  'Scheduled'   => 'badge-scheduled',
                  'Cancelled'   => 'badge-cancelled',
                  default       => 'badge-pending',
                };
                $badgeIcon = match($status) {
                  'Completed'   => 'bi-check-lg',
                  'In Progress' => 'bi-arrow-repeat',
                  'Pending'     => 'bi-clock',
                  'Scheduled'   => 'bi-calendar-check',
                  'Cancelled'   => 'bi-x-lg',
                  default       => 'bi-clock',
                };
              ?>
              <tr>
                <td><?= htmlspecialchars($r['employee'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['pickup'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['destination'] ?? '') ?></td>
                <td style="white-space:nowrap">
                  <?php
                    $pt = $r['pickupTime'] ?? '';
                    $ts = $pt ? strtotime($pt) : false;
                  ?>
                  <?php if ($ts): ?>
                    <div style="color:#111827;line-height:1.2;"><?= date('d-m-y', $ts) ?></div>
                    <div style="font-size:var(--fs-label);color:#6b7280;line-height:1.2;margin-top:2px;"><?= date('h:i A', $ts) ?></div>
                  <?php else: ?>
                    <?= htmlspecialchars($pt) ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['vehicle_number'] ?? 'N/A') ?></td>
                <td>€<?= htmlspecialchars($r['fare'] ?? '0') ?></td>
                <td>
                  <?php if (($r['category'] ?? '') === 'Meet & Greet'): ?>
                    <span style="font-size:11px;font-weight:600;color:#7c3aed;background:#f5f3ff;border-radius:6px;padding:2px 7px;white-space:nowrap">M&amp;G</span>
                  <?php else: ?>
                    <span style="font-size:11px;font-weight:600;color:#0369a1;background:#e0f2fe;border-radius:6px;padding:2px 7px;white-space:nowrap">Corporate</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge-status <?= $badgeClass ?>" title="<?= htmlspecialchars($status) ?>"><i class="bi <?= $badgeIcon ?>"></i></span></td>
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
  <script>
    // Silently cancel past pending/scheduled rides on page load
    fetch('php/cleanup_rides.php', { credentials: 'same-origin' }).catch(() => {});
  </script>

</body>
</html>