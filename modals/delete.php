<!-- <div
    class="modal fade w-100 h-100"
    id="deleteEmployeeModal<?= $data_emp['Employee_id']; ?>"
    tabindex="-1"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-dialog-centered d-flex align-items-center justify-content-center"
      style="
        width: 90%;
        max-width: 680px;
        max-height: 500px;
        margin: auto;
        padding: 40px 40px;
        border-radius: 25px;
      "
    >
      <div class="modal-content">
        <div class="modal-body text-center" style='padding: 60px 40px;'>
         <form action="php/deleteemployee.php" method="post">
 <input type="hidden" name="id" value="<?= $data_emp['Employee_id'];?>">
         <div class="mx-auto mb-3" style="display: flex; justify-content: center; align-items: center;">
              <img
              src="assets/delete.svg"
              alt="delete svg"
              class="rounded-circle profile-img"
              style="width: 50px; height: 50px; cursor: pointer"
            />

          </div>
          <h3 class="fw-bold mb-4">Delete Employee</h3>
          <p class="mb-4" style="color: #1f1f21;">
              Are you sure you want to delete the employee (<?= $data_emp['Employee_id']; ?>)
              <span id="deleteEmployeeName" class="fw-bold" style="font-size: 16px;"></span> record?
          </p>
          <input type="hidden" id="deleteEmployeeEmail">
          <div class="d-flex flex-wrap justify-content-center gap-3">
              <button id="confirmDeleteBtn" class="btn px-4" style='background: #f37a20; color: #fff;'>
                  Delete Record
              </button>
              <button class="btn px-4" data-bs-dismiss="modal" style='border: 1px solid black;'>
                  Back to Dashboard
              </button>
          </div>
         </form>
      </div>
      </div>
    </div>
  </div> -->

  <?php $eid = $emp['Employee_id']; ?>
<div class="modal fade pc-modal" id="deleteEmployeeModal<?= $eid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content">

      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center px-4 pt-2 pb-3">

        <!-- Danger icon -->
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
             style="width:48px;height:48px;background:#fef2f2">
          <i class="bi bi-trash3 text-danger" style="font-size:1.2rem"></i>
        </div>

        <h6 class="fw-bold mb-1" style="font-size:.9rem;color:#111827">Remove Employee?</h6>
        <p class="mb-0" style="font-size:.78rem;color:#6b7280">
          <strong><?= htmlspecialchars($emp['name']) ?></strong> will be permanently removed
          from the directory. This cannot be undone.
        </p>

      </div>

      <div class="modal-footer justify-content-center gap-2">
        <button type="button" class="btn-pc-cancel" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-pc-danger"
                data-employee-id="<?= $eid ?>"
                id="confirmDeleteBtn<?= $eid ?>">
          Remove
        </button>
      </div>

    </div>
  </div>
</div>