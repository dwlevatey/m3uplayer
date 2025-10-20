<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_client_logged_in()) {
    json_response(['success' => false, 'message' => 'Não autenticado'], 401);
}

$search_query = sanitize_input($_GET['q'] ?? '');

if (empty($search_query)) {
    json_response(['success' => false, 'message' => 'Query de busca não fornecida'], 400);
}

try {
    // Build M3U URL
    $m3u_url = build_m3u_url(
        $_SESSION['dns_url'],
        $_SESSION['client_username'],
        $_SESSION['client_password']
    );
    
    // Fetch M3U content
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'M3U Streaming Client/1.0'
        ]
    ]);
    
    $m3u_content = @file_get_contents($m3u_url, false, $context);
    
    if ($m3u_content === false) {
        json_response(['success' => false, 'message' => 'Erro ao buscar playlist'], 500);
    }
    
    // Parse M3U content
    $channels = parse_m3u_content($m3u_content);
    
    // Filter channels by search query
    $filtered_channels = array_filter($channels, function($channel) use ($search_query) {
        return stripos($channel['name'], $search_query) !== false ||
               stripos($channel['category'], $search_query) !== false;
    });
    
    // Reset array keys
    $filtered_channels = array_values($filtered_channels);
    
    json_response([
        'success' => true,
        'data' => [
            'channels' => $filtered_channels,
            'total' => count($filtered_channels),
            'query' => $search_query
        ]
    ]);
    
} catch (Exception $e) {
    json_response([
        'success' => false,
        'message' => 'Erro ao buscar canais: ' . $e->getMessage()
    ], 500);
}
?>
