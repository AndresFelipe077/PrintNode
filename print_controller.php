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
$url = 'http://127.0.0.1:5000/print';

$ch = curl_init($url);
$payload = json_encode(['order' => $order]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === FALSE || $httpCode !== 200) {
    error_log("Print Error: Code $httpCode, Detail: $error");
    http_response_code(503);
    echo json_encode([
        "status" => "error", 
        "message" => "The Python print server is not responding correctly.",
        "details" => $error,
        "http_code" => $httpCode
    ]);
} else {
    echo json_encode(["status" => "success", "message" => "Order processed successfully"]);
}