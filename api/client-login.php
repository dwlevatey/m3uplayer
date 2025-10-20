<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$username = sanitize_input($data['username'] ?? '');
$password = sanitize_input($data['password'] ?? '');
$selected_dns_id = isset($data['dns_id']) ? (int)$data['dns_id'] : null;

if (empty($username) || empty($password)) {
    json_response(['success' => false, 'message' => 'Usuário e senha são obrigatórios'], 400);
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT c.*, d.dns_url, d.name as dns_name 
    FROM client_credentials c
    INNER JOIN dns_configs d ON c.dns_config_id = d.id
    WHERE c.username = ? AND c.password = ? AND c.is_active = 1 AND d.is_active = 1
");
$stmt->execute([$username, $password]);
$client = $stmt->fetch();

if ($client) {
    if ($client['expiry_date'] && strtotime($client['expiry_date']) < time()) {
        json_response(['success' => false, 'message' => 'Sua conta expirou'], 401);
    }
    
    create_client_session($conn, $client);
    
    json_response([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'username' => $client['username'],
            'dns_name' => $client['dns_name'],
            'dns_url' => $client['dns_url'],
            'cached' => true
        ]
    ]);
}

if ($selected_dns_id) {
    $stmt = $conn->prepare("SELECT * FROM dns_configs WHERE id = ? AND is_active = 1");
    $stmt->execute([$selected_dns_id]);
    $dns_servers = $stmt->fetchAll();
    error_log("[M3U] User selected specific DNS ID: {$selected_dns_id}");
} else {
    $stmt = $conn->prepare("SELECT * FROM dns_configs WHERE is_active = 1");
    $stmt->execute();
    $dns_servers = $stmt->fetchAll();
    error_log("[M3U] Checking all active DNS servers");
}

error_log("[M3U] Found " . count($dns_servers) . " DNS server(s) to check");

if (empty($dns_servers)) {
    json_response([
        'success' => false, 
        'message' => 'Nenhum servidor DNS configurado. Por favor, configure um servidor DNS no painel administrativo primeiro.',
        'debug' => 'NO_DNS_CONFIGURED'
    ], 500);
}

$output_formats = ['mpegts', 'm3u8', 'hls', 'ts'];

$found_dns = null;
$m3u_content = null;
$debug_info = [];
$successful_format = null;

foreach ($dns_servers as $dns) {
    error_log("[M3U] Testing DNS: {$dns['name']} ({$dns['dns_url']})");
    
    foreach ($output_formats as $format) {
        $m3u_url = build_m3u_url($dns['dns_url'], $username, $password, $format);
        
        error_log("[M3U] Trying format '{$format}': {$m3u_url}");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $m3u_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'M3U Streaming System');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $is_valid_m3u = $response && strpos($response, '#EXTM3U') !== false;
        
        $debug_info[] = [
            'dns_id' => $dns['id'],
            'dns_name' => $dns['name'],
            'dns_base_url' => $dns['dns_url'],
            'output_format' => $format,
            'full_m3u_url' => $m3u_url,
            'http_code' => $http_code,
            'has_response' => !empty($response),
            'response_length' => strlen($response),
            'is_valid_m3u' => $is_valid_m3u,
            'curl_error' => $curl_error ?: null
        ];
        
        error_log("[M3U] Result - HTTP: {$http_code}, Valid M3U: " . ($is_valid_m3u ? 'YES' : 'NO') . ", Error: " . ($curl_error ?: 'none'));
        
        if ($http_code == 200 && $is_valid_m3u) {
            $found_dns = $dns;
            $m3u_content = $response;
            $successful_format = $format;
            error_log("[M3U] SUCCESS! Valid credentials found on DNS: {$dns['name']} with format: {$format}");
            break 2; // Break both loops
        }
    }
}

if (!$found_dns) {
    error_log("[M3U] FAILED! No valid credentials found on any DNS server with any format");
    json_response([
        'success' => false, 
        'message' => 'Credenciais inválidas em todos os servidores DNS configurados (' . count($dns_servers) . ' servidor(es) verificado(s))',
        'checked_servers' => count($dns_servers),
        'formats_tried' => $output_formats,
        'debug' => $debug_info
    ], 401);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO client_credentials (username, password, dns_config_id, is_active) 
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE is_active = 1, updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$username, $password, $found_dns['id']]);
    
    $stmt = $conn->prepare("
        SELECT c.*, d.dns_url, d.name as dns_name 
        FROM client_credentials c
        INNER JOIN dns_configs d ON c.dns_config_id = d.id
        WHERE c.username = ? AND c.password = ? AND c.dns_config_id = ?
    ");
    $stmt->execute([$username, $password, $found_dns['id']]);
    $client = $stmt->fetch();
    
    create_client_session($conn, $client);
    
    error_log("[M3U] Login successful for user: {$username} on DNS: {$found_dns['name']} with format: {$successful_format}");
    
    json_response([
        'success' => true,
        'message' => "Login realizado com sucesso! Credenciais encontradas no servidor: {$found_dns['name']}",
        'data' => [
            'username' => $client['username'],
            'dns_name' => $client['dns_name'],
            'dns_url' => $client['dns_url'],
            'output_format' => $successful_format,
            'auto_detected' => !$selected_dns_id,
            'checked_servers' => count($dns_servers),
            'debug' => $debug_info
        ]
    ]);
    
} catch (Exception $e) {
    error_log("[M3U] Error saving credentials: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Erro ao salvar credenciais: ' . $e->getMessage()], 500);
}

function create_client_session($conn, $client) {
    $session_token = generate_session_token();
    $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    
    $stmt = $conn->prepare("INSERT INTO client_sessions (session_token, client_id, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$session_token, $client['id'], $expires_at]);
    
    $output_format = $client['output_format'] ?? 'mpegts';
    
    $_SESSION['client_id'] = $client['id'];
    $_SESSION['client_username'] = $client['username'];
    $_SESSION['client_password'] = $client['password'];
    $_SESSION['output_format'] = $output_format;
    $_SESSION['dns_url'] = $client['dns_url'];
    $_SESSION['dns_name'] = $client['dns_name'];
    $_SESSION['session_token'] = $session_token;
    
    error_log("[v0 DEBUG] Sessão criada para cliente: " . json_encode([
        'client_id' => $client['id'],
        'username' => $client['username'],
        'dns_url' => $client['dns_url'],
        'output_format' => $output_format
    ]));
}
?>
