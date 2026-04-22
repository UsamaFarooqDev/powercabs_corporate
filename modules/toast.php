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

<!-- Transient toast container (top-right) -->
<div id="toastContainer" aria-live="polite" aria-atomic="true"
     style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
</div>

<!-- Persistent notifications container (bottom-left) -->
<div id="persistentToastContainer" aria-live="polite" aria-atomic="true"
     style="position: fixed; bottom: 24px; left: 24px; z-index: 9999; width: 380px; max-width: calc(100vw - 48px); display: flex; flex-direction: column; gap: 10px;">
</div>

<style>
  .pc-notif {
    position: relative;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 40px 14px 16px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    box-shadow: 0 10px 25px -5px rgba(17,24,39,.10), 0 4px 6px -2px rgba(17,24,39,.05);
    overflow: hidden;
    animation: pcNotifIn .28s cubic-bezier(.2,.8,.2,1);
  }
  .pc-notif::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
  }
  .pc-notif--success::before { background: #10b981; }
  .pc-notif--warning::before { background: #f37a20; }
  .pc-notif--error::before   { background: #ef4444; }

  .pc-notif-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
    line-height: 1;
    margin-left: 4px;
  }
  .pc-notif--success .pc-notif-icon { background: #d1fae5; color: #047857; }
  .pc-notif--warning .pc-notif-icon { background: #fff4eb; color: #d97706; }
  .pc-notif--error   .pc-notif-icon { background: #fee2e2; color: #b91c1c; }

  .pc-notif-body { flex: 1; min-width: 0; padding-top: 1px; }
  .pc-notif-title {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
    margin: 0 0 2px;
  }
  .pc-notif-message {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.45;
    word-break: break-word;
    margin: 0;
  }

  .pc-notif-close {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 24px;
    height: 24px;
    padding: 0;
    background: transparent;
    border: none;
    color: #9ca3af;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: background .15s, color .15s;
  }
  .pc-notif-close:hover { background: #f3f4f6; color: #111827; }
  .pc-notif-close:focus-visible { outline: 2px solid #93c5fd; outline-offset: 1px; }

  @keyframes pcNotifIn {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
  }
</style>

<script>
function showToast(message, type, options) {
  type = type || 'success';
  options = options || {};
  const persistent = options.persistent === true;

  const bg = type === 'success' ? '#28a745' : (type === 'warning' ? '#f37a20' : '#dc3545');
  const icon = type === 'success'
    ? '<i class="bi bi-check-circle-fill me-2"></i>'
    : (type === 'warning'
        ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>'
        : '<i class="bi bi-x-circle-fill me-2"></i>');

  const id = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
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
  const bsToast = new bootstrap.Toast(toastEl, persistent ? { autohide: false } : { delay: 4000 });
  bsToast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

/* ─────────────────────────────────────────────────────────────────
   Persistent notifications (bottom-left)
   Stored in localStorage scoped by current CID so they survive page
   reloads, logout, and session-end. Only dismissed by the X button.
   ───────────────────────────────────────────────────────────────── */
(function () {
  const STORAGE_PREFIX = 'powercab_corporate_persistent_toasts_';

  function storageKey() {
    const cid = (window.PC_USER_CID || '').toString();
    return STORAGE_PREFIX + (cid || 'default');
  }

  function readList() {
    try {
      const raw = localStorage.getItem(storageKey());
      const list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch (_) { return []; }
  }

  function writeList(list) {
    try { localStorage.setItem(storageKey(), JSON.stringify(list)); } catch (_) {}
  }

  function removeById(id) {
    const list = readList().filter(n => n.id !== id);
    writeList(list);
  }

  function defaultTitle(type) {
    if (type === 'warning') return 'Heads up';
    if (type === 'error')   return 'Something went wrong';
    return 'Notification';
  }

  function iconFor(type) {
    if (type === 'warning') return 'bi-exclamation-triangle-fill';
    if (type === 'error')   return 'bi-x-circle-fill';
    return 'bi-check-circle-fill';
  }

  function renderOne(notif) {
    const container = document.getElementById('persistentToastContainer');
    if (!container) return;
    if (container.querySelector('[data-pc-notif-id="' + CSS.escape(notif.id) + '"]')) return;

    const type = notif.type || 'success';
    const title = notif.title || defaultTitle(type);

    const wrapper = document.createElement('div');
    wrapper.className = 'pc-notif pc-notif--' + type;
    wrapper.setAttribute('role', 'alert');
    wrapper.setAttribute('aria-live', 'assertive');
    wrapper.setAttribute('aria-atomic', 'true');
    wrapper.setAttribute('data-pc-notif-id', notif.id);
    wrapper.innerHTML = `
      <div class="pc-notif-icon"><i class="bi ${iconFor(type)}"></i></div>
      <div class="pc-notif-body">
        <div class="pc-notif-title">${escapeHtml(title)}</div>
        <div class="pc-notif-message">${escapeHtml(notif.message || '')}</div>
      </div>
      <button type="button" class="pc-notif-close" aria-label="Dismiss">
        <i class="bi bi-x-lg"></i>
      </button>`;

    wrapper.querySelector('.pc-notif-close').addEventListener('click', function () {
      removeById(notif.id);
      wrapper.remove();
    });

    container.appendChild(wrapper);
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // Public API
  window.showPersistentNotification = function (notif) {
    if (!notif || !notif.id || !notif.message) return;
    const list = readList();
    const existing = list.find(n => n.id === notif.id);
    if (existing) {
      renderOne(existing);
      return;
    }
    const entry = {
      id: String(notif.id),
      title: notif.title ? String(notif.title) : '',
      message: String(notif.message),
      type: notif.type || 'success',
      ts: Date.now()
    };
    list.push(entry);
    writeList(list);
    renderOne(entry);
  };

  // Rehydrate stored notifications on every page load
  document.addEventListener('DOMContentLoaded', function () {
    readList().forEach(renderOne);
  });

  // If another tab dismisses or adds a notification, sync this tab
  window.addEventListener('storage', function (e) {
    if (e.key !== storageKey()) return;
    const container = document.getElementById('persistentToastContainer');
    if (!container) return;
    const current = readList();
    const currentIds = new Set(current.map(n => n.id));
    // Remove any DOM nodes whose ids were dropped
    Array.from(container.children).forEach(el => {
      const id = el.getAttribute('data-pc-notif-id');
      if (id && !currentIds.has(id)) el.remove();
    });
    // Render any new ones
    current.forEach(renderOne);
  });
})();

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
