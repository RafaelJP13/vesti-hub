<?php

declare(strict_types=1);

$routes = [
    '/erp/xpto/produtos.json' => __DIR__ . '/../data/erpxpto/produtos-erp.json',
    '/erp/xpto/variacoes.json' => __DIR__ . '/../data/erpxpto/variacoes-erp.json',
    '/erp/xyz/produtos.json' => __DIR__ . '/../data/erpxyz/produtos-erp.json',
    '/erp/xyz/variacoes.json' => __DIR__ . '/../data/erpxyz/variacoes-erp.json',
];

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (!isset($routes[$requestPath])) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Not Found']);
    return;
}

$filePath = $routes[$requestPath];

if (!is_file($filePath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Mock data file not found']);
    return;
}

$content = file_get_contents($filePath);

if ($content === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unable to read mock data']);
    return;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo $content;
