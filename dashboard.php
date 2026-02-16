<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
  <!-- Sidebar -->
  <div class="sidebar">
  <?php 
        @require('modules/sidebar.php');
      ?>
  </div>

  <!-- Header -->
  <div class="header">
    <h4>Dashboard</h4>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card card-stats">
          <div class="card-body">
            <div class="icon-box">
              <i class="fas fa-car"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Total Rides</h6>
              <h4>120</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats">
          <div class="card-body">
            <div class="icon-box">
              <i class="fas fa-users"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Active Employees</h6>
              <h4>50</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats">
          <div class="card-body">
            <div class="icon-box">
              <i class="fas fa-sack-dollar"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Total Expenditures</h6>
              <h4>€2,500</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats">
          <div class="card-body">
            <div class="icon-box">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Upcoming Rides</h6>
              <h4>10</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ride History Table -->
    <div class="card table-responsive">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Rides</h5>
        <span class="text-muted">This Month</span>
      </div>
      <div class="card-body">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Pickup Location</th>
              <th>Dropoff Location</th>
              <th>Date and Time</th>
              <th>Cab #</th>
              <th>Cost</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>John Doe</td>
              <td>123 Main St</td>
              <td>456 Elm St</td>
              <td>2023-10-15 10:00 AM</td>
              <td>ABC123</td>
              <td>€25.50</td>
              <td class="status-completed">Completed</td>
            </tr>
            <tr>
              <td>Jane Smith</td>
              <td>789 Oak St</td>
              <td>321 Pine St</td>
              <td>2023-10-16 02:30 PM</td>
              <td>N/A</td>
              <td>N/A</td>
              <td class="status-pending">Pending</td>
            </tr>
            <!-- Add More Rows as Needed -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS (Optional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>