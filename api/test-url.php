<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$url = $data['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'URL is required']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'M3U Streaming System URL Tester');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$is_valid_m3u = $response && (
    strpos($response, '#EXTM3U') !== false || 
    strpos($response, '#EXTINF') !== false
);

$response_preview = $response ? substr($response, 0, 500) : '';

$looks_like_html = $response && (
    stripos($response, '<!DOCTYPE') !== false ||
    stripos($response, '<html') !== false ||
    stripos($response, '<body') !== false
);

echo json_encode([
    'success' => $http_code == 200 && $is_valid_m3u,
    'http_code' => $http_code,
    'is_valid_m3u' => $is_valid_m3u,
    'has_response' => !empty($response),
    'response_length' => strlen($response),
    'response_preview' => $response_preview,
    'looks_like_html' => $looks_like_html,
    'error' => $curl_error ?: null
]);
?>
