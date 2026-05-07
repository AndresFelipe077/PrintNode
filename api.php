<?php
/**
 * Shared Hosting API for PrintNode
 * Manages the order queue without requiring sockets.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$queueFile = 'orders_queue.json';

// Initialize queue file if not exists
if (!file_exists($queueFile)) {
    file_put_contents($queueFile, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    // SAVE ORDER
    if (isset($input['order'])) {
        $queue = json_decode(file_get_contents($queueFile), true);
        $newOrder = [
            'id' => uniqid(),
            'content' => $input['order'],
            'timestamp' => time(),
            'status' => 'pending'
        ];
        $queue[] = $newOrder;
        file_put_contents($queueFile, json_encode($queue));
        echo json_encode(['status' => 'success', 'message' => 'Order queued', 'order_id' => $newOrder['id']]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No order content provided']);
    }
} elseif ($method === 'GET') {
    // GET PENDING ORDERS
    $queue = json_decode(file_get_contents($queueFile), true);
    $pending = array_filter($queue, function($o) {
        return $o['status'] === 'pending';
    });

    // Mark as "processing" so they aren't picked up again immediately
    // In a real app, you'd wait for a "printed" confirmation
    foreach ($queue as &$o) {
        if ($o['status'] === 'pending') {
            $o['status'] = 'processing';
        }
    }
    file_put_contents($queueFile, json_encode($queue));

    echo json_encode(['status' => 'success', 'orders' => array_values($pending)]);
}
