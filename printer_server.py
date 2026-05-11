import os
import json
import logging
import time
import threading
from collections import defaultdict
from flask import Flask, request, jsonify
from flask_cors import CORS
import win32print
import win32ui
import win32con
from datetime import datetime
import requests
import urllib3

# Desactivar advertencias de SSL para certificados auto-firmados
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# =========================
# CONFIGURACIÓN DE LOGS
# =========================
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("server.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)  # Enable CORS to avoid network issues

@app.after_request
def add_cors_headers(response):
    response.headers['Access-Control-Allow-Private-Network'] = 'true'
    return response

CONFIG_FILE = "config_impresora.json"
TEST_JSON_PATH = os.path.join("integracion", "server_comandas.json")
HISTORIAL_FILE = "historial_impresion.json"

# =========================
# GESTIÓN DE HISTORIAL
# =========================

def cargar_historial():
    if os.path.exists(HISTORIAL_FILE):
        try:
            with open(HISTORIAL_FILE, "r") as f:
                return set(json.load(f))
        except:
            return set()
    return set()

def guardar_historial(historial):
    try:
        with open(HISTORIAL_FILE, "w") as f:
            json.dump(list(historial), f)
    except Exception as e:
        logger.error(f"Error guardando historial: {e}")

# =========================
# FORMATEO (Template from remota.py)
# =========================

def formatear_comanda(comandas, totalizar=False):
    if not comandas:
        return ""
        
    base = comandas[0]
    mesas = base.get("mesas", [])

    texto = "\n"
    texto += f"PEDIDO #{base.get('id_pedido', 'N/A')}\n"

    if mesas:
        mesa = mesas[0]
        texto += f"{mesa.get('zona', 'MESA')} {mesa.get('numero_mesa', '')}\n"
    else:
        texto += "PEDIDO SIN MESA\n"

    texto += f"MESERO: {base.get('mesero','')}\n"
    texto += f"AREA: {base.get('area_preparacion','general').upper()}\n"
    texto += "-" * 24 + "\n"

    if base.get("nombre_cliente"):
        texto += f"CLIENTE: {base['nombre_cliente']}\n"

    telefono = str(base.get("telefono", "")).strip()
    if telefono:
        texto += f"TEL: {telefono}\n"

    direccion = base.get("direccion", "").strip()
    if direccion:
        texto += f"DIRECCION: {direccion}\n"

    texto += "-" * 24 + "\n"

    for c in comandas:
        for p in c.get("productos", []):
            texto += f"{p['cantidad']}x {p['nombre_producto']}\n"

            if p.get("observacion"):
                for linea in p["observacion"].split("\n"):
                    texto += f"  {linea}\n"

    texto += "-" * 24 + "\n"

    if totalizar:
        total = 0
        for c in comandas:
            for p in c.get("productos", []):
                total += p["cantidad"] * p.get("precio_raw", 0)
        texto += f"TOTAL: {int(total)}\n"
        texto += "-" * 24 + "\n"

    texto += base.get("fecha", "") + "\n\n"

    return texto

class PrinterService:
    def __init__(self, config_path):
        self.config_path = config_path
        self.config = self.load_config()

    def load_config(self):
        if os.path.exists(self.config_path):
            with open(self.config_path, "r") as f:
                config = json.load(f)
            logger.info(f"Configuration loaded: {config['printer']}")
            return config
        
        return self.setup_printer()

    def setup_printer(self):
        printers = win32print.EnumPrinters(
            win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
        )
        printer_list = [p[2] for p in printers]

        print("\n=== SELECT PRINTER ===")
        for i, name in enumerate(printer_list, 1):
            print(f"{i}. {name}")

        try:
            idx = int(input("\nSelect printer number: "))
            printer_name = printer_list[idx - 1]
            
            config = {
                "printer": printer_name,
                "font_size": 34,
                "totalizar": False
            }

            with open(self.config_path, "w") as f:
                json.dump(config, f, indent=4)
            
            logger.info(f"Printer configured: {printer_name}")
            return config
        except (ValueError, IndexError):
            logger.error("Invalid selection. Retrying...")
            return self.setup_printer()

    def print_thermal(self, texto, job_name="Comanda"):
        try:
            printer_name = self.config["printer"]
            tamano_letra = self.config.get("font_size", 34)
            
            hDC = win32ui.CreateDC()
            hDC.CreatePrinterDC(printer_name)

            hDC.StartDoc(job_name)
            hDC.StartPage()

            font_producto = win32ui.CreateFont({
                "name": "Lucida Console",
                "height": -tamano_letra,
                "weight": 700,
            })

            font_obs = win32ui.CreateFont({
                "name": "Consolas",
                "height": -tamano_letra,
                "weight": 400,
            })

            hDC.SelectObject(font_obs)
            width = hDC.GetDeviceCaps(win32con.HORZRES)

            def wrap(line):
                words = line.split()
                out = []
                current = ""

                for w in words:
                    prueba = f"{current} {w}".strip()
                    try:
                        ancho = hDC.GetTextExtent(prueba)[0]
                    except:
                        ancho = 0

                    if ancho <= width - 100:
                        current = prueba
                    else:
                        out.append(current)
                        current = w

                if current:
                    out.append(current)

                return out

            y = 100
            for line in texto.upper().split("\n"):
                if line.startswith("  "):
                    hDC.SelectObject(font_obs)
                elif line.strip()[:1].isdigit() and ("X" in line or "x" in line):
                    hDC.SelectObject(font_producto)
                else:
                    hDC.SelectObject(font_obs)

                text_height = hDC.GetTextExtent("Ay")[1]
                line_height = text_height + 6

                for wline in wrap(line):
                    hDC.TextOut(20, y, wline)
                    y += line_height

            hDC.EndPage()
            hDC.EndDoc()
            hDC.DeleteDC()
            logger.info(f"Document sent to {printer_name}")
            return True
        except Exception as e:
            logger.error(f"Print error: {str(e)}")
            return False

# =========================
# POLLING EN SEGUNDO PLANO
# =========================

def background_polling(printer_service):
    """Monitorea una URL remota o un archivo local y evita duplicados usando historial"""
    logger.info("Iniciando monitoreo de pedidos...")
    historial = cargar_historial()
    first_run = True # Bandera para no imprimir el backlog al arrancar
    
    while True:
        try:
            remote_url = printer_service.config.get("remote_url")
            data = []

            # 1. Intentar obtener datos desde URL remota si está configurada
            if remote_url:
                try:
                    response = requests.get(remote_url, timeout=10, verify=False)
                    if response.status_code == 200:
                        data = response.json()
                    else:
                        logger.error(f"Error accediendo a URL remota ({response.status_code}): {remote_url}")
                except Exception as e:
                    logger.error(f"Fallo de conexión remota: {e}")
            
            # 2. Si no hay URL o falló, intentar con archivo local (fallback)
            elif os.path.exists(TEST_JSON_PATH):
                try:
                    with open(TEST_JSON_PATH, "r", encoding="utf-8") as f:
                        data = json.load(f)
                except json.JSONDecodeError:
                    data = []
            
            if not isinstance(data, list):
                data = []

            # Agrupar por id_pedido para imprimir tickets completos
            nuevos_por_pedido = defaultdict(list)
            for item in data:
                item_id = str(item.get("id") or item.get("id_unico"))
                if item_id not in historial:
                    nuevos_por_pedido[item.get("id_pedido")].append(item)
            
            if nuevos_por_pedido:
                if first_run:
                    # En la primera ejecución, solo actualizamos el historial para ignorar el pasado
                    for items in nuevos_por_pedido.values():
                        for item in items:
                            item_id = str(item.get("id") or item.get("id_unico"))
                            historial.add(item_id)
                    guardar_historial(historial)
                    logger.info(f"Arranque: Se detectaron {len(nuevos_por_pedido)} pedidos antiguos. Han sido marcados como leídos.")
                else:
                    # Impresión normal de nuevos pedidos
                    for pid, items in nuevos_por_pedido.items():
                        ticket = formatear_comanda(items, totalizar=printer_service.config.get("totalizar", False))
                        if printer_service.print_thermal(ticket, job_name=f"Auto-Print Order {pid}"):
                            # Marcar como impresos
                            for item in items:
                                item_id = str(item.get("id") or item.get("id_unico"))
                                historial.add(item_id)
                    
                    guardar_historial(historial)
                    logger.info(f"Se imprimieron {len(nuevos_por_pedido)} pedidos nuevos detectados.")
            
            first_run = False # Ya podemos procesar nuevos pedidos normalmente
            
        except Exception as e:
            logger.error(f"Error en el ciclo de monitoreo: {e}")
            
        time.sleep(5) # Revisa cada 5 segundos

printer_service = None

@app.route('/print', methods=['POST'])
def print_endpoint():
    data = request.get_json()
    if not data:
        return jsonify({"status": "error", "message": "Incomplete data"}), 400

    # If it's a list of comandas (new structure)
    if isinstance(data, list):
        # Filtrar los que ya se imprimieron (por si acaso hay reenvío)
        historial = cargar_historial()
        nuevos = [item for item in data if str(item.get("id") or item.get("id_unico")) not in historial]
        
        if not nuevos:
            return jsonify({"status": "ok", "message": "Already printed"})
            
        ticket = formatear_comanda(nuevos, totalizar=printer_service.config.get("totalizar", False))
        if printer_service.print_thermal(ticket):
            # Actualizar historial
            for item in nuevos:
                historial.add(str(item.get("id") or item.get("id_unico")))
            guardar_historial(historial)
            return jsonify({"status": "ok"})
    elif 'order' in data:
        # Compatibility with old structure (no ID tracking here)
        if printer_service.print_thermal(data['order']):
            return jsonify({"status": "ok"})
    else:
        return jsonify({"status": "error", "message": "Format not recognized"}), 400
    
    return jsonify({"status": "error", "message": "Printer error"}), 500

@app.route('/test-json', methods=['GET'])
def test_json():
    """Endpoint for testing with local JSON file (ignoring history for test)"""
    if not os.path.exists(TEST_JSON_PATH):
        return jsonify({"status": "error", "message": f"File not found: {TEST_JSON_PATH}"}), 404
        
    try:
        with open(TEST_JSON_PATH, "r", encoding="utf-8") as f:
            data = json.load(f)
            
        if not data:
            return jsonify({"status": "error", "message": "Empty JSON"}), 400
            
        first_order_id = data[0].get("id_pedido")
        order_items = [item for item in data if item.get("id_pedido") == first_order_id]
        
        ticket = formatear_comanda(order_items, totalizar=printer_service.config.get("totalizar", False))
        
        if printer_service.print_thermal(ticket, job_name=f"Test Order {first_order_id}"):
            return jsonify({"status": "ok", "message": f"Test print sent for order {first_order_id}"})
        else:
            return jsonify({"status": "error", "message": "Print failed"}), 500
            
    except Exception as e:
        logger.exception("Error in test-json")
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route('/status', methods=['GET'])
def status():
    return jsonify({
        "status": "running",
        "printer": printer_service.config.get("printer"),
        "config": printer_service.config
    })

if __name__ == '__main__':
    printer_service = PrinterService(CONFIG_FILE)
    
    # Iniciar hilo de monitoreo (Polling)
    poll_thread = threading.Thread(target=background_polling, args=(printer_service,), daemon=True)
    poll_thread.start()
    
    logger.info("Print server started at http://0.0.0.0:5000")
    app.run(host='0.0.0.0', port=5000, debug=False)

