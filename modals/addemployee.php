<!-- 
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content modal-content-custom p-4">
                  <div class="modal-header border-0">
                      <h2 class="modal-title text-center w-100" style="font-size: 2rem; font-weight: bold;">Add New Employee</h2>
                  </div>
                  <div class="modal-body">
                      <form id="employeeForm" action="php/addemployee.php" method="POST">
                        <input type="hidden" name="companyname" value="<?= $user['name']?>">
                        <input type="hidden" name="cid" value="<?= $user['cid']?>">
                          <div class="mb-3">
                              <label for="name" class="form-label" style="color: #1f1f21;">
                                  Name<span class="required-field" style='color: #df2d2d'>*</span>
                              </label>
                              <input type="text" class="form-control form-input-custom" 
                                     id="name" name="name" placeholder="Enter Name" required>
                          </div>
                          
                          <div class="mb-3">
                              <label for="email" class="form-label" style="color: #1f1f21;">
                                  Email<span class="required-field" style='color: #df2d2d'>*</span>
                              </label>
                              <input type="email" class="form-control form-input-custom" 
                                     id="email" name="email" placeholder="Enter Email" required>
                          </div>
                          
                          <div class="mb-3">
                              <label for="contact" class="form-label" style="color: #1f1f21;">
                                  Contact<span class="required-field" style='color: #df2d2d'>*</span>
                              </label>
                              <input type="text" class="form-control form-input-custom" 
                                     id="contact" name="contact" placeholder="Enter Contact Number" required>
                          </div>
                          
                          <div class="mb-3">
                              <label for="department" class="form-label" style="color: #1f1f21;">
                                  Department
                              </label>
                              <input type="text" class="form-control form-input-custom" 
                                     id="department" name="department" placeholder="Enter Department Name">
                          </div>
                          
                          <div class="d-flex justify-content-between mt-4">
                              <button type="submit" class="btn btn-save" style='background: #f37a20; color: #fff;'>
                                  Save Changes
                              </button>
                              <button type="button" class="btn btn-cancel" style='border: 1px solid black;' data-bs-dismiss="modal">
                                  Back to Dashboard
                              </button>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div> -->
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