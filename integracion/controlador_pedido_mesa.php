<?php
include("connect.php");

$conn->set_charset("utf8");
$conn->query("SET time_zone = '-05:00'");

date_default_timezone_set('America/Bogota');

$fecha_pedido = date("Y-m-d H:i:s");

$hash_id = $_POST['hash_id'] ?? '';

if (!empty($hash_id)) {

    $stmtCheck = $conn->prepare("SELECT id FROM tabla_pedidos WHERE hash_id = ?");
    $stmtCheck->bind_param("s", $hash_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Si ya existe el pedido con ese hash, redirigir o mostrar mensaje
        header("Location: ventas_globales.php?error=Pedido+ya+enviado");
        exit;
    }

    $stmtCheck->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $pedido_json = $_POST['pedido_json'] ?? '';

    // Decodificar pedido
    $productos = json_decode($pedido_json, true);

    // Agregar fecha_hora_agregado a cada grupo de producto
    if (is_array($productos)) {
        foreach ($productos as &$producto) {
            if (!isset($producto['fecha_hora_agregado'])) {
                $producto['fecha_hora_agregado'] = date('Y-m-d H:i:s');
            }
        }
        unset($producto);

        // Volver a convertir a JSON actualizado
        $pedido_json = json_encode($productos, JSON_UNESCAPED_UNICODE);
    }

    $mesas_seleccionadas = json_decode($_POST['mesas_seleccionadas'] ?? '[]', true);
    $dato_estatus = $_POST['dato_estatus'] ?? 'preparacion';
    $slug = $_POST['slug'] ?? 'sin etiqueta';
    $ticket_total = $_POST['ticket_total'] ?? 0;
    $tipo_venta = $_POST['tipo_venta'] ?? 'ventas en mesa';
    $mesero_pedido = $_POST['mesero_pedido'] ?? '';
    $estatus_pago = 'pendiente';
    $tipo_pago = 'sin definir';
    $estatus_orden = 'abierta';
    $direccion = $_POST['direccion'] ?? '';
    $valor_zona_domicilio = $_POST['valor_zona_domicilio'] ?? '';
    $hash_id = $_POST['hash_id'] ?? '';

    /* =====================================================
   CREAR O ACTUALIZAR CLIENTE AUTOMÁTICAMENTE
===================================================== */

    if (!empty($telefono)) {

        // Verificar si el cliente ya existe
        $stmtCliente = $conn->prepare("SELECT id_cliente FROM tabla_clientes WHERE telefono = ?");
        $stmtCliente->bind_param("s", $telefono);
        $stmtCliente->execute();
        $resultCliente = $stmtCliente->get_result();

        if ($resultCliente->num_rows > 0) {

            // 🔄 ACTUALIZAR cliente existente
            $stmtUpdateCliente = $conn->prepare("
                UPDATE tabla_clientes 
                SET nombre = ?, 
                    direccion = ?, 
                    frecuencia_visita = frecuencia_visita + 1,
                    total_compras = total_compras + ?
                WHERE telefono = ?
            ");

            $stmtUpdateCliente->bind_param("ssds", $nombre, $direccion, $ticket_total, $telefono);
            $stmtUpdateCliente->execute();
            $stmtUpdateCliente->close();
        } else {

            // ➕ CREAR nuevo cliente
            $stmtInsertCliente = $conn->prepare("
                INSERT INTO tabla_clientes 
                (nombre, telefono, direccion, fecha_registro, total_compras, frecuencia_visita)
                VALUES (?, ?, ?, CURDATE(), ?, 1)
            ");
            $stmtInsertCliente->bind_param("sssd", $nombre, $telefono, $direccion, $ticket_total);
            $stmtInsertCliente->execute();
            $stmtInsertCliente->close();
        }

        $stmtCliente->close();
    }

    // Marcar mesas como Ocupadas
    // Verificar si la mesa ya está ocupada antes de crear el pedido
    if (!empty($mesas_seleccionadas)) {
        $ids = array_column($mesas_seleccionadas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // 1. Comprobar si alguna de las mesas está ocupada
        $stmtCheck = $conn->prepare("SELECT id, numero_mesa, zona, estatus_mesa FROM tabla_mesa_zona WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmtCheck->bind_param($types, ...$ids);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        $mesa_ocupada = false;
        $detalle_mesa_ocupada = '';

        while ($row = $result->fetch_assoc()) {
            if (strtolower($row['estatus_mesa']) === 'ocupada') {
                $mesa_ocupada = true;
                $detalle_mesa_ocupada = $row['zona'] . ' - Mesa ' . $row['numero_mesa'];
                break;
            }
        }
        $stmtCheck->close();

        if ($mesa_ocupada) {
            // Redirigir si la mesa ya está ocupada
            header("Location: ventas_globales.php?error=Mesa+ocupada:+$detalle_mesa_ocupada");
            exit;
        }

        // 2. Si está libre, marcarla como ocupada
        $stmtMesas = $conn->prepare("UPDATE tabla_mesa_zona SET estatus_mesa = 'ocupada' WHERE id IN ($placeholders)");
        $stmtMesas->bind_param($types, ...$ids);
        $stmtMesas->execute();
        $stmtMesas->close();
    }

    // Guardar el pedido
    $stmt = $conn->prepare("INSERT INTO tabla_pedidos (
    nombre, telefono, direccion, observaciones, pedido_json, dato_estatus,
    slug, ticket_total, tipo_venta, estatus_pago,
    tipo_pago, mesero_pedido, estatus_orden, mesas_seleccionadas, fecha_pedido, valor_zona_domicilio, hash_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $mesas_json = json_encode($mesas_seleccionadas);
    $stmt->bind_param(
        "sssssssssssssssss",
        $nombre,
        $telefono,
        $direccion,
        $observaciones,
        $pedido_json,
        $dato_estatus,
        $slug,
        $ticket_total,
        $tipo_venta,
        $estatus_pago,
        $tipo_pago,
        $mesero_pedido,
        $estatus_orden,
        $mesas_json,
        $fecha_pedido,
        $valor_zona_domicilio,
        $hash_id
    );

    if ($stmt->execute()) {
        $id_pedido = $stmt->insert_id;

        $comandas_por_area = [];

        // ✅ AGRUPAR PRIMERO
        foreach ($productos as $producto) {
            $area = $producto['area_preparacion'] ?? 'general';
            $comandas_por_area[$area][] = $producto;
        }

        // ✅ AHORA SÍ PREPARAR INSERT
        $stmtDetalle = $conn->prepare("
            INSERT INTO tabla_detalle_pedido
            (id_pedido, id_comanda, id_producto, nombre_producto, categoria, area_preparacion,
            cantidad, precio_unitario, observacion, composicion_json, preparacion_json,
            clasificacion, conteo, hash_item)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($comandas_por_area as $area => $items) {

            $id_comanda = md5($id_pedido . $area . time());

            foreach ($items as $item) {

                $id_producto = (int)$item['id_producto'];
                $nombre_producto = $item['nombre_producto'];
                $categoria = $item['dato_categoria'] ?? null;
                $area_item = (isset($item['area_preparacion']) && trim($item['area_preparacion']) !== '')
                    ? trim($item['area_preparacion'])
                    : 'general';
                $cantidad = (int)$item['cantidad'];
                $precio_unitario = (float)$item['precio_raw'];
                $observacion = $item['observacion'] ?? null;
                $composicion_json = (
                    isset($item['composicion']) && is_array($item['composicion'])
                )
                    ? json_encode($item['composicion'], JSON_UNESCAPED_UNICODE)
                    : json_encode([], JSON_UNESCAPED_UNICODE);
                $preparacion_json = (
                    isset($item['preparacion']) && is_array($item['preparacion'])
                )
                    ? json_encode($item['preparacion'], JSON_UNESCAPED_UNICODE)
                    : json_encode([], JSON_UNESCAPED_UNICODE);
                $clasificacion = $item['clasificacion'] ?? 'venta';
                $conteo = $item['conteo'] ?? 'no';
                $hash_item = md5($id_comanda . $id_producto . microtime(true));

                $stmtDetalle->bind_param(
                    "issssiisssssss",
                    $id_pedido,
                    $id_comanda,
                    $id_producto,
                    $nombre_producto,
                    $categoria,
                    $area_item,
                    $cantidad,
                    $precio_unitario,
                    $observacion,
                    $composicion_json,
                    $preparacion_json,
                    $clasificacion,
                    $conteo,
                    $hash_item
                );
                $stmtDetalle->execute();
            }
        }

        $stmtDetalle->close();


        // Preparar contenido para el archivo JSON
        $contenido_actual = [];
        $ruta_json = 'server_comandas.json';

        if (file_exists($ruta_json)) {
            $contenido_actual = json_decode(file_get_contents($ruta_json), true);
            if (!is_array($contenido_actual)) {
                $contenido_actual = [];
            }
        }

        // Leer configuraciones
        $config_file = 'server_configuraciones.json';
        $colocar_cliente_comanda = false;
        $impresion_automatica = true; // ← NUEVA VARIABLE

        if (file_exists($config_file)) {
            $config_data = json_decode(file_get_contents($config_file), true);
            if (is_array($config_data)) {
                foreach ($config_data as $conf) {

                    if ($conf['clave'] === 'colocar_cliente_comanda' && strtolower($conf['valor']) === 'si') {
                        $colocar_cliente_comanda = true;
                    }

                    if ($conf['clave'] === 'impresion_automatica_de_comandas' && strtolower($conf['valor']) === 'no') {
                        $impresion_automatica = false;
                    }
                }
            }
        }

        if ($impresion_automatica) {
            foreach ($comandas_por_area as $area => $items) {
                $hash_id = md5($id_pedido . $area . time()); // ID único

                // Construir comanda base
                $comanda = [
                    'id' => $hash_id,
                    'id_pedido' => $id_pedido,
                    'tipo_documento' => 'comanda_automatica',
                    'mesas' => $mesas_seleccionadas,
                    'area_preparacion' => $area,
                    'productos' => $items,
                    'telefono' => $telefono,
                    'direccion' => $direccion,
                    'mesero' => $mesero_pedido,
                    'fecha' => date('Y-m-d H:i:s'),
                    'fecha_creacion_documento' => date('Y-m-d H:i:s')
                ];

                // Si está activa la opción de mostrar nombre, lo agregamos
                if ($colocar_cliente_comanda && !empty($nombre)) {
                    $comanda['nombre_cliente'] = $nombre;
                }

                // =====================================================
                // ENVÍO AL SERVIDOR DE IMPRESIÓN (Python)
                // =====================================================
                // Solo intentamos CURL directo si estamos en local para rapidez.
                // En el servidor real, el Python "jalara" el pedido por Polling.
                if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1') {
                    try {
                        $ch = curl_init('http://127.0.0.1:5000/print');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$comanda])); // Enviamos como lista
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Reducimos a 1s para no afectar al usuario
                        curl_exec($ch);
                        curl_close($ch);
                    } catch (Exception $e) {
                        // Error silencioso
                    }
                }

                $contenido_actual[] = $comanda;
            }

            // Guardar el archivo actualizado
            file_put_contents($ruta_json, json_encode($contenido_actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Actualizar cada mesa seleccionada con id_pedido y ticket_total
        if (!empty($mesas_seleccionadas)) {
            $stmtUpdate = $conn->prepare("UPDATE tabla_mesa_zona SET id_pedido = ?, ticket_total = ? WHERE id = ?");

            foreach ($mesas_seleccionadas as $mesa) {
                $mesa_id = $mesa['id'];
                $stmtUpdate->bind_param("iii", $id_pedido, $ticket_total, $mesa_id);
                $stmtUpdate->execute();
            }

            $stmtUpdate->close();
        }

        if ($tipo_venta === 'rapida') {
            header("Location: ticket_final.php?id=" . $id_pedido);
        } else {
            header("Location: control_impresion_comandas.php?id=" . $id_pedido);
        }
        exit;
    }
}
