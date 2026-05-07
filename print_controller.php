<?php
/**
 * PrintNode Controller - Professional Bridge
 * Handled by Alexander Printer System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow access from other devices if necessary

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['order'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request or empty order"]);
    exit;
}

$order = $data['order'];
$url = 'http://localhost:5000/print';

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode(['order' => $order]),
        'timeout' => 5 // 5 second timeout
    ]
];

$context = stream_context_create($options);

// Suppress errors to handle them manually
$response = @file_get_contents($url, false, $context);

if ($response === FALSE) {
    http_response_code(503);
    echo json_encode([
        "status" => "error", 
        "message" => "The Python print server is not responding. Make sure printer_server.py is running."
    ]);
} else {
    echo json_encode(["status" => "success", "message" => "Order processed successfully"]);
}