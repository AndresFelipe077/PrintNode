import requests
import json
import time
import os
import win32print
import win32ui
import win32con
from datetime import datetime
from collections import defaultdict

CONFIG_FILE      = "configuracion.json"
ARCHIVO_COMANDAS = "historial_impresion.json"
CHECK_INTERVAL   = 5

# =========================
# UTILIDADES
# =========================


def obtener_fecha_actual():
    return datetime.now().strftime("%Y-%m-%d")


# =========================
# CONFIGURACIÓN
# =========================


def cargar_configuracion():
    if os.path.exists(CONFIG_FILE):
        with open(CONFIG_FILE, "r") as f:
            config = json.load(f)

        if config.get("fecha") != obtener_fecha_actual():
            if os.path.exists(ARCHIVO_COMANDAS):
                os.remove(ARCHIVO_COMANDAS)
            config["fecha"] = obtener_fecha_actual()
            with open(CONFIG_FILE, "w") as fw:
                json.dump(config, fw, indent=4)

        return config

    nombre_pagina = input("Nombre de la página: ").strip().lower().replace(" ", "")
    impresoras_por_area = {}

    totalizar_comanda = (
        input("¿Totalizar valor en comanda? (si/no): ").strip().lower().replace(" ", "")
        == "si"
    )
    tamano_letra = int(
        input("Tamaño de letra para productos/observaciones (ej: 34): ").strip() or "34"
    )
    copias_comanda = int(
        input("¿Cuántas comandas imprimir por pedido?: ").strip() or "1"
    )

    impresoras = win32print.EnumPrinters(
        win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
    )
    lista = [i[2] for i in impresoras]

    print("\n=== IMPRESORAS DISPONIBLES ===")
    for i, n in enumerate(lista, 1):
        print(f"{i}. {n}")

    print("\n=== IMPRESORA FACTURAS ===")
    idx = int(input("Indica el número de impresora para facturas y prefacturas: "))
    impresoras_por_area["facturas"] = lista[idx - 1]

    print("\n=== IMPRESORAS POR ÁREA ===")
    print("Ej: cocina, bar, general, todas")
    while True:
        area = input("Área (vacío para terminar): ").strip().lower().replace(" ", "")
        if not area:
            break
        idx = int(input("Número de impresora: "))
        impresoras_por_area[area] = lista[idx - 1]

    config = {
        "nombre_pagina": nombre_pagina,
        "impresoras_por_area": impresoras_por_area,
        "totalizar_comanda": totalizar_comanda,
        "tamano_letra": tamano_letra,
        "copias_comanda": copias_comanda,
        "fecha": obtener_fecha_actual(),
    }

    with open(CONFIG_FILE, "w") as f:
        json.dump(config, f, indent=4)

    return config


# =========================
# HISTORIAL
# =========================


def cargar_historial():
    if not os.path.exists(ARCHIVO_COMANDAS):
        return set()

    try:
        with open(ARCHIVO_COMANDAS, "r") as f:
            contenido = f.read().strip()

            # Archivo vacío o en blanco
            if not contenido:
                return set()

            data = json.loads(contenido)

            # Si no es lista, ignorar
            if not isinstance(data, list):
                return set()

            return set(data)

    except (json.JSONDecodeError, IOError):
        # Archivo corrupto o inválido → no romper el sistema
        return set()


def guardar_historial(historial):
    with open(ARCHIVO_COMANDAS, "w") as f:
        json.dump(list(historial or []), f)


# =========================
# DESCARGA SEGURA
# =========================


def obtener_json(url, espera=10):
    while True:
        try:
            r = requests.get(url, timeout=10)

            if r.status_code == 404:
                # Archivo aún no creado, no es un error
                return []

            if r.status_code != 200:
                print(f"Servidor respondió {r.status_code}. Reintentando...")
                time.sleep(espera)
                continue

            # Si la respuesta está vacía
            if not r.text.strip():
                return []

            try:
                data = r.json()
            except json.JSONDecodeError:
                print("JSON vacío o inválido, ignorando...")
                return []

            # Si no es lista, devolver lista vacía
            if not isinstance(data, list):
                return []

            return data

        except requests.exceptions.RequestException as e:
            print(f"Error de conexión: {e}")

        time.sleep(espera)


# =========================
# FORMATEO
# =========================


def formatear_comanda(comandas, totalizar=False):
    base = comandas[0]
    mesas = base.get("mesas", [])

    texto = "\n"
    texto += f"PEDIDO #{base['id_pedido']}\n"

    if mesas:
        mesa = mesas[0]
        texto += f"{mesa['zona']} {mesa['numero_mesa']}\n"
    else:
        texto += "PEDIDO SIN MESA\n"

    texto += f"MESERO: {base.get('mesero','')}\n"
    texto += f"AREA: {base.get('area_preparacion','general').upper()}\n"
    texto += "-" * 24 + "\n"

    if base.get("nombre_cliente"):
        texto += f"CLIENTE: {base['nombre_cliente']}\n"

    telefono = base.get("telefono", "").strip()
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


def formatear_comanda_remota(doc, totalizar=False):
    datos = doc["datos"]
    pedido = datos["pedido"]
    productos = datos.get("productos", [])
    mesas = datos.get("mesas", [])

    texto = "\n"
    texto += f"PEDIDO #{pedido.get('id','')}\n"

    if mesas:
        m = mesas[0]
        texto += f"{m.get('zona','')} {m.get('numero_mesa','')}\n"

    texto += f"MESERO: {pedido.get('mesero_pedido','')}\n"

    nombre_cliente = pedido.get("nombre", "").strip()
    if nombre_cliente:
        texto += f"CLIENTE: {nombre_cliente}\n"

    direccion = pedido.get("direccion", "").strip()
    if direccion:
        texto += f"DIRECCION: {direccion}\n"

    area = (
        productos[0].get("area_preparacion", "general").upper()
        if productos
        else "GENERAL"
    )
    texto += f"AREA: {area}\n"
    texto += "-" * 24 + "\n"

    for p in productos:
        texto += f"{p['cantidad']}x {p['nombre_producto']}\n"
        if p.get("observacion"):
            for linea in p["observacion"].split("\n"):
                texto += f"  {linea}\n"

    texto += "-" * 24 + "\n"

    if totalizar:
        total = 0
        for p in productos:
            total += p["cantidad"] * p.get("precio_raw", 0)
        texto += f"TOTAL: {int(total)}\n"
        texto += "-" * 24 + "\n"

    texto += pedido.get("fecha_pedido", "") + "\n\n"

    return texto


def linea_total(label, valor, ancho=32):
    valor_str = f"{valor:,.0f}".replace(",", ".")
    espacio = ancho - len(label) - len(valor_str)
    if espacio < 1:
        espacio = 1
    return f"{label}{' ' * espacio}{valor_str}\n"


def formatear_factura(doc):
    datos = doc["datos"]
    emp = datos["empresa"]
    ped = datos["pedido"]
    prods = datos["productos"]
    tot = datos["totales"]
    mesas = datos.get("mesas", [])

    texto = "\n"

    # =========================
    # ENCABEZADO EMPRESA
    # =========================
    texto += emp.get("nombre_establecimiento", "").upper() + "\n"
    texto += emp.get("razon_social", "") + "\n"
    texto += emp.get("regimen_fiscal", "") + "\n"
    texto += emp.get("telefono", "") + "\n"
    texto += emp.get("direccion", "") + "\n"
    texto += "-" * 32 + "\n"

    # =========================
    # DATOS DEL PEDIDO
    # =========================
    texto += f"FECHA: {ped.get('fecha_pedido','')}\n"
    texto += f"PEDIDO: #{ped.get('id','')}\n"

    if mesas:
        m = mesas[0]
        texto += f"MESA: {m.get('zona','')} #{m.get('numero_mesa','')}\n"

    texto += f"MESERO: {ped.get('mesero_pedido','')}\n"
    texto += f"FACTURA: {ped.get('estatus_orden','').upper()}\n"
    texto += "-" * 32 + "\n"

    # =========================
    # CABECERA PRODUCTOS
    # =========================
    texto += "CANT  PRODUCTO            VALOR\n"
    texto += "-" * 32 + "\n"

    # =========================
    # PRODUCTOS
    # =========================
    for p in prods:
        nombre = p["nombre_producto"][:18]
        total_linea = p["cantidad"] * p["precio_raw"]
        texto += f"{p['cantidad']:>3}  {nombre:<18} {total_linea:>6}\n"

    texto += "-" * 32 + "\n"

    # =========================
    # TOTALES
    # =========================
    texto += linea_total("SUBTOTAL", tot.get("subtotal", 0))
    texto += linea_total("DESCUENTOS", tot.get("descuentos", 0))

    propina = tot.get("propina", 0)
    domicilio = tot.get("domicilio", 0)

    if propina > 0:
        texto += linea_total("PROPINA", propina)

    if domicilio > 0:
        texto += linea_total("DOMICILIO", domicilio)

    texto += "-" * 32 + "\n"
    texto += linea_total("TOTAL", tot.get("total", 0))
    texto += "\n"

    return texto


def formatear_prefactura(doc):
    datos = doc["datos"]
    emp = datos["empresa"]
    ped = datos["pedido"]
    prods = datos["productos"]
    tot = datos["totales"]
    mesas = datos.get("mesas", [])

    texto = "\n"
    texto += "*** PREFACTURA ***\n"
    texto += emp.get("nombre_establecimiento", "").upper() + "\n"
    texto += emp.get("direccion", "") + "\n"
    texto += "-" * 32 + "\n"

    texto += f"FECHA: {ped.get('fecha_pedido','')}\n"
    texto += f"PEDIDO: #{ped.get('id','')}\n"

    if mesas:
        m = mesas[0]
        texto += f"MESA: {m.get('zona','')} #{m.get('numero_mesa','')}\n"

    texto += f"MESERO: {ped.get('mesero_pedido','')}\n"
    texto += "-" * 32 + "\n"

    for p in prods:
        nombre = p["nombre_producto"][:18]
        total_linea = p["cantidad"] * p["precio_raw"]
        texto += f"{p['cantidad']:>3}  {nombre:<18} {total_linea:>6}\n"

    texto += "-" * 32 + "\n"

    texto += linea_total("SUBTOTAL", tot.get("subtotal", 0))
    texto += linea_total("DESCUENTOS", tot.get("descuentos", 0))

    if tot.get("valor_propina", 0) > 0:
        texto += linea_total(
            f"PROPINA ({tot.get('porcentaje_propina',0)}%)", tot.get("valor_propina", 0)
        )

    texto += "-" * 32 + "\n"
    texto += linea_total("TOTAL", tot.get("total_con_propina", tot.get("subtotal", 0)))
    texto += "\nNO VALIDO COMO FACTURA\n\n"

    return texto


# =========================
# IMPRESIÓN SEGURA
# =========================


def imprimir_thermal(texto, printer_name, tamano_letra):
    try:
        hDC = win32ui.CreateDC()
        hDC.CreatePrinterDC(printer_name)

        hDC.StartDoc("Documento")
        hDC.StartPage()

        font_producto = win32ui.CreateFont(
            {
                "name": "Lucida Console",
                "height": -tamano_letra,  # 👈 DINÁMICO
                "weight": 700,
            }
        )

        font_obs = win32ui.CreateFont(
            {
                "name": "Consolas",
                "height": -tamano_letra,  # 👈 MISMO TAMAÑO
                "weight": 400,
            }
        )

        # Fuente base para cálculos
        hDC.SelectObject(font_obs)

        width = hDC.GetDeviceCaps(win32con.HORZRES)
        char_width = hDC.GetTextExtent("A")[0]
        max_chars = width // char_width

        def wrap(line):
            words = line.split()
            out = []
            current = ""

            for w in words:
                prueba = f"{current} {w}".strip()
                ancho = hDC.GetTextExtent(prueba)[0]

                if ancho <= width - 100:  # <- 100 px de margen de seguridad
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

            elif line.strip()[:1].isdigit() and "X" in line:
                hDC.SelectObject(font_producto)

            else:
                hDC.SelectObject(font_obs)

            # 🔥 altura automática real
            text_height = hDC.GetTextExtent("Ay")[1]
            line_height = text_height + 6

            for wline in wrap(line):
                size = hDC.GetTextExtent(wline)
                x = 20
                hDC.TextOut(x, y, wline)
                y += line_height

        hDC.EndPage()
        hDC.EndDoc()
        hDC.DeleteDC()

    except Exception as e:
        print(f"ERROR DE IMPRESIÓN ({printer_name}): {e}")


# =========================
# MAIN
# =========================


def main():
    config = cargar_configuracion()
    impresoras = config["impresoras_por_area"]
    nombre = config["nombre_pagina"]
    totalizar = config.get("totalizar_comanda", False)
    tamano_letra = config.get("tamano_letra", 34)
    copias_comanda = config.get("copias_comanda", 1)

    URL_COMANDAS = f"https://{nombre}.linkmetric.click/server_comandas.json"
    URL_FACTURAS = f"https://{nombre}.linkmetric.click/server_documentos_remotos.json"

    historial = cargar_historial()
    print("Sistema de impresión activo...")

    while True:
        documentos = []
        documentos += obtener_json(URL_COMANDAS)
        documentos += obtener_json(URL_FACTURAS)

        from datetime import datetime, timedelta

        nuevos = []
        ahora = datetime.now()

        for d in documentos:
            doc_id = str(d.get("id") or d.get("id_unico"))

            # Ya impreso
            if doc_id in historial:
                continue

            # ❌ Si no tiene fecha_creacion_documento, saltar
            fecha_str = d.get("fecha_creacion_documento")
            if not fecha_str:
                continue

            # ❌ Convertir a datetime y saltar si es mayor a 2 horas
            try:
                fecha_doc = datetime.strptime(fecha_str, "%Y-%m-%d %H:%M:%S")
                if ahora - fecha_doc > timedelta(hours=2):
                    continue
            except Exception:
                continue  # si no puede parsear, ignorar

            # ✅ Si pasó los filtros, agregar
            nuevos.append(d)

        grupos = defaultdict(list)

        for d in nuevos:
            tipo = d.get("tipo_documento", "")
            pid = d.get("id_pedido")

            if tipo in ("factura_remota", "prefactura_remota"):
                grupos[(pid, "facturas")].append(d)

            elif tipo == "comanda_remota":

                productos = d.get("datos", {}).get("productos", [])

                for p in productos:
                    area = str(p.get("area_preparacion", "general")).lower()

                    # ❌ Si el área NO está configurada y NO existe "todas", NO imprimir
                    if area not in impresoras and "todas" not in impresoras:
                        continue

                    # ✅ Si existe "todas", imprimir en todas las áreas configuradas (menos facturas)
                    if "todas" in impresoras:
                        for a in impresoras:
                            if a != "facturas":
                                grupos[(pid, a)].append(
                                    {**d, "datos": {**d["datos"], "productos": [p]}}
                                )
                    else:
                        grupos[(pid, area)].append(
                            {**d, "datos": {**d["datos"], "productos": [p]}}
                        )

            else:
                area = str(d.get("area_preparacion", "")).lower()

                # ❌ Si el área NO está configurada y NO existe "todas", NO imprimir
                if area not in impresoras and "todas" not in impresoras:
                    continue

                # ✅ Si existe "todas", imprimir en todas las áreas configuradas (menos facturas)
                if "todas" in impresoras:
                    for a in impresoras:
                        if a != "facturas":
                            grupos[(pid, a)].append(d)
                else:
                    grupos[(pid, area)].append(d)

        for (pid, area), docs in grupos.items():
            tipo = docs[0].get("tipo_documento", "")
            if tipo == "factura_remota":
                texto = formatear_factura(docs[0])

            elif tipo == "prefactura_remota":
                texto = formatear_prefactura(docs[0])

            elif tipo == "comanda_remota":
                texto = formatear_comanda_remota(
                    {
                        **docs[0],
                        "datos": {
                            **docs[0]["datos"],
                            "productos": [
                                p
                                for d in docs
                                for p in d.get("datos", {}).get("productos", [])
                            ],
                        },
                    },
                    totalizar,
                )

            else:
                texto = formatear_comanda(docs, totalizar)

            if tipo == "comanda_remota":
                printer = impresoras.get("general") or win32print.GetDefaultPrinter()
            else:
                printer = impresoras.get(area)

            if not printer:
                continue

            veces = copias_comanda if tipo == "comanda_automatica" else 1

            for _ in range(veces):
                imprimir_thermal(texto, printer, tamano_letra)

            print(f"IMPRESO {tipo.upper()} PEDIDO {pid} → {printer}")
            time.sleep(3)

            for d in docs:
                historial.add(str(d.get("id") or d.get("id_unico")))

        guardar_historial(historial)
        time.sleep(CHECK_INTERVAL)

if __name__ == "__main__":
    main()