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
  $rides    = $supabase->select('corporate_rides', ['cid' => $cid], '*', 'id.desc');
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

    /* ── Cancel-ride action ── */
    .btn-cancel-ride {
      font-size: var(--fs-small); font-weight: 500; color: #dc2626;
      border: 1px solid #fecaca; border-radius: 7px;
      padding: .28rem .65rem; background: #fff;
      display: inline-flex; align-items: center; gap: .35rem;
      transition: background .12s, border-color .12s, box-shadow .12s;
    }
    .btn-cancel-ride:hover {
      background: #fef2f2; border-color: #fca5a5;
      box-shadow: 0 1px 4px rgba(220,38,38,.08);
    }
    .btn-cancel-ride i { font-size: .78rem; }
    .rh-status-final {
      font-size: var(--fs-small); color: #94a3b8; font-style: italic;
    }

    /* ── Cancel-ride confirmation modal ── */
    .pc-modal .modal-content {
      border-radius: 16px; border: 1px solid #e2e8f0;
      box-shadow: 0 24px 60px rgba(15,23,42,.18);
    }
    .cancel-icon-wrap {
      width: 64px; height: 64px; border-radius: 50%;
      background: #fef2f2;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto;
      box-shadow: 0 0 0 8px #fff5f5;
    }
    .cancel-icon-wrap i { color: #dc2626; font-size: 1.55rem; }
    .cancel-title {
      font-size: 1.1rem; font-weight: 700; color: #0f172a;
      letter-spacing: -.01em;
    }
    .cancel-sub { font-size: .85rem; color: #64748b; line-height: 1.5; }

    .ride-summary {
      background: #f8fafc; border: 1px solid #e2e8f0;
      border-radius: 10px; padding: .85rem 1rem;
      font-size: .85rem; color: #475569;
      line-height: 1.55; text-align: left;
    }
    .ride-summary .rs-name {
      font-size: .92rem; font-weight: 600; color: #0f172a;
      display: flex; align-items: center; gap: 8px;
      margin-bottom: 6px;
    }
    .ride-summary .rs-line {
      display: flex; align-items: flex-start; gap: 8px;
      font-size: .82rem; color: #475569;
      padding: 2px 0;
    }
    .ride-summary .rs-line i {
      color: #94a3b8; font-size: .82rem; width: 14px; margin-top: 3px;
      flex-shrink: 0;
    }
    .ride-summary .rs-line span { word-break: break-word; }

    .btn-pc-danger {
      background: #dc2626; color: #fff; border: 1px solid #dc2626;
      border-radius: 9px; font-size: var(--fs-btn); font-weight: 600;
      padding: .55rem 1.1rem;
      display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
      transition: background .15s, border-color .15s, box-shadow .15s;
      cursor: pointer;
    }
    .btn-pc-danger:hover {
      background: #b91c1c; border-color: #b91c1c; color: #fff;
      box-shadow: 0 6px 18px rgba(220,38,38,.25);
    }
    .btn-pc-danger:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }

    .btn-pc-cancel {
      background: #fff; color: #334155; border: 1px solid #e2e8f0;
      border-radius: 9px; font-size: var(--fs-btn); font-weight: 500;
      padding: .55rem 1.1rem; transition: background .15s;
      display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
    }
    .btn-pc-cancel:hover { background: #f1f5f9; }

    /* ── Cancel-reason picker ── */
    .cancel-reason-label {
      font-size: 11px; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase;
      color: #475569; margin: 4px 0 8px;
      text-align: left;
    }
    .cancel-reason-list {
      display: flex; flex-direction: column; gap: 6px;
      max-height: 200px; overflow-y: auto;
      padding: 2px 2px 2px 2px;
    }
    .cancel-reason-opt {
      position: relative;
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      background: #fff;
      cursor: pointer;
      font-size: .85rem; color: #334155;
      text-align: left;
      transition: border-color .12s, background .12s, box-shadow .12s;
    }
    .cancel-reason-opt input[type="radio"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .cancel-reason-opt:hover { background: #f8fafc; border-color: #cbd5e1; }
    .cancel-reason-opt.is-selected {
      border-color: #dc2626;
      background: #fef2f2;
      color: #991b1b;
      box-shadow: 0 0 0 3px rgba(220,38,38,.08);
      font-weight: 600;
    }
    .cancel-reason-opt .ic {
      width: 18px; height: 18px; border-radius: 50%;
      border: 2px solid #cbd5e1;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: border-color .12s, background .12s;
    }
    .cancel-reason-opt.is-selected .ic {
      border-color: #dc2626; background: #dc2626;
    }
    .cancel-reason-opt.is-selected .ic::after {
      content: ''; width: 7px; height: 7px; border-radius: 50%;
      background: #fff;
    }
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
          <table class="table pc-table datatable w-100" id="rhTable">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Date &amp; Time</th>
                <th>Cab #</th>
                <th>Cost</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
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
                <td class="text-end">
                  <?php if ($status === 'Completed' || $status === 'Cancelled'): ?>
                    <span class="rh-status-final">—</span>
                  <?php else: ?>
                    <button type="button" class="btn-cancel-ride"
                            data-ride-id="<?= htmlspecialchars((string)($r['id'] ?? '')) ?>"
                            data-employee="<?= htmlspecialchars($r['employee'] ?? '') ?>"
                            data-pickup="<?= htmlspecialchars($r['pickup'] ?? '') ?>"
                            data-destination="<?= htmlspecialchars($r['destination'] ?? '') ?>"
                            data-status="<?= htmlspecialchars($status) ?>"
                            title="Cancel this ride">
                      <i class="bi bi-x-circle"></i> Cancel
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>

  <!-- ── Cancel-Ride Confirmation Modal ── -->
  <div class="modal fade pc-modal" id="cancelRideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
      <div class="modal-content">

        <div class="modal-body p-4 text-center">
          <div class="cancel-icon-wrap mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="cancel-title mb-2">Cancel this ride?</div>
          <div class="cancel-sub mb-3">
            The dispatcher will be notified and this ride will be marked as <strong>Cancelled</strong>.
            This action cannot be undone.
          </div>

          <div class="ride-summary">
            <div class="rs-name">
              <i class="bi bi-person-fill text-muted"></i>
              <span id="cancelRideEmployee">—</span>
            </div>
            <div class="rs-line"><i class="bi bi-geo-alt-fill"></i><span id="cancelRidePickup">—</span></div>
            <div class="rs-line"><i class="bi bi-flag-fill"></i><span id="cancelRideDest">—</span></div>
          </div>

          <div class="cancel-reason-label mt-3">Why are you cancelling?</div>
          <div class="cancel-reason-list" id="cancelReasonList">
            <?php
              $cancelReasons = [
                'Waiting for long time',
                'Unable to contact driver',
                'Driver denied to go to destination',
                'Driver denied to come to pickup',
                'Wrong address shown',
                'The price is not reasonable',
              ];
              foreach ($cancelReasons as $reason): ?>
              <label class="cancel-reason-opt" data-reason="<?= htmlspecialchars($reason) ?>">
                <input type="radio" name="cancelReason" value="<?= htmlspecialchars($reason) ?>"/>
                <span class="ic"></span>
                <span class="txt"><?= htmlspecialchars($reason) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <input type="hidden" id="cancelRideId"/>
        </div>

        <div class="d-flex gap-2 px-4 pb-4">
          <button type="button" class="btn-pc-cancel flex-fill" data-bs-dismiss="modal">
            Keep ride
          </button>
          <button type="button" id="cancelRideConfirm" class="btn-pc-danger flex-fill" disabled>
            <i class="bi bi-x-circle"></i> Yes, cancel ride
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- Scripts — order preserved -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

  <script>
    // DataTable is initialised by js/script.js (targets .datatable class).
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

    // ── Hook: realtime-rides.js renders the Actions cell on this page ──
    function escapeAttr(s) {
      return String(s == null ? '' : s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    window.rideRowExtraCells = function (ride) {
      const status = (ride.status || '').toString();
      // Final statuses can't be cancelled — show a placeholder dash.
      if (status === 'Completed' || status === 'Cancelled') {
        return `<td class="text-end"><span class="rh-status-final">—</span></td>`;
      }
      return `
        <td class="text-end">
          <button type="button" class="btn-cancel-ride"
                  data-ride-id="${escapeAttr(ride.id)}"
                  data-employee="${escapeAttr(ride.employee || '')}"
                  data-pickup="${escapeAttr(ride.pickup || '')}"
                  data-destination="${escapeAttr(ride.destination || '')}"
                  data-status="${escapeAttr(status)}"
                  title="Cancel this ride">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
        </td>`;
    };

    // ── Cancel ride: open confirmation modal ──
    const cancelModalEl = document.getElementById('cancelRideModal');
    const cancelModal   = new bootstrap.Modal(cancelModalEl);
    const cancelConfirm = document.getElementById('cancelRideConfirm');
    const cancelReasonList = document.getElementById('cancelReasonList');

    function clearCancelReason() {
      cancelReasonList.querySelectorAll('.cancel-reason-opt').forEach(o => o.classList.remove('is-selected'));
      cancelReasonList.querySelectorAll('input[type="radio"]').forEach(r => { r.checked = false; });
      cancelConfirm.disabled = true;
    }

    cancelReasonList.addEventListener('click', (e) => {
      const opt = e.target.closest('.cancel-reason-opt');
      if (!opt) return;
      cancelReasonList.querySelectorAll('.cancel-reason-opt').forEach(o => o.classList.remove('is-selected'));
      opt.classList.add('is-selected');
      const radio = opt.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      cancelConfirm.disabled = false;   // unlock confirm only after a reason is chosen
    });

    // Reset reason every time the modal opens (and on close too).
    cancelModalEl.addEventListener('show.bs.modal',   clearCancelReason);
    cancelModalEl.addEventListener('hidden.bs.modal', clearCancelReason);

    document.getElementById('rhTable').addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-cancel-ride');
      if (!btn) return;
      document.getElementById('cancelRideId').value           = btn.dataset.rideId || '';
      document.getElementById('cancelRideEmployee').textContent = btn.dataset.employee    || '—';
      document.getElementById('cancelRidePickup').textContent   = btn.dataset.pickup      || '—';
      document.getElementById('cancelRideDest').textContent     = btn.dataset.destination || '—';
      cancelModal.show();
    });

    // ── Cancel ride: confirm ──
    cancelConfirm.addEventListener('click', function () {
      const rideId = document.getElementById('cancelRideId').value;
      const reason = cancelReasonList.querySelector('input[name="cancelReason"]:checked')?.value || '';
      if (!rideId) return;
      if (!reason) {
        if (typeof showToast === 'function') showToast('Please pick a reason for cancellation.', 'error');
        return;
      }
      const orig = cancelConfirm.innerHTML;
      cancelConfirm.disabled  = true;
      cancelConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Cancelling…';

      const fd = new FormData();
      fd.append('ride_id', rideId);
      fd.append('status',  'Cancelled');
      fd.append('cancel_reason', reason);

      fetch('php/edit_ride.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
          cancelConfirm.disabled  = false;
          cancelConfirm.innerHTML = orig;
          cancelModal.hide();
          if (typeof showToast === 'function') {
            showToast(
              data.success ? 'Ride cancelled.' : (data.message || 'Failed to cancel ride'),
              data.success ? 'success' : 'error'
            );
          }
          if (data.success && typeof window.refreshCorporateRidesDashboard === 'function') {
            window.refreshCorporateRidesDashboard();
          } else if (data.success) {
            setTimeout(() => location.reload(), 1000);
          }
        })
        .catch(() => {
          cancelConfirm.disabled  = false;
          cancelConfirm.innerHTML = orig;
          if (typeof showToast === 'function') {
            showToast('Network error. Please try again.', 'error');
          }
        });
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