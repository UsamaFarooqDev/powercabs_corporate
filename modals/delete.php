<?php $eid = $emp['Employee_id']; ?>
<div class="modal fade pc-modal" id="deleteEmployeeModal<?= $eid ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
    <div class="modal-content">

      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center px-5 pt-2 pb-4">

        <!-- Danger icon -->
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
             style="width:56px;height:56px;background:#fef2f2">
          <i class="bi bi-trash3 text-danger" style="font-size:1.4rem"></i>
        </div>

        <h6 class="fw-bold mb-2" style="font-size:var(--fs-card-heading);color:#111827">Remove Employee?</h6>
        <p class="mb-0" style="font-size:var(--fs-body);color:#6b7280; line-height:1.5;">
          <strong><?= htmlspecialchars($emp['name']) ?></strong> will be permanently removed
          from the directory. This action cannot be undone.
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