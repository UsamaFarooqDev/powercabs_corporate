<?php
require_once __DIR__ . '/config.php';

class SupabaseClient {
    private $baseUrl;
    private $apiKey;

    public function __construct($serviceRole = true) {
        $this->baseUrl = SUPABASE_URL;
        $this->apiKey = $serviceRole ? SUPABASE_SERVICE_ROLE_KEY : SUPABASE_ANON_KEY;
    }

    private function request($method, $path, $query = [], $body = null, $extraHeaders = []) {
        $url = rtrim($this->baseUrl, '/') . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ], $extraHeaders);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        if ($response === false || $err) {
            throw new Exception('Supabase request failed: ' . $err);
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = is_array($decoded) ? json_encode($decoded) : $response;
            throw new Exception('Supabase error (' . $httpCode . '): ' . $message);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function select($table, $filters = [], $select = '*', $order = null, $limit = null) {
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

    public function insert($table, $data) {
        return $this->request('POST', '/rest/v1/' . $table, [], $data);
    }

    public function update($table, $filters, $data) {
        $query = [];
        foreach ($filters as $column => $value) {
            $query[$column] = 'eq.' . $value;
        }
        return $this->request('PATCH', '/rest/v1/' . $table, $query, $data);
    }

    public function delete($table, $filters) {
        $query = [];
        foreach ($filters as $column => $value) {
            $query[$column] = 'eq.' . $value;
        }
        return $this->request('DELETE', '/rest/v1/' . $table, $query, null, ['Prefer: return=minimal']);
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

