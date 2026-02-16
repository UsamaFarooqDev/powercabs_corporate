
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
      </div>