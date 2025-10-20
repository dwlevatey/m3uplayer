<?php
// Middleware to check client authentication
require_once __DIR__ . '/../includes/functions.php';

function require_client_auth() {
    if (!is_client_logged_in()) {
        redirect('../index.php');
    }
    
    // Verify session is still valid
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT * FROM client_sessions 
        WHERE session_token = ? AND client_id = ? AND expires_at > NOW()
    ");
    $stmt->execute([$_SESSION['session_token'], $_SESSION['client_id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        session_destroy();
        redirect('../index.php');
    }
    
    // Update session expiry
    $new_expiry = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    $stmt = $conn->prepare("UPDATE client_sessions SET expires_at = ? WHERE session_token = ?");
    $stmt->execute([$new_expiry, $_SESSION['session_token']]);
}
?>
