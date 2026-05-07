# Alexander Print System - Sistema de Impresión Remota Local

Una solución profesional y eficiente para enviar órdenes desde dispositivos móviles (celulares/tablets) directamente a una impresora térmica local en Windows, utilizando una arquitectura híbrida de PHP y Python.

---

## 🏗️ ¿Cómo funciona? (Arquitectura)

Este sistema elimina la necesidad de servicios costosos en la nube o túneles lentos como ngrok. Funciona 100% en tu red local (Intranet):

1.  **Interfaz Web (PHP/Vue.js)**: Los meseros o clientes acceden a una página web moderna desde su celular.
2.  **Controlador Puente (PHP)**: Recibe la orden y la reenvía internamente al servidor de impresión.
3.  **Servidor de Impresión (Python)**: Se comunica directamente con el sistema de impresión de Windows para sacar el ticket físico.

---

## 📋 Requisitos del Sistema

### Hardware
*   **PC con Windows**: Donde estará conectada la impresora.
*   **Impresora Térmica/POS**: Instalada y configurada correctamente en Windows.
*   **Red Wi-Fi**: Todos los dispositivos deben estar conectados a la misma red.

### Software
*   **XAMPP**: (Específicamente el módulo Apache).
*   **Python 3.10 o superior**: Instalado en Windows (asegúrate de marcar la opción "Add Python to PATH" durante la instalación).

---

## 🚀 Guía de Instalación Paso a Paso

### 1. Preparar el Servidor Web (XAMPP)
*   Copia la carpeta completa `Alexander` dentro del directorio de XAMPP: `C:\xampp\htdocs\`.
*   Abre el **XAMPP Control Panel** e inicia el módulo **Apache**.

### 2. Configurar el Entorno Python
Abre una terminal (PowerShell o CMD) dentro de la carpeta del proyecto y ejecuta el siguiente comando para instalar las librerías necesarias:
```powershell
pip install flask flask-cors pywin32
```

### 3. Configurar el Firewall de Windows (Muy importante)
Para que los celulares puedan "ver" a tu PC, debes abrir el puerto de Apache:
1.  Busca en Windows: **Firewall de Windows Defender con seguridad avanzada**.
2.  Ve a **Reglas de entrada** -> **Nueva regla**.
3.  Selecciona **Puerto** -> **TCP** -> **Puertos locales específicos: 80**.
4.  Selecciona **Permitir la conexión**.
5.  Dale un nombre como "Acceso Web Alexander" y finaliza.

---

## 🏃 Cómo ponerlo en marcha

### Paso 1: Iniciar el Servidor de Impresión
En la terminal del proyecto, ejecuta:
```powershell
python printer_server.py
```
*   **Nota**: Si es la primera vez, el sistema te mostrará una lista de impresoras. Escribe el número correspondiente a tu impresora térmica y presiona **Enter**. Esto creará un archivo `config_impresora.json`.

### Paso 2: Conectar desde el Celular
1.  Averigua la IP local de tu PC (abre CMD y escribe `ipconfig`. Busca "Dirección IPv4", suele ser algo como `192.168.x.x`).
2.  En el navegador del celular, ingresa la URL:
    `http://TU_IP_LOCAL/Alexander/`
    *(Ejemplo: http://192.168.10.244/Alexander/)*

---

## ⚙️ Configuración Avanzada

Puedes editar el archivo `config_impresora.json` para ajustar detalles sin reiniciar todo:
*   `"printer"`: Nombre exacto de la impresora en Windows.
*   `"font_size"`: Tamaño de la letra en el ticket (ej: `34` para estándar, `40` para más grande).

---

## 🔍 Solución de Problemas

*   **El servidor Python da error de "Puerto ocupado"**: Asegúrate de que no tengas otra instancia de `printer_server.py` abierta. Cierra la ventana y vuelve a intentar.
*   **El celular no carga la página**:
    *   Verifica que el celular esté en el mismo Wi-Fi.
    *   Asegúrate de que Apache en XAMPP esté en color VERDE.
    *   Revisa el Paso 3 del Firewall.
*   **Imprime caracteres extraños o no imprime**:
    *   Verifica que la impresora esté encendida y tenga papel.
    *   Asegúrate de haber seleccionado la impresora correcta en la configuración inicial.
*   **La IP cambió**: Si reinicias el router, la IP de tu PC podría cambiar. Deberás usar la nueva IP en los celulares. (Se recomienda configurar una "IP Estática" en Windows para evitar esto).

---

© 2026 Alexander System - Desarrollo Profesional Local
