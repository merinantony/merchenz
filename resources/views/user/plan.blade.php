<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Plan & Usage — MerchenZ</title>
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
            --badge-user-bg: rgba(255, 51, 75, 0.15);
            --badge-user-color: #ff334b;
            --progress-bg: #232330;
            --progress-bar: linear-gradient(90deg, #ff334b, #e61e38);
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
            --badge-user-bg: #fee2e2;
            --badge-user-color: #b91c1c;
            --progress-bg: #fee2e2;
            --progress-bar: linear-gradient(90deg, #dc2626, #ef4444);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 24px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 24px;
            margin-bottom: 28px;
            border-bottom: 1px solid var(--border-subtle);
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent-red), #990012);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }

        .header-title h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .user-role-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            background: var(--badge-user-bg);
            color: var(--badge-user-color);
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

        .btn-logout {
            background: transparent;
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        /* Notice Banner */
        .scope-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 51, 75, 0.08);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 12px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .scope-icon {
            font-size: 16px;
        }

        /* Plan Hero Card */
        .plan-card {
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .plan-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 51, 75, 0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .plan-title-block h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-status-pill {
            display: inline-block;
            background: rgba(5, 150, 105, 0.15);
            border: 1px solid rgba(5, 150, 105, 0.3);
            color: #10b981;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 12px;
        }

        .plan-price-block {
            text-align: right;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 800;
            color: var(--accent-red);
            line-height: 1;
        }

        .plan-cycle {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Quota Meter Section */
        .quota-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 24px;
            margin-bottom: 24px;
        }

        .quota-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 12px;
        }

        .quota-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quota-numbers {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .quota-numbers span {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            background: var(--progress-bg);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-fill {
            height: 100%;
            background: var(--progress-bar);
            border-radius: 6px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quota-footer {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .quota-status {
            font-weight: 600;
            color: var(--accent-red);
        }

        /* Detail Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            padding: 20px;
        }

        .detail-card h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 13px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-secondary);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Permissions Footer Info */
        .info-card {
            background: var(--bg-card);
            border: 1px dashed var(--border-subtle);
            border-radius: var(--radius);
            padding: 20px;
            font-size: 12px;
            line-height: 1.6;
            color: var(--text-secondary);
        }

        .info-card strong {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="brand-section">
                <div class="logo">M</div>
                <div class="header-title">
                    <h1>{{ $merchant->company_name ?? $merchant->name ?? 'MerchenZ Tenant' }}</h1>
                    <p>My Subscription & Quota Usage</p>
                </div>
            </div>

            <div class="header-actions">
                <div class="user-pill">
                    <span>{{ $user->name }}</span>
                    <span class="user-role-badge">User</span>
                </div>

                <!-- Theme Switcher: Dark and White only -->
                <div class="theme-switcher">
                    <button type="button" class="theme-btn active" id="btn-theme-dark">Dark</button>
                    <button type="button" class="theme-btn" id="btn-theme-light">White</button>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-logout">Sign Out</button>
                </form>
            </div>
        </header>

        <!-- Scope Banner: Regular User sees own plan only -->
        <div class="scope-banner">
            <span class="scope-icon">🔒</span>
            <div>
                <strong>Regular Tenant User Access:</strong> You are viewing your assigned company plan and usage quota. Company billing, API keys, and customer management are restricted to Tenant Admins.
            </div>
        </div>

        <!-- Plan Hero Card -->
        <div class="plan-card">
            <div class="plan-header">
                <div class="plan-title-block">
                    <h2>
                        {{ $plan ? $plan->name : 'Standard Tenant Plan' }}
                        <span class="plan-status-pill">Active</span>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">
                        Assigned company subscription tier
                    </p>
                </div>

                <div class="plan-price-block">
                    <div class="plan-price">${{ number_format($plan->base_price ?? 49.00, 2) }}</div>
                    <div class="plan-cycle">per {{ $plan->billing_cycle_days ?? $plan->billing_cycle ?? 30 }} days billing cycle</div>
                </div>
            </div>

            <!-- Quota & Allowance Progress -->
            <div class="quota-section">
                <div class="quota-header">
                    <span class="quota-label">Current Cycle Usage</span>
                    <div class="quota-numbers">
                        {{ number_format($currentUsage) }}
                        <span>/ {{ number_format($includedUnits) }} units</span>
                    </div>
                </div>

                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ min(100, max(2, $allowancePct)) }}%;"></div>
                </div>

                <div class="quota-footer">
                    <span>{{ $allowancePct }}% of included allowance consumed</span>
                    <span class="quota-status">
                        @if($remainingUnits > 0)
                            ✓ {{ number_format($remainingUnits) }} units remaining
                        @else
                            ⚠ Overage Rate: ${{ number_format($plan->overage_rate_per_unit ?? 0.02, 4) }}/unit
                        @endif
                    </span>
                </div>
            </div>

            <div class="details-grid">
                <!-- Plan Specifications -->
                <div class="detail-card">
                    <h3>📋 Plan Entitlements</h3>
                    <div class="detail-row">
                        <span class="detail-label">Included Usage</span>
                        <span class="detail-value">{{ number_format($includedUnits) }} units / cycle</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Overage Rate</span>
                        <span class="detail-value">${{ number_format($plan->overage_rate_per_unit ?? 0.02, 4) }} / unit</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Billing Cycle</span>
                        <span class="detail-value">{{ $plan->billing_cycle_days ?? $plan->billing_cycle ?? 30 }} days recurring</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value" style="color: #10b981;">Active</span>
                    </div>
                </div>

                <!-- Account & Organization -->
                <div class="detail-card">
                    <h3>🏢 Tenant Organization</h3>
                    <div class="detail-row">
                        <span class="detail-label">Company</span>
                        <span class="detail-value">{{ $merchant->company_name ?? $merchant->name ?? 'Acme Corp' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Logged In User</span>
                        <span class="detail-value">{{ $user->name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">User Email</span>
                        <span class="detail-value">{{ $user->email }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Role Privilege</span>
                        <span class="detail-value" style="color: var(--accent-red);">Regular User (Plan Only)</span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <strong>Multi-Tenant Architecture Note:</strong> All data is stored in a single database partitioned with Row-Level Tenancy (<code>merchant_id</code>). Laravel Global Scopes enforce strict data isolation between companies. Super Admins monitor platform health across all companies, Tenant Admins manage merchant operations, and Regular Users are limited strictly to viewing their active plan and usage metrics.
            </div>
        </div>
    </div>

    <script>
        // Theme Switching: Dark (Black & Red) and White (Red & White)
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('merchenz_theme', theme);

            const isDark = (theme === 'dark');
            document.getElementById('btn-theme-dark').classList.toggle('active', isDark);
            document.getElementById('btn-theme-light').classList.toggle('active', !isDark);
        }

        document.getElementById('btn-theme-dark').addEventListener('click', function() {
            setTheme('dark');
        });

        document.getElementById('btn-theme-light').addEventListener('click', function() {
            setTheme('light');
        });

        // Initialize state on load
        (function() {
            const current = localStorage.getItem('merchenz_theme') || 'dark';
            setTheme(current);
        })();
    </script>
</body>
</html>

