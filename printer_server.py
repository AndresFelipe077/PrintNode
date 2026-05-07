import os
import json
import logging
from flask import Flask, request, jsonify
from flask_cors import CORS
import win32print
import win32ui
import win32con

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
    # This header is required for Private Network Access (PNA)
    # when a public site tries to talk to a local/loopback address
    response.headers['Access-Control-Allow-Private-Network'] = 'true'
    return response

CONFIG_FILE = "config_impresora.json"

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
                "font_size": 34
            }

            with open(self.config_path, "w") as f:
                json.dump(config, f, indent=4)
            
            logger.info(f"Printer configured: {printer_name}")
            return config
        except (ValueError, IndexError):
            logger.error("Invalid selection. Retrying...")
            return self.setup_printer()

    def print_text(self, text, job_name="Order"):
        try:
            hDC = win32ui.CreateDC()
            hDC.CreatePrinterDC(self.config["printer"])

            hDC.StartDoc(job_name)
            hDC.StartPage()

            font = win32ui.CreateFont({
                "name": "Lucida Console",
                "height": -self.config["font_size"],
                "weight": 700
            })
            hDC.SelectObject(font)

            y = 100
            for line in text.split("\n"):
                hDC.TextOut(50, y, line)
                y += 40

            hDC.EndPage()
            hDC.EndDoc()
            hDC.DeleteDC()
            logger.info(f"Document sent to {self.config['printer']}")
            return True
        except Exception as e:
            logger.error(f"Print error: {str(e)}")
            return False

printer_service = None

@app.route('/print', methods=['POST'])
def print_endpoint():
    data = request.get_json()
    if not data or 'order' not in data:
        return jsonify({"status": "error", "message": "Incomplete data"}), 400

    order = data['order']
    ticket = f"\n================\n   NEW ORDER\n================\n{order}\n================\n"
    
    if printer_service.print_text(ticket):
        return jsonify({"status": "ok"})
    else:
        return jsonify({"status": "error", "message": "Printer error"}), 500

@app.route('/status', methods=['GET'])
def status():
    return jsonify({
        "status": "running",
        "printer": printer_service.config.get("printer"),
        "config": printer_service.config
    })

if __name__ == '__main__':
    printer_service = PrinterService(CONFIG_FILE)
    # Listening on all interfaces to allow access from mobile
    logger.info("Print server started at http://0.0.0.0:5000")
    app.run(host='0.0.0.0', port=5000, debug=False)