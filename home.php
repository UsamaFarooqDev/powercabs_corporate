<?php 

session_start();
@include('php/connection.php');
if (!isset($_SESSION['user'])) {
  header("Location: index.php"); // Redirect to login if the user is not logged in
  exit;
}
$user = $_SESSION['user'];
$cid = $user['cid'];
$sql = "select * from corporate_data where company_id = '$cid'";
$result = mysqli_query($conn,$sql);
$row = mysqli_num_rows($result);

if($row < 0){
  $total_ride = 0;
  $pending_rides = 0;
  $employees = 0;
  $expense = 0;
}
else{
   $data = mysqli_fetch_array($result);
   $total_ride = $data['total_rides'];
   $pending_rides = $data['pending_rides'];
   $employees = $data['total_employees'];;
   $expense = $data['total_fare'];;
}

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
    <link rel="stylesheet" href="global.css">
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
          Dashboard
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
      <div class="d-flex justify-content-start my-0">
        <a
          href="bookRide.php"
          class="btn glowing-btn fs-5 p-2 fw-semibold"
          style="background: #f37a20; color: #fff; border-radius: 10px;"
        >
          Book New Ride
          <i
            class="bi bi-arrow-right ms-2"
            style="font-size: 1.2rem; color: #fff"
          ></i>
        </a>
      </div>
      <div class="row mb-4 mt-2 g-3">
        <div class="col-lg-3 col-md-6">
          <div
            class="card h-100 border-0 shadow-sm p-1"
            style="border-radius: 25px"
          >
            <div class="card-body d-flex align-items-center">
              <div
                class="rounded-circle d-flex align-items-center justify-content-center"
                style="width: 65px; height: 65px; background: #e8e8e8"
              >
                <i
                  class="bi bi-car-front-fill"
                  style="color: #f37a20; font-size: 1.9rem"
                ></i>
              </div>
              <div class="ms-3">
                <h6 class="text-secondary mb-1">Total Rides</h6>
                <h4 class="mb-0"><?= $total_ride; ?></h4>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div
            class="card h-100 border-0 shadow-sm p-1"
            style="border-radius: 25px"
          >
            <div class="card-body d-flex align-items-center">
              <div
                class="rounded-circle d-flex align-items-center justify-content-center"
                style="width: 65px; height: 65px; background: #e8e8e8"
              >
                <i
                  class="bi bi-person-badge-fill"
                  style="color: #f37a20; font-size: 1.9rem"
                ></i>
              </div>
              <div class="ms-3">
                <h6 class="text-secondary mb-1">Active Employees</h6>
                <h4 class="mb-0"><?= $employees; ?></h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div
            class="card h-100 border-0 shadow-sm p-1"
            style="border-radius: 25px"
          >
            <div class="card-body d-flex align-items-center">
              <div
                class="rounded-circle d-flex align-items-center justify-content-center"
                style="width: 65px; height: 65px; background: #e8e8e8"
              >
                <i
                  class="bi bi-cash-coin"
                  style="color: #f37a20; font-size: 1.9rem"
                ></i>
              </div>
              <div class="ms-3">
                <h6 class="text-secondary mb-1">Total Expenditures</h6>
                <h4 class="mb-0">€<?= $expense; ?></h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div
            class="card h-100 border-0 shadow-sm p-1"
            style="border-radius: 25px"
          >
            <div class="card-body d-flex align-items-center">
              <div
                class="rounded-circle d-flex align-items-center justify-content-center"
                style="width: 65px; height: 65px; background: #e8e8e8"
              >
                <i
                  class="bi bi-calendar-event"
                  style="color: #f37a20; font-size: 1.9rem"
                ></i>
              </div>
              <div class="ms-3">
                <h6 class="text-secondary mb-1">Upcoming Rides</h6>
                <h4 class="mb-0"><?= $pending_rides; ?></h4>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow border-0" style='border-radius: 25px;'>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <div class='p-2'>
                    <h5 class="card-title mb-1">Recent Rides</h5>
                    <p class="mt-2 mb-0" style='color: #f37a20;'>This Month</p>
                </div>
                <div class="d-flex gap-3 mt-3 mt-md-0">
                    <input type="text" class="form-control rounded-pill bg-light border-0" 
                           placeholder="Search" style="max-width: 200px;">
                    <select class="form-select rounded-pill bg-light border-0" style="max-width: 170px;">
                        <option>Sort by: Newest</option>
                        <option>Sort by: Oldest</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
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
                    <tbody id="rides-body">
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

            <div class="d-flex justify-content-between align-items-center mt-4 p-2">
              <span class="text-secondary" id="pagination-info">Showing 1-9 of 13 entries</span>
              <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary rounded-circle" id="prev-page" disabled>
                      <i class="bi bi-chevron-left"></i>
                  </button>
                  <button class="btn btn-outline-secondary rounded-circle" id="next-page">
                      <i class="bi bi-chevron-right"></i>
                  </button>
              </div>
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
      
    

const elements = {
    tbody: document.getElementById('rides-body'),
    paginationInfo: document.getElementById('pagination-info'),
    prevBtn: document.getElementById('prev-page'),
    nextBtn: document.getElementById('next-page')
};

const statusColors = {
    'Pending': '#ff0000',
    'In Progress': '#ff0000',
    'Completed': '#28a745'
};

function renderTable() {
    const start = (config.currentPage - 1) * config.entriesPerPage;
    const end = start + config.entriesPerPage;
    const pageData = allRides.slice(start, end);

    elements.tbody.innerHTML = pageData.map(ride => `
        <tr style='border-bottom: 1px solid #e5e5e5;'>
            <td class='py-3' style='font-size: 14px;'>${ride.employee}</td>
            <td class='py-3' style='font-size: 14px;'>${ride.pickup}</td>
            <td class='py-3' style='font-size: 14px;'>${ride.dropoff}</td>
            <td class='py-3' style='font-size: 14px;'>${ride.date}</td>
            <td class='py-3' style='font-size: 14px;'>${ride.cab}</td>
            <td class='py-3' style='font-size: 14px;'>${ride.cost}</td>
            <td class='py-3' style='font-size: 14px;'>
                <span style="color: ${statusColors[ride.status] || '#000'}; font-weight: 500;">
                    ${ride.status}
                </span>
            </td>
        </tr>
    `).join('');
}

function updatePagination() {
    const start = (config.currentPage - 1) * config.entriesPerPage + 1;
    const end = Math.min(config.currentPage * config.entriesPerPage, config.totalEntries);
  
    elements.paginationInfo.textContent = 
        `Showing ${start}-${end} of ${config.totalEntries} entries`;
    
    elements.prevBtn.disabled = config.currentPage === 1;
    elements.nextBtn.disabled = config.currentPage === config.totalPages;
}

function handlePrevPage() {
    if (config.currentPage > 1) {
        config.currentPage--;
        renderTable();
        updatePagination();
    }
}

function handleNextPage() {
    if (config.currentPage < config.totalPages) {
        config.currentPage++;
        renderTable();
        updatePagination();
    }
}

elements.prevBtn.addEventListener('click', handlePrevPage);
elements.nextBtn.addEventListener('click', handleNextPage);

renderTable();
updatePagination();
    </script>
  </body>
</html>
