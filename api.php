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
        $fileContent = file_exists($queueFile) ? file_get_contents($queueFile) : '';
        $queue = $fileContent ? json_decode($fileContent, true) : [];
        if (!is_array($queue)) $queue = [];
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
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $file = 'integracion/server_comandas.json';
        
        if ($action === 'get_comandas') {
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                if (!is_array($data)) $data = [];
                
                $comandasAgrupadas = [];
                foreach ($data as $item) {
                    $pid = $item['id_pedido'];
                    if (!isset($comandasAgrupadas[$pid])) {
                        $comandasAgrupadas[$pid] = [
                            'id_pedido' => $pid,
                            'cliente' => isset($item['nombre_cliente']) ? $item['nombre_cliente'] : 'Consumidor Final',
                            'fecha' => isset($item['fecha']) ? $item['fecha'] : '',
                            'items' => 0
                        ];
                    }
                    if (isset($item['productos']) && is_array($item['productos'])) {
                        foreach ($item['productos'] as $prod) {
                            $comandasAgrupadas[$pid]['items'] += isset($prod['cantidad']) ? $prod['cantidad'] : 1;
                        }
                    }
                }
                echo json_encode(['status' => 'success', 'comandas' => array_reverse(array_values($comandasAgrupadas))]);
            } else {
                echo json_encode(['status' => 'success', 'comandas' => []]);
            }
            exit;
        }

        if ($action === 'trigger_test') {
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                $target_id = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : null;
                $new_id = (string)time();
                $triggered = false;
                
                foreach ($data as &$item) {
                    if (!$target_id || $item['id_pedido'] == $target_id) {
                        $item['id'] = uniqid();
                        $item['id_unico'] = $item['id'];
                        $triggered = true;
                    }
                }
                
                if ($triggered) {
                    file_put_contents($file, json_encode($data));
                    echo json_encode(['status' => 'success', 'message' => 'Comanda encolada para impresión']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado en el JSON']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Archivo JSON no encontrado']);
            }
            exit;
        }
    }

    $fileContent = file_exists($queueFile) ? file_get_contents($queueFile) : '';
    $queue = $fileContent ? json_decode($fileContent, true) : [];
    if (!is_array($queue)) {
        $queue = [];
    }

    $pending = array_filter($queue, function($o) {
        return isset($o['status']) && $o['status'] === 'pending';
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
