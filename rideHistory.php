<?php 

session_start();
@include('php/connection.php');
if (!isset($_SESSION['user'])) {
  header("Location: index.php"); // Redirect to login if the user is not logged in
  exit;
}
$user = $_SESSION['user'];
$cid = $user['cid'];
$emp = "select * from corporate_rides where cid = '$cid'";
$result2 = mysqli_query($conn,$emp);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
    <!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="global.css" />
  </head>
  <body>

    <nav
      class="navbar navbar-expand-lg navbar-light bg-white d-flex align-items-center justify-content-between p-3"
    >
      <div class="d-flex align-items-center">
        <button
          class="navbar-toggler me-2 d-md-none btn btn-light border-none"
          style="padding: 4px; margin-left: 0px"
          type="button"
          id="sidebarToggle"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-title m-0 fw-bold ms-lg-230" id="pageTitle">
          Ride History
        </h1>
      </div>

      <div class="d-flex align-items-center">
        <!-- <div class="me-4 d-none d-lg-inline-block">
          <input
            type="text"
            placeholder="Search for something"
            class="form-control"
            style="
              border-radius: 50px;
              height: 50px;
              width: 200px;
              background: #f2f6fd;
              border: none;
              padding: 0 20px;
              font-size: 0.9rem;
              color: #333;
            "
          />
        </div> -->

        <div class="d-flex align-items-center ms-3">
          <!-- <button
            class="btn rounded-circle d-none d-lg-inline-block me-4"
            style="width: 50px; height: 50px; background: #f2f6fd"
          >
            <i
              class="bi bi-gear-fill"
              style="color: #969696; font-size: 1.45rem"
            ></i>
          </button>
          <button
            class="btn rounded-circle d-none d-lg-inline-block me-4"
            style="width: 50px; height: 50px; background: #f2f6fd"
          >
            <i
              class="bi bi-bell-fill"
              style="color: #f37a20; font-size: 1.45rem"
            ></i>
          </button> -->

          <div class="dropdown" id="avatarDropdown">
            <img
              src="assets/profile.svg"
              alt="Profile"
              class="rounded-circle profile-img"
              style="width: 50px; height: 50px; cursor: pointer"
            />
          </div>
        </div>
      </div>
    </nav>

    <div class="sidebar text-white p-3">
    <?php 
        @require('modules/sidebar.php');
      ?>
    </div>

    <main class="main-content p-4" style="background: #f5f7fa">
      <div class="card shadow border-0" style='border-radius: 25px;'>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap p-4">
              <!-- <div class="d-flex flex-column flex-md-row align-items-md-center">
                <input
                  type="text"
                  placeholder="Search"
                  class="form-control flex-grow-1 me-md-3 mb-3 mb-md-0 border-0"
                  style="max-width: 250px; border-radius: 30px; background: #f5f7fa; color: #969696;"
                />
            
                <select
                  class="form-select border-0 me-md-3 mb-3 mb-md-0"
                  style="max-width: 170px; border-radius: 30px; background: #f5f7fa; color: #969696;"
                >
                  <option value="department">Sort by: Dept.</option>
                  <option value="name">Sort by: Name</option>
                </select>
              </div> -->
            
              <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <!-- <a
                  href="#"
                  class="btn mt-md-3 me-md-3 mb-3 mb-md-0"
                  style="background-color: #fff; color: #1f1f21; text-decoration: none; border: 1px solid #969696; border-radius: 10px; font-weight: 600;"
                >
                  Export CSV
                </a> -->
            
                <a
                  href="bookRide.php"
                  class="btn mt-md-3"
                  style="background-color: #f37a20; color: #fff; text-decoration: none; border-radius: 10px;"
                >
                  Book New Ride
                </a>
              </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead>
                      <tr>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Employee</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Pickup Location</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Dropoff Location</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Date and Time</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Cab #</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Cost</th>
                        <th style='border-bottom: 1px solid #e5e5e5; color: #969696; font-size: 14px; font-weight: 500;'>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php 
                        while($emp_data = mysqli_fetch_array($result2))
                        {
                      ?>
                        <tr style='border-bottom: 1px solid #e5e5e5;'>
                            <td class='py-3' style='font-size: 14px;'><?= $emp_data['employee']; ?></td>
                            <td class='py-3' style='font-size: 14px;'><?= $emp_data['pickup']; ?></td>
                            <td class='py-3' style='font-size: 14px;'><?= $emp_data['destination']; ?></td>
                            <td class='py-3' style='font-size: 14px;'><?= $emp_data['pickupTime']; ?></td>
                            <td class='py-3' style='font-size: 14px;'><?= $emp_data['vehicle_number'] ?? 'N/A' ?></td>
                            <td class='py-3' style='font-size: 14px;'>€<?= $emp_data['fare']; ?></td>
                            <td class='py-3' style='font-size: 14px;'>
                            <?php
$status = $emp_data['status'];
$color_class = '';

if ($status == 'In Progress') {
    $color_class = 'text-warning';
} elseif ($status == 'Completed') {
    $color_class = 'text-success';
} elseif ($status == 'Cancelled') {
    $color_class = 'text-danger';
}
?>

<span class="<?= $color_class; ?>">
    <?= $status; ?>
</span>
                            </td>
                        </tr>
                        <?php 
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        

        </div>
    </div>
</div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="js/script.js"></script>
    <script>
      document
        .getElementById('sidebarToggle')
        .addEventListener('click', function () {
          document.querySelector('.sidebar').classList.toggle('active');
        });

      document.addEventListener('click', function (event) {
        const sidebar = document.querySelector('.sidebar');
        if (
          window.innerWidth < 768 &&
          !event.target.closest('.sidebar') &&
          !event.target.closest('#sidebarToggle')
        ) {
          sidebar.classList.remove('active');
        }
      });
      function updatePageTitle() {
        const routeTitles = {
          '/dashboard': 'Dashboard',
          '/employee': 'Employee Directory',
          '/ride-history': 'Ride History',
          '/book-ride': 'Book a Ride',
          '/promotion': 'Promotions & Coupon',
          '/profile': 'Profile',
        };
        const path = window.location.pathname;
        document.getElementById('pageTitle').textContent =
          routeTitles[path] || 'Dashboard';
      }

      function toggleSidebar() {
        console.log('Sidebar toggle functionality would go here');
      }
    </script>
  </body>
</html>
