<!-- <div class="modal fade" id="editEmployeeModal<?= $data_emp['Employee_id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-custom p-4">
                <div class="modal-header border-0">
                    <h2 class="modal-title text-center w-100" style="font-size: 2rem; font-weight: bold;">Edit Employee</h2>
                </div>
                <div class="modal-body">
                  <form id="editEmployeeForm" action="php/editemployee.php" method="POST">
                    <input type="hidden" name="employee_id" value="<?= $data_emp['Employee_id']; ?>">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label" style="color: #1f1f21;">
                            Name<span class="required-field" style='color: #df2d2d'>*</span>
                        </label>
                        <input type="text" class="form-control form-input-custom" 
                               id="edit-name" name="name" placeholder="Enter Name" value="<?= $data_emp['name']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-email" class="form-label" style="color: #1f1f21;">
                            Email<span class="required-field" style='color: #df2d2d'>*</span>
                        </label>
                        <input type="email" class="form-control form-input-custom" 
                               id="edit-email" name="email" placeholder="Enter Email" value="<?= $data_emp['email']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-contact" class="form-label" style="color: #1f1f21;">
                            Contact<span class="required-field" style='color: #df2d2d'>*</span>
                        </label>
                        <input type="text" class="form-control form-input-custom" 
                               id="edit-contact" name="contact" placeholder="Enter Contact Number" value="<?= $data_emp['phone']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-department" class="form-label" style="color: #1f1f21;">
                            Department
                        </label>
                        <input type="text" class="form-control form-input-custom" 
                               id="edit-department" name="department" placeholder="Enter Department Name" value="<?= $data_emp['department']; ?>">
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" class="btn btn-save" style='background: #f37a20; color: #fff;'>
                            Save Changes
                        </button>
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal" style='border: 1px solid black;'>
                            Cancel
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div> -->

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