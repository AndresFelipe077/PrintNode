<?php
include("connect.php");
session_start();
$rol = $_SESSION['rol'] ?? '';

if (!in_array($rol, ['cajero', 'mesero', 'jefe', 'administrador'])) {
  header("Location: tableroadmin.php");
  exit;
}

$nombreMeseroSesion = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';

include 'navbar_tipo_ventas.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirmar Pedido</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "San Francisco", "Helvetica Neue", Helvetica, Arial, sans-serif;
      background-color: #fff;
    }

    input,
    textarea,
    select {
      transition: all 0.3s ease;
    }

    input:focus,
    textarea:focus,
    select:focus {
      outline: none;
      border-color: #007aff;
      box-shadow: 0 0 0 2px rgba(0, 122, 255, 0.2);
    }
  </style>
</head>

<body class="w-full pb-40">

  <!-- Pasar variable PHP a JavaScript -->
  <script>
    const nombreMeseroSesion = "<?php echo htmlspecialchars($nombreMeseroSesion, ENT_QUOTES, 'UTF-8'); ?>";
  </script>

  <h2 class="text-2xl font-semibold text-center uppercase mt-6 text-gray-900">Confirmación del pedido</h2>

  <div class="mx-auto w-full max-w-md mb-40">

    <!-- Vue app -->
    <div id="app" class="bg-white rounded-xl p-6">

      <div class="space-y-4 mb-4">

        <div class="flex gap-4">

          <div class="w-1/2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del cliente</label>
            <input v-model="nombre" type="text" placeholder="Nombre del cliente"
              class="border border-gray-300 rounded-md w-full p-2 bg-gray-200" />
          </div>

          <div class="w-1/2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
            <input
              v-model="telefono"
              type="tel"
              inputmode="numeric"
              @input="telefono = telefono.replace(/\D/g, '')"
              placeholder="Opcional"
              class="border border-gray-300 rounded-md w-full p-2 bg-gray-200" />
          </div>

        </div>

        <div v-if="esDomicilio">
          <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
          <input v-model="direccion" type="text"
            class="border border-gray-300 rounded-md w-full p-2 bg-gray-200" />
        </div>

        <div v-if="esDomicilio">
          <label class="block text-xs font-medium text-gray-600 mb-1">Barrio o zona</label>
          <select v-model="zonaDomicilio" class="border border-gray-300 rounded-md w-full p-2 bg-gray-200">
            <option value="" disabled selected>Selecciona una barrio</option>
            <option v-for="zona in zonasDomicilios" :key="zona.id_domicilio" :value="zona">
              {{ zona.zona }} - ${{ zona.monto }} <!-- Mostrar monto junto con la zona -->
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Zona/Mesa original</label>
          <input type="text" :value="zonaMesaOriginal" class="text-gray-400 border border-gray-200 rounded-md w-full p-2 bg-gray-200" readonly />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Mesero asignado</label>
          <input type="text" :value="mesero_pedido"
            class="text-gray-400 border border-gray-200 rounded-md w-full p-2 bg-gray-200 cursor-not-allowed" readonly />
        </div>
      </div>

      <!-- Formulario -->
      <form action="controlador_pedido_mesa.php" method="POST" @submit.prevent="enviarPedido">
        <input type="hidden" name="dato_estatus" :value="dato_estatus">
        <input type="hidden" name="slug" :value="slug">
        <input type="hidden" name="nombre" :value="nombre">
        <input type="hidden" name="telefono" :value="telefono">
        <input type="hidden" name="direccion" :value="direccion">
        <input type="hidden" name="valor_zona_domicilio" :value="zonaDomicilio">
        <input type="hidden" name="observaciones" :value="observaciones">
        <input type="hidden" name="ticket_total" :value="Math.round(totalGeneral())">
        <input type="hidden" name="tipo_venta" :value="tipoVenta">
        <input type="hidden" name="mesero_pedido" :value="mesero_pedido">

        <div class="bg-white border-t-2 pt-2 fixed bottom-0 left-0 w-full z-50">
          <div class="flex justify-center items-center w-full max-w-2xl mx-auto">
            <button
              type="submit"
              :disabled="envioEnProceso"
              class="mx-auto text-center select-none mt-4 mb-10 w-80 text-white font-bold py-3 rounded
         bg-green-600 hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed">
              <span v-if="envioEnProceso">Procesando...</span>
              <span v-else>Confirmar pedido</span>
            </button>

          </div>
        </div>
      </form>

      <div v-if="pedidoEnviado" class="text-center text-green-600 font-medium mt-6">
        ✅ Pedido enviado con éxito.
      </div>

      <!-- Overlay de carga -->
      <div v-if="envioEnProceso"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
          <i class="fas fa-spinner fa-spin text-3xl text-green-600 mb-3"></i>
          <p class="text-gray-800 font-semibold">Enviando pedido, por favor espera...</p>
        </div>
      </div>


    </div>
  </div>

  <script>
    new Vue({
      el: '#app',
      data: {
        id_carrito: null,
        nombre: '',
        telefono: '',
        direccion: '',
        observaciones: '',
        zonaMesaOriginal: '',
        carrito: [],
        mesas_seleccionadas: [],
        estatus: localStorage.getItem('estatus') || 'Nuevo pedido',
        slug: localStorage.getItem('slug') || 'sin etiqueta',
        pedidoEnviado: false,
        mesero_pedido: nombreMeseroSesion,
        meseroInvalido: false,
        envioEnProceso: false,
        zonaDomicilio: '',
        zonasDomicilios: [],
        hash_id: localStorage.getItem('hash_id') || '',
        clienteExiste: false,
        clienteOriginal: null
      },
      methods: {
        cargarZonasDomicilios() {
          // Llamar a un script para obtener las zonas de la base de datos
          fetch('obtener_zonas_domicilio.php')
            .then(response => response.json())
            .then(data => {
              this.zonasDomicilios = data;
            });
        },
        obtenerIdCarrito() {
          const params = new URLSearchParams(window.location.search);
          const id = params.get('id');
          this.id_carrito = id || null;
        },
        totalGeneral() {
          return this.carrito.reduce((sum, i) => sum + i.precio_raw * i.cantidad, 0);
        },
        cargarCarrito() {
          if (!this.id_carrito) return;
          const data = localStorage.getItem('carrito_' + this.id_carrito);
          this.carrito = data ? JSON.parse(data) : [];
        },
        cargarDatosUsuario() {
          this.nombre = ''; // NO tomar valor de localStorage
          this.telefono = ''; // NO tomar valor de localStorage
          this.observaciones = ''; // Si quieres que observaciones sí se mantengan, deja la línea como está

          const params = new URLSearchParams(window.location.search);
          const idParam = params.get('id'); // Ej: "Mesa_6_id_38"

          if (idParam && idParam.includes('_id_')) {
            const [zona, numero_mesa, _, id] = idParam.split('_');

            this.mesas_seleccionadas = [{
              id: id, // ID real de la tabla
              numero_mesa: numero_mesa, // número de mesa
              zona: (zona || '').toString().trim().toLowerCase()
            }];

            this.zonaMesaOriginal = `${zona}: ${numero_mesa}`;
            this.direccion = ''; // Permite que el usuario escriba la dirección personalizada
          } else {
            this.mesas_seleccionadas = [];
            this.direccion = '';
          }
        },
        enviarPedido() {

          if (this.envioEnProceso) return;

          // 🔎 Si el cliente existe y cambió algo
          if (this.clienteExiste && this.clienteOriginal) {
            if (
              this.nombre !== this.clienteOriginal.nombre ||
              this.direccion !== this.clienteOriginal.direccion
            ) {
              const actualizar = confirm("Los datos del cliente cambiaron. ¿Deseas actualizar el registro?");
              if (!actualizar) {
                return;
              }
            }
          }

          this.envioEnProceso = true;

          let zonaDomicilioJson = '';

          if (this.zonaDomicilio && this.zonaDomicilio.zona) {
            zonaDomicilioJson = JSON.stringify({
              zona: this.zonaDomicilio.zona,
              monto: this.zonaDomicilio.monto
            });
          }

          this.$nextTick(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'controlador_pedido_mesa.php';

            const campos = {
              nombre: this.nombre,
              telefono: this.telefono,
              direccion: this.direccion,
              observaciones: this.observaciones,
              pedido_json: JSON.stringify(this.carrito),
              mesas_seleccionadas: JSON.stringify(this.mesas_seleccionadas),
              estatus: this.estatus,
              slug: this.slug,
              mesero_pedido: this.mesero_pedido,
              ticket_total: Math.round(this.totalGeneral()),
              tipo_venta: this.tipoVenta,
              valor_zona_domicilio: zonaDomicilioJson,
              hash_id: this.hash_id
            };

            for (const key in campos) {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = key;
              input.value = campos[key];
              form.appendChild(input);
            }

            document.body.appendChild(form);

            // Limpiar datos del localStorage
            localStorage.removeItem('nombre');
            localStorage.removeItem('telefono');
            localStorage.removeItem('observaciones');
            localStorage.removeItem('mesas_seleccionadas');
            if (this.id_carrito) {
              localStorage.removeItem('carrito_' + this.id_carrito);
            }

            this.observaciones = '';
            this.carrito = [];
            this.pedidoEnviado = true;

            form.submit();
          });
        }

      },
      watch: {
        nombre(val) {
          if (val) {
            this.nombre = val.toLowerCase(); // Convierte a minúsculas
          }
          localStorage.setItem('nombre', this.nombre);
        },
        telefono(val) {
          localStorage.setItem('telefono', val);

          if (val.length < 6) return; // evita consultas innecesarias

          fetch('buscar_cliente.php?telefono=' + val)
            .then(res => res.json())
            .then(data => {
              if (data.existe) {
                this.clienteExiste = true;
                this.nombre = data.cliente.nombre.toLowerCase(); // Minúscula
                this.direccion = data.cliente.direccion.toLowerCase(); // Minúscula

                this.clienteOriginal = {
                  nombre: data.cliente.nombre.toLowerCase(),
                  direccion: data.cliente.direccion.toLowerCase()
                };
              } else {
                this.clienteExiste = false;
                this.clienteOriginal = null;
              }
            });
        },
        direccion(val) {
          if (val) {
            this.direccion = val.toLowerCase(); // Convierte a minúsculas
          }
        },
        observaciones(val) {
          localStorage.setItem('observaciones', val);
        }
      },
      mounted() {
        this.cargarZonasDomicilios();
        this.obtenerIdCarrito();
        localStorage.setItem('dato_estatus', 'Nuevo pedido');
        localStorage.setItem('local_navegacion_volver', 'tableroadmin.php');

        if (!localStorage.getItem('slug')) {
          localStorage.setItem('slug', 'sin etiqueta');
        }

        this.cargarCarrito();
        this.cargarDatosUsuario();
      },
      computed: {
        tipoVenta() {
          if (this.mesas_seleccionadas.length > 0) {
            return this.mesas_seleccionadas[0].zona || 'ventas en mesa';
          }
          return 'ventas en mesa';
        },
        esDomicilio() {
          if (this.mesas_seleccionadas.length > 0) {
            const zona = (this.mesas_seleccionadas[0].zona || '').toString().trim().toLowerCase();
            return ['domicilio', 'domicilios', 'rapida'].includes(zona);
          }
          return false;
        }
      }

    });
  </script>

</body>

</html>