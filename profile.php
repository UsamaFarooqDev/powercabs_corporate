<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    header('Location: login.php');
    exit;
}
$user      = $_SESSION['user'];
$pageTitle = 'Profile';
$row = [
    'name'    => trim((string)($user['name']  ?? '')),
    'email'   => trim((string)($user['email'] ?? '')),
    'phone'   => '',
    'address' => '',
];
try {
    $supabase = new SupabaseClient(true);
    $dbRow    = null;
    foreach (corporate_row_filters_try($user) as $filter) {
        $results = $supabase->select('corporate', $filter, '*', null, 1);
        if (!empty($results)) { $dbRow = $results[0]; break; }
    }
    if ($dbRow !== null) {
        $row['name']    = trim((string)($dbRow['name']    ?? $row['name']));
        $row['email']   = trim((string)($dbRow['email']   ?? $row['email']));
        $row['phone']   = trim((string)($dbRow['phone']   ?? $dbRow['Phone']   ?? ''));
        $row['address'] = trim((string)($dbRow['address'] ?? $dbRow['Address'] ?? ''));
    }
} catch (Throwable $e) {
    // silently ignore
}

// Initials for avatar
$nameParts = array_filter(explode(' ', trim($row['name'])));
$initials  = strtoupper(count($nameParts) >= 2
    ? substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)
    : substr($nameParts[0] ?? 'CO', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate - Profile</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    body { background: #f5f7fa; }

    /* ── Cards ── */
    .pf-card { border-radius: 16px; border: 1px solid #eeeff2; }

    /* ── Avatar ── */
    .pf-avatar {
      width: 56px; height: 56px; border-radius: 50%;
      background: #f37a20; color: #fff;
      font-size: 1.1rem; font-weight: 700; letter-spacing: .03em;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    /* ── Section heading ── */
    .pf-section-label {
      font-size: var(--fs-label); font-weight: 600;
      text-transform: uppercase; letter-spacing: .07em;
      color: #9ca3af;
    }

    /* ── Read-only fields ── */
    .pf-field label {
      font-size: var(--fs-label); font-weight: 600; color: #6b7280;
      text-transform: uppercase; letter-spacing: .05em;
      margin-bottom: .3rem; display: block;
    }
    .pf-field .form-control {
      font-size: var(--fs-input); color: #111827;
      background: #f9fafb; border: 1px solid #e5e7eb;
      border-radius: 8px; padding: .42rem .7rem;
    }
    .pf-field .form-control[readonly] { cursor: default; }

    /* ── Buttons ── */
    .btn-pc-primary {
      background: #f37a20; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .42rem 1rem; display: inline-flex; align-items: center; gap: .35rem;
      transition: background .15s, box-shadow .15s;
    }
    .btn-pc-primary:hover { background: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.3); }

    .btn-pc-cancel {
      background: #fff; color: #374151; border: 1px solid #e5e7eb;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 500;
      padding: .42rem 1rem; transition: background .15s;
    }
    .btn-pc-cancel:hover { background: #f9fafb; }

    /* ── Modals ── */
    .pc-modal .modal-content  { border-radius: 14px; border: 1px solid #eeeff2; }
    .pc-modal .modal-header   { border-bottom: 1px solid #f3f4f6; padding: 1.1rem 1.4rem .85rem; }
    .pc-modal .modal-title    { font-size: var(--fs-card-heading); font-weight: 700; color: #111827; }
    .pc-modal .btn-close      { opacity: .4; }
    .pc-modal .modal-body     { padding: 1.25rem 1.4rem; }
    .pc-modal .modal-footer   { border-top: 1px solid #f3f4f6; padding: .85rem 1.4rem; }

    .pc-modal label {
      font-size: var(--fs-label); font-weight: 600; color: #6b7280;
      text-transform: uppercase; letter-spacing: .05em;
      margin-bottom: .3rem;
    }
    .pc-modal .form-control {
      font-size: var(--fs-input); border: 1px solid #e5e7eb;
      border-radius: 8px; padding: .42rem .7rem; color: #111827;
      transition: border-color .15s, box-shadow .15s;
    }
    .pc-modal .form-control:focus {
      border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12);
    }
    .pc-modal .form-text { font-size: var(--fs-small); color: #9ca3af; margin-top: .3rem; }

    /* ── Password dots field ── */
    .pf-pw-dots {
      font-size: var(--fs-input); color: #9ca3af;
      background: #f9fafb; border: 1px solid #e5e7eb;
      border-radius: 8px; padding: .42rem .7rem;
      letter-spacing: .15em;
    }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">
    <div class="d-flex flex-column gap-4">

      <!-- ── Identity card ── -->
      <div class="card pf-card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">
              <div class="pf-avatar"><?= htmlspecialchars($initials) ?></div>
              <div>
                <div class="fw-semibold" style="font-size:var(--fs-card-heading); color:#111827">
                  <?= htmlspecialchars($row['name']) ?>
                </div>
                <div style="font-size:var(--fs-card-sub); color:#9ca3af">
                  <?= htmlspecialchars($row['email']) ?>
                </div>
              </div>
            </div>

            <button class="btn-pc-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
              <i class="bi bi-pencil"></i> Edit Profile
            </button>

          </div>
        </div>
      </div>

      <!-- ── Corporate details card ── -->
      <div class="card pf-card border-0 shadow-sm">
        <div class="card-body p-4">

          <p class="pf-section-label mb-3">Corporate Details</p>

          <div class="row g-3">

            <div class="col-md-6 pf-field">
              <label>Company Name</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" readonly/>
            </div>

            <div class="col-md-6 pf-field">
              <label>Email Address</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>" readonly/>
            </div>

            <div class="col-md-6 pf-field">
              <label>Contact Number</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($row['phone']) ?>" readonly/>
            </div>

            <div class="col-md-6 pf-field">
              <label>Address</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($row['address']) ?>" readonly/>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Password card ── -->
      <div class="card pf-card border-0 shadow-sm">
        <div class="card-body p-4">

          <p class="pf-section-label mb-3">Security</p>

          <div class="d-flex align-items-end gap-3 flex-wrap">
            <div class="pf-field flex-grow-1" style="max-width:320px">
              <label>Password</label>
              <div class="pf-pw-dots">••••••••••••••••</div>
            </div>
            <button class="btn-pc-primary mb-1" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
              <i class="bi bi-lock"></i> Change Password
            </button>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- ── Edit Profile Modal ── -->
  <div class="modal fade pc-modal" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <form action="php/update_profile.php" method="POST">
          <div class="modal-body d-flex flex-column gap-3">

            <div>
              <label for="edit-name">Company Name</label>
              <input type="text" class="form-control" id="edit-name" name="name"
                     value="<?= htmlspecialchars($row['name']) ?>" required/>
            </div>

            <div>
              <label>Email Address</label>
              <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($row['email']) ?>" readonly disabled/>
              <div class="form-text">Email cannot be changed here. Contact support if needed.</div>
            </div>

            <div class="row g-3">
              <div class="col-6">
                <label for="edit-phone">Phone</label>
                <input type="text" class="form-control" id="edit-phone" name="phone"
                       value="<?= htmlspecialchars($row['phone']) ?>" required/>
              </div>
              <div class="col-6">
                <label for="edit-address">Address</label>
                <input type="text" class="form-control" id="edit-address" name="address"
                       value="<?= htmlspecialchars($row['address']) ?>"/>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn-pc-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-pc-primary">Save Changes</button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- ── Change Password Modal ── -->
  <div class="modal fade pc-modal" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <form id="changePasswordForm" action="php/change_password.php" method="POST">
          <div class="modal-body d-flex flex-column gap-3">

            <div>
              <label for="old-password">Current Password</label>
              <input type="password" class="form-control" id="old-password"
                     name="old_password" required/>
            </div>

            <div>
              <label for="new-password">New Password</label>
              <input type="password" class="form-control" id="new-password"
                     name="new_password" required minlength="8" autocomplete="new-password"/>
              <div class="form-text">Minimum 8 characters.</div>
            </div>

            <div>
              <label for="confirm-password">Confirm New Password</label>
              <input type="password" class="form-control" id="confirm-password"
                     name="confirm_password" required/>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn-pc-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-pc-primary">
              <i class="bi bi-lock"></i> Update Password
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
    document.addEventListener('click', e => {
      if (window.innerWidth < 768
        && !e.target.closest('.sidebar')
        && !e.target.closest('#sidebarToggle'))
        document.querySelector('.sidebar')?.classList.remove('active');
    });

    // Submit loaders for modal forms
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function (e) {
        if (!this.checkValidity()) return;
        const btn = this.querySelector('button[type="submit"]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Saving…';
      });
    });
  </script>

</body>
</html>