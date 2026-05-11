<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PrintNode | Order Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 md:p-8 flex items-center justify-center bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-900/40 via-slate-900 to-slate-900">

<div id="app" class="w-full max-w-md bg-slate-800/80 backdrop-blur-xl border border-slate-700 rounded-3xl p-6 md:p-8 shadow-2xl transition-all duration-300">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-400 to-fuchsia-400 bg-clip-text text-transparent">PrintNode</h1>
        <p class="text-slate-400 text-sm mt-1">Terminal de Impresión Profesional</p>
    </div>

    <!-- TABS -->
    <div class="flex gap-2 mb-6 bg-slate-900/50 p-1 rounded-xl">
        <button @click="activeTab = 'texto'" 
                :class="activeTab === 'texto' ? 'bg-indigo-500 shadow-lg text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'" 
                class="flex-1 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
            Texto Rápido
        </button>
        <button @click="activeTab = 'manual'" 
                :class="activeTab === 'manual' ? 'bg-indigo-500 shadow-lg text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'" 
                class="flex-1 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
            Comanda Manual
        </button>
    </div>

    <!-- TEXT FORM -->
    <form v-if="activeTab === 'texto'" @submit.prevent="sendOrder" class="space-y-4 animate-[fadeIn_0.3s_ease-out]">
        <div>
            <label class="block text-xs font-medium text-slate-400 mb-2">Mensaje Directo</label>
            <textarea v-model="order" 
                      placeholder="Escribe algo aquí para imprimir..." 
                      class="w-full bg-slate-900/50 border border-slate-700 rounded-xl p-4 text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-32 resize-none" 
                      required :disabled="loading"></textarea>
        </div>
        
        <button type="submit" :disabled="loading" 
                class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-3.5 rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center gap-2">
            <span v-if="loading">Enviando...</span>
            <template v-else>
                <span>Imprimir Texto</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            </template>
        </button>
    </form>

    <!-- MANUAL FORM -->
    <form v-if="activeTab === 'manual'" @submit.prevent="sendCustomComanda" class="space-y-4 animate-[fadeIn_0.3s_ease-out]">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Cliente</label>
                <input v-model="customComanda.nombre" type="text" placeholder="Ej: Juan" 
                       class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Teléfono</label>
                <input v-model="customComanda.telefono" type="tel" placeholder="Opcional" 
                       class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Dirección</label>
            <input v-model="customComanda.direccion" type="text" placeholder="Opcional" 
                   class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
        </div>
        
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Zona / Mesa</label>
                <input v-model="customComanda.zona" type="text" placeholder="Ej: Domicilio" 
                       class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Mesero</label>
                <input v-model="customComanda.mesero" type="text" placeholder="Ej: Caja" 
                       class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Productos (uno por línea)</label>
            <textarea v-model="customComanda.productosText" 
                      placeholder="1x Hamburguesa Especial&#10;2x Gaseosa" 
                      class="w-full bg-slate-900/50 border border-slate-700 rounded-xl p-4 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-28 resize-none" 
                      required></textarea>
        </div>

        <button type="submit" :disabled="loading" 
                class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-3.5 rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 mt-2">
            <span v-if="loading">Enviando...</span>
            <template v-else>
                <span>Imprimir Comanda</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            </template>
        </button>
    </form>

    <!-- STATUS FOOTER -->
    <div class="mt-8 pt-6 border-t border-slate-700/50 flex flex-col items-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium border"
             :class="printerStatus === 'online' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'">
            <div class="w-2 h-2 rounded-full" 
                 :class="printerStatus === 'online' ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' : 'bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.8)]'"></div>
            Servidor: {{ printerStatus === 'online' ? 'En Línea' : 'Desconectado' }}
        </div>
        <div v-if="printerName" class="text-xs text-slate-500 mt-3 font-medium">
            Impresora: <span class="text-slate-300">{{ printerName }}</span>
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
            this.pollingInterval = setInterval(this.pollOrders, 5000);
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