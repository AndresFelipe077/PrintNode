# 🖨️ Alexander PrintNode - Guía de Configuración A a Z

Este sistema permite enviar órdenes desde cualquier parte del mundo (vía internet) e imprimirlas automáticamente en una impresora térmica local conectada a una PC con Windows.

---

## 🏗️ 1. Arquitectura del Sistema (¿Cómo funciona?)

Para que el sistema funcione en un **Hosting Compartido** sin necesidad de túneles complejos (como Ngrok), utilizamos una arquitectura de **Puente por Navegador**:

1.  **Hosting (Nube):** Aloja la web y la cola de pedidos (`api.php`). Recibe órdenes de clientes/celulares.
2.  **Navegador (Local):** Una pestaña abierta en la PC de la impresora actúa como "puente". Escucha pedidos nuevos en la nube y los manda a la impresora.
3.  **Servidor Python (Local):** Recibe las órdenes del navegador y habla con el driver de Windows para imprimir.

---

## 🌐 2. Configuración en el Hosting (Producción)

### A. Subida de Archivos
Sube los siguientes archivos a tu hosting vía FTP o Administrador de Archivos:
- `index.php` (Interfaz principal)
- `api.php` (Gestor de la cola de pedidos)
- `orders_queue.json` (Archivo donde se guardan los pedidos temporalmente)

### B. Permisos de Escritura (IMPORTANTE)
Asegúrate de que el archivo `orders_queue.json` tenga **permisos de escritura (775 o 777)**. El sistema lo creará automáticamente, pero el servidor debe tener permiso para escribir en la carpeta.

### C. URL del Sistema
Tu URL será algo como: `https://tu-dominio.com/Alexander/`

---

## 💻 3. Configuración en la PC Local (Donde está la impresora)

### A. Requisitos
1.  **Python 3.10+**: Descárgalo de [python.org](https://www.python.org/). Al instalar, marca la casilla **"Add Python to PATH"**.
2.  **Librerías**: Abre una terminal (CMD o PowerShell) y ejecuta:
    ```bash
    pip install flask flask-cors pywin32
    ```

### B. Iniciar el Servidor de Impresión
1.  Abre una terminal en la carpeta del proyecto.
2.  Ejecuta: `python printer_server.py`
3.  Si es la primera vez, selecciona tu impresora de la lista numerada.
4.  **Mantén esta ventana abierta.**

---

## 🔌 4. El "Puente" de Impresión (Paso Crítico)

Para que las órdenes se impriman solas, **debes tener siempre abierta una pestaña del navegador** con la URL de tu sistema en la PC donde está conectada la impresora.

1.  Abre Chrome o Edge en la PC local.
2.  Ingresa a la URL de tu hosting (ej: `https://tu-dominio.com/Alexander/`).
3.  Verifica que el estado diga **"Printer: Online"** (en color verde).
4.  **No cierres esta pestaña.** El sistema revisará automáticamente cada 5 segundos si hay pedidos nuevos para imprimir.

---

## 🔒 5. Solución al Bloqueo de HTTPS (Mixed Content)

Si tu hosting usa **HTTPS** (candado verde), el navegador bloqueará la comunicación con la impresora local (`http://127.0.0.1`) por seguridad. Para permitirlo **sin túneles**:

### En Google Chrome / Microsoft Edge:
1.  En la barra de direcciones de la PC local, escribe: `chrome://flags/#allow-insecure-localhost` (o `edge://flags/...`)
2.  Cambia la opción a **Enabled**.
3.  Reinicia el navegador.

*Alternativa:* Si no quieres tocar los flags, accede a tu sitio vía **HTTP** (sin la S) si tu hosting lo permite.

---

## 🛠️ 6. Solución de Problemas

- **Estatus "Offline":** 
    - Verifica que `printer_server.py` esté corriendo.
    - Asegúrate de que el antivirus/firewall no esté bloqueando el puerto 5000.
- **No se guardan los pedidos:**
    - Revisa los permisos de `orders_queue.json` en el hosting.
- **Impresión lenta:**
    - Puedes ajustar el tiempo de revisión en `index.php` cambiando `setInterval(this.pollOrders, 5000)` (5000ms = 5 seg).

---

© 2026 Alexander System - Documentación de Producción
