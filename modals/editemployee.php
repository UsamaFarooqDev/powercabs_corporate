<?php
// $emp must be in scope (called inside foreach)
$eid = $emp['Employee_id'];
?>
<div class="modal fade pc-modal" id="editEmployeeModal<?= $eid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="editEmployeeForm<?= $eid ?>" data-employee-id="<?= $eid ?>">
        <div class="modal-body d-flex flex-column gap-3">

          <div>
            <label for="edit_name_<?= $eid ?>">Full Name</label>
            <input type="text" class="form-control" id="edit_name_<?= $eid ?>" name="name"
                   value="<?= htmlspecialchars($emp['name']) ?>" required/>
          </div>

          <div>
            <label for="edit_dept_<?= $eid ?>">Department</label>
            <input type="text" class="form-control" id="edit_dept_<?= $eid ?>" name="department"
                   value="<?= htmlspecialchars($emp['department']) ?>"/>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <label for="edit_email_<?= $eid ?>">Email</label>
              <input type="email" class="form-control" id="edit_email_<?= $eid ?>" name="email"
                     value="<?= htmlspecialchars($emp['email']) ?>" required/>
            </div>
            <div class="col-6">
              <label for="edit_phone_<?= $eid ?>">Phone</label>
              <input type="tel" class="form-control" id="edit_phone_<?= $eid ?>" name="phone"
                     value="<?= htmlspecialchars($emp['phone']) ?>"/>
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