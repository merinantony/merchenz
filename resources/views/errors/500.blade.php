<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error | MerchenZ</title>
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
            --accent-red: #ff334b;
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
            --glow-color: rgba(255, 51, 75, 0.15);
        }

        [data-theme="light"], [data-theme="white"] {
            --bg-primary: #fcfcfd;
            --bg-secondary: #f6f6f9;
            --bg-card: #ffffff;
            --accent-red: #dc2626;
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
            --glow-color: rgba(220, 38, 38, 0.08);
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
            background: radial-gradient(circle, var(--glow-color) 0%, transparent 70%);
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

        .error-card {
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius);
            padding: 44px 36px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-card);
            z-index: 10;
            margin: 40px auto;
        }

        .error-code {
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--accent-red), #e11d48);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .error-badge {
            display: inline-block;
            background: rgba(255, 51, 75, 0.12);
            color: var(--accent-red);
            border: 1px solid var(--border-subtle);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .error-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .error-message {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
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
        <div class="error-code">500</div>
        <div class="error-badge">Internal Exception</div>
        <h1 class="error-title">Unexpected Server Exception</h1>
        <p class="error-message">
            The server encountered an unexpected error while processing your metering request. Our telemetry logging has recorded this instance.
        </p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn-primary">
                Return to Safe Dashboard &rarr;
            </a>
            <button type="button" onclick="location.reload()" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-subtle); padding: 10px 20px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600;">
                Reload Page
            </button>
        </div>
    </div>

    <footer class="footer">
        MerchenZ Multi-Tenant Metering Engine &bull; Error 500
    </footer>

    <script>
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

