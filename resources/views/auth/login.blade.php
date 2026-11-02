<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — MerchenZ Multi-Tenant Platform</title>
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
            --input-bg: #111116;
            --input-border: rgba(255, 255, 255, 0.1);
            --toggle-bg: #121217;
            --toggle-btn-active: #e61e38;
            --pill-bg: rgba(255, 51, 75, 0.1);
            --pill-border: rgba(255, 51, 75, 0.25);
            --pill-hover: rgba(255, 51, 75, 0.2);
        }

        [data-theme="light"], [data-theme="white"] {
            --bg-primary: #fcfcfd;
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
            --input-bg: #ffffff;
            --input-border: #e5e7eb;
            --toggle-bg: #f3f4f6;
            --toggle-btn-active: #dc2626;
            --pill-bg: #fee2e2;
            --pill-border: #fca5a5;
            --pill-hover: #fecaca;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
        }

        .logo {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent-red), #990012);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
        }

        .theme-switcher {
            display: inline-flex;
            background: var(--toggle-bg);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 2px;
            gap: 2px;
        }

        .theme-btn {
            border: none;
            background: transparent;
            color: var(--text-muted);
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 14px;
            cursor: pointer;
        }

        .theme-btn.active {
            background: var(--toggle-btn-active);
            color: #ffffff;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
            padding: 32px 28px;
        }

        .card h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .card p.subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent-red);
            box-shadow: 0 0 0 3px rgba(255, 51, 75, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent-red), #b91c1c);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
            margin-top: 8px;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
        }

        /* Demo Role Pills */
        .demo-roles {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-subtle);
        }

        .demo-roles-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 10px;
            text-align: center;
        }

        .role-pills {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .role-pill {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: var(--pill-bg);
            border: 1px solid var(--pill-border);
            border-radius: var(--radius-sm);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .role-pill:hover {
            background: var(--pill-hover);
            border-color: var(--accent-red);
        }

        .role-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--accent-red);
            color: white;
        }

        .card-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .card-footer a {
            color: var(--accent-red);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="header-bar">
            <a href="/" class="brand">
                <div class="logo">M</div>
                <div class="brand-name">MerchenZ</div>
            </a>

            <div class="theme-switcher">
                <button type="button" class="theme-btn active" id="btn-theme-dark">Dark</button>
                <button type="button" class="theme-btn" id="btn-theme-light">White</button>
            </div>
        </div>

        <div class="card">
            <h1>Sign In</h1>
            <p class="subtitle">Access your tenant analytics or platform dashboard</p>

            @if(session('info'))
                <div style="background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 12px; color: #60a5fa;">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('success'))
                <div style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 12px; color: #4ade80;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 12px; color: #ef4444;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', 'admin@acmelogistics.io') }}" placeholder="you@company.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" value="password" placeholder="••••••••" required>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 12px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--text-secondary);">
                        <input type="checkbox" name="remember" style="accent-color: var(--accent-red);"> Remember me
                    </label>
                </div>

                <button type="submit" class="btn-submit">Sign In &rarr;</button>
            </form>

            <!-- Quick-fill demo account pills for testing -->
            <div class="demo-roles">
                <div class="demo-roles-title">Demo Roles (Click to autofill)</div>
                <div class="role-pills">
                    <div class="role-pill" onclick="fillCreds('superadmin@merchenz.io', 'password')">
                        <span><strong>Super Admin:</strong> Platform Monitor</span>
                        <span class="role-badge">All Data</span>
                    </div>
                    <div class="role-pill" onclick="fillCreds('admin@acmelogistics.io', 'password')">
                        <span><strong>Tenant Admin:</strong> Acme Logistics</span>
                        <span class="role-badge">Company</span>
                    </div>
                    <div class="role-pill" onclick="fillCreds('user@acmelogistics.io', 'password')">
                        <span><strong>Tenant User:</strong> Alex Miller</span>
                        <span class="role-badge">Plan Only</span>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                Need to register a company? <a href="{{ route('onboarding.create') }}">Onboard here</a>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

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

        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(currentTheme);
    </script>
</body>
</html>

