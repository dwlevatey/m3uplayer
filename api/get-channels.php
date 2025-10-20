<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_client_logged_in()) {
    json_response(['success' => false, 'message' => 'Não autenticado'], 401);
}

try {
    error_log("[v0 DEBUG] Session data: " . json_encode([
        'client_id' => $_SESSION['client_id'] ?? 'NOT SET',
        'client_username' => $_SESSION['client_username'] ?? 'NOT SET',
        'dns_url' => $_SESSION['dns_url'] ?? 'NOT SET',
        'output_format' => $_SESSION['output_format'] ?? 'NOT SET'
    ]));
    
    $output_format = $_SESSION['output_format'] ?? 'mpegts';
    
    // Build M3U URL
    $m3u_url = build_m3u_url(
        $_SESSION['dns_url'],
        $_SESSION['client_username'],
        $_SESSION['client_password'],
        $output_format
    );
    
    error_log("[v0 DEBUG] M3U URL construída: {$m3u_url}");
    
    // Fetch M3U content with timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'M3U Streaming Client/1.0'
        ]
    ]);
    
    $m3u_content = @file_get_contents($m3u_url, false, $context);
    
    if ($m3u_content === false) {
        $error = error_get_last();
        error_log("[v0 DEBUG] Falha ao buscar playlist. Erro: " . json_encode($error));
        json_response([
            'success' => false, 
            'message' => 'Erro ao buscar playlist. Verifique suas credenciais.',
            'debug' => [
                'url' => $m3u_url,
                'output_format' => $output_format,
                'error' => $error['message'] ?? 'Erro desconhecido'
            ]
        ], 500);
    }
    
    error_log("[v0 DEBUG] Conteúdo M3U recebido. Tamanho: " . strlen($m3u_content) . " bytes");
    
    if (strpos($m3u_content, '#EXTM3U') === false) {
        error_log("[v0 DEBUG] Conteúdo M3U inválido. Primeiros 500 caracteres: " . substr($m3u_content, 0, 500));
        json_response([
            'success' => false,
            'message' => 'Conteúdo M3U inválido recebido do servidor',
            'debug' => [
                'content_preview' => substr($m3u_content, 0, 200)
            ]
        ], 500);
    }
    
    // Parse M3U content
    $channels = parse_m3u_content($m3u_content);
    
    error_log("[v0 DEBUG] Canais parseados: " . count($channels));
    
    if (empty($channels)) {
        json_response([
            'success' => false,
            'message' => 'Nenhum canal encontrado na playlist'
        ], 404);
    }
    
    // Group by category
    $grouped_channels = group_channels_by_category($channels);
    
    // Get categories list
    $categories = array_keys($grouped_channels);
    
    error_log("[v0 DEBUG] Sucesso! " . count($channels) . " canais carregados");
    
    json_response([
        'success' => true,
        'data' => [
            'channels' => $channels,
            'grouped' => $grouped_channels,
            'categories' => $categories,
            'total' => count($channels)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("[v0 DEBUG] Exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    json_response([
        'success' => false,
        'message' => 'Erro ao processar playlist: ' . $e->getMessage()
    ], 500);
}
?>
