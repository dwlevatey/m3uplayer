<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_client_logged_in()) {
    json_response(['success' => false, 'message' => 'Não autenticado'], 401);
}

$channel_url = $_GET['url'] ?? '';

if (empty($channel_url)) {
    json_response(['success' => false, 'message' => 'URL do canal não fornecida'], 400);
}

// Validate URL
if (!filter_var($channel_url, FILTER_VALIDATE_URL)) {
    json_response(['success' => false, 'message' => 'URL inválida'], 400);
}

json_response([
    'success' => true,
    'data' => [
        'stream_url' => $channel_url
    ]
]);
?>
