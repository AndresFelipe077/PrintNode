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

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
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
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            margin-top: 20px;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 8px #4ade80;
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
        <p>Sending orders directly to the kitchen</p>
    </div>

    <form @submit.prevent="sendOrder">
        <div class="form-group">
            <label for="order">Order Details</label>
            <textarea 
                id="order" 
                v-model="order" 
                placeholder="Ex: 2 Pepperoni Pizzas, 1 Coke..." 
                required
                :disabled="loading"
            ></textarea>
        </div>
        
        <button type="submit" :disabled="loading">
            <span v-if="loading">Sending...</span>
            <span v-else>Print Ticket</span>
            <svg v-if="!loading" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        </button>
    </form>

    <div class="status-badge" :style="{ color: printerStatus === 'online' ? '#4ade80' : '#f87171', background: printerStatus === 'online' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)' }">
        <div class="status-dot" :style="{ background: printerStatus === 'online' ? '#4ade80' : '#f87171', boxShadow: printerStatus === 'online' ? '0 0 8px #4ade80' : '0 0 8px #f87171' }"></div>
        Printer: {{ printerStatus === 'online' ? 'Online' : 'Offline / Local Server Not Found' }}
    </div>
</div>

<script>
    new Vue({
        el: "#app",
        data: {
            order: '',
            loading: false,
            printerStatus: 'checking',
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
                        this.printerStatus = 'online';
                    } else {
                        this.printerStatus = 'offline';
                    }
                } catch (error) {
                    this.printerStatus = 'offline';
                }
            },
            async pollOrders() {
                // Only poll if the printer is online to avoid unnecessary errors
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
                        // If we are on the printer machine, we might want to trigger poll immediately
                        this.pollOrders();
                        alert("✅ Order queued for printing!");
                    } else {
                        alert("❌ Error: " + data.message);
                    }
                } catch (error) {
                    alert("❌ Server connection error");
                } finally {
                    this.loading = false;
                }
            }
        }
    });
</script>

</body>
</html>