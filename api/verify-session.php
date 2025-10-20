<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_client_logged_in()) {
    json_response(['success' => false, 'message' => 'Não autenticado'], 401);
}

$db = new Database();
$conn = $db->getConnection();

// Verify session token
$stmt = $conn->prepare("
    SELECT * FROM client_sessions 
    WHERE session_token = ? AND client_id = ? AND expires_at > NOW()
");
$stmt->execute([$_SESSION['session_token'], $_SESSION['client_id']]);
$session = $stmt->fetch();

if (!$session) {
    session_destroy();
    json_response(['success' => false, 'message' => 'Sessão expirada'], 401);
}

json_response([
    'success' => true,
    'data' => [
        'username' => $_SESSION['client_username'],
        'dns_name' => $_SESSION['dns_name']
    ]
]);
?>
