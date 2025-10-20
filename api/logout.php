<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (is_client_logged_in() && isset($_SESSION['session_token'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Delete session from database
    $stmt = $conn->prepare("DELETE FROM client_sessions WHERE session_token = ?");
    $stmt->execute([$_SESSION['session_token']]);
}

session_destroy();

json_response(['success' => true, 'message' => 'Logout realizado com sucesso']);
?>
