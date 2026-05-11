# Arquitectura de Impresión Híbrida (PHP + Python)

Este documento explica cómo implementar y escalar el sistema de impresión térmica remota utilizando PHP como emisor de datos y Python como controlador de hardware.

## 1. El Concepto: Puente de Datos
En un entorno web, el servidor (Hosting) no puede acceder directamente a las impresoras USB/Red del cliente por razones de seguridad del navegador. La solución es un **modelo de cola (Queue Model)**:

1.  **PHP** genera la información y la guarda en un archivo JSON.
2.  **Python** (corriendo localmente) vigila ese archivo e imprime cuando detecta cambios.

---

## 2. Fase 1: Formateo de Datos en PHP
El objetivo de PHP es construir un objeto con toda la información necesaria. **Nunca envíes texto plano**, envía datos estructurados.

### Ejemplo de Estructura JSON Sugerida:
```php
$comanda = [
    "id" => "ORDER_12345",
    "id_pedido" => 501,
    "tipo_documento" => "comanda", // o "factura", "recibo"
    "fecha" => date("Y-m-d H:i:s"),
    "cliente" => [
        "nombre" => "Andres Felipe",
        "telefono" => "3148446011",
        "direccion" => "Calle 10 #20-30"
    ],
    "mesas" => [
        ["zona" => "Terraza", "numero_mesa" => "5"]
    ],
    "productos" => [
        ["cantidad" => 2, "nombre_producto" => "Hamburguesa", "precio_raw" => 25000],
        ["cantidad" => 1, "nombre_producto" => "Coca Cola", "precio_raw" => 5000]
    ],
    "mesero" => "Caja Principal"
];
```

### Acción de PHP:
PHP debe tomar este array, convertirlo a JSON y agregarlo a la cola:
```php
$json_data = json_encode($comanda);
// Guardar en el archivo de cola (orders_queue.json)
```

---

## 3. Fase 2: El Servidor de Impresión (Python)
Python actúa como un "Escuchador" (Listener). Su trabajo es:
1.  Recibir el JSON vía HTTP (Flask) o leerlo de un archivo.
2.  **Parsear** el JSON: Recorrer los arrays de productos.
3.  **Dibujar** el ticket: Aplicar lógica de diseño (negritas para títulos, texto normal para observaciones).

### ¿Por qué Python?
*   Acceso directo a la API de Windows (`win32print`).
*   Capacidad de manejar fuentes y tamaños de letra píxel por píxel.
*   Estabilidad para procesos de fondo (Background services).

---

## 4. Estandarización de la Comunicación
Para que el sistema sea escalable, ambos lenguajes deben respetar el mismo "Contrato de Datos":

| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | String | Identificador único para evitar duplicados. |
| `productos` | Array | Lista de objetos con `cantidad` y `nombre_producto`. |
| `tipo_documento`| String | Permite a Python elegir un diseño (Comanda vs Factura). |

---

## 5. Ventajas de este Modelo
*   **Independencia del Hosting:** Tu web puede estar en cualquier servidor del mundo.
*   **Cero Configuración de Red:** No necesitas abrir puertos en el router (Port Forwarding) porque el navegador hace de puente.
*   **Formateo Profesional:** Python permite cortes de papel, apertura de cajón monedero y logos, algo casi imposible desde PHP directo.

## 6. Recomendaciones de Seguridad
1.  **Limpieza:** Una vez que Python imprima el pedido, el sistema debe marcarlo como "impreso" o eliminarlo de la cola para evitar repeticiones.
2.  **Validación:** Python debe validar que el JSON recibido tenga la estructura correcta antes de intentar enviarlo a la cola de impresión de Windows.

---
*Documentación generada para el proyecto PrintNode - 2026*
