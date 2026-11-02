<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 — Server Unavailable | MerchenZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('merchenz_theme') || 'dark';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <style>
        :root, [data-theme="dark"] {
            --bg-primary: #09090c;
            --bg-secondary: #121217;
            --bg-card: #15151e;
            --bg-card-hover: #1c1c28;
            --accent-red: #ff334b;
            --accent-red-hover: #ff4d61;
            --text-primary: #f5f5f7;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --border-subtle: rgba(255, 51, 75, 0.15);
            --border-accent: rgba(255, 51, 75, 0.45);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.7);
            --radius: 16px;
            --radius-sm: 10px;
            --toggle-bg: #121217;
            --toggle-btn-active: #e61e38;
            --pulse-color: rgba(255, 51, 75, 0.25);
        }

        [data-theme="light"], [data-theme="white"] {
            --bg-primary: #fcfcfd;
            --bg-secondary: #f6f6f9;
            --bg-card: #ffffff;
            --bg-card-hover: #fff5f5;
            --accent-red: #dc2626;
            --accent-red-hover: #b91c1c;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #6b7280;
            --border-subtle: #fee2e2;
            --border-accent: rgba(220, 38, 38, 0.4);
            --shadow-card: 0 4px 20px rgba(220, 38, 38, 0.08);
            --radius: 16px;
            --radius-sm: 10px;
            --toggle-bg: #f3f4f6;
            --toggle-btn-active: #dc2626;
            --pulse-color: rgba(220, 38, 38, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 25%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--pulse-color) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .top-nav {
            width: 100%;
            max-width: 900px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent-red), #990012);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }

        .brand-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        /* Theme Switcher: Dark and White only */
        .theme-switcher {
            display: inline-flex;
            background: var(--toggle-bg);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 3px;
            gap: 2px;
        }

        .theme-btn {
            border: none;
            background: transparent;
            color: var(--text-muted);
            padding: 5px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-btn.active {
            background: var(--toggle-btn-active);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);
        }

        /* Card */
        .error-card {
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius);
            padding: 44px 36px;
            max-width: 620px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-card);
            z-index: 10;
            margin: 40px auto;
        }

        /* Pulsing Dot Status */
        .pulse-container {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 51, 75, 0.12);
            border: 1px solid var(--border-subtle);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-red);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background: var(--accent-red);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-red);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 51, 75, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(255, 51, 75, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 51, 75, 0); }
        }

        .error-code {
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--accent-red), #ff758c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .error-message {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        /* Status Diagnostics Grid */
        .diagnostics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 24px;
        }

        .diag-item {
            text-align: center;
        }

        .diag-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .diag-val {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent-red);
        }

        /* Countdown Notice */
        .reconnect-notice {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .reconnect-notice strong {
            color: var(--text-primary);
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent-red), #b91c1c);
            color: white;
            padding: 10px 22px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(220, 38, 38, 0.5);
        }

        .cli-tip {
            background: var(--bg-secondary);
            border: 1px dashed var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 12px;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: left;
            margin-top: 20px;
        }

        .cli-tip code {
            color: var(--accent-red);
            font-weight: 700;
            background: var(--bg-card);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .footer {
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <a href="{{ url('/') }}" class="brand">
            <div class="logo">M</div>
            <div class="brand-name">MerchenZ</div>
        </a>

        <!-- Theme Switcher: Dark and White only -->
        <div class="theme-switcher">
            <button type="button" class="theme-btn active" id="btn-theme-dark">Dark</button>
            <button type="button" class="theme-btn" id="btn-theme-light">White</button>
        </div>
    </div>

    <div class="error-card">
        <div class="pulse-container">
            <div class="pulse-dot"></div>
            <span>System Unavailable</span>
        </div>

        <div class="error-code">503</div>
        <h1 class="error-title">Server Engine Not Started</h1>
        <p class="error-message">
            The MerchenZ backend metering engine is currently unavailable, offline, or starting up. All ongoing client usage events will be safely queued once connectivity is restored.
        </p>

        <div class="diagnostics-grid">
            <div class="diag-item">
                <div class="diag-label">Ingestion API</div>
                <div class="diag-val">Offline</div>
            </div>
            <div class="diag-item">
                <div class="diag-label">Tenant DB</div>
                <div class="diag-val">Standby</div>
            </div>
            <div class="diag-item">
                <div class="diag-label">Queue Worker</div>
                <div class="diag-val">Paused</div>
            </div>
        </div>

        <div class="reconnect-notice">
            Automatic reconnect in <strong id="countdown">15</strong> seconds...
        </div>

        <div class="actions">
            <button type="button" onclick="location.reload()" class="btn-primary">
                ⟳ Retry Connection Now
            </button>
        </div>

        <div class="cli-tip">
            <strong>Developer Tip:</strong> If you are running locally and the server is not started, start it via terminal using: <code>php artisan serve</code>
        </div>
    </div>

    <footer class="footer">
        MerchenZ Multi-Tenant Metering Engine &bull; Service Unavailable (503)
    </footer>

    <script>
        // Countdown auto-retry
        let seconds = 15;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                location.reload();
            }
        }, 1000);

        // Theme switching: Dark and White only
        const darkBtn = document.getElementById('btn-theme-dark');
        const lightBtn = document.getElementById('btn-theme-light');

        function setTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('merchenz_theme', t);
            if (t === 'dark') {
                darkBtn.classList.add('active');
                lightBtn.classList.remove('active');
            } else {
                lightBtn.classList.add('active');
                darkBtn.classList.remove('active');
            }
        }

        darkBtn.addEventListener('click', () => setTheme('dark'));
        lightBtn.addEventListener('click', () => setTheme('light'));

        const currentTheme = localStorage.getItem('merchenz_theme') || 'dark';
        setTheme(currentTheme);
    </script>
</body>
</html>

