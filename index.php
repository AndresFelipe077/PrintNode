<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PrintNode | Order Terminal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        #app {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #818cf8, #c084fc);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
        }

        textarea {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            color: var(--text);
            font-family: inherit;
            font-size: 16px;
            resize: none;
            height: 120px;
            transition: all 0.3s ease;
            outline: none;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        button {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button.btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 12px;
        }

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        button.btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 100px;
            font-size: 13px;
            margin-top: 32px;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.1);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 10px #4ade80;
        }

        .printer-info {
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
        }

        /* Toast styles */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 12px;
            background: #1e293b;
            color: white;
            font-size: 14px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
    </style>
</head>
<body>

<div id="app">
    <div class="header">
        <h1>PrintNode</h1>
        <p>Terminal de Impresión Profesional</p>
    </div>

    <form @submit.prevent="sendOrder">
        <div class="form-group">
            <label for="order">Mensaje de Prueba Rápida</label>
            <textarea 
                id="order" 
                v-model="order" 
                placeholder="Escribe algo aquí para imprimir directamente..." 
                required
                :disabled="loading"
            ></textarea>
        </div>
        
        <button type="submit" :disabled="loading">
            <span v-if="loading">Enviando...</span>
            <span v-else>Imprimir Texto</span>
            <svg v-if="!loading" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        </button>

        <button type="button" @click="testJson" class="btn-secondary" :disabled="loading">
            <span>Prueba Comanda Real (JSON)</span>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </button>
    </form>

    <center>
        <div class="status-badge" :style="{ color: printerStatus === 'online' ? '#4ade80' : '#f87171', background: printerStatus === 'online' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)', borderColor: printerStatus === 'online' ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.2)' }">
            <div class="status-dot" :style="{ background: printerStatus === 'online' ? '#4ade80' : '#f87171', boxShadow: printerStatus === 'online' ? '0 0 10px #4ade80' : '0 0 10px #f87171' }"></div>
            Servidor: {{ printerStatus === 'online' ? 'En Línea' : 'Desconectado' }}
        </div>
        <div v-if="printerName" class="printer-info">
            Impresora: <b>{{ printerName }}</b>
        </div>
    </center>
</div>

<script>
    new Vue({
        el: "#app",
        data: {
            order: '',
            loading: false,
            printerStatus: 'checking',
            printerName: '',
            localServerUrl: 'http://127.0.0.1:5000',
            pollingInterval: null
        },
        mounted() {
            this.checkPrinterStatus();
            // Start polling for new orders every 5 seconds
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
                    const response = await fetch(`${this.localServerUrl}/print`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order: orderData.content })
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
            async testJson() {
                this.loading = true;
                try {
                    const response = await fetch(`${this.localServerUrl}/test-json`);
                    const data = await response.json();
                    if (data.status === 'ok') {
                        alert("✅ " + data.message);
                    } else {
                        alert("❌ " + data.message);
                    }
                } catch (error) {
                    alert("❌ No se pudo conectar con el servidor de impresión. Asegúrate de que printer_server.py esté ejecutándose.");
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
                        headers: {
                            'Content-Type': 'application/json'
                        },
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