<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_client_logged_in()) {
    json_response(['success' => false, 'message' => 'Não autenticado'], 401);
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
    
    // Group by category
    $grouped_channels = group_channels_by_category($channels);
    
    // Get category with specific name if provided
    $category_name = $_GET['category'] ?? null;
    
    if ($category_name) {
        $category_channels = $grouped_channels[$category_name] ?? [];
        
        json_response([
            'success' => true,
            'data' => [
                'category' => $category_name,
                'channels' => $category_channels,
                'total' => count($category_channels)
            ]
        ]);
    } else {
        // Return all categories with channel counts
        $categories = [];
        foreach ($grouped_channels as $category => $channels_list) {
            $categories[] = [
                'name' => $category,
                'count' => count($channels_list)
            ];
        }
        
        json_response([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'total' => count($categories)
            ]
        ]);
    }
    
} catch (Exception $e) {
    json_response([
        'success' => false,
        'message' => 'Erro ao buscar categorias: ' . $e->getMessage()
    ], 500);
}
?>
