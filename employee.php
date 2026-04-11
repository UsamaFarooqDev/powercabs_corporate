<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user = $_SESSION['user'];
$cid  = $user['cid'];

$pageTitle     = 'Employee Directory';
$employeesRows = [];

try {
  $supabase    = new SupabaseClient(true);
  $employees   = $supabase->select('corporate_employees', ['cid' => $cid], '*', 'name.asc');
  $summaryRows = [];
  try {
    $summaryRows = $supabase->select('employee_ride_summary', ['cid' => $cid], '*', null);
  } catch (Throwable $e) { $summaryRows = []; }

  $summaryMap = [];
  foreach ($summaryRows as $row) {
    $sid = $row['Employee_id'] ?? ($row['id'] ?? null);
    if ($sid) $summaryMap[$sid] = $row;
  }

  foreach ($employees as $emp) {
    $eid = $emp['id'] ?? '';
    $s   = $summaryMap[$eid] ?? [];
    $employeesRows[] = [
      'Employee_id'     => $eid,
      'name'            => $emp['name']       ?? '',
      'department'      => $emp['department'] ?? '',
      'email'           => $emp['email']      ?? '',
      'phone'           => $emp['phone']      ?? '',
      'number_of_rides' => $s['number_of_rides']  ?? 0,
      'expense_of_rides'=> $s['expense_of_rides'] ?? 0,
    ];
  }
} catch (Throwable $e) { $employeesRows = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate - Employee Directory</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    body { background: #f5f7fa; }

    /* ── Table card ── */
    .emp-card { border-radius: 16px; border: 1px solid #eeeff2; }

    /* ── Add button ── */
    .btn-add {
      background: #f37a20; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .42rem .95rem;
      display: inline-flex; align-items: center; gap: .35rem;
      transition: background .15s, box-shadow .15s;
    }
    .btn-add:hover { background: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.3); }

    /* ── Search ── */
    .emp-search {
      font-size: var(--fs-input); border: 1px solid #e5e7eb;
      border-radius: 8px; padding: .38rem .75rem;
      background: #fff; max-width: 210px;
      transition: border-color .15s, box-shadow .15s;
    }
    .emp-search:focus { outline: none; border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12); }

    /* ── Table ── */
    .emp-table thead th {
      font-size: var(--fs-th); font-weight: 600; color: #9ca3af;
      text-transform: uppercase; letter-spacing: .05em;
      border-bottom: 1px solid #e5e7eb !important;
      padding-bottom: .65rem; white-space: nowrap;
    }
    .emp-table tbody td {
      font-size: var(--fs-td); color: #374151;
      padding: .75rem .5rem;
      border-bottom: 1px solid #f3f4f6 !important;
      vertical-align: middle;
    }
    .emp-table tbody tr:last-child td { border-bottom: none !important; }
    .emp-table tbody tr:hover td     { background: #fafafa; }

    /* ── Avatar chip in name cell ── */
    .emp-avatar {
      width: 28px; height: 28px; border-radius: 50%;
      background: #fff4eb; color: #f37a20;
      font-size: .7rem; font-weight: 700;
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0; letter-spacing: .02em;
    }

    /* ── Action buttons ── */
    .btn-edit {
      font-size: var(--fs-small); font-weight: 500; color: #374151;
      border: 1px solid #e5e7eb; border-radius: 6px;
      padding: .25rem .65rem; background: #fff;
      transition: background .12s, border-color .12s;
    }
    .btn-edit:hover { background: #f9fafb; border-color: #d1d5db; }

    .btn-remove {
      font-size: var(--fs-small); font-weight: 500; color: #dc2626;
      border: 1px solid #fecaca; border-radius: 6px;
      padding: .25rem .65rem; background: #fff;
      transition: background .12s;
    }
    .btn-remove:hover { background: #fef2f2; }

    /* ── Shared modal styles ── */
    .pc-modal .modal-content  { border-radius: 14px; border: 1px solid #eeeff2; }
    .pc-modal .modal-header   { border-bottom: 1px solid #f3f4f6; padding: 1.1rem 1.4rem .85rem; }
    .pc-modal .modal-title    { font-size: var(--fs-card-heading); font-weight: 700; color: #111827; }
    .pc-modal .btn-close      { opacity: .4; }
    .pc-modal .modal-body     { padding: 1.25rem 1.4rem; }
    .pc-modal .modal-footer   { border-top: 1px solid #f3f4f6; padding: .85rem 1.4rem; }

    .pc-modal label {
      font-size: var(--fs-label); font-weight: 600; color: #6b7280;
      text-transform: uppercase; letter-spacing: .05em; margin-bottom: .3rem;
    }
    .pc-modal .form-control,
    .pc-modal .form-select {
      font-size: var(--fs-input); border: 1px solid #e5e7eb; border-radius: 8px;
      padding: .42rem .7rem; color: #111827;
      transition: border-color .15s, box-shadow .15s;
    }
    .pc-modal .form-control:focus,
    .pc-modal .form-select:focus {
      border-color: #f37a20; box-shadow: 0 0 0 3px rgba(243,122,32,.12);
    }

    .btn-pc-primary {
      background: #f37a20; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .45rem 1.1rem; transition: background .15s;
    }
    .btn-pc-primary:hover { background: #e06910; color: #fff; }

    .btn-pc-danger {
      background: #dc2626; color: #fff; border: none;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 600;
      padding: .45rem 1.1rem; transition: background .15s;
    }
    .btn-pc-danger:hover { background: #b91c1c; color: #fff; }

    .btn-pc-cancel {
      background: #fff; color: #374151; border: 1px solid #e5e7eb;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 500;
      padding: .45rem 1.1rem; transition: background .15s;
    }
    .btn-pc-cancel:hover { background: #f9fafb; }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">
    <div class="card emp-card border-0 shadow-sm">
      <div class="card-body p-4">

        <!-- ── Card header ── -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
          <div>
            <h6 class="fw-semibold mb-0" style="font-size:var(--fs-card-heading); color:#111827">Employees</h6>
            <span class="d-block mt-1" style="font-size:var(--fs-card-sub); color:#9ca3af">
              <?= count($employeesRows) ?> total
            </span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <input type="text" id="empSearch" class="emp-search" placeholder="Search employees…"/>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
              <i class="bi bi-plus-lg"></i> Add Employee
            </button>
          </div>
        </div>

        <!-- ── Table ── -->
        <div class="table-responsive">
          <table class="table emp-table datatable w-100">
            <thead>
              <tr>
                <th>Name</th>
                <th>Department</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Rides</th>
                <th>Expense</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($employeesRows as $emp):
                $nameParts = array_filter(explode(' ', trim($emp['name'])));
                $av = strtoupper(count($nameParts) >= 2
                  ? substr($nameParts[0],0,1) . substr(end($nameParts),0,1)
                  : substr($nameParts[0]??'?',0,2));
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="emp-avatar"><?= htmlspecialchars($av) ?></span>
                    <?= htmlspecialchars($emp['name']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($emp['department']) ?></td>
                <td class="text-truncate" style="max-width:170px"><?= htmlspecialchars($emp['email']) ?></td>
                <td><?= htmlspecialchars($emp['phone']) ?></td>
                <td><?= htmlspecialchars($emp['number_of_rides']) ?></td>
                <td>€<?= htmlspecialchars($emp['expense_of_rides']) ?></td>
                <td>
                  <div class="d-flex gap-2 justify-content-end">
                    <button class="btn-edit"
                      data-bs-toggle="modal"
                      data-bs-target="#editEmployeeModal<?= $emp['Employee_id'] ?>">
                      <i class="bi bi-pencil me-1"></i>Edit
                    </button>
                    <button class="btn-remove"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteEmployeeModal<?= $emp['Employee_id'] ?>">
                      <i class="bi bi-trash3 me-1"></i>Remove
                    </button>
                  </div>
                  <?php
                    @require('modals/editemployee.php');
                    @require('modals/delete.php');
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </main>

  <?php @require('modals/addemployee.php'); ?>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    // ── DataTable ──
    const empTable = $('.datatable').DataTable({ pageLength: 10, order: [] });
    document.getElementById('empSearch').addEventListener('input', function () {
      empTable.search(this.value).draw();
    });

    // ── Sidebar toggle ──
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
    document.addEventListener('click', e => {
      if (window.innerWidth < 768
        && !e.target.closest('.sidebar')
        && !e.target.closest('#sidebarToggle'))
        document.querySelector('.sidebar')?.classList.remove('active');
    });

    // ── Helper: toggle loader on a button ──
    function setBtnLoading(btn, loading, loadingText) {
      if (!btn) return;
      if (loading) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + (loadingText || 'Please wait…');
      } else {
        btn.disabled = false;
        if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
      }
    }

    // ── Helper: submit a FormData via fetch and show toast ──
    function postForm(url, formData, modalEl, submitBtn, loadingText) {
      setBtnLoading(submitBtn, true, loadingText);
      return fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          setBtnLoading(submitBtn, false);
          bootstrap.Modal.getInstance(modalEl)?.hide();
          showToast(data.message, data.success ? 'success' : 'error');
          if (data.success) setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
          setBtnLoading(submitBtn, false);
          showToast('Network error. Please try again.', 'error');
        });
    }

    // ── Add Employee ──
    document.getElementById('addEmployeeForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      const btn = this.querySelector('button[type="submit"]');
      postForm('php/addemployee.php', fd, document.getElementById('addEmployeeModal'), btn, 'Adding…');
    });

    // ── Edit Employee (one listener per modal, delegated by form id) ──
    document.querySelectorAll('[id^="editEmployeeForm"]').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const eid = this.dataset.employeeId;
        const fd  = new FormData(this);
        fd.append('employee_id', eid);
        const btn = this.querySelector('button[type="submit"]');
        postForm('php/editemployee.php', fd, document.getElementById('editEmployeeModal' + eid), btn, 'Saving…');
      });
    });

    // ── Delete Employee ──
    document.querySelectorAll('[id^="confirmDeleteBtn"]').forEach(btn => {
      btn.addEventListener('click', function () {
        const eid = this.dataset.employeeId;
        const fd  = new FormData();
        fd.append('id', eid);
        postForm('php/deleteemployee.php', fd, document.getElementById('deleteEmployeeModal' + eid), this, 'Removing…');
      });
    });
  </script>
</body>
</html>