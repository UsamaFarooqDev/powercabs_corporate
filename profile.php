<?php 
session_start();
@include('php/connection.php');
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user = $_SESSION['user'];
$cid = $user['cid'];
$sql = "SELECT * FROM corporate WHERE CID = '$cid'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="global.css" />
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white d-flex align-items-center justify-content-between p-3">
    <div class="d-flex align-items-center">
      <button class="navbar-toggler me-2 btn btn-light border-none" type="button" id="sidebarToggle">
        <span class="navbar-toggler-icon"></span>
      </button>
      <h1 class="navbar-title m-0 fw-bold ms-lg-2" id="pageTitle">Profile</h1>
    </div>
    <div class="d-flex align-items-center">
      <div class="dropdown">
        <img src="assets/profile.svg" alt="Profile" class="rounded-circle profile-img" style="width: 50px; height: 50px; cursor: pointer" data-bs-toggle="dropdown" aria-expanded="false" />
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#">Profile</a></li>
          <li><a class="dropdown-item" href="php/logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <div class="sidebar text-white p-3">
    <?php @require('modules/sidebar.php'); ?>
  </div>

  <!-- Main Content -->
  <main class="main-content p-4" style="background: #f5f7fa">
    <div class="card shadow border-0" style="border-radius: 25px;">
      <div class="card-body">

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Company Info -->
        <div class="d-flex justify-content-between align-items-center flex-wrap px-5 py-3">
          <div class="d-flex flex-column flex-md-row align-items-md-center">
            <img src="assets/powercabs-logo-black.svg" alt="Company Logo" class="me-3">
            <div>
              <h5 class="mb-1"><?= htmlspecialchars($row['name']); ?></h5>
              <p class="text-muted mb-0"><?= htmlspecialchars($row['email']); ?></p>
            </div>
          </div>
          <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center mt-2 mt-md-0">
            <button class="btn mt-2 mt-md-0" style="background: #f37a20;color: #fff;" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit</button>
          </div>
        </div>

        <!-- Corporate Details -->
        <div class="row g-3 mb-4 px-3 px-sm-5">
          <h6 class="section-title mt-5" style="font-size: 20px; font-weight: 600;">Corporate Details</h6>
          <div class="col-md-6 py-2">
            <label class="mb-1" style="color: #1f1f21; font-weight: 600;">Name</label>
            <input type="text" class="form-control custom-input border-0" style="background: #f2f6fd;" value="<?= htmlspecialchars($row['name']); ?>" readonly>
          </div>
          <div class="col-md-6 py-2">
            <label class="mb-1" style="color: #1f1f21;font-weight: 600;">Email</label>
            <input type="email" class="form-control custom-input border-0" style="background: #f2f6fd;" value="<?= htmlspecialchars($row['email']); ?>" readonly>
          </div>
          <div class="col-md-6 py-3">
            <label class="mb-1" style="color: #1f1f21;font-weight: 600;">Contact</label>
            <input type="text" class="form-control custom-input border-0" style="background: #f2f6fd;" value="<?= htmlspecialchars($row['phone']); ?>" readonly>
          </div>
          <div class="col-md-6 py-3">
            <label class="mb-1" style="color: #1f1f21;font-weight: 600;">Address</label>
            <input type="text" class="form-control custom-input border-0" style="background: #f2f6fd;" value="<?= htmlspecialchars($row['address']); ?>" readonly>
          </div>
        </div>

        <!-- Password Section -->
        <div class="row g-3 mb-4 px-3 px-sm-5 align-items-end">
          <h6 class="section-title mt-5" style="font-size: 20px; font-weight: 600;">Password</h6>
          <div class="col-md-6">
            <label class="mb-1" style="color: #1f1f21; font-weight: 600;">Update Password</label>
            <input type="password" class="form-control" id="passwordInput" placeholder="*****************" readonly>
          </div>
          <div class="col-md-2 d-flex align-items-end">
  <button class="btn" style="background: #f37a20; color: #fff;" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Update</button>
</div>
        </div>

      </div>
    </div>
  </main>

  <!-- Edit Profile Modal -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-4">
        <div class="modal-header border-0">
          <h2 class="modal-title text-center w-100" style="font-size: 2rem; font-weight: bold;">Edit Profile</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="php/update_profile.php" method="POST">
            <input type="hidden" name="cid" value="<?= htmlspecialchars($user['cid']); ?>" />

            <div class="mb-3">
              <label for="edit-name" class="form-label">Company Name</label>
              <input type="text" class="form-control" id="edit-name" name="name" value="<?= htmlspecialchars($row['name']); ?>" required />
            </div>

            <div class="mb-3">
              <label for="edit-email" class="form-label">Email</label>
              <input type="email" class="form-control" id="edit-email" name="email" value="<?= htmlspecialchars($row['email']); ?>" required />
            </div>

            <div class="mb-3">
              <label for="edit-phone" class="form-label">Phone</label>
              <input type="text" class="form-control" id="edit-phone" name="phone" value="<?= htmlspecialchars($row['phone']); ?>" required />
            </div>

            <div class="mb-3">
              <label for="edit-address" class="form-label">Address</label>
              <input type="text" class="form-control" id="edit-address" name="address" value="<?= htmlspecialchars($row['address']); ?>" required />
            </div>

            <div class="d-flex justify-content-between mt-4">
              <button type="submit" class="btn btn-save" style="background: #f37a20; color: #fff;">Save Changes</button>
              <button type="button" class="btn btn-cancel" data-bs-dismiss="modal" style="border: 1px solid black;">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4">
      <div class="modal-header border-0">
        <h2 class="modal-title text-center w-100" style="font-size: 2rem; font-weight: bold;">Change Password</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm" action="php/change_password.php" method="POST">
          <input type="hidden" name="cid" value="<?= htmlspecialchars($user['cid']); ?>" />

          <div class="mb-3">
            <label for="old-password" class="form-label">Old Password</label>
            <input type="password" class="form-control" id="old-password" name="old_password" required />
          </div>

          <div class="mb-3">
            <label for="new-password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="new-password" name="new_password" required />
          </div>

          <div class="mb-3">
            <label for="confirm-password" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required />
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="submit" class="btn btn-save" style="background: #f37a20; color: #fff;">Change Password</button>
            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal" style="border: 1px solid black;">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

  <!-- Password Success Modal -->
  <div class="modal fade" id="passwordSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0" style="border-radius: 20px; padding: 60px;">
        <div class="modal-body text-center">
          <div class="mx-auto mb-3">
            <img src="assets/success.svg" alt="success icon" class="rounded-circle" style="width: 50px; height: 50px; cursor: pointer" />
          </div>
          <h3 class="fw-bold mb-2">Request sent successfully.</h3>
          <p class="text-muted mb-4">
            Your request to update password has been successfully sent! Please check your email soon.
          </p>
          <div class="d-flex flex-wrap justify-content-center">
            <button class="btn px-4" data-bs-dismiss="modal" style="border: 1px solid black;">Back to Dashboard</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function submitPasswordRequest() {
      window.location.href = 'php/change_password.php?cid=<?= urlencode($user['cid']) ?>';
    }
  </script>
</body>
</html>