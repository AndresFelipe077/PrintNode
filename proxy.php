<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$path = isset($_GET['path']) ? $_GET['path'] : '';
if (!$path) {
    http_response_code(400);
    echo json_encode(['status'=>'error', 'message'=>'No path specified']);
    exit;
}

$url = 'http://127.0.0.1:5000/' . ltrim($path, '/');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($input)
    ));
}

$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($result === false) {
    http_response_code(503);
    echo json_encode(['status'=>'error', 'message'=>'Print server not reachable', 'detail'=>$error]);
    exit;
}

http_response_code($httpcode ?: 500);
header('Content-Type: application/json');
echo $result;
