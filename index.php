<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PrintNode | Order Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .shadcn-card { background-color: #09090b; border: 1px solid #27272a; }
        .shadcn-input { background-color: transparent; border: 1px solid #27272a; transition: all 0.2s; }
        .shadcn-input:focus { border-color: #3f3f46; ring: 2px solid #3f3f46; }
    </style>
</head>
<body class="bg-[#020202] text-slate-50 min-h-screen p-4 md:p-8 flex items-center justify-center">

<div id="app" class="w-full max-w-md shadcn-card rounded-xl p-6 md:p-8 shadow-2xl">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight">PrintNode</h1>
        <p class="text-slate-400 text-sm">Terminal de impresión térmica directa.</p>
    </div>

    <!-- TABS (Shadcn style) -->
    <div class="flex p-1 mb-8 bg-[#18181b] rounded-lg">
        <button @click="activeTab = 'texto'" 
                :class="activeTab === 'texto' ? 'bg-[#09090b] text-slate-50 shadow-sm' : 'text-slate-400 hover:text-slate-200'" 
                class="flex-1 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
            Texto
        </button>
        <button @click="activeTab = 'manual'" 
                :class="activeTab === 'manual' ? 'bg-[#09090b] text-slate-50 shadow-sm' : 'text-slate-400 hover:text-slate-200'" 
                class="flex-1 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
            Manual
        </button>
    </div>

    <!-- TEXT FORM -->
    <form v-if="activeTab === 'texto'" @submit.prevent="sendOrder" class="space-y-6">
        <div class="space-y-2">
            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Mensaje rápido</label>
            <textarea v-model="order" 
                      placeholder="Contenido del ticket..." 
                      class="flex min-h-[120px] w-full rounded-md shadcn-input px-3 py-2 text-sm placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 disabled:cursor-not-allowed disabled:opacity-50 resize-none" 
                      required :disabled="loading"></textarea>
        </div>
        
        <button type="submit" :disabled="loading" 
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-slate-50 text-slate-900 hover:bg-slate-50/90 h-10 px-4 py-2 w-full">
            <span v-if="loading">Procesando...</span>
            <template v-else>
                Imprimir ticket
            </template>
        </button>
    </form>

    <!-- MANUAL FORM -->
    <form v-if="activeTab === 'manual'" @submit.prevent="sendCustomComanda" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Cliente</label>
                <input v-model="customComanda.nombre" type="text" placeholder="Nombre" 
                       class="flex h-10 w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400" required>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Teléfono</label>
                <input v-model="customComanda.telefono" type="tel" placeholder="314..." 
                       class="flex h-10 w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
            </div>
        </div>
        
        <div class="space-y-2">
            <label class="text-sm font-medium leading-none">Dirección</label>
            <input v-model="customComanda.direccion" type="text" placeholder="Calle/Carrera..." 
                   class="flex h-10 w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Zona / Mesa</label>
                <input v-model="customComanda.zona" type="text" 
                       class="flex h-10 w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Atiende</label>
                <input v-model="customComanda.mesero" type="text" 
                       class="flex h-10 w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
            </div>
        </div>
        
        <div class="space-y-2">
            <label class="text-sm font-medium leading-none">Productos</label>
            <textarea v-model="customComanda.productosText" 
                      placeholder="1x Hamburguesa..." 
                      class="flex min-h-[80px] w-full rounded-md shadcn-input px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400" 
                      required></textarea>
        </div>

        <button type="submit" :disabled="loading" 
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-slate-50 text-slate-900 hover:bg-slate-50/90 h-10 px-4 py-2 w-full mt-2">
            <span v-if="loading">Generando...</span>
            <span v-else>Imprimir comanda</span>
        </button>
    </form>

    <!-- STATUS FOOTER -->
    <div class="mt-8 pt-6 border-t border-slate-800 flex flex-col items-center">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full" 
                 :class="printerStatus === 'online' ? 'bg-emerald-500' : 'bg-red-500'"></div>
            <span class="text-xs font-medium"
                  :class="printerStatus === 'online' ? 'text-emerald-500' : 'text-red-500'">
                {{ printerStatus === 'online' ? 'Servidor conectado' : 'Servidor desconectado' }}
            </span>
        </div>
        <div v-if="printerName" class="text-[10px] text-slate-500 mt-2">
            {{ printerName }}
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    new Vue({
        el: "#app",
        data: {
            activeTab: 'texto',
            order: '',
            loading: false,
            printerStatus: 'checking',
            printerName: '',
            localServerUrl: 'http://127.0.0.1:5000',
            pollingInterval: null,
            customComanda: {
                nombre: '',
                telefono: '',
                direccion: '',
                zona: 'Domicilio',
                mesero: 'Caja',
                productosText: ''
            }
        },
        mounted() {
            this.checkPrinterStatus();
            this.pollingInterval = setInterval(this.pollOrders, 1000);
        },
        beforeDestroy() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
        },
        methods: {
            async checkPrinterStatus() {
                try {
                    const response = await fetch(`${this.localServerUrl}/status`);
                    if (response.ok) {
                        const data = await response.json();
                        this.printerStatus = 'online';
                        this.printerName = data.printer;
                    } else {
                        this.printerStatus = 'offline';
                    }
                } catch (error) {
                    this.printerStatus = 'offline';
                }
            },
            async pollOrders() {
                if (this.printerStatus !== 'online') {
                    await this.checkPrinterStatus();
                    return;
                }

                try {
                    const response = await fetch('api.php');
                    const data = await response.json();

                    if (data.status === 'success' && data.orders.length > 0) {
                        for (const order of data.orders) {
                            await this.printLocally(order);
                        }
                    }
                } catch (error) {
                    console.error("Polling error:", error);
                }
            },
            async printLocally(orderData) {
                try {
                    const payload = Array.isArray(orderData.content) ? orderData.content : { order: orderData.content };
                    const response = await fetch(`${this.localServerUrl}/print`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    
                    if (response.ok) {
                        console.log(`Order ${orderData.id} printed successfully`);
                    } else {
                        console.error(`Failed to print order ${orderData.id}`);
                    }
                } catch (error) {
                    console.error("Local print error:", error);
                }
            },
            async sendCustomComanda() {
                this.loading = true;
                try {
                    const lines = this.customComanda.productosText.split('\n').filter(l => l.trim() !== '');
                    const productos = lines.map(line => {
                        const match = line.match(/^(\d+)[xX]\s+(.+)/);
                        if (match) {
                            return { cantidad: parseInt(match[1], 10), nombre_producto: match[2].trim(), precio_raw: 0 };
                        } else {
                            return { cantidad: 1, nombre_producto: line.trim(), precio_raw: 0 };
                        }
                    });

                    const d = new Date();
                    const pad = n => n.toString().padStart(2, '0');
                    const fecha = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                    
                    const comanda = {
                        id: 'MANUAL_' + Math.floor(Math.random() * 1000000),
                        id_pedido: Math.floor(Math.random() * 1000),
                        tipo_documento: 'comanda_automatica',
                        nombre_cliente: this.customComanda.nombre,
                        telefono: this.customComanda.telefono,
                        direccion: this.customComanda.direccion,
                        mesero: this.customComanda.mesero,
                        fecha: fecha,
                        fecha_creacion_documento: fecha,
                        mesas: [{zona: this.customComanda.zona, numero_mesa: "1"}],
                        area_preparacion: "general",
                        productos: productos
                    };

                    const response = await fetch('api.php?action=enqueue_custom_comanda', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(comanda)
                    });
                    
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert("✅ Comanda encolada. Se imprimirá en breve.");
                        this.customComanda.productosText = '';
                    } else {
                        alert("❌ Error: " + data.message);
                    }
                } catch (error) {
                    alert("❌ Error de conexión: " + error.message);
                } finally {
                    this.loading = false;
                }
            },
            async sendOrder() {
                if (!this.order.trim()) return;
                
                this.loading = true;
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order: this.order })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.order = '';
                        this.pollOrders();
                        alert("✅ ¡Pedido en cola de impresión!");
                    } else {
                        alert("❌ Error: " + data.message);
                    }
                } catch (error) {
                    alert("❌ Error de conexión con el servidor PHP");
                } finally {
                    this.loading = false;
                }
            }
        }
    });
</script>

</body>
</html>