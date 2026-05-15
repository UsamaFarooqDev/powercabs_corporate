<?php
session_start();
require_once __DIR__ . '/auth/supabase.php';
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}
$user      = $_SESSION['user'];
$cid       = $user['cid'];
$pageTitle = 'Airport Meet & Greet';

$employees = [];
$rideTypes = [];
try {
  $supabase  = new SupabaseClient(true);
  $employees = $supabase->select('corporate_employees', ['cid' => $cid], 'id,name,email,department,phone', 'name.asc');
} catch (Throwable $e) { /* ignore */ }

/**
 * Tolerant icon renderer. Reads whichever icon column the `ride_types` row
 * exposes and turns it into renderable HTML. Accepts:
 *   • inline SVG markup        (`<svg …>…</svg>`)
 *   • an image URL              (`http://…`, `https://…`, `//…`, `data:image/…`, `/path…`)
 *   • a Bootstrap-Icons class   (`bi-car-front` or already `bi bi-car-front`)
 *   • a bare BI name            (`car-front` → `bi-car-front`)
 *   • a wrapped <i> tag         (`<i class="bi bi-car-front"></i>` — passed through verbatim)
 */
function mg_render_ride_icon(array $row, string $fallback = 'bi-car-front'): string {
  $candidates = [
    $row['icon']        ?? null,
    $row['icon_class']  ?? null,
    $row['icon_name']   ?? null,
    $row['image']       ?? null,
    $row['image_url']   ?? null,
    $row['icon_url']    ?? null,
    $row['svg']         ?? null,
  ];
  foreach ($candidates as $val) {
    $v = trim((string)$val);
    if ($v === '') continue;
    // Already-wrapped <i> tag — assume the row is intentional, return verbatim.
    if (stripos($v, '<i ') === 0 || stripos($v, '<i>') === 0) return $v;
    // Inline SVG.
    if (stripos($v, '<svg') === 0) return $v;
    // Image URL (any flavour).
    if (preg_match('#^(https?:)?//|^/[^/]|^data:image#i', $v)) {
      return '<img src="' . htmlspecialchars($v, ENT_QUOTES) . '" alt="" style="height:1.4rem;width:auto;display:block;"/>';
    }
    // Bootstrap-Icons class — normalise so the final element is `<i class="bi …">`.
    $cls = $v;
    // Strip a leading `bi ` if the row already includes the family.
    if (stripos($cls, 'bi ') === 0) $cls = trim(substr($cls, 3));
    if (stripos($cls, 'bi-') !== 0) $cls = 'bi-' . ltrim($cls, '-');
    return '<i class="bi ' . htmlspecialchars($cls) . '"></i>';
  }
  return '<i class="bi ' . htmlspecialchars($fallback) . '"></i>';
}

// Fetch ride_types from Supabase (with graceful fallback). If a request
// times out we must NOT retry — that would multiply the latency by the
// number of orderings and blow PHP's max_execution_time.
$rideTypesFromDb = false;
try {
  $rtSupabase   = $supabase ?? new SupabaseClient(true);
  $rideTypeRows = [];
  foreach (['sort_order.asc', 'order.asc', 'id.asc'] as $orderTry) {
    try {
      $rideTypeRows = $rtSupabase->select('ride_types', [], '*', $orderTry);
      break;
    } catch (Throwable $orderErr) {
      if (strpos($orderErr->getMessage(), '[28]') !== false) {
        $rideTypeRows = []; break;     // network timeout — don't retry
      }
      // 4xx on a missing column → try the next ordering.
    }
  }
  foreach ($rideTypeRows as $row) {
    // Read the human label from the most likely column names.
    $name  = trim((string)(
        $row['display_name'] ?? $row['label'] ?? $row['title']
        ?? $row['name']      ?? $row['type']  ?? ''
    ));
    // Read the value/identifier — this is what we send back to the server
    // when the user picks a ride type. Falls back to the name itself.
    $value = trim((string)($row['value'] ?? $row['key'] ?? $row['code'] ?? $row['type'] ?? $name));
    $desc  = trim((string)($row['description'] ?? $row['desc'] ?? $row['subtitle'] ?? ''));
    if ($value === '' && $name === '') continue;
    if ($value === '') $value = $name;
    if ($name  === '') $name  = $value;
    $rideTypes[] = [
      'value'    => $value,
      'name'     => $name,
      'iconHtml' => mg_render_ride_icon($row),
      'desc'     => $desc,
    ];
  }
  $rideTypesFromDb = !empty($rideTypes);
} catch (Throwable $e) {
  // Logged for debugging — page still loads with the static fallback.
  error_log('[meetGreet] ride_types fetch failed: ' . $e->getMessage());
  $rideTypes = [];
}
if (empty($rideTypes)) {
  $fallback = [
    ['value' => 'Economy',              'name' => 'Economy',         'icon' => 'bi-car-front',         'desc' => 'Affordable everyday rides'],
    ['value' => 'Economy XL',           'name' => 'Economy XL',      'icon' => 'bi-truck-front',       'desc' => 'Up to 6 passengers'],
    ['value' => 'Business',             'name' => 'Business',        'icon' => 'bi-briefcase-fill',    'desc' => 'Premium sedans'],
    ['value' => 'Business Plus',        'name' => 'Business Plus',   'icon' => 'bi-gem',               'desc' => 'Top-tier business rides'],
    ['value' => 'Limousine',            'name' => 'Limousine',       'icon' => 'bi-car-front-fill',    'desc' => 'Luxury chauffeur service'],
  ];
  foreach ($fallback as $rt) {
    $rideTypes[] = ['value' => $rt['value'], 'name' => $rt['name'], 'iconHtml' => mg_render_ride_icon($rt), 'desc' => $rt['desc']];
  }
}
$defaultRideType = $rideTypes[0]['value'] ?? 'Economy';

// Curated list of airports we serve. Coordinates are used to draw the route
// on the map and to compute the distance/fare against the user's address.
$airports = [
  ['code' => 'DUB', 'name' => 'Dublin Airport',                  'city' => 'Dublin',     'addr' => 'Dublin Airport, Co. Dublin, Ireland',                'lat' => 53.42139,  'lng' => -6.27000],
  ['code' => 'ORK', 'name' => 'Cork Airport',                    'city' => 'Cork',       'addr' => 'Cork Airport, Co. Cork, Ireland',                    'lat' => 51.84130,  'lng' => -8.49110],
  ['code' => 'SNN', 'name' => 'Shannon Airport',                 'city' => 'Shannon',    'addr' => 'Shannon Airport, Co. Clare, Ireland',                'lat' => 52.70190,  'lng' => -8.92480],
  ['code' => 'BFS', 'name' => 'Belfast International Airport',   'city' => 'Belfast',    'addr' => 'Belfast International Airport, Belfast, UK',         'lat' => 54.65750,  'lng' => -6.21560],
  ['code' => 'NOC', 'name' => 'Ireland West Airport Knock',      'city' => 'Knock',      'addr' => 'Ireland West Airport, Knock, Co. Mayo, Ireland',     'lat' => 53.91000,  'lng' => -8.81810],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PowerCabs Corporate — Airport Meet &amp; Greet</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="global.css" rel="stylesheet"/>

  <style>
    /* ── shadcn-ish neutrals ── */
    body { background: #f8fafc; color: #0f172a; }

    .mg-card {
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    /* Section labels */
    .mg-section-label {
      font-size: 11px; font-weight: 600;
      letter-spacing: .07em; text-transform: uppercase;
      color: #64748b;
    }
    .mg-card-title {
      font-size: 1.05rem; font-weight: 600;
      color: #0f172a; letter-spacing: -.01em;
    }
    .mg-card-sub { font-size: .82rem; color: #64748b; }

    /* ── HERO STRIP ── */
    .mg-hero {
      position: relative; overflow: hidden;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      background:
        radial-gradient(circle at 100% 0%, rgba(243,122,32,.10) 0%, transparent 55%),
        linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #ffffff;
      padding: 26px 28px;
    }
    .mg-hero::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
      background-size: 36px 36px;
      pointer-events: none;
    }
    .mg-hero-content { position: relative; z-index: 1; }
    .mg-hero-title-row {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 8px;
    }
    .mg-hero-icon {
      width: 48px; height: 48px;
      border-radius: 12px;
      background: rgba(243,122,32,.14);
      color: #f37a20;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.25rem;
      border: 1px solid rgba(243,122,32,.25);
    }
    .mg-hero-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.01em; line-height: 1.2; }
    .mg-hero-sub   { font-size: .9rem;  color: rgba(255,255,255,.65); line-height: 1.55; max-width: 940px; }

    .mg-hero-pills {
      display: flex; flex-wrap: wrap; gap: 8px;
    }
    .mg-hero-pill {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      font-size: .76rem; font-weight: 500;
      color: rgba(255,255,255,.85);
    }
    .mg-hero-pill i { color: #f37a20; font-size: .8rem; }

    .mg-hero-badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 10px;
      border-radius: 999px;
      background: rgba(243,122,32,.15);
      border: 1px solid rgba(243,122,32,.30);
      color: #fb923c;
      font-size: .69rem; font-weight: 700;
      letter-spacing: .06em; text-transform: uppercase;
      margin-bottom: 7px;
    }
    .mg-hero-badge i { font-size: .63rem; }

    .mg-hero-footer {
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 14px; flex-wrap: wrap;
      margin-top: 16px;
    }

    /* ── Service-type radio cards (Arrival / Departure) ── */
    .mg-service-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .mg-service-card {
      position: relative;
      padding: 14px 16px;
      border: 1px solid #e2e8f0;
      border-radius: 11px;
      background: #ffffff;
      cursor: pointer;
      transition: border-color .15s, background .15s, box-shadow .15s;
      display: flex; align-items: center; gap: 14px;
    }
    .mg-service-card:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-service-card.is-selected {
      border-color: #f37a20;
      background: #fff7f0;
      box-shadow: 0 0 0 3px rgba(243,122,32,.10);
    }
    .mg-service-card input[type="radio"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .mg-service-icon {
      width: 40px; height: 40px;
      border-radius: 10px;
      background: #f1f5f9;
      color: #475569;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.15rem;
      transition: background .15s, color .15s;
    }
    .mg-service-card.is-selected .mg-service-icon {
      background: #fff4eb; color: #f37a20;
    }
    .mg-service-info { flex: 1; min-width: 0; line-height: 1.3; }
    .mg-service-info .nm { font-size: .95rem; font-weight: 600; color: #0f172a; }
    .mg-service-info .desc { font-size: .77rem; color: #64748b; margin-top: 2px; }

    /* ── Field grid ── */
    .mg-label {
      font-size: 11px; font-weight: 600; color: #475569;
      text-transform: uppercase; letter-spacing: .06em;
      margin-bottom: 6px; display: block;
    }
    .mg-input,
    .mg-select,
    .mg-textarea {
      width: 100%;
      font-size: .9rem;
      padding: .5rem .75rem;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      color: #0f172a;
      background: #ffffff;
      transition: border-color .15s, box-shadow .15s;
    }
    .mg-textarea { min-height: 78px; resize: vertical; line-height: 1.5; }
    .mg-input:focus,
    .mg-select:focus,
    .mg-textarea:focus {
      outline: none;
      border-color: #f37a20;
      box-shadow: 0 0 0 3px rgba(243,122,32,.15);
    }
    .mg-input-prefix {
      position: relative;
    }
    .mg-input-prefix .pre-ic {
      position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: .9rem; pointer-events: none;
    }
    .mg-input-prefix .mg-input { padding-left: 32px; }

    .mg-help-text {
      font-size: .76rem; color: #94a3b8; margin-top: 5px;
    }

    /* ── Airport quick-pick chips ── */
    .mg-airport-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 10px;
    }
    .mg-airport-card {
      position: relative;
      padding: 12px 14px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      cursor: pointer;
      transition: border-color .15s, background .15s, box-shadow .15s;
      display: flex; align-items: center; gap: 12px;
    }
    .mg-airport-card input[type="radio"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .mg-airport-card:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-airport-card.is-selected {
      border-color: #f37a20;
      background: #fff7f0;
      box-shadow: 0 0 0 3px rgba(243,122,32,.10);
    }
    .mg-airport-code {
      width: 44px; height: 38px;
      border-radius: 8px;
      background: #f1f5f9;
      color: #334155;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-weight: 700; font-size: .82rem; letter-spacing: .03em;
      transition: background .15s, color .15s;
    }
    .mg-airport-card.is-selected .mg-airport-code {
      background: #f37a20; color: #fff;
    }
    .mg-airport-info { flex: 1; min-width: 0; line-height: 1.25; }
    .mg-airport-info .nm   { font-size: .82rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mg-airport-info .city { font-size: .72rem; color: #64748b; margin-top: 2px; }

    /* ── Buttons ── */
    .btn-mg-primary {
      background: #f37a20; color: #fff; border: 1px solid #f37a20;
      border-radius: 10px;
      padding: .6rem 1.2rem;
      font-size: .92rem; font-weight: 600;
      display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
      cursor: pointer;
      transition: background .15s, box-shadow .15s, border-color .15s;
    }
    .btn-mg-primary:hover {
      background: #e06910; border-color: #e06910;
      box-shadow: 0 6px 18px rgba(243,122,32,.28); color: #fff;
    }
    .btn-mg-primary:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }

    .btn-mg-ghost {
      background: #fff; color: #0f172a; border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: .55rem 1.1rem;
      font-size: .88rem; font-weight: 500;
      transition: background .15s;
    }
    .btn-mg-ghost:hover { background: #f1f5f9; }

    /* ── Side: included list ── */
    .mg-incl-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    .mg-incl-item:last-child { border-bottom: none; }
    .mg-incl-item .ic {
      width: 28px; height: 28px;
      border-radius: 7px;
      background: #fff4eb;
      color: #f37a20;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: .85rem;
    }
    .mg-incl-item .info { flex: 1; min-width: 0; line-height: 1.35; }
    .mg-incl-item .ttl { font-size: .85rem; font-weight: 600; color: #0f172a; }
    .mg-incl-item .sub { font-size: .76rem; color: #64748b; margin-top: 2px; }

    /* ── Live summary panel ── */
    .mg-summary {
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      padding: 18px 20px;
    }
    .mg-summary .row-line {
      display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;
      padding: 8px 0;
      border-bottom: 1px dashed #e2e8f0;
      font-size: .85rem;
    }
    .mg-summary .row-line:last-child { border-bottom: none; }
    .mg-summary .row-line .k { color: #64748b; flex-shrink: 0; }
    .mg-summary .row-line .v {
      color: #0f172a; font-weight: 500; text-align: right;
      max-width: 65%;
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .mg-summary .row-line .v.muted { color: #94a3b8; font-weight: 400; }

    /* ── Service tag ─ */
    .mg-tag {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: .72rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .mg-tag-arrival   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .mg-tag-departure { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }

    /* ── Airport card overflow fix ─ */
    .mg-airport-card { min-width: 0; overflow: hidden; }
    .mg-airport-card .nm,
    .mg-airport-card .city {
      display: block; max-width: 100%;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── 10% premium banner inside the hero ── */
    .mg-hero-notice {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 13px;
      border-radius: 10px;
      background: rgba(251,191,36,.10);
      border: 1px solid rgba(251,191,36,.30);
      color: #fde68a;
      font-size: .8rem; line-height: 1.4;
    }
    .mg-hero-notice i { color: #fbbf24; font-size: .95rem; flex-shrink: 0; }
    .mg-hero-notice strong { color: #ffffff; font-weight: 700; }

    /* ── Ride type grid (mg-styled, mirrors bookRide pattern) ── */
    .mg-ride-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 10px;
    }
    .mg-ride-card {
      position: relative;
      padding: 12px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      cursor: pointer;
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      text-align: center;
      transition: border-color .15s, background .15s, box-shadow .15s;
      min-width: 0;
    }
    .mg-ride-card input[type="radio"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .mg-ride-card:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-ride-card.is-selected {
      border-color: #f37a20;
      background: #fff7f0;
      box-shadow: 0 0 0 3px rgba(243,122,32,.10);
    }
    .mg-ride-icon {
      width: 38px; height: 38px;
      border-radius: 9px;
      background: #f1f5f9; color: #475569;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.05rem;
      transition: background .15s, color .15s;
    }
    .mg-ride-card.is-selected .mg-ride-icon {
      background: #fff4eb; color: #f37a20;
    }
    .mg-ride-name {
      font-size: .82rem; font-weight: 600; color: #0f172a;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 100%;
    }
    .mg-ride-desc {
      font-size: .68rem; color: #94a3b8;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      max-width: 100%; margin-top: 1px;
    }
    .mg-ride-card.is-selected .mg-ride-desc { color: #b45309; }

    /* "Live from ride_types" badge — confirms the source of the data */
    .mg-source-tag {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px; font-weight: 600;
      letter-spacing: .03em;
    }
    .mg-source-tag i { font-size: .82rem; }
    .mg-source-tag code {
      font-size: 10.5px; padding: 1px 5px;
      border-radius: 4px;
      background: rgba(15,23,42,.06);
      color: inherit;
    }
    .mg-source-tag .mg-source-count {
      padding: 1px 7px;
      border-radius: 999px;
      background: rgba(15,23,42,.06);
      font-size: 10px; font-weight: 700;
    }
    .mg-source-live {
      background: #ecfdf5; color: #047857;
      border: 1px solid #a7f3d0;
    }
    .mg-source-fallback {
      background: #fef3c7; color: #92400e;
      border: 1px solid #fde68a;
    }

    /* ── Map + fare display ── */
    .mg-map-wrap {
      position: relative;
      width: 100%;
      height: 260px;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      overflow: hidden;
      background: #f1f5f9;
    }
    .mg-map-wrap #mgMap { width: 100%; height: 100%; }
    .mg-map-wrap .map-placeholder {
      position: absolute; inset: 0;
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
      color: #94a3b8; font-size: .85rem;
      pointer-events: none;
    }
    .mg-map-wrap.has-route .map-placeholder { display: none; }

    /* ── Fare result panel ── */
    .mg-fare-panel {
      margin-top: 14px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      padding: 14px 16px;
    }
    .mg-fare-panel.is-empty { background: #fafbfc; }
    .mg-fare-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 10px;
    }
    .mg-fare-stat {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      padding: 10px 12px;
      line-height: 1.25;
    }
    .mg-fare-stat .lbl {
      font-size: 10.5px; font-weight: 600;
      text-transform: uppercase; letter-spacing: .06em;
      color: #94a3b8;
    }
    .mg-fare-stat .val {
      font-size: 1rem; font-weight: 700; color: #0f172a;
      margin-top: 2px;
    }
    .mg-fare-stat.is-fare {
      background: #fff7f0;
      border-color: #fde2c4;
    }
    .mg-fare-stat.is-fare .val { color: #b45309; }
    .mg-fare-note {
      font-size: .76rem; color: #64748b;
      display: flex; align-items: flex-start; gap: 8px;
    }
    .mg-fare-note i { color: #f37a20; font-size: .85rem; margin-top: 2px; flex-shrink: 0; }
    .mg-fare-note strong { color: #0f172a; }

    /* ── Stripe payment area ── */
    .mg-pay-card {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #ffffff;
      padding: 16px;
    }
    .mg-pay-row {
      display: flex; align-items: center; gap: 12px;
      flex-wrap: wrap;
    }
    .btn-stripe {
      background: #635bff; color: #ffffff; border: 1px solid #635bff;
      border-radius: 10px;
      padding: .6rem 1rem;
      font-size: .92rem; font-weight: 600;
      display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
      text-decoration: none;
      transition: background .15s, box-shadow .15s, border-color .15s, transform .12s;
      cursor: pointer;
    }
    .btn-stripe:hover {
      background: #524ce0; border-color: #524ce0; color: #ffffff;
      box-shadow: 0 6px 18px rgba(99,91,255,.25);
      transform: translateY(-1px);
    }
    .btn-stripe svg { width: 38px; height: 14px; }
    .mg-pay-amount {
      font-size: .85rem; color: #475569;
    }
    .mg-pay-amount strong { color: #0f172a; font-size: .95rem; font-weight: 700; }

    .mg-pay-question {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px dashed #e2e8f0;
    }
    .mg-pay-question .q {
      font-size: .85rem; font-weight: 600; color: #0f172a;
      margin-bottom: 8px;
    }
    .mg-pay-options {
      display: flex; gap: 10px; flex-wrap: wrap;
    }
    .mg-pay-opt {
      position: relative;
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 14px;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      background: #ffffff;
      cursor: pointer;
      font-size: .85rem; font-weight: 500; color: #334155;
      transition: border-color .15s, background .15s, box-shadow .15s;
      min-width: 110px;
      justify-content: center;
    }
    .mg-pay-opt input[type="radio"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .mg-pay-opt:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-pay-opt.is-yes.is-selected {
      border-color: #16a34a; background: #f0fdf4; color: #15803d;
      box-shadow: 0 0 0 3px rgba(22,163,74,.10);
    }
    .mg-pay-opt.is-no.is-selected {
      border-color: #dc2626; background: #fef2f2; color: #b91c1c;
      box-shadow: 0 0 0 3px rgba(220,38,38,.10);
    }
    .mg-pay-opt i { font-size: .9rem; }

    /* Disabled state visual when payment is not confirmed */
    .btn-mg-primary[disabled] {
      opacity: .5;
      cursor: not-allowed;
      box-shadow: none;
    }

    /* ── Payment method selector ── */
    .mg-pay-methods {
      display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;
      margin-bottom: 16px;
    }
    .mg-pay-method {
      position: relative;
      display: flex; align-items: center; gap: 12px;
      padding: 12px 14px;
      border: 1px solid #e2e8f0; border-radius: 10px;
      background: #ffffff; cursor: pointer;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .mg-pay-method:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-pay-method.is-selected {
      border-color: #f37a20; background: #fff7f0;
      box-shadow: 0 0 0 3px rgba(243,122,32,.10);
    }
    .mg-pay-method .mp-icon {
      width: 36px; height: 36px; border-radius: 8px;
      background: #f1f5f9; color: #475569;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1rem;
      transition: background .15s, color .15s;
    }
    .mg-pay-method.is-selected .mp-icon { background: #fff4eb; color: #f37a20; }
    .mp-title { font-size: .88rem; font-weight: 600; color: #0f172a; }
    .mp-sub   { font-size: .74rem; color: #64748b; margin-top: 1px; }

    /* Stripe button — two-line variant */
    .btn-stripe.two-line {
      flex-direction: column; align-items: flex-start; gap: .25rem;
      padding: .65rem 1.1rem;
    }
    .btn-stripe.two-line .stripe-top {
      display: flex; align-items: center; gap: .45rem;
    }
    .btn-stripe.two-line .stripe-fee {
      font-size: .72rem; font-weight: 400; opacity: .82; line-height: 1.3;
    }

    /* Company charge panel */
    .mg-company-notice {
      display: flex; align-items: flex-start; gap: 14px;
      padding: 14px 16px;
      border-radius: 10px;
      background: #f0fdf4; border: 1px solid #bbf7d0;
    }
    .mg-company-notice .cn-icon {
      width: 38px; height: 38px; border-radius: 9px;
      background: #dcfce7; color: #16a34a;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 1.15rem;
    }
    .mg-company-notice .cn-title { font-size: .9rem; font-weight: 600; color: #0f172a; margin-bottom: 3px; }
    .mg-company-notice .cn-sub   { font-size: .78rem; color: #64748b; line-height: 1.45; }

    /* ── In-summary fare row ── */
    .mg-summary .row-line .v-fare { color: #b45309; font-weight: 700; }

    /* ── Passenger type cards ── */
    .mg-pax-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px; margin-bottom: 18px;
    }
    .mg-pax-card {
      position: relative;
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      padding: 12px 10px;
      border: 1px solid #e2e8f0; border-radius: 10px;
      background: #ffffff; cursor: pointer; text-align: center;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .mg-pax-card:hover { border-color: #cbd5e1; background: #f8fafc; }
    .mg-pax-card.is-selected {
      border-color: #f37a20; background: #fff7f0;
      box-shadow: 0 0 0 3px rgba(243,122,32,.10);
    }
    .mg-pax-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
    .mg-pax-card .pax-ic {
      width: 36px; height: 36px; border-radius: 9px;
      background: #f1f5f9; color: #475569;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; flex-shrink: 0;
      transition: background .15s, color .15s;
    }
    .mg-pax-card.is-selected .pax-ic { background: #fff4eb; color: #f37a20; }
    .mg-pax-card .pax-nm { font-size: .82rem; font-weight: 600; color: #0f172a; }
  </style>
</head>
<body>

  <?php require 'modules/navbar.php'; ?>

  <main class="main-content p-4">

    <!-- ── HERO ── -->
    <div class="mg-hero mb-4">
      <div class="mg-hero-content">
        <div class="mg-hero-title-row">
          <div class="mg-hero-icon"><i class="bi bi-airplane-fill"></i></div>
          <div>
            <span class="mg-hero-badge"><i class="bi bi-star-fill"></i> Premium Transfer Service</span>
            <div class="mg-hero-title">Airport Meet &amp; Greet</div>
            <div class="mg-hero-sub">A premium, pre-booked airport transfer service for your employees, clients, and VIP guests — delivered by professional drivers who provide a personalised meet-and-greet with a name placard, assist with luggage, and monitor flights in real time to ensure a smooth and punctual arrival. For VIP collections, our limousine service features professionally dressed chauffeurs; simply select the limousine option for this elevated experience.</div>
          </div>
        </div>

        <div class="mg-hero-footer">
          <div class="mg-hero-pills">
            <span class="mg-hero-pill"><i class="bi bi-person-vcard-fill"></i> Personal name placard</span>
            <span class="mg-hero-pill"><i class="bi bi-bag-fill"></i> Luggage assistance</span>
            <span class="mg-hero-pill"><i class="bi bi-broadcast"></i> Live flight tracking</span>
            <span class="mg-hero-pill"><i class="bi bi-stopwatch"></i> 60-min free wait</span>
            <span class="mg-hero-pill"><i class="bi bi-shield-check"></i> Vetted drivers</span>
          </div>
          <div class="mg-hero-notice">
            <i class="bi bi-info-circle-fill"></i>
            <span><strong>10% premium</strong> applies on top of the standard ride fare.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <!-- ───────── LEFT: Form ───────── -->
      <div class="col-xl-8">
        <form id="meetGreetForm" novalidate>

          <!-- Service type -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <h6 class="mg-card-title mb-0">Service Type</h6>
                  <span class="mg-card-sub d-block mt-1">Choose whether you need a pickup at the airport or a drop-off there.</span>
                </div>
              </div>

              <div class="mg-service-grid">
                <label class="mg-service-card is-selected" data-value="Arrival">
                  <input type="radio" name="serviceType" value="Arrival" checked/>
                  <span class="mg-service-icon"><i class="bi bi-airplane-engines-fill"></i></span>
                  <span class="mg-service-info">
                    <span class="nm">Arrival</span>
                    <span class="desc">Driver meets traveller at airport &amp; drops them at destination.</span>
                  </span>
                </label>
                <label class="mg-service-card" data-value="Departure">
                  <input type="radio" name="serviceType" value="Departure"/>
                  <span class="mg-service-icon"><i class="bi bi-send-fill"></i></span>
                  <span class="mg-service-info">
                    <span class="nm">Departure</span>
                    <span class="desc">Driver collects traveller and escorts them to the airport.</span>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Passenger -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0">Passenger</h6>
                <span class="mg-card-sub d-block mt-1">Who is travelling? Contact details will be passed to the driver.</span>
              </div>

              <!-- Passenger type selector -->
              <div class="mg-pax-grid" id="paxTypeGrid">
                <label class="mg-pax-card is-selected" data-pax="employee">
                  <input type="radio" name="passengerType" value="employee" checked/>
                  <span class="pax-ic"><i class="bi bi-person-fill"></i></span>
                  <span class="pax-nm">Employee</span>
                </label>
                <label class="mg-pax-card" data-pax="guest">
                  <input type="radio" name="passengerType" value="guest"/>
                  <span class="pax-ic"><i class="bi bi-person-badge"></i></span>
                  <span class="pax-nm">Guest</span>
                </label>
                <label class="mg-pax-card" data-pax="both">
                  <input type="radio" name="passengerType" value="both"/>
                  <span class="pax-ic"><i class="bi bi-people-fill"></i></span>
                  <span class="pax-nm">Both</span>
                </label>
              </div>

              <div class="row g-3">
                <!-- Employee dropdown: shown for Employee + Both -->
                <div class="col-md-6" id="mgEmployeeCol">
                  <label class="mg-label" for="employee">Employee</label>
                  <select id="employee" name="employee_id" class="mg-select">
                    <option value="">— Select an employee —</option>
                    <?php foreach ($employees as $emp): ?>
                      <option
                        value="<?= htmlspecialchars((string)($emp['id'] ?? '')) ?>"
                        data-name="<?= htmlspecialchars((string)($emp['name'] ?? '')) ?>"
                        data-email="<?= htmlspecialchars((string)($emp['email'] ?? '')) ?>"
                        data-phone="<?= htmlspecialchars((string)($emp['phone'] ?? '')) ?>"
                        data-dept="<?= htmlspecialchars((string)($emp['department'] ?? '')) ?>"
                      ><?= htmlspecialchars((string)($emp['name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <!-- Guest name: shown for Guest + Both -->
                <div class="col-md-6" id="mgGuestNameCol" style="display:none">
                  <label class="mg-label" for="guestName">Guest Name</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-person-badge pre-ic"></i>
                    <input type="text" id="guestName" name="guest_name" class="mg-input"
                           placeholder="Full name of the guest"/>
                  </div>
                </div>
                <!-- Guest phone: shown for Guest + Both -->
                <div class="col-md-6" id="mgGuestPhoneCol" style="display:none">
                  <label class="mg-label" for="guestPhone">Guest Phone</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-telephone pre-ic"></i>
                    <input type="text" id="guestPhone" name="guest_phone" class="mg-input"
                           placeholder="Contact number (optional)"/>
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="mg-label" for="passengers">Passengers</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-people pre-ic"></i>
                    <input type="number" min="1" max="8" value="1" id="passengers" name="passengers" class="mg-input"/>
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="mg-label" for="luggage">Luggage</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-bag pre-ic"></i>
                    <input type="number" min="0" max="12" value="2" id="luggage" name="luggage" class="mg-input"/>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ride Type -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                  <div>
                    <h6 class="mg-card-title mb-0">Ride Type</h6>
                    <span class="mg-card-sub d-block mt-1">Pick the vehicle class. Fare scales with the ride type and the 10% Meet &amp; Greet premium.</span>
                  </div>
                </div>
              </div>

              <div class="mg-ride-grid" id="rideTypeGrid">
                <?php foreach ($rideTypes as $i => $rt):
                    $checked = $i === 0;
                ?>
                  <label class="mg-ride-card<?= $checked ? ' is-selected' : '' ?>"
                         data-value="<?= htmlspecialchars($rt['value']) ?>"
                         title="<?= htmlspecialchars($rt['desc'] ?: $rt['name']) ?>">
                    <input type="radio" name="rideTypeRadio" value="<?= htmlspecialchars($rt['value']) ?>" <?= $checked ? 'checked' : '' ?>/>
                    <span class="mg-ride-icon"><?= $rt['iconHtml'] ?></span>
                    <span class="mg-ride-name"><?= htmlspecialchars($rt['name']) ?></span>
                    <?php if (!empty($rt['desc'])): ?>
                      <span class="mg-ride-desc"><?= htmlspecialchars($rt['desc']) ?></span>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="carType" id="carType" value="<?= htmlspecialchars($defaultRideType) ?>"/>
            </div>
          </div>

          <!-- Airport -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0">Airport</h6>
                <span class="mg-card-sub d-block mt-1">Pick the airport for this booking.</span>
              </div>

              <div class="mg-airport-grid mb-3">
                <?php foreach ($airports as $i => $ap): ?>
                <label class="mg-airport-card<?= $i === 0 ? ' is-selected' : '' ?>"
                       data-code="<?= htmlspecialchars($ap['code']) ?>"
                       data-name="<?= htmlspecialchars($ap['name']) ?>"
                       data-addr="<?= htmlspecialchars($ap['addr']) ?>"
                       data-lat="<?= htmlspecialchars((string)$ap['lat']) ?>"
                       data-lng="<?= htmlspecialchars((string)$ap['lng']) ?>">
                  <input type="radio" name="airport" value="<?= htmlspecialchars($ap['code']) ?>" <?= $i === 0 ? 'checked' : '' ?>/>
                  <span class="mg-airport-code"><?= htmlspecialchars($ap['code']) ?></span>
                  <span class="mg-airport-info">
                    <span class="nm"><?= htmlspecialchars($ap['name']) ?></span>
                    <span class="city"><?= htmlspecialchars($ap['city']) ?></span>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="mg-label" for="terminal">Terminal / Meeting Point</label>
                  <select id="terminal" class="mg-select">
                    <option value="">Driver decides on the day</option>
                    <option>Terminal 1 — Arrivals</option>
                    <option>Terminal 2 — Arrivals</option>
                    <option>Terminal 1 — Departures</option>
                    <option>Terminal 2 — Departures</option>
                    <option>Terminal not sure</option>
                    <option>Platinum Arrivals</option>
                    <option>Platinum Departures</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="mg-label" for="placard" id="placardLabel">Name on Placard</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-person-vcard pre-ic"></i>
                    <input type="text" id="placard" name="placard" class="mg-input"
                           placeholder="e.g. Dr. Mustafa Khan / Acme Ltd"/>
                  </div>
                  <div class="mg-help-text">Shown by the driver at arrivals.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Flight -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0">Flight Details</h6>
                <span class="mg-card-sub d-block mt-1">Used for live flight tracking and ETA adjustments.</span>
              </div>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="mg-label" for="airline">Airline</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-airplane-fill pre-ic"></i>
                    <input type="text" id="airline" name="airline" class="mg-input" placeholder="e.g. Aer Lingus"/>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="mg-label" for="flightNo">Flight Number</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-tag pre-ic"></i>
                    <input type="text" id="flightNo" name="flight_no" class="mg-input" placeholder="e.g. EI 521" required/>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="mg-label" for="flightTime" id="flightTimeLabel">Arrival Date &amp; Time</label>
                  <input type="datetime-local" id="flightTime" name="flight_time" class="mg-input" required/>
                </div>
              </div>
            </div>
          </div>

          <!-- Address + map + fare -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0" id="addressCardTitle">Drop-off Address</h6>
                <span class="mg-card-sub d-block mt-1" id="addressCardSub">Where to take the passenger after airport pickup.</span>
              </div>

              <div class="row g-3">
                <div class="col-md-12">
                  <label class="mg-label" id="addressLabel" for="address">Address</label>
                  <div class="mg-input-prefix">
                    <i class="bi bi-geo-alt pre-ic"></i>
                    <input type="text" id="address" name="address" class="mg-input"
                           placeholder="Hotel name, office or full street address" autocomplete="off" required/>
                  </div>
                  <div class="mg-help-text">Type to search &mdash; places will auto-suggest.</div>
                </div>

                <div class="col-md-12">
                  <div class="mg-map-wrap" id="mgMapWrap">
                    <div id="mgMap"></div>
                    <div class="map-placeholder">
                      <i class="bi bi-map" style="font-size:1.4rem"></i>
                      <span>Pick an airport and an address to draw the route &amp; calculate fare.</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Fare result panel -->
              <div class="mg-fare-panel is-empty mt-3" id="fareResult">
                <div class="mg-fare-grid">
                  <div class="mg-fare-stat">
                    <div class="lbl">Distance</div>
                    <div class="val" id="fareDistance">—</div>
                  </div>
                  <div class="mg-fare-stat">
                    <div class="lbl">Duration</div>
                    <div class="val" id="fareDuration">—</div>
                  </div>
                  <div class="mg-fare-stat is-fare">
                    <div class="lbl">Estimated fare</div>
                    <div class="val" id="fareTotal">€—</div>
                  </div>
                </div>
                <div class="mg-fare-note">
                  <i class="bi bi-info-circle-fill"></i>
                  <span>
                    Fare is calculated from the route and ride type, then a
                    <strong>10% Meet &amp; Greet premium</strong> is added.
                    Payment is taken via Stripe before the booking is confirmed.
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Special instructions -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0">Special Instructions</h6>
                <span class="mg-card-sub d-block mt-1">Anything the driver should know &mdash; child seat, accessibility needs, multiple stops.</span>
              </div>
              <textarea id="notes" name="notes" class="mg-textarea"
                        placeholder="e.g. Two child seats required. Passenger uses a wheelchair. Stop at office before hotel."></textarea>
            </div>
          </div>

          <!-- Payment -->
          <div class="card mg-card border-0 mb-4">
            <div class="card-body p-4">
              <div class="mb-3">
                <h6 class="mg-card-title mb-0">Payment</h6>
                <span class="mg-card-sub d-block mt-1">Choose how this booking will be paid.</span>
              </div>

              <div class="mg-pay-card">

                <!-- Payment method selector -->
                <div class="mg-pay-methods">
                  <div class="mg-pay-method is-selected" id="methodStripe" data-method="stripe">
                    <div class="mp-icon"><i class="bi bi-credit-card"></i></div>
                    <div>
                      <div class="mp-title">Pay via Stripe</div>
                      <div class="mp-sub">Secure online payment</div>
                    </div>
                  </div>
                  <div class="mg-pay-method" id="methodCompany" data-method="company">
                    <div class="mp-icon"><i class="bi bi-building"></i></div>
                    <div>
                      <div class="mp-title">Charge to Company</div>
                      <div class="mp-sub">Invoice your company account</div>
                    </div>
                  </div>
                </div>

                <!-- Stripe panel -->
                <div id="stripePanel">
                  <div class="mg-pay-row mb-3">
                    <a href="https://buy.stripe.com/5kQ6oH1NL1Zpd81arZfQI02"
                       target="_blank" rel="noopener"
                       class="btn-stripe two-line" id="stripeBtn">
                      <span class="stripe-top">
                        <svg viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg" style="width:38px;height:14px">
                          <path fill="#fff" d="M59.64 14.28h-8.06v-1.6c0-.94.56-1.5 1.27-1.5.66 0 1.16.32 1.21 1.04l4.71-.27c-.21-2.84-2.5-4.05-5.94-4.05-3.65 0-6.04 1.96-6.04 5.36v6.5c0 3.4 2.39 5.36 6.04 5.36 3.45 0 5.74-1.21 5.94-4.06l-4.71-.27c-.05.72-.55 1.04-1.21 1.04-.71 0-1.27-.55-1.27-1.5v-1.6h8.06v-2.45zM30.27 7.91c-2.05 0-3.36.96-4.04 1.62l-.27-1.29h-4.55v22.97l5.17-1.1V25.6c.74.54 1.83 1.31 3.65 1.31 3.7 0 7.06-2.97 7.06-9.51 0-5.99-3.41-9.49-7.02-9.49zm-1.24 14.6c-1.22 0-1.94-.43-2.43-.96l-.04-7.65c.53-.59 1.27-1 2.47-1 1.89 0 3.21 2.13 3.21 4.78 0 2.7-1.3 4.83-3.21 4.83zm-15.7-7.91c0-1.6 1.28-2.21 3.4-2.21 3.04 0 6.88.93 9.92 2.59V8.49c-3.31-1.34-6.59-1.86-9.92-1.86-8.13 0-13.5 4.27-13.5 11.4 0 11.13 15.21 9.34 15.21 14.16 0 1.88-1.6 2.46-3.83 2.46-3.32 0-7.59-1.36-10.96-3.21v5.6c3.74 1.62 7.55 2.31 10.96 2.31 8.36 0 14.04-4.16 14.04-11.42-.04-11.99-15.32-9.85-15.32-14.34zM41.85 5.55l-5.17 1.1V2L41.85.9v4.65zm0 2.69h-5.17V25.93h5.17V8.24z"/>
                        </svg>
                        Pay with Stripe
                        <i class="bi bi-arrow-up-right" style="font-size:.8rem"></i>
                      </span>
                      <span class="stripe-fee">Amount due (additional €10.00 for this service will apply)</span>
                    </a>
                    <div class="mg-pay-amount">
                      Total due (incl. 10% premium): <strong id="payAmount">€—</strong>
                    </div>
                  </div>

                  <div class="mg-pay-question">
                    <div class="q">
                      <i class="bi bi-credit-card-2-front me-1" style="color:#f37a20"></i>
                      Did you complete the Stripe payment?
                    </div>
                    <div class="mg-pay-options">
                      <label class="mg-pay-opt is-yes" data-paid="yes">
                        <input type="radio" name="paid" value="yes"/>
                        <i class="bi bi-check-circle-fill"></i> Yes, paid
                      </label>
                      <label class="mg-pay-opt is-no is-selected" data-paid="no">
                        <input type="radio" name="paid" value="no" checked/>
                        <i class="bi bi-x-circle-fill"></i> Not yet
                      </label>
                    </div>
                    <div class="mg-help-text mt-2" id="payHint">
                      Confirm Booking unlocks once you mark the payment as paid.
                    </div>
                  </div>
                </div>

                <!-- Company charge panel -->
                <div id="companyPanel" style="display:none">
                  <div class="mg-company-notice">
                    <div class="cn-icon"><i class="bi bi-building-check"></i></div>
                    <div>
                      <div class="cn-title">Charge to company account</div>
                      <div class="cn-sub">This booking will be invoiced to your company account. No upfront payment is required — click Confirm Booking below to send it to dispatch.</div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Submit row -->
          <div class="d-flex justify-content-end gap-2 mb-3">
            <button type="reset" class="btn-mg-ghost">Reset</button>
            <button type="submit" id="confirmBtn" class="btn-mg-primary" disabled>
              <i class="bi bi-check2-circle"></i> Confirm Booking
            </button>
          </div>
        </form>
      </div>

      <!-- ───────── RIGHT: Live summary + included ───────── -->
      <div class="col-xl-4">

        <!-- Live booking summary -->
        <div class="mg-summary mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mg-card-title mb-0">Booking Summary</h6>
            <span class="mg-tag mg-tag-arrival" id="summaryTag">Arrival</span>
          </div>
          <p class="mg-card-sub mb-3">Live preview of the booking you're creating.</p>

          <div class="row-line"><span class="k">Service</span>     <span class="v" id="sumService">Arrival</span></div>
          <div class="row-line"><span class="k">Passenger</span>   <span class="v muted" id="sumPassenger">—</span></div>
          <div class="row-line"><span class="k">Airport</span>     <span class="v" id="sumAirport">DUB · Dublin Airport</span></div>
          <div class="row-line"><span class="k">Flight</span>      <span class="v muted" id="sumFlight">—</span></div>
          <div class="row-line"><span class="k">Date &amp; Time</span> <span class="v muted" id="sumDateTime">—</span></div>
          <div class="row-line"><span class="k" id="sumAddrKey">Drop-off</span><span class="v muted" id="sumAddress">—</span></div>
          <div class="row-line"><span class="k">Terminal</span>    <span class="v muted" id="sumTerminal">Driver decides</span></div>
          <div class="row-line"><span class="k">Ride Type</span>   <span class="v" id="sumRideType"><?= htmlspecialchars($defaultRideType) ?></span></div>
          <div class="row-line"><span class="k">Pax / Bags</span>  <span class="v" id="sumPaxBags">1 pax · 2 bags</span></div>
          <div class="row-line"><span class="k">Placard</span>     <span class="v muted" id="sumPlacard">—</span></div>
          <div class="row-line"><span class="k">Distance</span>    <span class="v muted" id="sumDistance">—</span></div>
          <div class="row-line"><span class="k">Fare (incl. 10%)</span> <span class="v-fare" id="sumFare">€—</span></div>
        </div>

        <!-- What's included -->
        <div class="card mg-card border-0 mb-4">
          <div class="card-body p-4">
            <div class="mb-2">
              <h6 class="mg-card-title mb-0">What's Included</h6>
              <span class="mg-card-sub d-block mt-1">Every Meet &amp; Greet booking comes with these as standard.</span>
            </div>

            <div class="mg-incl-item">
              <span class="ic"><i class="bi bi-person-vcard-fill"></i></span>
              <div class="info">
                <div class="ttl">Personalised name placard</div>
                <div class="sub">Driver greets the passenger by name at arrivals.</div>
              </div>
            </div>
            <div class="mg-incl-item">
              <span class="ic"><i class="bi bi-bag-fill"></i></span>
              <div class="info">
                <div class="ttl">Luggage assistance</div>
                <div class="sub">Help with bags from the hall to the vehicle.</div>
              </div>
            </div>
            <div class="mg-incl-item">
              <span class="ic"><i class="bi bi-broadcast"></i></span>
              <div class="info">
                <div class="ttl">Live flight tracking</div>
                <div class="sub">We monitor delays and adjust the pickup time automatically.</div>
              </div>
            </div>
            <div class="mg-incl-item">
              <span class="ic"><i class="bi bi-stopwatch-fill"></i></span>
              <div class="info">
                <div class="ttl">60 minutes free wait</div>
                <div class="sub">After landing for arrivals — no extra charge.</div>
              </div>
            </div>
            <div class="mg-incl-item">
              <span class="ic"><i class="bi bi-shield-check"></i></span>
              <div class="info">
                <div class="ttl">Vetted, English-speaking drivers</div>
                <div class="sub">PSV-licensed and insured for corporate transport.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notice -->
        <div class="card mg-card border-0">
          <div class="card-body p-4 d-flex gap-3 align-items-start">
            <span class="mg-incl-item-ic" style="width:32px;height:32px;border-radius:8px;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi bi-info-circle-fill"></i>
            </span>
            <div style="line-height:1.45">
              <div style="font-size:.88rem;font-weight:600;color:#0f172a;margin-bottom:2px;">Confirmation by dispatch</div>
              <div style="font-size:.78rem;color:#64748b;">
                Bookings are confirmed by our dispatcher within 30 minutes during business hours.
                Final fare is calculated based on distance, vehicle class, and time of day.
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </main>

  <!-- ── Booking confirmed modal ── -->
  <style>
    .mg-success-icon {
      width: 76px; height: 76px;
      border-radius: 50%;
      background: #ecfdf5;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
      box-shadow: 0 0 0 10px #f0fdf4;
      position: relative;
    }
    .mg-success-icon i { color: #16a34a; font-size: 2rem; }
    .mg-success-icon::after {
      content: '';
      position: absolute; inset: -10px;
      border-radius: 50%;
      border: 2px solid rgba(22,163,74,.18);
      animation: mgPulse 2.4s ease-out infinite;
    }
    @keyframes mgPulse {
      0%   { transform: scale(.85); opacity: .9; }
      70%  { transform: scale(1.4);  opacity: 0; }
      100% { transform: scale(1.4);  opacity: 0; }
    }
    .mg-success-title {
      font-size: 1.25rem; font-weight: 700; color: #0f172a;
      letter-spacing: -.01em; text-align: center;
    }
    .mg-success-msg {
      font-size: .92rem; color: #475569;
      line-height: 1.6; text-align: center;
      margin: 8px auto 0; max-width: 380px;
    }
    .mg-success-meta {
      margin-top: 18px;
      padding: 12px 14px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      display: flex; flex-direction: column; gap: 6px;
      font-size: .82rem;
    }
    .mg-success-meta .row {
      display: flex; justify-content: space-between; align-items: center;
      gap: 12px;
    }
    .mg-success-meta .row .k { color: #64748b; }
    .mg-success-meta .row .v {
      color: #0f172a; font-weight: 600;
      max-width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
  </style>
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
      <div class="modal-content" style="border-radius:18px;border:1px solid #e2e8f0;box-shadow:0 24px 60px rgba(15,23,42,.18);">
        <div class="modal-body" style="padding:30px 28px 24px;">
          <div class="mg-success-icon"><i class="bi bi-check-lg"></i></div>
          <div class="mg-success-title">Booking Confirmed!</div>
          <p class="mg-success-msg">
            Your ride has been confirmed and based on availability of driver
            this would be assigned.
          </p>
          <div class="mg-success-meta">
            <div class="row"><span class="k">Service</span> <span class="v" id="successService">—</span></div>
            <div class="row"><span class="k">Airport</span> <span class="v" id="successAirport">—</span></div>
            <div class="row"><span class="k">Terminal</span><span class="v" id="successTerminal">—</span></div>
            <div class="row"><span class="k">Flight</span>  <span class="v" id="successFlight">—</span></div>
            <div class="row"><span class="k">Total paid</span> <span class="v" id="successFare">—</span></div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex gap-2">
          <button type="button" class="btn-mg-ghost flex-fill" data-bs-dismiss="modal">Close</button>
          <button type="button" id="successBookAnotherBtn" class="btn-mg-primary flex-fill">
            <i class="bi bi-plus-lg"></i> Book another
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB9ea0A-mjnD5iHfT9X8Dn5YYH4_KZopLI&libraries=places&callback=initMeetGreetMaps" async defer></script>

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

    // ── Refs ─────────────────────────────────────────────
    const form         = document.getElementById('meetGreetForm');
    const empSel        = document.getElementById('employee');
    const guestNameInput = document.getElementById('guestName');
    const guestPhoneInput = document.getElementById('guestPhone');
    const paxTypeGrid   = document.getElementById('paxTypeGrid');
    const mgEmployeeCol = document.getElementById('mgEmployeeCol');
    const mgGuestNameCol = document.getElementById('mgGuestNameCol');
    const mgGuestPhoneCol = document.getElementById('mgGuestPhoneCol');
    const passInput    = document.getElementById('passengers');
    const lugInput     = document.getElementById('luggage');
    const placard      = document.getElementById('placard');
    const airline      = document.getElementById('airline');
    const flightNo     = document.getElementById('flightNo');
    const flightTime   = document.getElementById('flightTime');
    const addressInput = document.getElementById('address');
    const terminalSel  = document.getElementById('terminal');
    const notes        = document.getElementById('notes');
    const carTypeHidden = document.getElementById('carType');
    const rideTypeGrid  = document.getElementById('rideTypeGrid');

    const fareDistance  = document.getElementById('fareDistance');
    const fareDuration  = document.getElementById('fareDuration');
    const fareTotal     = document.getElementById('fareTotal');
    const farePanel     = document.getElementById('fareResult');
    const payAmount     = document.getElementById('payAmount');
    const payHint       = document.getElementById('payHint');
    const stripeBtn     = document.getElementById('stripeBtn');
    const confirmBtn    = document.getElementById('confirmBtn');
    const sumRideType   = document.getElementById('sumRideType');
    const sumDistance   = document.getElementById('sumDistance');
    const sumFare       = document.getElementById('sumFare');
    const mgMapWrap     = document.getElementById('mgMapWrap');

    const sumService   = document.getElementById('sumService');
    const sumPassenger = document.getElementById('sumPassenger');
    const sumAirport   = document.getElementById('sumAirport');
    const sumFlight    = document.getElementById('sumFlight');
    const sumDateTime  = document.getElementById('sumDateTime');
    const sumAddress   = document.getElementById('sumAddress');
    const sumAddrKey   = document.getElementById('sumAddrKey');
    const sumPaxBags   = document.getElementById('sumPaxBags');
    const sumPlacard   = document.getElementById('sumPlacard');
    const sumTerminal  = document.getElementById('sumTerminal');
    const summaryTag   = document.getElementById('summaryTag');

    const addrCardTitle = document.getElementById('addressCardTitle');
    const addrCardSub   = document.getElementById('addressCardSub');
    const addrLabel     = document.getElementById('addressLabel');
    const placardLabel  = document.getElementById('placardLabel');
    const flightTimeLabel = document.getElementById('flightTimeLabel');

    // ── Helpers ──────────────────────────────────────────
    function setText(el, val, dashIfEmpty = true) {
      if (!el) return;
      const v = (val == null ? '' : String(val)).trim();
      el.textContent = v || (dashIfEmpty ? '—' : '');
      el.classList.toggle('muted', !v);
    }
    function fmtDateTime(raw) {
      if (!raw) return '';
      const d = new Date(raw);
      if (isNaN(d.getTime())) return raw;
      const dd = ('0' + d.getDate()).slice(-2);
      const mm = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()];
      const yy = d.getFullYear();
      let h = d.getHours();
      const ap = h >= 12 ? 'PM' : 'AM';
      h = h % 12 || 12;
      const min = ('0' + d.getMinutes()).slice(-2);
      return `${dd} ${mm} ${yy} · ${h}:${min} ${ap}`;
    }

    // ── Passenger type cards ─────────────────────────────
    function getMgPassengerType() {
      return document.querySelector('input[name="passengerType"]:checked')?.value || 'employee';
    }
    function applyMgPassengerType(type) {
      const isEmp   = type === 'employee' || type === 'both';
      const isGuest = type === 'guest'    || type === 'both';
      if (mgEmployeeCol)   mgEmployeeCol.style.display   = isEmp   ? '' : 'none';
      if (mgGuestNameCol)  mgGuestNameCol.style.display  = isGuest ? '' : 'none';
      if (mgGuestPhoneCol) mgGuestPhoneCol.style.display = isGuest ? '' : 'none';
      refreshSummary();
    }
    paxTypeGrid?.addEventListener('click', (e) => {
      const card = e.target.closest('.mg-pax-card');
      if (!card) return;
      paxTypeGrid.querySelectorAll('.mg-pax-card').forEach(c => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      applyMgPassengerType(card.dataset.pax || 'employee');
    });

    // ── Service-type radio cards ─────────────────────────
    document.querySelectorAll('.mg-service-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.mg-service-card').forEach(c => c.classList.remove('is-selected'));
        card.classList.add('is-selected');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        applyServiceType(card.dataset.value);
        refreshSummary();
      });
    });

    function applyServiceType(value) {
      const isArrival = value === 'Arrival';
      addrCardTitle.textContent = isArrival ? 'Drop-off Address' : 'Pickup Address';
      addrCardSub.textContent   = isArrival
        ? 'Where to take the passenger after airport pickup.'
        : 'Where to collect the passenger before driving to the airport.';
      addrLabel.textContent     = isArrival ? 'Drop-off Address' : 'Pickup Address';
      sumAddrKey.textContent    = isArrival ? 'Drop-off' : 'Pickup';
      flightTimeLabel.textContent = isArrival ? 'Arrival Date & Time' : 'Departure Date & Time';
      placardLabel.textContent  = isArrival ? 'Name on Placard' : 'Reference / Name (optional)';

      summaryTag.textContent = value;
      summaryTag.classList.toggle('mg-tag-arrival',   isArrival);
      summaryTag.classList.toggle('mg-tag-departure', !isArrival);
    }

    // ── Airport quick-pick cards ────────────────────────
    document.querySelectorAll('.mg-airport-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.mg-airport-card').forEach(c => c.classList.remove('is-selected'));
        card.classList.add('is-selected');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        refreshSummary();
        recomputeRoute();
      });
    });

    // ── Ride-type cards ─────────────────────────────────
    rideTypeGrid?.addEventListener('click', (e) => {
      const card = e.target.closest('.mg-ride-card');
      if (!card) return;
      rideTypeGrid.querySelectorAll('.mg-ride-card').forEach(c => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      const v = card.dataset.value || 'Economy';
      carTypeHidden.value = v;
      sumRideType.textContent = v;
      // Recompute fare with the new multiplier
      recalculateFareForCurrentRoute();
    });

    // ── Wire form events to live summary ────────────────
    [empSel, passInput, lugInput, placard, airline, flightNo, flightTime, addressInput, terminalSel, notes,
     guestNameInput, guestPhoneInput]
      .forEach(el => el && el.addEventListener('input', refreshSummary));
    // <select> elements don't always emit `input` reliably — also bind change.
    terminalSel?.addEventListener('change', refreshSummary);
    empSel.addEventListener('change', () => {
      // when employee changes, default placard to their name if blank
      const opt = empSel.options[empSel.selectedIndex];
      const name = opt && opt.value ? (opt.dataset.name || '') : '';
      if (name && !placard.value) {
        placard.value = name;
      }
      refreshSummary();
    });

    function getServiceType() {
      return document.querySelector('input[name="serviceType"]:checked')?.value || 'Arrival';
    }
    function getAirport() {
      const card = document.querySelector('.mg-airport-card.is-selected');
      if (!card) return { code: '', name: '' };
      return { code: card.dataset.code || '', name: card.dataset.name || '' };
    }

    function refreshSummary() {
      const type    = getServiceType();
      const paxType = getMgPassengerType();
      const ap      = getAirport();
      const opt     = empSel.options[empSel.selectedIndex];
      const empName   = opt && opt.value ? (opt.dataset.name || '') : '';
      const guestName = (guestNameInput?.value || '').trim();

      let passengerLabel = '';
      if (paxType === 'employee') {
        passengerLabel = empName;
      } else if (paxType === 'guest') {
        passengerLabel = guestName ? `${guestName} (Guest)` : '';
      } else {
        const parts = [empName, guestName ? `${guestName} (Guest)` : ''].filter(Boolean);
        passengerLabel = parts.join(' + ');
      }

      setText(sumService,   type, false);
      setText(sumPassenger, passengerLabel);
      setText(sumAirport,   ap.code ? `${ap.code} · ${ap.name}` : '', false);
      setText(sumFlight,    [airline.value.trim(), flightNo.value.trim()].filter(Boolean).join(' · '));
      setText(sumDateTime,  fmtDateTime(flightTime.value));
      setText(sumAddress,   addressInput.value);
      setText(sumPlacard,   placard.value);
      // Terminal: show literal selection or the friendly default.
      if (terminalSel.value) {
        sumTerminal.textContent = terminalSel.value;
        sumTerminal.classList.remove('muted');
      } else {
        sumTerminal.textContent = 'Driver decides';
        sumTerminal.classList.add('muted');
      }

      const pax = Math.max(1, parseInt(passInput.value || '1', 10));
      const lug = Math.max(0, parseInt(lugInput.value  || '0', 10));
      sumPaxBags.textContent = `${pax} pax · ${lug} bag${lug === 1 ? '' : 's'}`;
      sumPaxBags.classList.remove('muted');
    }

    // Initial sync
    applyServiceType('Arrival');
    applyMgPassengerType('employee');
    refreshSummary();

    // ── Google Maps + Places Autocomplete + fare calc ───
    let mgMap, mgDirSvc, mgDirRen, mgAddrAuto, mgAddrLatLng = null;
    let currentDistanceKm = null;
    let currentDurationMin = null;
    // Lat/Lng of the route's start (pickup) and end (dropoff) — captured
    // from the DirectionsService leg so we can persist them with the
    // booking. The dispatcher's `rides` table requires these as NOT NULL.
    let currentRouteCoords = null;
    let _mapsInitTries   = 0;
    let _mapsInitialised = false;

    window.initMeetGreetMaps = function () {
      // Google Maps loader calls this once. Retry a small number of times if
      // the namespace isn't ready yet, then give up — prevents an infinite
      // setTimeout loop if the API key is blocked or the network drops.
      if (_mapsInitialised) return;
      if (typeof google === 'undefined' || !google.maps) {
        if (++_mapsInitTries > 25) {
          console.warn('[MeetGreet] Google Maps failed to load — fare/route disabled.');
          return;
        }
        return setTimeout(window.initMeetGreetMaps, 200);
      }
      _mapsInitialised = true;
      const ap = getAirport();
      mgMap = new google.maps.Map(document.getElementById('mgMap'), {
        center: { lat: parseFloat(ap.lat) || 53.349805, lng: parseFloat(ap.lng) || -6.26031 },
        zoom: 11,
        disableDefaultUI: true,
        zoomControl: true,
      });
      mgDirSvc = new google.maps.DirectionsService();
      mgDirRen = new google.maps.DirectionsRenderer({ map: mgMap, suppressMarkers: false });

      mgAddrAuto = new google.maps.places.Autocomplete(addressInput);
      mgAddrAuto.addListener('place_changed', () => {
        const place = mgAddrAuto.getPlace();
        if (place && place.formatted_address) addressInput.value = place.formatted_address;
        mgAddrLatLng = (place && place.geometry) ? place.geometry.location : null;
        recomputeRoute();
      });
      addressInput.addEventListener('blur',  recomputeRoute);
      addressInput.addEventListener('change', recomputeRoute);
    };

    function getAirportCoords() {
      const card = document.querySelector('.mg-airport-card.is-selected');
      if (!card) return null;
      const lat = parseFloat(card.dataset.lat);
      const lng = parseFloat(card.dataset.lng);
      const addr = card.dataset.addr || '';
      if (isNaN(lat) || isNaN(lng)) return null;
      return { lat, lng, addr };
    }

    function recomputeRoute() {
      if (!mgMap || !mgDirSvc || !mgDirRen) return;
      const apc = getAirportCoords();
      const addr = (addressInput.value || '').trim();
      if (!apc || !addr) {
        currentDistanceKm = null; currentDurationMin = null;
        renderFareEmpty();
        return;
      }
      const isArrival = getServiceType() === 'Arrival';
      // Arrival: airport → address. Departure: address → airport.
      const origin      = isArrival ? new google.maps.LatLng(apc.lat, apc.lng) : (mgAddrLatLng || addr);
      const destination = isArrival ? (mgAddrLatLng || addr) : new google.maps.LatLng(apc.lat, apc.lng);

      mgDirSvc.route({
        origin, destination,
        travelMode: google.maps.TravelMode.DRIVING,
      }, (result, status) => {
        if (status !== google.maps.DirectionsStatus.OK) {
          currentDistanceKm = null; currentDurationMin = null;
          currentRouteCoords = null;
          renderFareEmpty();
          return;
        }
        mgDirRen.setDirections(result);
        const leg = result.routes[0].legs[0];
        currentDistanceKm  = leg.distance.value / 1000;
        currentDurationMin = Math.round(leg.duration.value / 60);
        // start_location / end_location are LatLng objects regardless of
        // whether the user typed an address or picked from autocomplete.
        currentRouteCoords = {
          pickup_lat:  leg.start_location.lat(),
          pickup_lng:  leg.start_location.lng(),
          dropoff_lat: leg.end_location.lat(),
          dropoff_lng: leg.end_location.lng(),
        };
        mgMapWrap.classList.add('has-route');
        recalculateFareForCurrentRoute();
      });
    }

    /**
     * Same pricing as bookRide.php → calculateFare, with a +10% Meet & Greet
     * premium applied on top.
     */
    function calculateFareWithPremium(distanceKm, durationMin, pickupTimeStr, rideType) {
      const pickupDate = new Date(pickupTimeStr || Date.now());
      const hour = isNaN(pickupDate.getTime()) ? new Date().getHours() : pickupDate.getHours();
      const initialFare = 3.0;
      let baseFare, ratePerKm, ratePerMinute;
      if (hour >= 8 && hour < 20) {
        baseFare = 4.4; ratePerKm = 1.32; ratePerMinute = 0.2;
      } else {
        baseFare = 5.4; ratePerKm = 1.81; ratePerMinute = 0.3;
      }
      const rawFare = initialFare + baseFare + (distanceKm || 0) * ratePerKm + (durationMin || 0) * ratePerMinute;
      const multipliers = {
        'Economy': 1.0, 'Economy XL': 1.2, 'Business': 1.0, 'Business Plus': 1.2,
        'Limousine': 2.0, 'Wheelchair accessible': 1.1, 'Wheelchair Taxi': 1.1,
        'Pets Taxi': 1.15, 'Parcel Delivery': 0.9, 'Courier / Parcel': 0.9,
      };
      const m = multipliers[rideType] ?? 1.0;
      // 10% Meet & Greet premium
      return Math.round(rawFare * m * 1.10 * 100) / 100;
    }

    function renderFareEmpty() {
      farePanel.classList.add('is-empty');
      fareDistance.textContent = '—';
      fareDuration.textContent = '—';
      fareTotal.textContent    = '€—';
      sumDistance.textContent  = '—';
      sumDistance.classList.add('muted');
      sumFare.textContent      = '€—';
      payAmount.textContent    = '€—';
    }

    function recalculateFareForCurrentRoute() {
      if (currentDistanceKm == null || currentDurationMin == null) {
        renderFareEmpty();
        return;
      }
      const fare = calculateFareWithPremium(
        currentDistanceKm,
        currentDurationMin,
        flightTime.value,
        carTypeHidden.value || 'Economy'
      );
      farePanel.classList.remove('is-empty');
      fareDistance.textContent = currentDistanceKm.toFixed(2) + ' km';
      fareDuration.textContent = currentDurationMin + ' min';
      fareTotal.textContent    = '€' + fare.toFixed(2);
      sumDistance.textContent  = currentDistanceKm.toFixed(2) + ' km';
      sumDistance.classList.remove('muted');
      sumFare.textContent      = '€' + fare.toFixed(2);
      payAmount.textContent    = '€' + fare.toFixed(2);
    }

    flightTime.addEventListener('change', recalculateFareForCurrentRoute);

    // ── Payment method selector (Stripe vs Company) ─────
    let currentPaymentMethod = 'stripe';
    const methodStripe  = document.getElementById('methodStripe');
    const methodCompany = document.getElementById('methodCompany');
    const stripePanel   = document.getElementById('stripePanel');
    const companyPanel  = document.getElementById('companyPanel');

    function setPaymentMethod(method) {
      currentPaymentMethod = method;
      const isStripe = method === 'stripe';
      methodStripe.classList.toggle('is-selected',  isStripe);
      methodCompany.classList.toggle('is-selected', !isStripe);
      stripePanel.style.display  = isStripe ? '' : 'none';
      companyPanel.style.display = isStripe ? 'none' : '';
      if (isStripe) {
        const paid = document.querySelector('input[name="paid"]:checked')?.value === 'yes';
        confirmBtn.disabled = !paid;
      } else {
        confirmBtn.disabled = false;
      }
    }
    methodStripe?.addEventListener('click',  () => setPaymentMethod('stripe'));
    methodCompany?.addEventListener('click', () => setPaymentMethod('company'));

    // ── Payment confirmation toggle (Stripe only) ───────
    const payOpts = document.querySelectorAll('.mg-pay-opt');
    payOpts.forEach(opt => {
      opt.addEventListener('click', () => {
        if (currentPaymentMethod !== 'stripe') return;
        payOpts.forEach(o => o.classList.remove('is-selected'));
        opt.classList.add('is-selected');
        const radio = opt.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        const paid = opt.dataset.paid === 'yes';
        confirmBtn.disabled = !paid;
        payHint.textContent = paid
          ? 'Payment confirmed. You can now release the booking to dispatch.'
          : 'Confirm Booking unlocks once you mark the payment as paid.';
        payHint.style.color = paid ? '#15803d' : '';
      });
    });

    // ── Confirm submit (saves to backend → rides table) ─
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal   = new bootstrap.Modal(confirmModalEl);

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      // Basic validation
      const paxType  = getMgPassengerType();
      const isEmpReq = paxType === 'employee' || paxType === 'both';
      const isGstReq = paxType === 'guest'    || paxType === 'both';
      const guestNameVal  = (guestNameInput?.value  || '').trim();
      const guestPhoneVal = (guestPhoneInput?.value || '').trim();

      if (isEmpReq && !empSel.value) {
        showToast?.('Please select an employee.', 'error');
        empSel.focus();
        return;
      }
      if (isGstReq && !guestNameVal) {
        showToast?.('Please enter the guest name.', 'error');
        guestNameInput?.focus();
        return;
      }
      if (!flightNo.value.trim()) { flightNo.focus(); return; }
      if (!flightTime.value)      { flightTime.focus(); return; }
      if (!addressInput.value.trim()) { addressInput.focus(); return; }
      if (currentDistanceKm == null) {
        showToast?.('Please pick an airport and an address so we can calculate fare.', 'error');
        return;
      }
      const paid = currentPaymentMethod === 'company'
        || document.querySelector('input[name="paid"]:checked')?.value === 'yes';
      if (!paid) { showToast?.('Please mark the Stripe payment as completed first.', 'error'); return; }

      const opt    = empSel.options[empSel.selectedIndex];
      const ap     = getAirport();
      const apc    = getAirportCoords();
      const isArr  = getServiceType() === 'Arrival';
      const fare   = calculateFareWithPremium(currentDistanceKm, currentDurationMin, flightTime.value, carTypeHidden.value || 'Economy');

      const pickup       = isArr ? (apc?.addr || ap.name) : addressInput.value.trim();
      const dropoff      = isArr ? addressInput.value.trim() : (apc?.addr || ap.name);

      const coords = currentRouteCoords || {};
      const payload = {
        passenger_type: paxType,
        service_type:   getServiceType(),
        airport_code:   ap.code,
        airport_name:   ap.name,
        terminal:       terminalSel.value || '',
        employee_id:    isEmpReq ? empSel.value : '',
        employee_name:  isEmpReq ? (opt?.dataset.name  || '') : guestNameVal,
        employee_email: isEmpReq ? (opt?.dataset.email || '') : '',
        employee_phone: isEmpReq ? (opt?.dataset.phone || '') : guestPhoneVal,
        guest_name:     guestNameVal,
        guest_phone:    guestPhoneVal,
        airline:        airline.value.trim(),
        flight_no:      flightNo.value.trim(),
        flight_time:    flightTime.value,
        passengers:     parseInt(passInput.value || '1', 10),
        luggage:        parseInt(lugInput.value  || '0', 10),
        address:        addressInput.value.trim(),
        pickup, dropoff,
        pickup_lat:     coords.pickup_lat,
        pickup_lng:     coords.pickup_lng,
        dropoff_lat:    coords.dropoff_lat,
        dropoff_lng:    coords.dropoff_lng,
        placard:        placard.value.trim(),
        notes:          notes.value.trim(),
        carType:        carTypeHidden.value || 'Economy',
        distance:       currentDistanceKm,
        eta:            currentDurationMin,
        fare,
        paid_via:       currentPaymentMethod,
      };

      const submitBtn = confirmBtn;
      const orig = submitBtn.innerHTML;
      submitBtn.disabled  = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Confirming…';

      fetch('php/save_meetgreet.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(r => r.json())
        .then(data => {
          submitBtn.disabled  = false;
          submitBtn.innerHTML = orig;
          if (data.success) {
            // Populate the success modal with a quick recap before showing.
            document.getElementById('successService').textContent =
              `${payload.service_type} · ${payload.carType}`;
            document.getElementById('successAirport').textContent =
              `${payload.airport_code} · ${payload.airport_name}`;
            document.getElementById('successTerminal').textContent =
              payload.terminal || 'Driver decides';
            document.getElementById('successFlight').textContent  =
              [payload.airline, payload.flight_no].filter(Boolean).join(' · ') || '—';
            document.getElementById('successFare').textContent    =
              payload.paid_via === 'company'
                ? 'Charged to company account'
                : '€' + Number(payload.fare || 0).toFixed(2);
            confirmModal.show();
          } else {
            showToast?.(data.message || 'Failed to save booking.', 'error');
          }
        })
        .catch(() => {
          submitBtn.disabled  = false;
          submitBtn.innerHTML = orig;
          showToast?.('Network error. Please try again.', 'error');
        });
    });

    // ── Success-modal actions: reset form when closed / "Book another" ──
    function resetMeetGreetForm() {
      try { form.reset(); } catch (_) {}
      setPaymentMethod('stripe');
      // Restore default selections + clear computed state
      document.querySelectorAll('.mg-service-card').forEach((c, i) =>
        c.classList.toggle('is-selected', i === 0));
      document.querySelectorAll('.mg-airport-card').forEach((c, i) =>
        c.classList.toggle('is-selected', i === 0));
      const firstRide = rideTypeGrid?.querySelector('.mg-ride-card');
      rideTypeGrid?.querySelectorAll('.mg-ride-card').forEach(c =>
        c.classList.toggle('is-selected', c === firstRide));
      if (firstRide && carTypeHidden) carTypeHidden.value = firstRide.dataset.value || '';

      // Payment back to "Not yet" → Confirm button locked again
      payOpts.forEach(o => o.classList.remove('is-selected'));
      const noOpt = document.querySelector('.mg-pay-opt.is-no');
      noOpt?.classList.add('is-selected');
      const noRadio = noOpt?.querySelector('input[type="radio"]');
      if (noRadio) noRadio.checked = true;
      confirmBtn.disabled = true;
      payHint.textContent = 'Confirm Booking unlocks once you mark the payment as paid.';
      payHint.style.color = '';

      // Clear map / fare state
      currentDistanceKm = null;
      currentDurationMin = null;
      mgAddrLatLng = null;
      mgMapWrap?.classList.remove('has-route');
      try { mgDirRen?.set('directions', null); } catch (_) {}
      renderFareEmpty();

      // Reset passenger type to Employee
      paxTypeGrid?.querySelectorAll('.mg-pax-card').forEach((c, i) =>
        c.classList.toggle('is-selected', i === 0));
      applyMgPassengerType('employee');

      applyServiceType('Arrival');
      refreshSummary();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    document.getElementById('successBookAnotherBtn')?.addEventListener('click', () => {
      confirmModal.hide();
      resetMeetGreetForm();
    });
    confirmModalEl.addEventListener('hidden.bs.modal', resetMeetGreetForm);
  </script>
</body>
</html>
