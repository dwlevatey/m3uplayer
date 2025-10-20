<?php
error_log("[M3U] get-dns-list.php called");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/functions.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    error_log("[M3U] Database connected, querying dns_configs table");

    $stmt = $conn->prepare("SELECT id, name, dns_url FROM dns_configs WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $dns_servers = $stmt->fetchAll();
    
    error_log("[M3U] Found " . count($dns_servers) . " active DNS servers");
    
    foreach ($dns_servers as $dns) {
        error_log("[M3U] DNS Server: ID={$dns['id']}, Name={$dns['name']}, URL={$dns['dns_url']}");
    }

    json_response([
        'success' => true,
        'data' => $dns_servers,
        'count' => count($dns_servers)
    ]);
    
} catch (Exception $e) {
    error_log("[M3U] Error in get-dns-list.php: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Erro ao carregar servidores DNS: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ], 500);
}
?>
