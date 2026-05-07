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

