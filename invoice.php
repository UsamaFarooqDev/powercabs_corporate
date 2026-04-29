<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user      = $_SESSION['user'];
$cid       = $user['cid'];
$pageTitle = 'Invoices';

$employees = [];
$company   = [
  'name'    => trim((string)($user['name']  ?? '')),
  'email'   => trim((string)($user['email'] ?? '')),
  'phone'   => '',
  'address' => '',
];

try {
  $supabase  = new SupabaseClient(true);
  $employees = $supabase->select('corporate_employees', ['cid' => $cid], '*', 'id.asc');
  foreach (corporate_row_filters_try($user) as $filter) {
    $results = $supabase->select('corporate', $filter, '*', null, 1);
    if (!empty($results)) {
      $r = $results[0];
      $company['name']    = trim((string)($r['name']    ?? $company['name']));
      $company['email']   = trim((string)($r['email']   ?? $company['email']));
      $company['phone']   = trim((string)($r['phone']   ?? $r['Phone']   ?? ''));
      $company['address'] = trim((string)($r['address'] ?? $r['Address'] ?? ''));
      break;
    }
  }
} catch (Throwable $e) { /* ignore */ }

$logoSvg = @file_get_contents(__DIR__ . '/assets/powercabs-logo-black.svg');
if (!$logoSvg) { $logoSvg = ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate - Invoices</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    /* ── shadcn-ish neutrals ── */
    body { background: #f8fafc; color: #0f172a; }

    .inv-card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .inv-page-head h6 {
      font-size: 1.05rem; font-weight: 600;
      color: #0f172a; letter-spacing: -.01em;
    }
    .inv-page-head .sub {
      font-size: var(--fs-card-sub);
      color: #64748b;
    }

    .inv-step-label {
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: .06em;
      color: #64748b;
    }

    .inv-divider { height: 1px; background: #e2e8f0; }

    .form-control, .form-select {
      font-size: var(--fs-input);
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: .45rem .7rem;
      color: #0f172a;
      background: #ffffff;
      transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
      outline: none;
      border-color: #f37a20;
      box-shadow: 0 0 0 3px rgba(243,122,32,.15);
    }
    label.inv-label {
      font-size: 11px; font-weight: 600; color: #475569;
      text-transform: uppercase; letter-spacing: .06em;
      margin-bottom: .35rem; display: block;
    }

    .btn-pc-primary {
      background: #f37a20; color: #fff; border: 1px solid #f37a20;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 500;
      padding: .45rem 1rem; display: inline-flex; align-items: center; gap: .4rem;
      transition: background .15s, box-shadow .15s, border-color .15s;
    }
    .btn-pc-primary:hover { background: #e06910; border-color: #e06910; color: #fff; box-shadow: 0 4px 14px rgba(243,122,32,.25); }
    .btn-pc-primary:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }

    .btn-pc-ghost {
      background: #ffffff; color: #0f172a; border: 1px solid #e2e8f0;
      border-radius: 8px; font-size: var(--fs-btn); font-weight: 500;
      padding: .45rem 1rem; transition: background .15s;
    }
    .btn-pc-ghost:hover { background: #f1f5f9; }

    /* ── Searchable employee dropdown ── */
    .emp-combo { position: relative; }
    .emp-combo-input {
      width: 100%;
      display: flex; align-items: center; gap: .5rem;
      border: 1px solid #e2e8f0; border-radius: 8px;
      background: #ffffff; color: #0f172a;
      padding: .42rem .7rem;
      font-size: var(--fs-input);
      cursor: text;
      transition: border-color .15s, box-shadow .15s;
    }
    .emp-combo.open .emp-combo-input,
    .emp-combo-input:focus-within {
      border-color: #f37a20;
      box-shadow: 0 0 0 3px rgba(243,122,32,.15);
    }
    .emp-combo-input .ic-prefix { color: #94a3b8; flex-shrink: 0; }
    .emp-combo-input input {
      flex: 1; min-width: 0;
      border: none; outline: none; padding: 0; background: transparent;
      font-size: var(--fs-input); color: #0f172a;
    }
    .emp-combo-input input::placeholder { color: #94a3b8; }
    .emp-combo-input .caret {
      color: #94a3b8; flex-shrink: 0;
      transition: transform .15s;
    }
    .emp-combo.open .emp-combo-input .caret { transform: rotate(180deg); }
    .emp-combo-input .clear {
      background: none; border: none; padding: 0;
      color: #94a3b8; font-size: 1rem;
      display: none; align-items: center; cursor: pointer;
    }
    .emp-combo.has-value .emp-combo-input .clear { display: inline-flex; }
    .emp-combo-input .clear:hover { color: #475569; }

    .emp-combo-panel {
      position: absolute; top: calc(100% + 6px); left: 0; right: 0;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      box-shadow: 0 10px 24px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.05);
      max-height: 280px; overflow: hidden;
      z-index: 30;
      display: none;
    }
    .emp-combo.open .emp-combo-panel { display: block; }
    .emp-combo-panel .panel-head {
      display: flex; justify-content: space-between; align-items: center;
      padding: .45rem .75rem;
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: .06em;
      color: #94a3b8;
      border-bottom: 1px solid #f1f5f9;
      background: #f8fafc;
    }
    .emp-combo-panel .panel-list {
      max-height: 232px; overflow-y: auto; padding: 4px 0;
    }
    .emp-combo-item {
      display: flex; align-items: center; gap: .65rem;
      padding: .5rem .75rem; cursor: pointer;
      transition: background .12s;
    }
    .emp-combo-item:hover,
    .emp-combo-item.active { background: #f1f5f9; }
    .emp-combo-item.selected { background: #fff4eb; }
    .emp-combo-item .av {
      width: 28px; height: 28px; border-radius: 50%;
      background: #fff4eb; color: #f37a20;
      font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; letter-spacing: .02em;
    }
    .emp-combo-item .info { flex: 1; min-width: 0; }
    .emp-combo-item .nm {
      font-size: var(--fs-body); font-weight: 500; color: #0f172a;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .emp-combo-item .meta {
      font-size: 11.5px; color: #64748b;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      margin-top: 1px;
    }
    .emp-combo-item .check {
      color: #f37a20; font-size: 1rem;
      visibility: hidden; flex-shrink: 0;
    }
    .emp-combo-item.selected .check { visibility: visible; }
    .emp-combo-empty {
      padding: 1rem; text-align: center;
      font-size: var(--fs-small); color: #94a3b8;
    }

    /* Hero strip — replaces the verbose summary text */
    .inv-hero {
      display: flex; align-items: center; gap: .75rem;
      padding: .9rem 1.1rem;
      border: 1px solid #e2e8f0;
      background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
      border-radius: 10px;
    }
    .inv-hero .ic {
      width: 36px; height: 36px; border-radius: 8px;
      background: #fff4eb; color: #f37a20;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.05rem;
    }
    .inv-hero .ttl {
      font-size: var(--fs-body); font-weight: 600; color: #0f172a; line-height: 1.25;
    }
    .inv-hero .desc {
      font-size: var(--fs-small); color: #64748b; margin-top: 2px; line-height: 1.35;
    }
    .inv-hero .badge-vat {
      margin-left: auto;
      font-size: 11px; font-weight: 600; letter-spacing: .03em;
      padding: .25rem .55rem; border-radius: 999px;
      background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
      white-space: nowrap;
    }

    /* Rides table inside invoice page */
    .inv-rides-table thead th {
      font-size: 11px; font-weight: 600; color: #64748b;
      text-transform: uppercase; letter-spacing: .05em;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: .55rem .7rem; white-space: nowrap; background: #f8fafc;
    }
    .inv-rides-table tbody td {
      font-size: var(--fs-td); color: #334155;
      padding: .65rem .7rem; vertical-align: middle;
      border-bottom: 1px solid #f1f5f9;
    }
    .inv-rides-table tbody tr:last-child td { border-bottom: none; }
    .inv-rides-table tbody tr:hover td { background: #f8fafc; }
    .inv-rides-table .form-check-input {
      position: static; opacity: 1; pointer-events: auto;
      width: 1rem; height: 1rem; margin: 0;
    }
    .inv-rides-empty {
      padding: 2rem; text-align: center; color: #94a3b8;
      font-size: var(--fs-body);
      border: 1px dashed #e2e8f0; border-radius: 10px; background: #f8fafc;
    }

    /* Bill details panel — replaces the descriptive paragraph */
    .inv-bill {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      overflow: hidden;
    }
    .inv-bill .head {
      display: flex; align-items: center; gap: .5rem;
      padding: .65rem .9rem;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: .06em;
      color: #475569;
    }
    .inv-bill .body { padding: .35rem 0; }
    .inv-bill .row-line {
      display: flex; justify-content: space-between; align-items: center; gap: .75rem;
      padding: .45rem .9rem;
      font-size: var(--fs-body);
    }
    .inv-bill .row-line .k { color: #64748b; }
    .inv-bill .row-line .v {
      color: #0f172a; font-weight: 500;
      max-width: 65%; text-align: right;
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .inv-bill .row-line .v.muted { color: #94a3b8; font-weight: 400; }

    /* Totals panel */
    .inv-totals {
      background: #ffffff; border: 1px solid #e2e8f0;
      border-radius: 10px; padding: .35rem 0;
    }
    .inv-totals .row-line {
      display: flex; justify-content: space-between; align-items: center;
      font-size: var(--fs-body); color: #334155;
      padding: .45rem .9rem;
    }
    .inv-totals .row-line.grand {
      border-top: 1px solid #e2e8f0; margin-top: .25rem; padding-top: .7rem;
      font-weight: 700; color: #0f172a; font-size: 1.05rem;
    }
    .inv-totals .row-line .lbl { color: #64748b; }

    /* ── Off-screen PDF stage (A4 portrait @ 96dpi) ── */
    .inv-pdf-stage {
      position: fixed; left: -10000px; top: 0;
      width: 794px;
      background: #fff;
      overflow: hidden;
    }

    /* Invoice template — sized for full A4 page (margin handled inside) */
    .invoice-template {
      width: 794px;
      padding: 28px 32px 24px;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      color: #0f172a; background: #fff;
      box-sizing: border-box;
      overflow: hidden;
    }

    .invoice-template .inv-head {
      display: flex; justify-content: space-between; align-items: center;
      border-bottom: 2px solid #f37a20; padding-bottom: 14px;
    }
    .invoice-template .inv-head .logo {
      display: block;
    }
    .invoice-template .inv-head .logo svg {
      height: 96px; width: auto; display: block;
    }
    .invoice-template .inv-head .right {
      text-align: right; line-height: 1.35;
    }
    .invoice-template .inv-head .right .title {
      font-size: 24px; font-weight: 700; color: #f37a20;
      letter-spacing: .04em; line-height: 1;
    }
    .invoice-template .inv-head .right .meta {
      font-size: 11.5px; color: #64748b; margin-top: 5px;
    }
    .invoice-template .inv-head .right .meta strong { color: #0f172a; font-weight: 600; }

    .invoice-template .inv-parties {
      display: flex; gap: 24px; margin: 18px 0 14px;
    }
    .invoice-template .inv-parties .col {
      flex: 1; line-height: 1.4; min-width: 0;
    }
    .invoice-template .inv-parties .col .lbl {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: #94a3b8; margin-bottom: 4px;
    }
    .invoice-template .inv-parties .col .nm {
      font-size: 13.5px; font-weight: 700; color: #0f172a;
      word-break: break-word;
    }
    .invoice-template .inv-parties .col .ln {
      font-size: 11.5px; color: #475569; margin-top: 1px;
      word-break: break-word;
    }

    .invoice-template table.inv-tbl {
      width: 100%; border-collapse: collapse; margin-top: 4px;
      table-layout: fixed;
    }
    .invoice-template table.inv-tbl col.col-num    { width: 26px; }
    .invoice-template table.inv-tbl col.col-date   { width: 110px; }
    .invoice-template table.inv-tbl col.col-charge { width: 78px; }
    .invoice-template table.inv-tbl thead th {
      background: #fff4eb; color: #92400e;
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
      padding: 7px 8px; text-align: left;
      border-bottom: 1px solid #fde2c4;
    }
    .invoice-template table.inv-tbl tbody td {
      padding: 7px 8px; font-size: 11px; color: #334155;
      border-bottom: 1px solid #f1f5f9; vertical-align: top;
      word-break: break-word; overflow-wrap: anywhere;
    }
    .invoice-template table.inv-tbl tbody td.nowrap { white-space: nowrap; word-break: normal; overflow-wrap: normal; }
    .invoice-template table.inv-tbl tfoot td {
      padding: 6px 8px; font-size: 11.5px; color: #334155;
    }
    .invoice-template table.inv-tbl tfoot tr.grand td {
      border-top: 2px solid #0f172a; font-weight: 700; color: #0f172a; font-size: 13px;
    }
    .invoice-template .inv-foot {
      margin-top: 18px; padding-top: 10px;
      border-top: 1px solid #e2e8f0;
      font-size: 10.5px; color: #94a3b8; text-align: center; font-style: italic;
    }
    .invoice-template .text-right { text-align: right; }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">

    <div class="card inv-card border-0 mb-4">
      <div class="card-body p-4">
        <div class="inv-page-head mb-3">
          <h6 class="mb-0">Generate Invoice</h6>
          <span class="sub d-block mt-1">Pick an employee, select their completed rides, then download a PDF invoice.</span>
        </div>

        <!-- Hero strip -->
        <div class="inv-hero mb-4">
          <div class="ic"><i class="bi bi-receipt"></i></div>
          <div class="flex-grow-1">
            <div class="ttl">Bill completed rides in seconds.</div>
            <div class="desc">Itemised, branded PDF invoices &mdash; ready to send to your finance team.</div>
          </div>
          <span class="badge-vat">VAT 23% included</span>
        </div>

        <!-- Step 1 + filters -->
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="inv-label">Employee</label>
            <div class="emp-combo" id="empCombo">
              <div class="emp-combo-input" id="empComboInput" tabindex="0">
                <i class="bi bi-person ic-prefix"></i>
                <input type="text" id="empComboSearch"
                       placeholder="Search employee by name, dept or email…"
                       autocomplete="off"/>
                <button type="button" class="clear" id="empComboClear" title="Clear">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
                <i class="bi bi-chevron-down caret"></i>
              </div>
              <div class="emp-combo-panel" id="empComboPanel">
                <div class="panel-head">
                  <span>Employees</span>
                  <span id="empComboCount"><?= count($employees) ?> total</span>
                </div>
                <div class="panel-list" id="empComboList"></div>
              </div>
            </div>
            <select id="empSelect" hidden>
              <option value="">— Select an employee —</option>
              <?php foreach ($employees as $emp): ?>
                <option
                  value="<?= htmlspecialchars((string)($emp['id'] ?? '')) ?>"
                  data-name="<?= htmlspecialchars((string)($emp['name']       ?? '')) ?>"
                  data-dept="<?= htmlspecialchars((string)($emp['department'] ?? '')) ?>"
                  data-email="<?= htmlspecialchars((string)($emp['email']     ?? '')) ?>"
                  data-phone="<?= htmlspecialchars((string)($emp['phone']     ?? '')) ?>"
                ><?= htmlspecialchars((string)($emp['name'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="inv-label">From</label>
            <input type="date" id="fromDate" class="form-control"/>
          </div>
          <div class="col-md-3">
            <label class="inv-label">To</label>
            <input type="date" id="toDate" class="form-control"/>
          </div>
          <div class="col-md-2 d-flex">
            <button id="loadRidesBtn" class="btn-pc-primary w-100 justify-content-center" disabled>
              <i class="bi bi-search"></i> Load Rides
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card inv-card border-0 mb-4" id="ridesCard" style="display:none">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h6 class="fw-semibold mb-0" style="font-size:1rem; color:#0f172a; letter-spacing:-.01em">
              Completed Rides
            </h6>
            <span class="d-block mt-1" style="font-size:var(--fs-card-sub); color:#64748b" id="ridesSummary">
              Tick the rides to include.
            </span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <label class="d-flex align-items-center gap-2 m-0" style="font-size:var(--fs-body); color:#334155; cursor:pointer">
              <input type="checkbox" id="selectAll" class="form-check-input" style="position:static;opacity:1;pointer-events:auto;width:1rem;height:1rem;margin:0;"/>
              Select all
            </label>
          </div>
        </div>

        <div id="ridesContainer">
          <div class="inv-rides-empty">No rides loaded yet.</div>
        </div>
      </div>
    </div>

    <div class="card inv-card border-0" id="totalsCard" style="display:none">
      <div class="card-body p-4">
        <div class="row g-3 align-items-stretch">

          <div class="col-md-7">
            <div class="inv-bill h-100">
              <div class="head"><i class="bi bi-person-vcard"></i> Bill Details</div>
              <div class="body">
                <div class="row-line"><span class="k">Billed to</span>      <span class="v" id="bdEmpName">—</span></div>
                <div class="row-line"><span class="k">Department</span>     <span class="v muted" id="bdEmpDept">—</span></div>
                <div class="row-line"><span class="k">Email</span>          <span class="v muted" id="bdEmpEmail">—</span></div>
                <div class="row-line"><span class="k">Period</span>         <span class="v" id="bdPeriod">—</span></div>
                <div class="row-line"><span class="k">Issue date</span>     <span class="v" id="bdIssueDate">—</span></div>
                <div class="row-line"><span class="k">Currency</span>       <span class="v">EUR (€)</span></div>
              </div>
            </div>
          </div>

          <div class="col-md-5">
            <div class="inv-totals">
              <div class="row-line"><span class="lbl">Selected rides</span><span id="selCount">0</span></div>
              <div class="row-line"><span class="lbl">Subtotal</span><span id="selSubtotal">€0.00</span></div>
              <div class="row-line"><span class="lbl">VAT (23%)</span><span id="selVat">€0.00</span></div>
              <div class="row-line grand"><span>Total</span><span id="selTotal">€0.00</span></div>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button id="generateBtn" class="btn-pc-primary" disabled>
                <i class="bi bi-file-earmark-pdf"></i> Generate &amp; Download PDF
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>

  <!-- Off-screen invoice template used for PDF rendering -->
  <div class="inv-pdf-stage" aria-hidden="true">
    <div class="invoice-template" id="invoiceTemplate">
      <div class="inv-head">
        <div class="logo"><?= $logoSvg ?></div>
        <div class="right">
          <div class="title">INVOICE</div>
          <div class="meta">
            <div><strong>Invoice #:</strong> <span id="tplInvNo">—</span></div>
            <div><strong>Date:</strong> <span id="tplInvDate">—</span></div>
          </div>
        </div>
      </div>

      <div class="inv-parties">
        <div class="col">
          <div class="lbl">Bill To</div>
          <div class="nm" id="tplEmpName">—</div>
          <div class="ln" id="tplEmpDept"></div>
          <div class="ln" id="tplEmpEmail"></div>
          <div class="ln" id="tplEmpPhone"></div>
        </div>
        <div class="col">
          <div class="lbl">Company</div>
          <div class="nm" id="tplCoName"><?= htmlspecialchars($company['name']) ?></div>
          <div class="ln" id="tplCoAddr"><?= htmlspecialchars($company['address']) ?></div>
          <div class="ln" id="tplCoEmail"><?= htmlspecialchars($company['email']) ?></div>
          <div class="ln" id="tplCoPhone"><?= htmlspecialchars($company['phone']) ?></div>
        </div>
      </div>

      <table class="inv-tbl">
        <colgroup>
          <col class="col-num"/>
          <col class="col-date"/>
          <col/>
          <col/>
          <col class="col-charge"/>
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Pickup</th>
            <th>Dropoff</th>
            <th class="text-right">Charge</th>
          </tr>
        </thead>
        <tbody id="tplRides"></tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-right">Subtotal</td>
            <td class="text-right nowrap" id="tplSubtotal">€0.00</td>
          </tr>
          <tr>
            <td colspan="4" class="text-right">VAT (23%)</td>
            <td class="text-right nowrap" id="tplVat">€0.00</td>
          </tr>
          <tr class="grand">
            <td colspan="4" class="text-right">Total</td>
            <td class="text-right nowrap" id="tplTotal">€0.00</td>
          </tr>
        </tfoot>
      </table>

      <div class="inv-foot">
        This is a system-generated invoice and does not require a signature or stamp to be valid.
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <script>
    // Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
    document.addEventListener('click', e => {
      if (window.innerWidth < 768
        && !e.target.closest('.sidebar')
        && !e.target.closest('#sidebarToggle'))
        document.querySelector('.sidebar')?.classList.remove('active');
    });

    const VAT_RATE = 0.23;
    const empSelect      = document.getElementById('empSelect');
    const empCombo       = document.getElementById('empCombo');
    const empComboInput  = document.getElementById('empComboInput');
    const empComboSearch = document.getElementById('empComboSearch');
    const empComboPanel  = document.getElementById('empComboPanel');
    const empComboList   = document.getElementById('empComboList');
    const empComboClear  = document.getElementById('empComboClear');
    const fromDate     = document.getElementById('fromDate');
    const toDate       = document.getElementById('toDate');
    const loadRidesBtn = document.getElementById('loadRidesBtn');
    const ridesCard    = document.getElementById('ridesCard');
    const ridesContainer = document.getElementById('ridesContainer');
    const ridesSummary = document.getElementById('ridesSummary');
    const selectAll    = document.getElementById('selectAll');
    const totalsCard   = document.getElementById('totalsCard');
    const selCount     = document.getElementById('selCount');
    const selSubtotal  = document.getElementById('selSubtotal');
    const selVat       = document.getElementById('selVat');
    const selTotal     = document.getElementById('selTotal');
    const generateBtn  = document.getElementById('generateBtn');

    const bdEmpName    = document.getElementById('bdEmpName');
    const bdEmpDept    = document.getElementById('bdEmpDept');
    const bdEmpEmail   = document.getElementById('bdEmpEmail');
    const bdPeriod     = document.getElementById('bdPeriod');
    const bdIssueDate  = document.getElementById('bdIssueDate');

    let currentRides = [];

    function setText(el, val, dashIfEmpty = true) {
      if (!el) return;
      const v = (val == null ? '' : String(val)).trim();
      el.textContent = v || (dashIfEmpty ? '—' : '');
      el.classList.toggle('muted', !v);
    }
    function periodLabel() {
      const f = fromDate.value, t = toDate.value;
      if (f && t) return `${f} → ${t}`;
      if (f)      return `From ${f}`;
      if (t)      return `Up to ${t}`;
      return 'All time';
    }
    function refreshBillDetails() {
      const opt = empSelect.options[empSelect.selectedIndex];
      const name  = opt && opt.value ? (opt.dataset.name  || '') : '';
      const dept  = opt && opt.value ? (opt.dataset.dept  || '') : '';
      const email = opt && opt.value ? (opt.dataset.email || '') : '';
      setText(bdEmpName,  name);
      setText(bdEmpDept,  dept);
      setText(bdEmpEmail, email);
      setText(bdPeriod,   periodLabel());
      setText(bdIssueDate, fmtDateOnly(new Date()));
    }

    // ── Searchable employee combo ──
    const employeesData = Array.from(empSelect.options)
      .filter(o => o.value)
      .map(o => ({
        id:    o.value,
        name:  o.dataset.name  || '',
        dept:  o.dataset.dept  || '',
        email: o.dataset.email || '',
        phone: o.dataset.phone || '',
      }));
    let comboFilter = '';
    let comboActiveIdx = -1;

    function initials(name) {
      const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
      if (!parts.length) return '?';
      if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    function filteredEmployees() {
      const q = comboFilter.trim().toLowerCase();
      if (!q) return employeesData;
      return employeesData.filter(e =>
        e.name.toLowerCase().includes(q) ||
        e.dept.toLowerCase().includes(q) ||
        e.email.toLowerCase().includes(q)
      );
    }
    function renderCombo() {
      const list = filteredEmployees();
      const selectedId = empSelect.value;
      if (!list.length) {
        empComboList.innerHTML = `<div class="emp-combo-empty">No employees match &ldquo;${escapeHtml(comboFilter)}&rdquo;.</div>`;
        return;
      }
      empComboList.innerHTML = list.map((e, i) => `
        <div class="emp-combo-item${e.id === selectedId ? ' selected' : ''}${i === comboActiveIdx ? ' active' : ''}"
             data-id="${escapeHtml(e.id)}">
          <div class="av">${escapeHtml(initials(e.name))}</div>
          <div class="info">
            <div class="nm">${escapeHtml(e.name || '—')}</div>
            <div class="meta">${escapeHtml([e.dept, e.email].filter(Boolean).join(' · ') || '—')}</div>
          </div>
          <i class="bi bi-check-lg check"></i>
        </div>
      `).join('');
    }
    function openCombo() {
      empCombo.classList.add('open');
      comboFilter = '';
      comboActiveIdx = -1;
      renderCombo();
      empComboSearch.focus();
      empComboSearch.select();
    }
    function closeCombo() {
      empCombo.classList.remove('open');
      comboActiveIdx = -1;
      // Keep the selected name in the input
      const opt = empSelect.options[empSelect.selectedIndex];
      empComboSearch.value = opt && opt.value ? (opt.dataset.name || '') : '';
      comboFilter = '';
    }
    function selectEmployee(id) {
      empSelect.value = id || '';
      empCombo.classList.toggle('has-value', !!id);
      const opt = empSelect.options[empSelect.selectedIndex];
      empComboSearch.value = opt && opt.value ? (opt.dataset.name || '') : '';
      empSelect.dispatchEvent(new Event('change'));
      closeCombo();
    }

    empComboInput.addEventListener('mousedown', (e) => {
      if (e.target.closest('.clear')) return;
      if (!empCombo.classList.contains('open')) {
        e.preventDefault();
        openCombo();
        empComboSearch.select();
      }
    });
    empComboSearch.addEventListener('focus', () => {
      if (!empCombo.classList.contains('open')) openCombo();
    });
    empComboSearch.addEventListener('input', () => {
      comboFilter = empComboSearch.value;
      comboActiveIdx = filteredEmployees().length ? 0 : -1;
      empCombo.classList.add('open');
      renderCombo();
    });
    empComboSearch.addEventListener('keydown', (e) => {
      const list = filteredEmployees();
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        comboActiveIdx = Math.min((comboActiveIdx < 0 ? -1 : comboActiveIdx) + 1, list.length - 1);
        renderCombo();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        comboActiveIdx = Math.max(comboActiveIdx - 1, 0);
        renderCombo();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (comboActiveIdx >= 0 && list[comboActiveIdx]) selectEmployee(list[comboActiveIdx].id);
      } else if (e.key === 'Escape') {
        closeCombo();
        empComboInput.blur();
      }
    });
    empComboList.addEventListener('click', (e) => {
      const item = e.target.closest('.emp-combo-item');
      if (!item) return;
      selectEmployee(item.dataset.id);
    });
    empComboClear.addEventListener('click', (e) => {
      e.stopPropagation();
      selectEmployee('');
    });
    document.addEventListener('mousedown', (e) => {
      if (!empCombo.contains(e.target)) closeCombo();
    });

    renderCombo();

    function fmtMoney(n) { return '€' + (Number(n) || 0).toFixed(2); }
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmtDate(raw) {
      if (!raw) return '';
      const d = new Date(String(raw).replace(' ', 'T'));
      if (isNaN(d.getTime())) return String(raw);
      let h = d.getHours();
      const ampm = h >= 12 ? 'PM' : 'AM';
      h = h % 12 || 12;
      return `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${String(d.getFullYear()).slice(-2)} ${pad(h)}:${pad(d.getMinutes())} ${ampm}`;
    }
    function fmtDateOnly(d) {
      return `${pad(d.getDate())} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()]} ${d.getFullYear()}`;
    }
    function escapeHtml(s) {
      return String(s == null ? '' : s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    empSelect.addEventListener('change', () => {
      loadRidesBtn.disabled = !empSelect.value;
      ridesCard.style.display = 'none';
      totalsCard.style.display = 'none';
      currentRides = [];
      refreshBillDetails();
    });
    fromDate.addEventListener('change', refreshBillDetails);
    toDate.addEventListener('change', refreshBillDetails);

    loadRidesBtn.addEventListener('click', loadRides);

    function loadRides() {
      const eid = empSelect.value;
      if (!eid) return;
      loadRidesBtn.disabled = true;
      const orig = loadRidesBtn.innerHTML;
      loadRidesBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…';

      const params = new URLSearchParams({ employee_id: eid });
      if (fromDate.value) params.set('from', fromDate.value);
      if (toDate.value)   params.set('to',   toDate.value);

      fetch('php/invoice_rides.php?' + params.toString(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
          loadRidesBtn.disabled = false;
          loadRidesBtn.innerHTML = orig;
          if (!data.success) {
            renderEmpty(data.message || 'Failed to load rides.');
            return;
          }
          currentRides = data.rides || [];
          renderRides();
        })
        .catch(() => {
          loadRidesBtn.disabled = false;
          loadRidesBtn.innerHTML = orig;
          renderEmpty('Network error. Please try again.');
        });
    }

    function renderEmpty(msg) {
      ridesCard.style.display = '';
      totalsCard.style.display = 'none';
      ridesContainer.innerHTML = `<div class="inv-rides-empty">${escapeHtml(msg)}</div>`;
      ridesSummary.textContent = '';
      selectAll.checked = false;
    }

    function renderRides() {
      ridesCard.style.display = '';
      if (!currentRides.length) {
        ridesContainer.innerHTML =
          `<div class="inv-rides-empty">No completed rides found for the selected employee${(fromDate.value||toDate.value)?' in this date range':''}.</div>`;
        ridesSummary.textContent = '';
        totalsCard.style.display = 'none';
        return;
      }

      const rows = currentRides.map((r, i) => `
        <tr>
          <td style="width:36px">
            <input type="checkbox" class="form-check-input ride-check" data-idx="${i}" checked/>
          </td>
          <td style="white-space:nowrap">${escapeHtml(fmtDate(r.pickupTime))}</td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escapeHtml(r.pickup)}">${escapeHtml(r.pickup)}</td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escapeHtml(r.destination)}">${escapeHtml(r.destination)}</td>
          <td>${escapeHtml(r.vehicle_number || 'N/A')}</td>
          <td style="text-align:right;white-space:nowrap">${fmtMoney(r.charge)}</td>
        </tr>
      `).join('');

      ridesContainer.innerHTML = `
        <div class="table-responsive">
          <table class="table inv-rides-table mb-0 w-100">
            <thead>
              <tr>
                <th></th>
                <th>Date &amp; Time</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Cab #</th>
                <th style="text-align:right">Charge</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>`;

      selectAll.checked = true;
      ridesSummary.textContent = `${currentRides.length} completed ride${currentRides.length === 1 ? '' : 's'} found.`;
      totalsCard.style.display = '';

      ridesContainer.querySelectorAll('.ride-check').forEach(cb => {
        cb.addEventListener('change', updateTotals);
      });
      updateTotals();
    }

    selectAll.addEventListener('change', () => {
      ridesContainer.querySelectorAll('.ride-check').forEach(cb => {
        cb.checked = selectAll.checked;
      });
      updateTotals();
    });

    function selectedRides() {
      return Array.from(ridesContainer.querySelectorAll('.ride-check'))
        .filter(cb => cb.checked)
        .map(cb => currentRides[parseInt(cb.dataset.idx, 10)]);
    }

    function updateTotals() {
      const sel = selectedRides();
      const subtotal = sel.reduce((a, r) => a + (Number(r.charge) || 0), 0);
      const vat   = subtotal * VAT_RATE;
      const total = subtotal + vat;
      selCount.textContent    = sel.length;
      selSubtotal.textContent = fmtMoney(subtotal);
      selVat.textContent      = fmtMoney(vat);
      selTotal.textContent    = fmtMoney(total);
      generateBtn.disabled    = sel.length === 0;

      // sync select-all checkbox
      const total_cb = ridesContainer.querySelectorAll('.ride-check').length;
      selectAll.checked = total_cb > 0 && sel.length === total_cb;
      selectAll.indeterminate = sel.length > 0 && sel.length < total_cb;
    }

    function makeInvoiceNumber() {
      const d = new Date();
      const seq = Math.floor(Math.random() * 9000) + 1000;
      return `INV-${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}-${seq}`;
    }

    generateBtn.addEventListener('click', () => {
      const sel = selectedRides();
      if (!sel.length) return;

      const opt = empSelect.options[empSelect.selectedIndex];
      const empName  = opt.dataset.name  || '';
      const empDept  = opt.dataset.dept  || '';
      const empEmail = opt.dataset.email || '';
      const empPhone = opt.dataset.phone || '';

      const subtotal = sel.reduce((a, r) => a + (Number(r.charge) || 0), 0);
      const vat   = subtotal * VAT_RATE;
      const total = subtotal + vat;

      const invNo   = makeInvoiceNumber();
      const invDate = fmtDateOnly(new Date());

      document.getElementById('tplInvNo').textContent   = invNo;
      document.getElementById('tplInvDate').textContent = invDate;
      document.getElementById('tplEmpName').textContent  = empName || '—';
      document.getElementById('tplEmpDept').textContent  = empDept;
      document.getElementById('tplEmpEmail').textContent = empEmail;
      document.getElementById('tplEmpPhone').textContent = empPhone;

      const tbody = document.getElementById('tplRides');
      tbody.innerHTML = sel.map((r, i) => `
        <tr>
          <td>${i + 1}</td>
          <td class="nowrap">${escapeHtml(fmtDate(r.pickupTime))}</td>
          <td>${escapeHtml(r.pickup)}</td>
          <td>${escapeHtml(r.destination)}</td>
          <td class="text-right nowrap">${fmtMoney(r.charge)}</td>
        </tr>
      `).join('');
      document.getElementById('tplSubtotal').textContent = fmtMoney(subtotal);
      document.getElementById('tplVat').textContent      = fmtMoney(vat);
      document.getElementById('tplTotal').textContent    = fmtMoney(total);

      const safeName = (empName || 'employee').replace(/[^a-z0-9]+/gi, '_');
      const filename = `${invNo}_${safeName}.pdf`;

      const orig = generateBtn.innerHTML;
      generateBtn.disabled = true;
      generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Generating…';

      const stage   = document.querySelector('.inv-pdf-stage');
      const element = document.getElementById('invoiceTemplate');

      // Move template on-screen but invisible so html2canvas measures correctly
      const prevLeft = stage.style.left;
      stage.style.left    = '0';
      stage.style.opacity = '0';
      stage.style.zIndex  = '-1';

      // Wait two animation frames so layout settles before capturing
      requestAnimationFrame(() => requestAnimationFrame(async () => {
        try {
          const canvas = await html2canvas(element, {
            scale:           2,
            useCORS:         true,
            backgroundColor: '#ffffff',
            logging:         false,
            width:           element.offsetWidth,
            height:          element.offsetHeight,
            windowWidth:     element.offsetWidth,
            windowHeight:    element.offsetHeight,
          });

          // Restore off-screen
          stage.style.left    = prevLeft || '-10000px';
          stage.style.opacity = '';
          stage.style.zIndex  = '';

          const { jsPDF } = window.jspdf;
          const pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });

          const pageW = pdf.internal.pageSize.getWidth();   // 210
          const pageH = pdf.internal.pageSize.getHeight();  // 297
          const imgW  = pageW;
          const imgH  = (canvas.height / canvas.width) * imgW;
          const imgData = canvas.toDataURL('image/jpeg', 0.98);

          if (imgH <= pageH) {
            pdf.addImage(imgData, 'JPEG', 0, 0, imgW, imgH);
          } else {
            // Multi-page: redraw the same image on each page, sliding it up
            let heightLeft = imgH;
            let position   = 0;
            pdf.addImage(imgData, 'JPEG', 0, position, imgW, imgH);
            heightLeft -= pageH;
            while (heightLeft > 0) {
              position -= pageH;
              pdf.addPage();
              pdf.addImage(imgData, 'JPEG', 0, position, imgW, imgH);
              heightLeft -= pageH;
            }
          }

          pdf.save(filename);
          generateBtn.disabled = false;
          generateBtn.innerHTML = orig;
        } catch (err) {
          stage.style.left    = prevLeft || '-10000px';
          stage.style.opacity = '';
          stage.style.zIndex  = '';
          console.error(err);
          generateBtn.disabled = false;
          generateBtn.innerHTML = orig;
          alert('Failed to generate PDF. Please try again.');
        }
      }));
    });
  </script>

</body>
</html>
