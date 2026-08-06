<?php
require_once __DIR__ . '/config.php';

class SupabaseClient {
    private string $baseUrl;
    private string $apiKey;

    public function __construct(bool $serviceRole = true) {
        $this->baseUrl = SUPABASE_URL;
        $this->apiKey = $serviceRole ? SUPABASE_SERVICE_ROLE_KEY : SUPABASE_ANON_KEY;
    }

    /**
     * @param string $prefer Single Prefer: value (no "Prefer:" prefix), e.g. return=representation or return=minimal
     */
    private function request(string $method, string $path, array $query = [], $body = null, string $prefer = 'return=representation', array $extraHeaders = []): array {
        $url = rtrim($this->baseUrl, '/') . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: ' . $prefer,
        ], $extraHeaders);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // Fail fast: don't let a single hung request eat PHP's 30 s budget.
        // CONNECTTIMEOUT covers DNS + TCP/TLS handshake; TIMEOUT covers the
        // whole exchange. Keep both well under PHP max_execution_time.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        // IPv4 only — some Windows hosts hang when IPv6 routes silently drop.
        if (defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        // Encourage connection re-use & avoid Expect: 100-continue stalls.
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        $headers[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        $errno    = curl_errno($ch);
        // curl_close() is a no-op since PHP 8.0 and is deprecated from
        // PHP 8.5 onwards — the handle is freed when $ch goes out of scope.

        if ($response === false || $err) {
            // Surface the cURL error number so callers can distinguish a
            // timeout (CURLE_OPERATION_TIMEDOUT = 28) from other failures.
            throw new Exception('Supabase request failed [' . $errno . ']: ' . $err);
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = is_array($decoded) ? json_encode($decoded) : $response;
            throw new Exception('Supabase error (' . $httpCode . '): ' . $message);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function select(string $table, array $filters = [], string $select = '*', ?string $order = null, ?int $limit = null): array {
        $query = ['select' => $select];
        foreach ($filters as $column => $value) {
            $query[$column] = 'eq.' . $value;
        }
        if ($order !== null) {
            $query['order'] = $order;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        return $this->request('GET', '/rest/v1/' . $table, $query);
    }

    public function insert(string $table, array $data): array {
        return $this->request('POST', '/rest/v1/' . $table, [], $data);
    }

    public function update(string $table, array $filters, array $data): array {
        $query = [];
        foreach ($filters as $column => $value) {
            $query[$column] = 'eq.' . $value;
        }
        return $this->request('PATCH', '/rest/v1/' . $table, $query, $data);
    }

    public function delete(string $table, array $filters): array {
        $query = [];
        foreach ($filters as $column => $value) {
            $query[$column] = 'eq.' . $value;
        }
        return $this->request('DELETE', '/rest/v1/' . $table, $query, null, 'return=minimal');
    }

    /**
     * SELECT with raw PostgREST operator filters, e.g. ['created_at' => 'gte.2026-08-01T00:00:00Z']
     */
    public function selectWithOperators(string $table, array $operatorFilters, string $select = '*', ?string $order = null, ?int $limit = null): array {
        $query = ['select' => $select];
        foreach ($operatorFilters as $column => $value) {
            $query[$column] = $value; // value already includes the operator, e.g. 'eq.abc' or 'gte.2026-01-01'
        }
        if ($order !== null) $query['order'] = $order;
        if ($limit !== null) $query['limit'] = $limit;
        return $this->request('GET', '/rest/v1/' . $table, $query);
    }

    /**
     * DELETE with PostgREST operator filters, e.g. ['expiry' => 'lt.2026-01-01T00:00:00Z']
     */
    public function deleteWithOperators($table, array $operatorFilters) {
        $query = [];
        foreach ($operatorFilters as $column => $condition) {
            $query[$column] = $condition;
        }
        return $this->request('DELETE', '/rest/v1/' . $table, $query, null, 'return=minimal');
    }
}

function corporateRequireLogin() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION['user'];
}

/**
 * Filter candidates for corporate row (id, email, then cid column name variants).
 *
 * @return array<int, array<string, mixed>>
 */
function corporate_row_filters_try(array $user): array {
    $filters = [];
    $uid = $user['id'] ?? null;
    if ($uid !== null && $uid !== '') {
        $filters[] = ['id' => $uid];
    }
    $email = trim((string)($user['email'] ?? ''));
    if ($email !== '') {
        $filters[] = ['email' => $email];
    }
    $cid = trim((string)($user['cid'] ?? ''));
    if ($cid !== '') {
        $filters[] = ['cid' => $cid];
        $filters[] = ['CID' => $cid];
        $filters[] = ['company_id' => $cid];
    }
    return $filters;
}

/**
 * Billing setup for the logged-in company. Accounts on the `revenue` billing
 * model pay a flat per-ride fee on top of the metered fare; `regular` accounts
 * pay the metered fare only.
 *
 * Falls back to the regular model whenever the row, the column, or Supabase
 * itself is unavailable — a booking page must never break over billing setup.
 *
 * @return array{billing_model:string, revenue_fixed_fee:float}
 */
function corporate_billing_config(SupabaseClient $supabase, array $user): array {
    $config = ['billing_model' => 'regular', 'revenue_fixed_fee' => 0.0, 'revenue_tiers' => []];

    foreach (corporate_row_filters_try($user) as $filter) {
        try {
            $rows = $supabase->select('corporate', $filter, '*', null, 1);
        } catch (Throwable $e) {
            continue;
        }
        if (empty($rows) || !is_array($rows[0])) continue;

        $model = strtolower(trim((string)($rows[0]['billing_model'] ?? '')));
        $config['billing_model'] = $model !== '' ? $model : 'regular';
        if ($config['billing_model'] === 'revenue') {
            $fee = round(floatval($rows[0]['revenue_fixed_fee'] ?? 0), 2);
            $config['revenue_fixed_fee'] = $fee > 0 ? $fee : 0.0;
            $raw = $rows[0]['revenue_tiers'] ?? null;
            $config['revenue_tiers'] = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
        }
        break;
    }

    return $config;
}

/**
 * Compute the corporate credit balance for the current month.
 *
 * Credits are earned via ride-count tiers (loyalty programme) and reset at the
 * start of every calendar month. When a corporate employee books a ride with
 * payment_method = 'corporate_credit', that ride's fare is deducted from the
 * month's earned balance.
 *
 * @return array{is_revenue:bool, balance:float, credits_earned:float, credits_used:float, month_label:string, reset_date:string}
 */
function compute_credit_balance(SupabaseClient $supabase, array $user, array $billing): array {
    $empty = ['is_revenue' => false, 'balance' => 0.0, 'credits_earned' => 0.0, 'credits_used' => 0.0, 'month_label' => '', 'reset_date' => ''];

    if (($billing['billing_model'] ?? 'regular') !== 'revenue') return $empty;

    $tiers = $billing['revenue_tiers'] ?? [];
    $cid   = trim((string)($user['cid'] ?? ''));
    if ($cid === '') return $empty;

    $tz    = new DateTimeZone('Europe/Dublin');
    $now   = new DateTime('now', $tz);
    $from  = $now->format('Y-m-01') . 'T00:00:00+00:00';
    $to    = $now->format('Y-m-t')  . 'T23:59:59+00:00';

    try {
        // All completed corporate rides this month (ordered ascending for tier numbering)
        $rides = $supabase->selectWithOperators('rides', [
            'cid'        => 'eq.' . $cid,
            'status'     => 'eq.completed',
            'created_at' => 'gte.' . $from,
        ], 'id,created_at,fare_eur,final_fare,total_charged,payment_method,source', 'created_at.asc', 500);

        // Filter to corporate source only
        $rides = array_values(array_filter($rides, function ($r) {
            $src = strtolower(trim((string)($r['source'] ?? '')));
            return $src === 'corporate' || $src === 'corporate meet_and_greet';
        }));

        // Further filter to within month end (gte filter is open-ended)
        $toTs = strtotime($to);
        $rides = array_values(array_filter($rides, fn($r) => strtotime($r['created_at'] ?? '0') <= $toTs));

        // Compute credits earned from tier config
        $creditsEarned = 0.0;
        foreach ($rides as $i => $ride) {
            $rideNumber = $i + 1;
            foreach ($tiers as $tier) {
                $tFrom = (int)($tier['from'] ?? 1);
                $tTo   = isset($tier['to']) && $tier['to'] !== null ? (int)$tier['to'] : PHP_INT_MAX;
                if ($rideNumber >= $tFrom && $rideNumber <= $tTo) {
                    $creditsEarned += (float)($tier['discount'] ?? 0);
                    break;
                }
            }
        }

        // Compute credits already used (rides paid with corporate_credit this month)
        $creditsUsed = 0.0;
        foreach ($rides as $ride) {
            if (strtolower(trim((string)($ride['payment_method'] ?? ''))) === 'corporate_credit') {
                $creditsUsed += (float)($ride['total_charged'] ?? $ride['final_fare'] ?? $ride['fare_eur'] ?? 0);
            }
        }

        $balance   = max(0.0, round($creditsEarned - $creditsUsed, 2));
        $resetDate = (clone $now)->modify('first day of next month')->format('F j, Y');

        return [
            'is_revenue'     => true,
            'balance'        => $balance,
            'credits_earned' => round($creditsEarned, 2),
            'credits_used'   => round($creditsUsed, 2),
            'month_label'    => $now->format('F Y'),
            'reset_date'     => $resetDate,
        ];
    } catch (Throwable $e) {
        return $empty;
    }
}

