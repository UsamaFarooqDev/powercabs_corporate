<?php
$_toastSuccess = '';
$_toastError   = '';
if (isset($_SESSION['success'])) {
    $_toastSuccess = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $_toastError = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div id="toastContainer" aria-live="polite" aria-atomic="true"
     style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
</div>

<script>
function showToast(message, type) {
  type = type || 'success';
  const bg = type === 'success' ? '#28a745' : (type === 'warning' ? '#f37a20' : '#dc3545');
  const icon = type === 'success'
    ? '<i class="bi bi-check-circle-fill me-2"></i>'
    : (type === 'warning'
        ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>'
        : '<i class="bi bi-x-circle-fill me-2"></i>');

  const id = 'toast-' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center border-0 mb-2 show"
         role="alert" aria-live="assertive" aria-atomic="true"
         style="background:${bg}; color:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center" style="font-size:var(--fs-body); font-weight:500;">
          ${icon}${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>`;

  const container = document.getElementById('toastContainer');
  container.insertAdjacentHTML('beforeend', html);

  const toastEl = document.getElementById(id);
  const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
  bsToast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

<?php if ($_toastSuccess !== ''): ?>
document.addEventListener('DOMContentLoaded', function() {
  showToast(<?= json_encode($_toastSuccess); ?>, 'success');
});
<?php endif; ?>
<?php if ($_toastError !== ''): ?>
document.addEventListener('DOMContentLoaded', function() {
  showToast(<?= json_encode($_toastError); ?>, 'error');
});
<?php endif; ?>
</script>
