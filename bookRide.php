<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user      = $_SESSION['user'];
$cid       = $user['cid'];
$cname     = $user['name'];
$pageTitle = 'Book a Ride';
$employees = [];
try {
  $supabase  = new SupabaseClient(true);
  $employees = $supabase->select('corporate_employees', ['cid' => $cid], 'id,name', 'name.asc');
} catch (Throwable $e) {
  $employees = [];
}
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

              <div class="br-field mb-0">
                <label class="br-label" for="employee">Passenger</label>
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

              <div class="br-field mb-0">
                <label class="br-label" for="carType">Car Type</label>
                <select class="form-select" name="carType" id="carType">
                  <option value="Economy">Economy</option>
                  <option value="Economy XL">Economy XL</option>
                  <option value="Business">Business</option>
                  <option value="Business Plus">Business Plus</option>
                  <option value="Limousine">Limousine</option>
                  <option value="Wheelchair accessible">Wheelchair Accessible</option>
                </select>
              </div>

              <hr class="br-divider">

              <div class="br-field mb-0">
                <label class="br-label" for="pickupTime">Date &amp; Time</label>
                <input type="datetime-local" class="form-control" name="pickupTime" id="pickupTime"/>
              </div>

              <hr class="br-divider">

              <div class="br-field mb-0">
                <label class="br-label" for="paymentSource">Payment</label>
                <select class="form-select" name="paymentSource" id="paymentSource">
                  <option value="Cash">Cash</option>
                  <option value="Bill to company">Bill to company</option>
                </select>
              </div>

              <div class="alert d-none mt-4 mb-0" id="rideSummaryBar">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                  <div><strong>Est. Fare:</strong> €<span id="summaryFare">0</span></div>
                  <div><strong>Est. Time:</strong> <span id="summaryDuration">0</span> min</div>
                  <div><strong>Distance:</strong> <span id="summaryDistance">0</span> km</div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="home.php" class="btn-past">
                  <i class="bi bi-clock-history me-1"></i>View Past Rides
                </a>
                <button type="button" class="btn-book" id="bookRideBtn">
                  Book Ride <i class="bi bi-arrow-right ms-1"></i>
                </button>
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
  <script src="js/bookride.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB9ea0A-mjnD5iHfT9X8Dn5YYH4_KZopLI&libraries=places&callback=initBookRideGoogleMaps" async defer></script>

</body>
</html>