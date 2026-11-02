<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Onboarding — MerchenZ</title>
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
            --border-accent: rgba(255, 51, 75, 0.5);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.7);
            --radius: 16px;
            --radius-sm: 10px;
            --input-bg: #111116;
            --input-border: rgba(255, 255, 255, 0.1);
            --toggle-bg: #121217;
            --toggle-btn-active: #e61e38;
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
            --input-bg: #ffffff;
            --input-border: #e5e7eb;
            --toggle-bg: #f3f4f6;
            --toggle-btn-active: #dc2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 32px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-red), #990012);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }

        .brand h2 {
            font-size: 20px;
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
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
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
            padding: 36px;
        }

        .card-header {
            margin-bottom: 28px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .card-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--accent-red);
            margin-bottom: 16px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 680px) {
            .grid-2 { grid-template-columns: 1fr; }
            .card { padding: 24px 18px; }
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-secondary);
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

        .error-msg {
            color: #ef4444;
            font-size: 11px;
            margin-top: 4px;
        }

        /* Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        @media (max-width: 768px) {
            .plans-grid { grid-template-columns: 1fr; }
        }

        .plan-card {
            border: 2px solid var(--input-border);
            border-radius: var(--radius-sm);
            padding: 18px 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            background: var(--input-bg);
        }

        .plan-card:hover {
            border-color: var(--accent-red);
            background: var(--bg-card-hover);
        }

        .plan-card input[type="radio"] {
            position: absolute;
            top: 14px;
            right: 14px;
            accent-color: var(--accent-red);
        }

        .plan-card.selected {
            border-color: var(--accent-red);
            background: var(--bg-card-hover);
            box-shadow: 0 0 16px rgba(220, 38, 38, 0.15);
        }

        .plan-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .plan-price {
            font-size: 22px;
            font-weight: 800;
            color: var(--accent-red);
            margin-bottom: 8px;
        }

        .plan-price span {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .plan-meta {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .plan-meta div {
            margin-bottom: 4px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent-red), #b91c1c);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 18px;
        }

        .footer-note a {
            color: var(--accent-red);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="/" class="brand">
                <div class="logo">M</div>
                <h2>MerchenZ</h2>
            </a>

            <div class="theme-switcher">
                <button type="button" class="theme-btn active" id="btn-theme-dark">Dark</button>
                <button type="button" class="theme-btn" id="btn-theme-light">White</button>
            </div>
        </header>

        <div class="card">
            <div class="card-header">
                <h1>Launch Your SaaS Company</h1>
                <p>Register your company, choose your billing plan, and gain instant access to your meter dashboard.</p>
            </div>

            @if($errors->any())
                <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <ul style="color: #ef4444; font-size: 13px; list-style-position: inside;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('onboarding.store') }}" method="POST">
                @csrf

                <!-- Section 1: Administrator Credentials -->
                <div class="section-title">1. Administrator Details</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="name">Your Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Sarah Connor" value="{{ old('name') }}" required>
                        @error('name') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Work Email *</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="admin@company.com" value="{{ old('email') }}" required>
                        @error('email') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="password">Password * (Min 8 chars)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        @error('password') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Section 2: Company Details -->
                <div class="section-title">2. Company Details</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="company_name">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" placeholder="e.g. Acme Logistics Global Inc." value="{{ old('company_name') }}" required>
                        @error('company_name') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone">Business Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="+1 (555) 019-2834" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Billing Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="500 Tech Parkway, Suite 1200, Austin, TX" value="{{ old('address') }}">
                </div>

                <!-- Section 3: Select Plan -->
                <div class="section-title">3. Choose Usage Plan</div>
                <div class="plans-grid">
                    @foreach($plans as $index => $plan)
                        <label class="plan-card {{ ($index === 1 || old('plan_id') == $plan->id) ? 'selected' : '' }}" onclick="selectPlan(this)">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" {{ ($index === 1 || old('plan_id') == $plan->id) ? 'checked' : '' }} required>
                            <div class="plan-name">{{ $plan->name }}</div>
                            <div class="plan-price">${{ number_format($plan->base_price, 2) }} <span>/ month</span></div>
                            <div class="plan-meta">
                                <div><strong>{{ number_format($plan->included_usage_units ?? $plan->included_units ?? 0) }}</strong> included units</div>
                                <div>${{ number_format($plan->overage_rate_per_unit, 4) }} / unit overage</div>
                                <div>30-day billing cycle</div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="btn-submit">Complete Setup & Launch Dashboard &rarr;</button>

                <div class="footer-note">
                    Already registered? <a href="{{ route('login') }}">Sign In to your tenant account</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectPlan(card) {
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }

        // Theme switching
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

        // Initialize active state
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(currentTheme);
    </script>
</body>
</html>

