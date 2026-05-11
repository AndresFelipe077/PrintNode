import requests
import json
import uuid
import time
import os

# Configuración
URL = "http://127.0.0.1:5000/print"
JSON_PATH = os.path.join("integracion", "server_comandas.json")

def create_order(id_pedido, mesero="Simulador"):
    """Crea una estructura de orden compatible con el sistema"""
    return {
        "id": uuid.uuid4().hex,
        "id_pedido": id_pedido,
        "tipo_documento": "comanda_automatica",
        "mesas": [{"zona": "ZONA TEST", "numero_mesa": str(id_pedido)}],
        "productos": [
            {"cantidad": 1, "nombre_producto": f"Producto Prueba {id_pedido}", "precio_raw": 15000, "observacion": "Sin cebolla"},
            {"cantidad": 2, "nombre_producto": "Refresco Grande", "precio_raw": 4500}
        ],
        "mesero": mesero,
        "fecha": time.strftime("%Y-%m-%d %H:%M:%S"),
        "fecha_creacion_documento": time.strftime("%Y-%m-%d %H:%M:%S")
    }

def run_simulation():
    print("\n--- INICIANDO SIMULACION DE CARGA Y ESTABILIDAD ---\n")

    # TEST 1: Envío directo (API)
    order1 = create_order(500)
    print(f"1. Enviando Pedido #{order1['id_pedido']} via API...")
    try:
        r = requests.post(URL, json=[order1])
        print(f"   Resultado: {r.status_code} - {r.json()}\n")
    except Exception as e:
        print(f"   Error: {e}\n")

    # TEST 2: Intento de Duplicado (API)
    print(f"2. Re-enviando Pedido #{order1['id_pedido']} (Duplicado)...")
    try:
        r = requests.post(URL, json=[order1])
        print(f"   Resultado: {r.status_code} - {r.json()} (Deberia decir 'Already printed')\n")
    except Exception as e:
        print(f"   Error: {e}\n")

    # TEST 3: Polling (Escritura directa en JSON)
    order2 = create_order(501)
    print(f"3. Escribiendo Pedido #{order2['id_pedido']} directamente en server_comandas.json...")
    try:
        if os.path.exists(JSON_PATH):
            with open(JSON_PATH, "r", encoding="utf-8") as f:
                data = json.load(f)
            data.append(order2)
            with open(JSON_PATH, "w", encoding="utf-8") as f:
                json.dump(data, f, indent=4)
            print("   Escritura exitosa. El servidor (Polling) deberia detectarlo en < 5 seg.\n")
        else:
            print(f"   Error: No se encontro el archivo en {JSON_PATH}\n")
    except Exception as e:
        print(f"   Error: {e}\n")

    # TEST 4: Múltiples órdenes rápidas
    print("4. Enviando rafaga de 3 ordenes distintas...")
    for i in range(700, 703):
        o = create_order(i)
        try:
            requests.post(URL, json=[o])
            print(f"   Pedido #{i} enviado.")
        except:
            print(f"   Fallo Pedido #{i}")
    
    print("\n--- SIMULACION FINALIZADA ---")
    print("Revisa la impresora (o los PDFs que se abrieron) y la consola de Python.")

if __name__ == "__main__":
    # Esperar un momento por si el servidor se está reiniciando
    time.sleep(1)
    run_simulation()
