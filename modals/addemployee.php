<div class="modal fade pc-modal" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="addEmployeeForm">
        <div class="modal-body d-flex flex-column gap-3">

          <div>
            <label for="add_name">Full Name</label>
            <input type="text" class="form-control" id="add_name" name="name"
                   placeholder="e.g. Jane Smith" required/>
          </div>

          <div>
            <label for="add_department">Department</label>
            <input type="text" class="form-control" id="add_department" name="department"
                   placeholder="e.g. Engineering"/>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <label for="add_email">Email</label>
              <input type="email" class="form-control" id="add_email" name="email"
                     placeholder="jane@company.com" required/>
            </div>
            <div class="col-6">
              <label for="add_phone">Phone</label>
              <input type="tel" class="form-control" id="add_phone" name="phone"
                     placeholder="+353 …"/>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn-pc-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-pc-primary">Add Employee</button>
        </div>
      </form>

    </div>
  </div>
</div>