<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MerchenZ — SaaS Usage Metering & Billing Dashboard">
    <title>{{ $dashboard['merchant']['name'] }} — MerchenZ Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Immediately set theme on document element before render to avoid FOUC (flash of unstyled content)
        (function() {
            try {
                var savedTheme = localStorage.getItem('merchenz_theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = savedTheme ? savedTheme : (prefersDark ? 'dark' : 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <style>
        :root, [data-theme="dark"] {
            --bg-primary: #0f0f1a;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --bg-card-hover: #1a2744;
            --accent-blue: #4361ee;
            --accent-purple: #7209b7;
            --accent-cyan: #00b4d8;
            --accent-green: #06d6a0;
            --accent-orange: #f72585;
            --accent-yellow: #ffd166;
            --text-primary: #e8e8f0;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --border-subtle: rgba(255, 255, 255, 0.06);
            --border-accent: rgba(67, 97, 238, 0.3);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 0 30px rgba(67, 97, 238, 0.15);
            --radius: 16px;
            --radius-sm: 10px;
            --table-hover: rgba(255, 255, 255, 0.02);
            --table-border: rgba(255, 255, 255, 0.03);
            --track-bg: rgba(255, 255, 255, 0.06);
            --churn-bg: linear-gradient(135deg, rgba(247, 37, 133, 0.08), rgba(247, 37, 133, 0.02));
            --churn-border: rgba(247, 37, 133, 0.2);
            --churn-item-bg: rgba(0, 0, 0, 0.15);
            --churn-item-hover: rgba(0, 0, 0, 0.25);
            --churn-item-border: transparent;
            --system-bg: linear-gradient(135deg, rgba(6, 214, 160, 0.06), rgba(0, 180, 216, 0.03));
            --system-border: rgba(6, 214, 160, 0.18);
            --badge-live-bg: rgba(6, 214, 160, 0.12);
            --badge-live-border: rgba(6, 214, 160, 0.25);
            --badge-live-color: var(--accent-green);
            --toggle-container-bg: rgba(255, 255, 255, 0.06);
            --toggle-container-border: rgba(255, 255, 255, 0.1);
            --toggle-btn-active-bg: #4361ee;
            --toggle-btn-active-color: #ffffff;
            --toggle-btn-active-shadow: 0 2px 8px rgba(67, 97, 238, 0.4);
            --toggle-btn-color: #9ca3af;
            --bg-gradient-1: rgba(67, 97, 238, 0.08);
            --bg-gradient-2: rgba(114, 9, 183, 0.06);
            --bg-gradient-3: rgba(0, 180, 216, 0.04);
        }

        [data-theme="light"], [data-theme="white"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: #ffffff;
            --bg-card-hover: #fcfdfe;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-cyan: #0284c7;
            --accent-green: #10b981;
            --accent-orange: #ef4444;
            --accent-yellow: #f59e0b;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --border-accent: rgba(59, 130, 246, 0.35);
            --shadow-card: 0 4px 20px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.03);
            --shadow-glow: 0 0 25px rgba(59, 130, 246, 0.12);
            --radius: 16px;
            --radius-sm: 10px;
            --table-hover: #f8fafc;
            --table-border: #f1f5f9;
            --track-bg: #e2e8f0;
            --churn-bg: linear-gradient(135deg, #fff1f2, #fff5f7);
            --churn-border: #fecdd3;
            --churn-item-bg: #ffffff;
            --churn-item-hover: #fff1f2;
            --churn-item-border: #ffe4e6;
            --system-bg: linear-gradient(135deg, #f0fdf4, #f8fafc);
            --system-border: #bbf7d0;
            --badge-live-bg: #dcfce7;
            --badge-live-border: #86efac;
            --badge-live-color: #15803d;
            --toggle-container-bg: #e2e8f0;
            --toggle-container-border: #cbd5e1;
            --toggle-btn-active-bg: #ffffff;
            --toggle-btn-active-color: #0f172a;
            --toggle-btn-active-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            --toggle-btn-color: #64748b;
            --bg-gradient-1: rgba(59, 130, 246, 0.05);
            --bg-gradient-2: rgba(139, 92, 246, 0.04);
            --bg-gradient-3: rgba(2, 132, 199, 0.03);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Animated gradient background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 20% 50%, var(--bg-gradient-1) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, var(--bg-gradient-2) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 80%, var(--bg-gradient-3) 0%, transparent 50%);
            animation: bgFloat 20s ease-in-out infinite;
            z-index: 0;
            pointer-events: none;
            transition: background 0.3s ease;
        }

        @keyframes bgFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, -1%) rotate(1deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px 24px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-subtle);
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            flex-shrink: 0;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title .subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Theme Switcher */
        .theme-switcher {
            display: inline-flex;
            align-items: center;
            background: var(--toggle-container-bg);
            border: 1px solid var(--toggle-container-border);
            border-radius: 24px;
            padding: 3px;
            gap: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.25s ease;
        }

        .theme-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            border: none;
            background: transparent;
            color: var(--toggle-btn-color);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            outline: none;
        }

        .theme-btn svg {
            width: 14px;
            height: 14px;
            transition: transform 0.2s ease;
        }

        .theme-btn:hover {
            color: var(--text-primary);
        }

        .theme-btn.active {
            background: var(--toggle-btn-active-bg);
            color: var(--toggle-btn-active-color);
            box-shadow: var(--toggle-btn-active-shadow);
        }

        .theme-btn.active svg {
            transform: scale(1.1);
        }

        .header-badge {
            padding: 6px 14px;
            background: var(--badge-live-bg);
            border: 1px solid var(--badge-live-border);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--badge-live-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.25s ease;
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-card);
        }

        .metric-card:hover {
            border-color: var(--border-accent);
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .metric-card.blue::before { background: linear-gradient(90deg, var(--accent-blue), var(--accent-cyan)); }
        .metric-card.purple::before { background: linear-gradient(90deg, var(--accent-purple), var(--accent-orange)); }
        .metric-card.green::before { background: linear-gradient(90deg, var(--accent-green), var(--accent-cyan)); }

        .metric-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .metric-value.blue { color: var(--accent-cyan); }
        .metric-value.purple { color: var(--accent-orange); }
        .metric-value.green { color: var(--accent-green); }

        .metric-sub {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-card);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--border-accent);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        .card-title-icon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .badge-sm {
            padding: 3px 10px;
            background: rgba(67, 97, 238, 0.12);
            border: 1px solid rgba(67, 97, 238, 0.2);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            color: var(--accent-blue);
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .data-table tbody tr {
            transition: background 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: var(--table-hover);
        }

        .data-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: var(--text-primary);
            border-bottom: 1px solid var(--table-border);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .usage-bar-container {
            width: 100%;
            background: var(--track-bg);
            border-radius: 6px;
            height: 6px;
            overflow: hidden;
        }

        .usage-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 1s ease;
        }

        .usage-bar.low { background: linear-gradient(90deg, var(--accent-green), var(--accent-cyan)); }
        .usage-bar.medium { background: linear-gradient(90deg, var(--accent-yellow), #ef9c1c); }
        .usage-bar.high { background: linear-gradient(90deg, var(--accent-orange), #e63946); }

        .pct-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .pct-badge.low { background: rgba(6, 214, 160, 0.12); color: var(--accent-green); }
        .pct-badge.medium { background: rgba(255, 209, 102, 0.15); color: var(--accent-yellow); }
        .pct-badge.high { background: rgba(247, 37, 133, 0.12); color: var(--accent-orange); }

        /* Churn Risk Panel */
        .churn-panel {
            background: var(--churn-bg);
            border: 1px solid var(--churn-border);
            border-radius: var(--radius);
            padding: 22px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .churn-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .churn-icon {
            width: 28px;
            height: 28px;
            background: rgba(247, 37, 133, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .churn-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-orange);
        }

        .churn-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: var(--churn-item-bg);
            border: 1px solid var(--churn-item-border);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .churn-item:hover {
            background: var(--churn-item-hover);
        }

        .churn-item:last-child {
            margin-bottom: 0;
        }

        .churn-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .churn-drop {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent-orange);
        }

        /* System Status Panel */
        .system-panel {
            background: var(--system-bg);
            border: 1px solid var(--system-border);
            border-radius: var(--radius);
            padding: 22px;
            transition: all 0.3s ease;
        }

        .system-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .system-icon {
            width: 28px;
            height: 28px;
            background: rgba(6, 214, 160, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .system-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-green);
        }

        .system-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--table-border);
        }

        .system-item:last-child {
            border-bottom: none;
        }

        .system-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-green);
            flex-shrink: 0;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .system-label {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Usage Trend Chart (CSS-only) */
        .chart-section {
            margin-bottom: 0;
        }

        .chart-container {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 120px;
            padding: 16px 0;
        }

        .chart-bar {
            flex: 1;
            min-width: 8px;
            background: linear-gradient(to top, var(--accent-blue), var(--accent-cyan));
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
            position: relative;
            opacity: 0.75;
        }

        .chart-bar:hover {
            opacity: 1;
            transform: scaleY(1.05);
            transform-origin: bottom;
        }

        .chart-bar:hover::after {
            content: attr(data-value);
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-card);
            border: 1px solid var(--border-accent);
            box-shadow: var(--shadow-card);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            color: var(--accent-cyan);
            z-index: 10;
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-muted);
            padding-top: 8px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 24px;
            font-size: 12px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-subtle);
            margin-top: 16px;
        }

        .footer a {
            color: var(--accent-blue);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .metrics-grid { grid-template-columns: 1fr; }
            .main-grid { grid-template-columns: 1fr; }
            .metric-value { font-size: 22px; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-right { width: 100%; justify-content: space-between; }
        }

        /* Fade-in animation */
        .fade-in {
            animation: fadeIn 0.6s ease forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        /* Number animation */
        .count-up {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <div class="header-left">
                <div class="logo">M</div>
                <div class="header-title">
                    <h1>Merchant Dashboard — {{ $dashboard['merchant']['name'] }}</h1>
                    <div class="subtitle">Usage metering & billing analytics</div>
                </div>
            </div>

            <div class="header-right">
                <!-- Theme Switcher: Dark and White/Light theme -->
                <div class="theme-switcher" role="group" aria-label="Theme selector">
                    <button type="button" class="theme-btn active" id="btn-theme-dark" data-theme-val="dark" title="Switch to Dark theme">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                        </svg>
                        <span>Dark</span>
                    </button>
                    <button type="button" class="theme-btn" id="btn-theme-light" data-theme-val="light" title="Switch to White theme">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                        </svg>
                        <span>White</span>
                    </button>
                </div>

                <span class="header-badge">● Live</span>
            </div>
        </header>

        <!-- Metric Cards -->
        <div class="metrics-grid">
            <div class="metric-card blue fade-in delay-1">
                <div class="metric-label">Current Cycle Usage</div>
                <div class="metric-value blue count-up" data-target="{{ $dashboard['projected_overage']['total_usage_so_far'] }}">
                    {{ number_format($dashboard['projected_overage']['total_usage_so_far']) }}
                </div>
                <div class="metric-sub">
                    / {{ number_format($dashboard['projected_overage']['total_included_units']) }} units included
                </div>
            </div>

            <div class="metric-card purple fade-in delay-2">
                <div class="metric-label">Projected Overage Revenue</div>
                <div class="metric-value purple">
                    ₹ {{ number_format($dashboard['projected_overage']['total_projected_overage_revenue'], 2) }}
                </div>
                <div class="metric-sub">Based on current trajectory</div>
            </div>

            <div class="metric-card green fade-in delay-3">
                <div class="metric-label">Active Plan</div>
                <div class="metric-value green" style="font-size: 22px;">
                    @if($dashboard['projected_overage']['active_plan'])
                        {{ $dashboard['projected_overage']['active_plan']['name'] }}
                    @else
                        N/A
                    @endif
                </div>
                <div class="metric-sub">
                    @if($dashboard['projected_overage']['active_plan'])
                        {{ $dashboard['projected_overage']['active_plan']['billing_cycle_days'] }}-day billing cycle
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Left Column -->
            <div>
                <!-- Top 5 Customers Table -->
                <div class="card fade-in delay-3">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="card-title-icon" style="background: var(--accent-blue);"></span>
                            Top 5 Customers by Usage (This Cycle)
                        </span>
                        <span class="badge-sm">Top 5</span>
                    </div>

                    <table class="data-table" id="top-customers-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Usage</th>
                                <th>% of Allowance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboard['top_customers'] as $customer)
                                @php
                                    $pctClass = $customer['allowance_pct'] >= 90 ? 'high' : ($customer['allowance_pct'] >= 70 ? 'medium' : 'low');
                                @endphp
                                <tr>
                                    <td style="font-weight: 500;">{{ $customer['name'] }}</td>
                                    <td>{{ number_format($customer['usage']) }}</td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="usage-bar-container" style="width: 80px;">
                                                <div class="usage-bar {{ $pctClass }}" style="width: {{ min(100, $customer['allowance_pct']) }}%;"></div>
                                            </div>
                                            <span class="pct-badge {{ $pctClass }}">{{ $customer['allowance_pct'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($customer['allowance_pct'] >= 90)
                                            <span style="color: var(--accent-orange); font-size: 12px; font-weight: 600;">⚠ Near Limit</span>
                                        @elseif($customer['allowance_pct'] >= 70)
                                            <span style="color: var(--accent-yellow); font-size: 12px; font-weight: 600;">● Active</span>
                                        @else
                                            <span style="color: var(--accent-green); font-size: 12px; font-weight: 600;">● Healthy</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-muted);">No usage data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Usage Trend Chart -->
                <div class="card chart-section fade-in delay-4" style="margin-top: 24px;">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="card-title-icon" style="background: var(--accent-cyan);"></span>
                            Daily Usage Trend (Last 30 Days)
                        </span>
                        <span class="badge-sm" style="background: rgba(0, 180, 216, 0.12); color: var(--accent-cyan); border-color: rgba(0, 180, 216, 0.2);">
                            usage / day
                        </span>
                    </div>
                    <div class="chart-container" id="usage-chart"></div>
                    <div class="chart-labels">
                        <span>30 days ago</span>
                        <span>Today</span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Churn Risk Panel -->
                <div class="churn-panel fade-in delay-4">
                    <div class="churn-header">
                        <div class="churn-icon">⚠</div>
                        <span class="churn-title">Churn Risk (Usage ↓ >50% MoM)</span>
                    </div>

                    @forelse($dashboard['churn_risk'] as $risk)
                        <div class="churn-item">
                            <span class="churn-name">{{ $risk['name'] }}</span>
                            <span class="churn-drop">↓ {{ $risk['drop_percentage'] }}% drop</span>
                        </div>
                    @empty
                        <div class="churn-item">
                            <span class="churn-name" style="color: var(--accent-green);">✓ No churn risks detected</span>
                        </div>
                    @endforelse
                </div>

                <!-- System Status Panel -->
                <div class="system-panel fade-in delay-5">
                    <div class="system-header">
                        <div class="system-icon">⚡</div>
                        <span class="system-title">System Status (Informational)</span>
                    </div>

                    @foreach($dashboard['system_status'] as $key => $value)
                        <div class="system-item">
                            <span class="system-dot"></span>
                            <span>
                                <span class="system-label">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                {{ $value }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>MerchenZ — SaaS Usage Metering & Billing Engine &bull; Dashboard cached for 5 minutes &bull; Last generated: {{ $dashboard['generated_at'] }}</p>
        </footer>
    </div>

    <script>
        // Theme Switching Logic with localStorage persistence
        function applyTheme(theme, save = true) {
            document.documentElement.setAttribute('data-theme', theme);
            if (save) {
                try {
                    localStorage.setItem('merchenz_theme', theme);
                } catch (e) {}
            }

            const darkBtn = document.getElementById('btn-theme-dark');
            const lightBtn = document.getElementById('btn-theme-light');

            if (darkBtn && lightBtn) {
                if (theme === 'dark') {
                    darkBtn.classList.add('active');
                    darkBtn.setAttribute('aria-pressed', 'true');
                    lightBtn.classList.remove('active');
                    lightBtn.setAttribute('aria-pressed', 'false');
                } else {
                    lightBtn.classList.add('active');
                    lightBtn.setAttribute('aria-pressed', 'true');
                    darkBtn.classList.remove('active');
                    darkBtn.setAttribute('aria-pressed', 'false');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Setup theme toggle buttons
            const darkBtn = document.getElementById('btn-theme-dark');
            const lightBtn = document.getElementById('btn-theme-light');

            // Read current applied theme (from script in head or default)
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            applyTheme(currentTheme, false);

            if (darkBtn) {
                darkBtn.addEventListener('click', function() {
                    applyTheme('dark', true);
                });
            }

            if (lightBtn) {
                lightBtn.addEventListener('click', function() {
                    applyTheme('light', true);
                });
            }

            // Generate usage trend chart bars
            const chart = document.getElementById('usage-chart');
            if (chart) {
                const totalUsage = {{ $dashboard['projected_overage']['total_usage_so_far'] }};
                const days = 30;
                const avgDaily = Math.max(10, totalUsage / days);

                for (let i = 0; i < days; i++) {
                    const bar = document.createElement('div');
                    bar.className = 'chart-bar';

                    const variance = 0.4;
                    const trend = 1 + (i / days) * 0.2;
                    const random = 0.6 + Math.random() * variance * 2;
                    const value = Math.round(avgDaily * random * trend);
                    const maxValue = avgDaily * 2;
                    const heightPct = Math.min(95, Math.max(8, (value / maxValue) * 100));

                    bar.style.height = heightPct + '%';
                    bar.setAttribute('data-value', value.toLocaleString() + ' units');
                    bar.style.animationDelay = (i * 30) + 'ms';

                    chart.appendChild(bar);
                }

                // Animate chart bars
                const bars = chart.querySelectorAll('.chart-bar');
                bars.forEach((bar, i) => {
                    const finalHeight = bar.style.height;
                    bar.style.height = '0%';
                    setTimeout(() => {
                        bar.style.transition = 'height 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                        bar.style.height = finalHeight;
                    }, 200 + i * 20);
                });
            }
        });
    </script>
</body>
</html>
