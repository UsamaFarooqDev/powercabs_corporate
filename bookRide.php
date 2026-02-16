<?php 
session_start();
@include('php/connection.php');
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}
$user = $_SESSION['user'];
$cid = $user['cid'];
$cname= $user['name'];

$sql = "select * from corporate_employees where cid = '$cid'";
$result = mysqli_query($conn,$sql);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book Ride</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="global.css" />
    <style>
    /* Custom Styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
    }

    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      padding-top: 70px;
      overflow-y: auto;
    }

    .sidebar a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 15px 20px;
      transition: background-color 0.3s;
    }

    .sidebar a:hover {
      background-color: #495057;
    }

    .sidebar a.active {
      background-color: #f37a20;
      border-right: 4px solid #f37a20;
    }

    .main-content {
      margin-left: 250px;
      padding: 20px;
    }

    .header {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      left: 250px;
      right: 0;
      z-index: 1000;
      padding: 15px 20px;
    }

    .header h4 {
      margin: 0;
      font-weight: 600;
      color: #343a40;
    }

    .card-stats {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .card-stats .card-body {
      display: flex;
      align-items: center;
    }

    .card-stats .icon-box {
      width: 65px;
      height: 65px;
      background-color: #e8e8e8;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      margin-right: 15px;
    }

    .card-stats .icon-box i {
      color: #f37a20;
      font-size: 28px;
    }

    .table-responsive {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table thead th {
      color: #969696;
      font-size: 14px;
      font-weight: 500;
      border-bottom: 1px solid #e5e5e5;
    }

    .table tbody td {
      font-size: 14px;
      font-weight: 500;
      vertical-align: middle;
    }

    .status-completed {
      color: green;
    }

    .status-pending {
      color: red;
    }
  </style>
  </head>
  <body>
    <!-- Navbar -->
    <!--<nav class="navbar navbar-expand-lg navbar-light bg-white d-flex align-items-center justify-content-between p-3 " style="padding-left:10vw">-->
    <!--  <div class="d-flex align-items-center">-->
    <!--    <button class="navbar-toggler me-2 d-md-none btn btn-light border-none" style="padding: 4px;" type="button" id="sidebarToggle">-->
    <!--      <span class="navbar-toggler-icon"></span>-->
    <!--    </button>-->
    <!--    <h1 class="navbar-title m-0 fw-bold" id="pageTitle">Book a Ride</h1>-->
    <!--  </div>-->
    <!--  <div class="d-flex align-items-center">-->
    <!--    <div class="dropdown" id="avatarDropdown">-->
    <!--      <img src="assets/profile.svg" alt="Profile" class="rounded-circle profile-img" style="width: 50px; height: 50px; cursor: pointer"/>-->
    <!--    </div>-->
    <!--  </div>-->
    <!--</nav>-->

    <!-- Sidebar -->
    <div class="sidebar text-white p-3">
      <?php @require('modules/sidebar.php'); ?>
    </div>
<div class="header">
    <h4>Book Ride</h4>
  </div>

    <div class="main-content p-4" style="background: #f5f7fa">
      <div class="card-body">
        <div class="p-2 p-sm-5">
          <div class="row mb-4 flex-column-reverse flex-lg-row justify-content-center align-items-center">
            
            <!-- Form Column -->
            <div class="col-lg-6 col-md-12">
              <div class="alert alert-info mb-4 d-none" id="rideInfoAlert">
                <div class="d-flex justify-content-between">
                  <div><strong>Distance:</strong> <span id="distance">0</span> km</div>
                  <div><strong>Duration :</strong> <span id="duration">0</span> min</div>
                  <div><strong>Fare:</strong> €<span id="fare">0</span></div>
                </div>
              </div>

              <form id="rideForm">
                <!-- Passenger -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Passenger</label>
                  <select class="form-select" name="employee" id="employee" style="flex:1">
                    <option value="" disabled selected>Select employee</option>
                    <?php while($row = mysqli_fetch_array($result)) {
                      echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                    } ?>
                  </select>
                </div>
                <input type="hidden" id="employeeName" name="employeeName" />

                <!-- Pickup -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Pickup</label>
                  <input type="text" class="form-control" name="pickup" id="pickup" placeholder="Enter pickup location"/>
                  <input type="hidden" name="companyName" id="companyName" value="<?php echo $cname;?>"/>
                </div>

                <!-- Dropoff -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Drop Off</label>
                  <input type="text" class="form-control" name="dropoff" id="dropoff" placeholder="Enter dropoff location"/>
                </div>

                <!-- Car Type -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Car Type</label>
                  <select class="form-select" name="carType" id="carType">
                    <option value="Business">Business</option>
                    <option value="Economy">Economy</option>
                    <option value="Luxury">Luxury</option>
                  </select>
                </div>

                <!-- Pickup Time -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Pickup Date & Time</label>
                  <input type="datetime-local" class="form-control" name="pickupTime" id="pickupTime"/>
                </div>

                <!-- Payment -->
                <div class="mb-4 d-flex align-items-center">
                  <label class="form-label me-2" style="min-width: 130px; color: #969696">Payment</label>
                  <select class="form-select" name="paymentSource" id="paymentSource">
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Bill to company">Bill to company</option>
                  </select>
                </div>

                <!-- Stripe Payment Section (hidden until Credit Card is selected) -->
               <!-- Stripe Payment Section -->
<div id="paymentSection" class="mb-4 d-none">
  <label class="form-label" style="color:#969696">Card Payment</label>
  <div id="payment-element"><!-- Stripe injects card fields here --></div>
</div>


                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center p-3">
                  <a href="home.php" style="color:#f37a20; font-weight:700; text-decoration:none;">View Past Rides</a>
                  <button type="button" class="btn" style="color:#fff; background:#f37a20" id="bookRideBtn">
                    Book Ride Now
                  </button>
                </div>
              </form>
            </div>

            <!-- Map Column -->
            <div class="col-lg-6 col-md-12 mb-3 p-4">
              <div class="map-container" style="height: 450px; width: 100%">
                <iframe id="mapFrame" title="Google Map"
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2381.681797268639!2d-6.260309684349727!3d53.3498051799791!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48670e9d6c92d7c3%3A0x2776a88dddc5ea5f!2sDublin!5e0!3m2!1sen!2sie!4v1679245534743!5m2!1sen!2sie"
                  style="border:0; height:100%; width:100%" allowfullscreen="" loading="lazy">
                </iframe>
              </div>
            </div>
          </div>
        </div>

        <!-- Success Modal -->
        <div class="modal fade w-100 h-100" id="successModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" style="max-width:680px;">
            <div class="modal-content">
              <div class="modal-body text-center p-5">
                <img src="assets/success.svg" alt="success" style="width:50px; height:50px;"/>
                <h3 class="mt-3">The request has been sent Successfully.</h3>
                <p>Your ride request has been successfully sent! Please check your email soon for further details.</p>
                <div class="d-flex justify-content-center gap-3 mt-4">
                  <button type="button" class="btn" style="background:#f37a20; color:#fff" data-bs-dismiss="modal">Book Another Ride</button>
                  <a class="btn" href="home.php" style="border:1px solid black">Back to Dashboard</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB9ea0A-mjnD5iHfT9X8Dn5YYH4_KZopLI&libraries=places"></script>
    <script src="https://js.stripe.com/v3/"></script> <!-- Stripe -->
    <script src="js/bookride.js"></script>
  </body>
</html>
