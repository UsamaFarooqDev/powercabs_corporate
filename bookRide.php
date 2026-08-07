<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user      = $_SESSION['user'];
$cid       = $user['cid'];
$cname     = $user['name'] ?? '';
$pageTitle = 'Book a Ride';
$employees = [];
$rideTypes = [];
$billing     = ['billing_model' => 'regular', 'revenue_fixed_fee' => 0.0, 'revenue_tiers' => []];
$creditInfo  = ['is_revenue' => false, 'balance' => 0.0, 'credits_earned' => 0.0, 'credits_used' => 0.0, 'month_label' => '', 'reset_date' => ''];
try {
  $supabase   = new SupabaseClient(true);
  $employees  = $supabase->select('corporate_employees', ['cid' => $cid], 'id,name', 'name.asc');
  $billing    = corporate_billing_config($supabase, $user);
  $creditInfo = compute_credit_balance($supabase, $user, $billing);
} catch (Throwable $e) {
  $employees = [];
}

/**
 * Turn whatever the DB stores in the icon column into renderable HTML.
 * Accepts: inline SVG, http(s)/data/relative image URL, Bootstrap-Icons class
 * (`bi-car-front` or bare `car-front`).
 */
function pc_render_ride_icon($row, string $fallback = 'bi-car-front'): string {
  $candidates = [
    $row['icon']       ?? null,
    $row['icon_class'] ?? null,
    $row['image']      ?? null,
    $row['image_url']  ?? null,
    $row['svg']        ?? null,
  ];
  foreach ($candidates as $val) {
    $v = trim((string)$val);
    if ($v === '') continue;
    if (stripos($v, '<svg') === 0) return $v;
    if (preg_match('#^(https?:)?//|^/[^/]|^data:image#i', $v)) {
      return '<img src="' . htmlspecialchars($v, ENT_QUOTES) . '" alt="" style="height:1.4rem;width:auto;display:block;"/>';
    }
    $cls = stripos($v, 'bi-') === 0 ? $v : 'bi-' . ltrim($v, '-');
    return '<i class="bi ' . htmlspecialchars($cls) . '"></i>';
  }
  return '<i class="bi ' . htmlspecialchars($fallback) . '"></i>';
}

// Fetch ride types from Supabase. Try ordering candidates and tolerate any missing column.
try {
  $rtSupabase = $supabase ?? new SupabaseClient(true);
  $rideTypeRows = [];
  foreach (['sort_order.asc', 'order.asc', 'id.asc'] as $orderTry) {
    try {
      $rideTypeRows = $rtSupabase->select('ride_types', [], '*', $orderTry);
      break;
    } catch (Throwable $orderErr) {
      // cURL timeout (errno 28) → bail; retrying multiplies the latency.
      if (strpos($orderErr->getMessage(), '[28]') !== false) { $rideTypeRows = []; break; }
      /* 4xx → try next ordering */
    }
  }
  foreach ($rideTypeRows as $row) {
    $value = trim((string)($row['value'] ?? $row['name'] ?? $row['type'] ?? ''));
    $name  = trim((string)($row['display_name'] ?? $row['name']  ?? $value));
    $desc  = trim((string)($row['description'] ?? $row['desc']       ?? ''));
    if ($value === '' && $name === '') continue;
    if ($value === '') $value = $name;
    if ($name  === '') $name  = $value;
    $rideTypes[] = [
      'value'    => $value,
      'name'     => $name,
      'iconHtml' => pc_render_ride_icon($row),
      'desc'     => $desc,
    ];
  }
} catch (Throwable $e) {
  $rideTypes = [];
}

// Fallback if Supabase fetch failed or returned no rows
if (empty($rideTypes)) {
  $fallbackTypes = [
    ['value' => 'Economy',              'name' => 'Economy',         'icon' => 'bi-car-front',         'desc' => 'Affordable everyday rides'],
    ['value' => 'Economy XL',           'name' => 'Economy XL',      'icon' => 'bi-truck-front',       'desc' => 'Up to 6 passengers'],
    ['value' => 'Business',             'name' => 'Business',        'icon' => 'bi-briefcase-fill',    'desc' => 'Premium sedans'],
    ['value' => 'Business Plus',        'name' => 'Business Plus',   'icon' => 'bi-gem',               'desc' => 'Top-tier business rides'],
    ['value' => 'Limousine',            'name' => 'Limousine',       'icon' => 'bi-car-front-fill',    'desc' => 'Luxury chauffeur service'],
    ['value' => 'Wheelchair accessible','name' => 'Wheelchair',      'icon' => 'bi-person-wheelchair', 'desc' => 'Step-free access'],
    ['value' => 'Parcel Delivery',      'name' => 'Parcel Delivery', 'icon' => 'bi-box-seam-fill',     'desc' => 'Send packages & parcels'],
  ];
  foreach ($fallbackTypes as $rt) {
    $rideTypes[] = [
      'value'    => $rt['value'],
      'name'     => $rt['name'],
      'iconHtml' => pc_render_ride_icon($rt),
      'desc'     => $rt['desc'],
    ];
  }
}
$defaultRideType = $rideTypes[0]['value'] ?? 'Economy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PowerCabs Corporate - Book a Ride</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    body { background: #f5f7fa; }

    .br-card {
      border-radius: 16px;
      border: 1px solid #eeeff2;
    }

    .br-section-label {
      font-size: var(--fs-label);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: #9ca3af;
    }

    .br-field { display: flex; align-items: center; gap: .85rem; }

    .br-label {
      flex-shrink: 0;
      width: 130px;
      font-size: var(--fs-body);
      font-weight: 500;
      color: #6b7280;
      line-height: 1.3;
    }

    .br-card .form-control,
    .br-card .form-select {
      font-size: var(--fs-input);
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: .42rem .7rem;
      background: #fff;
      color: #111827;
      transition: border-color .15s, box-shadow .15s;
    }
    .br-card .form-control:focus,
    .br-card .form-select:focus {
      border-color: #f37a20;
      box-shadow: 0 0 0 3px rgba(243,122,32,.12);
    }
    .br-card .form-control::placeholder { color: #d1d5db; }

    .br-divider {
      border: none;
      border-top: 1px solid #f3f4f6;
      margin: .85rem 0;
    }

    /* ── Ride-type tile picker ── */
    .br-field-stacked {
      display: block;
    }
    .br-field-stacked > .br-label {
      width: auto;
      display: block;
      margin-bottom: .55rem;
      font-size: var(--fs-label);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #9ca3af;
    }
    .br-ride-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    @media (max-width: 480px) {
      .br-ride-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .br-ride-card {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 10px 6px;
      background: #fff;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      cursor: pointer;
      text-align: center;
      transition: border-color .15s, box-shadow .15s, transform .15s, background .15s;
      user-select: none;
    }
    .br-ride-card:hover {
      border-color: #f3b88e;
      transform: translateY(-1px);
      box-shadow: 0 3px 10px rgba(17,24,39,.05);
    }
    .br-ride-card.is-selected {
      border-color: #f37a20;
      background: #fff7f0;
      box-shadow: 0 0 0 2px rgba(243,122,32,.12);
    }
    .br-ride-card input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
    .br-ride-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: #f3f4f6;
      color: #374151;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      transition: background .15s, color .15s;
    }
    .br-ride-card.is-selected .br-ride-icon {
      background: #fff4eb;
      color: #f37a20;
    }
    .br-ride-name {
      font-size: 13px;
      font-weight: 600;
      color: #111827;
      line-height: 1.2;
    }

    #rideSummaryBar {
      border-radius: 10px;
      border: 1px solid #fde68a;
      background: #fffbeb;
      color: #92400e;
      font-size: var(--fs-body);
      padding: .65rem 1rem;
    }
    #rideSummaryBar strong { color: #78350f; }

    .btn-book {
      background: #f37a20;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: var(--fs-btn);
      font-weight: 600;
      padding: .48rem 1.2rem;
      transition: background .15s, box-shadow .15s;
    }
    .btn-book:hover { background: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.35); }

    .btn-book-outline {
      background: #fff;
      color: #f37a20;
      border: 1.5px solid #f37a20;
      border-radius: 8px;
      font-size: var(--fs-btn);
      font-weight: 600;
      padding: .46rem 1.1rem;
      transition: background .15s, color .15s, box-shadow .15s;
    }
    .btn-book-outline:hover { background: #fff7f0; }
    .btn-book-outline.is-active {
      background: #fff7f0;
      box-shadow: 0 0 0 2px rgba(243,122,32,.12);
    }

    .btn-past {
      font-size: var(--fs-small);
      font-weight: 600;
      color: #f37a20;
      text-decoration: none;
    }
    .btn-past:hover { text-decoration: underline; color: #e06910; }

    .br-map {
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid #eeeff2;
      height: 100%;
      min-height: 420px;
    }
    .br-map iframe { width: 100%; height: 100%; display: block; border: 0; }

    .success-modal .modal-content {
      border-radius: 16px;
      border: 1px solid #eeeff2;
    }
    .success-modal .modal-body { padding: 2.5rem; }

    .success-icon {
      width: 52px; height: 52px;
      border-radius: 50%;
      background: #f0fdf4;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
    }
    .success-icon i { color: #16a34a; font-size: 1.5rem; }

    .success-modal h5 { font-size: var(--fs-card-heading); font-weight: 700; color: #111827; }
    .success-modal p  { font-size: var(--fs-card-sub); color: #6b7280; }

    .btn-modal-primary {
      background: #f37a20; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .45rem 1rem;
      transition: background .15s;
    }
    .btn-modal-primary:hover { background: #e06910; color: #fff; }

    .btn-modal-secondary {
      border: 1px solid #e5e7eb; color: #374151;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 500;
      padding: .45rem 1rem; background: #fff;
      transition: background .15s;
    }
    .btn-modal-secondary:hover { background: #f9fafb; }

    /* ── Passenger type cards ── */
    .br-pax-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }
    .br-pax-card {
      position: relative;
      display: flex; flex-direction: column; align-items: center; gap: 5px;
      padding: 10px 8px;
      border: 1.5px solid #e5e7eb; border-radius: 10px;
      cursor: pointer; text-align: center;
      transition: border-color .15s, box-shadow .15s, background .15s;
      user-select: none;
    }
    .br-pax-card:hover { border-color: #f3b88e; }
    .br-pax-card.is-selected {
      border-color: #f37a20; background: #fff7f0;
      box-shadow: 0 0 0 2px rgba(243,122,32,.12);
    }
    .br-pax-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
    .br-pax-icon { font-size: 1.15rem; color: #6b7280; }
    .br-pax-card.is-selected .br-pax-icon { color: #f37a20; }
    .br-pax-name { font-size: 12px; font-weight: 600; color: #374151; }

  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-5">
    <div class="row g-4 align-items-stretch flex-column-reverse flex-lg-row">

      <div class="col-lg-6">
        <div class="card br-card border-0 shadow-sm h-100">
          <div class="card-body p-5">

            <p class="br-section-label mb-3">Ride details</p>

            <form id="rideForm">

              <!-- Passenger type -->
              <div class="br-field br-field-stacked mb-0">
                <label class="br-label">Passenger Type</label>
                <div class="br-pax-grid" id="passengerTypeGrid">
                  <label class="br-pax-card is-selected" data-pax="employee">
                    <input type="radio" name="passengerType" value="employee" checked/>
                    <span class="br-pax-icon"><i class="bi bi-person-fill"></i></span>
                    <span class="br-pax-name">Employee</span>
                  </label>
                  <label class="br-pax-card" data-pax="guest">
                    <input type="radio" name="passengerType" value="guest"/>
                    <span class="br-pax-icon"><i class="bi bi-person-badge"></i></span>
                    <span class="br-pax-name">Guest</span>
                  </label>
                  <label class="br-pax-card" data-pax="both">
                    <input type="radio" name="passengerType" value="both"/>
                    <span class="br-pax-icon"><i class="bi bi-people-fill"></i></span>
                    <span class="br-pax-name">Both</span>
                  </label>
                </div>
              </div>

              <hr class="br-divider">

              <!-- Employee row (shown for Employee + Both) -->
              <div class="br-field mb-0" id="employeeRow">
                <label class="br-label" for="employee">Employee</label>
                <select class="form-select" name="employee" id="employee">
                  <option value="" disabled selected>Select employee</option>
                  <?php foreach ($employees as $row): ?>
                    <option value="<?= htmlspecialchars($row['id'] ?? '') ?>">
                      <?= htmlspecialchars($row['name'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <input type="hidden" id="employeeName" name="employeeName"/>

              <!-- Guest fields (shown for Guest + Both) -->
              <div id="guestFields" style="display:none">
                <hr class="br-divider">
                <div class="br-field mb-0">
                  <label class="br-label" for="guestName">Guest Name</label>
                  <input type="text" class="form-control" id="guestName" name="guestName"
                         placeholder="Full name of the guest"/>
                </div>
                <hr class="br-divider">
                <div class="br-field mb-0">
                  <label class="br-label" for="guestPhone">Guest Phone</label>
                  <input type="text" class="form-control" id="guestPhone" name="guestPhone"
                         placeholder="Contact number (optional)"/>
                </div>
              </div>

              <hr class="br-divider">

              <div class="br-field mb-0">
                <label class="br-label" for="pickup">Pickup</label>
                <input type="text" class="form-control" name="pickup" id="pickup"
                       placeholder="Enter pickup location" autocomplete="off"/>
                <input type="hidden" name="companyName" id="companyName" value="<?= htmlspecialchars($cname) ?>"/>
              </div>

              <hr class="br-divider">

              <div class="br-field mb-0">
                <label class="br-label" for="dropoff">Drop Off</label>
                <input type="text" class="form-control" name="dropoff" id="dropoff"
                       placeholder="Enter dropoff location" autocomplete="off"/>
              </div>

              <hr class="br-divider">

              <div class="br-field br-field-stacked mb-0">
                <label class="br-label" for="carType">Ride Type</label>
                <div class="br-ride-grid" id="rideTypeGrid">
                  <?php foreach ($rideTypes as $i => $rt):
                      $checked = $i === 0 ? 'checked' : '';
                  ?>
                    <label class="br-ride-card<?= $checked ? ' is-selected' : '' ?>"
                           data-value="<?= htmlspecialchars($rt['value']) ?>"
                           title="<?= htmlspecialchars($rt['desc']) ?>">
                      <input type="radio" name="rideTypeRadio" value="<?= htmlspecialchars($rt['value']) ?>" <?= $checked ?>/>
                      <span class="br-ride-icon"><?= $rt['iconHtml'] ?></span>
                      <span class="br-ride-name"><?= htmlspecialchars($rt['name']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <input type="hidden" name="carType" id="carType" value="<?= htmlspecialchars($defaultRideType) ?>"/>
              </div>

              <hr class="br-divider">

              <div class="br-field mb-0" id="scheduleDateTimeRow" style="display:none">
                <label class="br-label" for="pickupTime">Pickup Date &amp; Time</label>
                <input type="datetime-local" class="form-control" name="pickupTime" id="pickupTime"/>
              </div>
              <div id="scheduleDateTimeHint" style="display:none;font-size:12px;color:#9ca3af;margin:-2px 0 0 138px;">
                Pick a future date &amp; time, then confirm below.
              </div>

              <hr class="br-divider" id="scheduleDateTimeDivider" style="display:none">

              <div class="br-field mb-0">
                <label class="br-label" for="paymentSource">Payment</label>
                <select class="form-select" name="paymentSource" id="paymentSource">
                  <option value="Cash">Cash</option>
                  <option value="Bill to company">Bill to company</option>
                  <?php if ($creditInfo['is_revenue'] && $creditInfo['balance'] > 0): ?>
                  <option value="corporate_credit" id="creditPaymentOption">
                    Use Credits (€<?= number_format($creditInfo['balance'], 2) ?> available)
                  </option>
                  <?php endif; ?>
                </select>
                <?php if ($creditInfo['is_revenue']): ?>
                <div id="creditBalanceHint" style="margin-top:8px;padding:10px 14px;border-radius:8px;font-size:12.5px;<?= $creditInfo['balance'] > 0 ? 'background:#f0fdf4;border:1px solid #86efac;color:#166534' : 'background:#fafafa;border:1px solid #e5e7eb;color:#6b7280' ?>">
                  <i class="bi bi-gift-fill me-1" style="color:<?= $creditInfo['balance'] > 0 ? '#16a34a' : '#9ca3af' ?>"></i>
                  <?php if ($creditInfo['balance'] > 0): ?>
                    <strong>€<?= number_format($creditInfo['balance'], 2) ?></strong> credits available — resets <?= htmlspecialchars($creditInfo['reset_date']) ?>
                  <?php else: ?>
                    No credits available this month yet
                    <?php if ($creditInfo['credits_earned'] > 0): ?>
                      (€<?= number_format($creditInfo['credits_earned'], 2) ?> earned, €<?= number_format($creditInfo['credits_used'], 2) ?> used)
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                <div id="creditInsufficientHint" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:12px;background:#fef2f2;border:1px solid #fca5a5;color:#dc2626">
                  <i class="bi bi-exclamation-circle me-1"></i>Insufficient credits for this ride — please choose another payment method.
                </div>
                <?php endif; ?>
              </div>

              <div class="alert d-none mt-4 mb-0" id="rideSummaryBar">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                  <div><strong>Est. Fare:</strong> €<span id="summaryFare">0</span></div>
                  <div><strong>Est. Time:</strong> <span id="summaryDuration">0</span> min</div>
                  <div><strong>Distance:</strong> <span id="summaryDistance">0</span> km</div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <a href="home.php" class="btn-past">
                  <i class="bi bi-clock-history me-1"></i>View Past Rides
                </a>
                <div class="d-flex gap-2">
                  <button type="button" class="btn-book-outline" id="scheduleLaterBtn">
                    <i class="bi bi-calendar-check me-1"></i><span id="scheduleLaterBtnLabel">Schedule for Later</span>
                  </button>
                  <button type="button" class="btn-book" id="bookNowBtn">
                    <span class="btn-label"><i class="bi bi-lightning-charge-fill me-1"></i>Book Now</span>
                  </button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="br-map shadow-sm">
          <iframe id="mapFrame" title="Map"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2381.681797268639!2d-6.260309684349727!3d53.3498051799791!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48670e9d6c92d7c3%3A0x2776a88dddc5ea5f!2sDublin!5e0!3m2!1sen!2sie!4v1679245534743!5m2!1sen!2sie"
            allowfullscreen loading="lazy">
          </iframe>
        </div>
      </div>

    </div>
  </main>

  <div class="modal fade success-modal" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
      <div class="modal-content">
        <div class="modal-body text-center">

          <div class="success-icon">
            <i class="bi bi-check-lg"></i>
          </div>

          <h5 class="mb-2">Ride Requested Successfully</h5>
          <p class="mb-0">Your ride has been submitted. Check your email shortly for confirmation and driver details.</p>

          <div class="d-flex justify-content-center gap-2 mt-4">
            <button type="button" class="btn-modal-primary" data-bs-dismiss="modal">
              Book Another
            </button>
            <a href="home.php" class="btn-modal-secondary text-decoration-none">
              Back to Dashboard
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.PC_BILLING = <?= json_encode([
      'model'         => $billing['billing_model'],
      'fixedFee'      => $billing['revenue_fixed_fee'],
      'creditBalance' => $creditInfo['balance'],
      'creditsEarned' => $creditInfo['credits_earned'],
      'creditsUsed'   => $creditInfo['credits_used'],
      'monthLabel'    => $creditInfo['month_label'],
      'resetDate'     => $creditInfo['reset_date'],
      'isRevenue'     => $creditInfo['is_revenue'],
    ]) ?>;
  </script>
  <script src="js/bookride.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB9ea0A-mjnD5iHfT9X8Dn5YYH4_KZopLI&libraries=places&callback=initBookRideGoogleMaps" async defer></script>

</body>
</html>