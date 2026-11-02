<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Platform Monitor — MerchenZ</title>
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
            --table-hover: rgba(255, 51, 75, 0.04);
            --table-border: rgba(255, 255, 255, 0.04);
            --toggle-bg: #121217;
            --toggle-btn-active: #e61e38;
            --badge-admin-bg: rgba(255, 51, 75, 0.15);
            --badge-admin-color: #ff334b;
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
            --table-hover: #fef2f2;
            --table-border: #f3f4f6;
            --toggle-bg: #f3f4f6;
            --toggle-btn-active: #dc2626;
            --badge-admin-bg: #fee2e2;
            --badge-admin-color: #b91c1c;
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
            max-width: 1280px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 24px;
            margin-bottom: 32px;
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
            font-size: 22px;
            font-weight: 800;
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
            font-size: 11px;
            font-weight: 600;
            border-radius: 16px;
            cursor: pointer;
        }

        .theme-btn.active {
            background: var(--toggle-btn-active);
            color: #ffffff;
        }

        .btn-outline {
            padding: 7px 14px;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-outline:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        @media (max-width: 900px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-red), #e11d48);
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent-red);
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Section Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
            padding: 24px;
            margin-bottom: 32px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .card-header h2 {
            font-size: 17px;
            font-weight: 700;
        }

        .badge-live {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            background: var(--badge-admin-bg);
            color: var(--badge-admin-color);
            border: 1px solid var(--border-subtle);
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .data-table th {
            padding: 10px 14px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-size: 11px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .data-table td {
            padding: 14px;
            color: var(--text-primary);
            border-bottom: 1px solid var(--table-border);
        }

        .data-table tr:hover {
            background: var(--table-hover);
        }

        .tag-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            background: rgba(255, 51, 75, 0.12);
            color: var(--accent-red);
        }

        .tag-super { background: rgba(220, 38, 38, 0.2); color: #ef4444; }
        .tag-admin { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .tag-user { background: rgba(16, 185, 129, 0.15); color: #10b981; }

        .btn-inspect {
            padding: 6px 12px;
            border-radius: 6px;
            background: var(--accent-red);
            color: white;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-inspect:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="brand-section">
                <div class="logo">M</div>
                <div class="header-title">
                    <h1>Super Admin Platform Monitor</h1>
                    <p>Global multi-tenant overview &bull; Single database with row-level tenancy</p>
                </div>
            </div>

            <div class="header-actions">
                <div class="theme-switcher">
                    <button type="button" class="theme-btn active" id="btn-theme-dark">Dark</button>
                    <button type="button" class="theme-btn" id="btn-theme-light">White</button>
                </div>

                <a href="{{ route('onboarding.create') }}" class="btn-outline">+ New Company</a>

                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-outline" style="color: #ef4444;">Sign Out</button>
                </form>
            </div>
        </header>

        <!-- KPI Summary Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Companies</div>
                <div class="kpi-value">{{ number_format($totalMerchants) }}</div>
                <div class="kpi-sub">Registered SaaS Tenants</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Platform Users</div>
                <div class="kpi-value">{{ number_format($totalUsers) }}</div>
                <div class="kpi-sub">Admins & team members</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Platform MRR</div>
                <div class="kpi-value">${{ number_format($totalMRR, 2) }}</div>
                <div class="kpi-sub">Active subscription revenue</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Metered Events</div>
                <div class="kpi-value">{{ number_format($totalEvents) }}</div>
                <div class="kpi-sub">Raw usage ticks recorded</div>
            </div>
        </div>

        <!-- Section 1: Tenant Monitoring Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Companies & Tenancy Status</h2>
                <span class="badge-live">Row-Level Scoped</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company</th>
                            <th>Admin Contact</th>
                            <th>Active Plan</th>
                            <th>Current Usage</th>
                            <th>Customers</th>
                            <th>Tenancy Mode</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $m)
                            <tr>
                                <td>#{{ $m['id'] }}</td>
                                <td>
                                    <strong>{{ $m['company_name'] }}</strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $m['email'] }}</div>
                                </td>
                                <td>
                                    {{ $m['admin_name'] }}
                                    <div style="font-size: 11px; color: var(--text-muted);">{{ $m['admin_email'] }}</div>
                                </td>
                                <td>
                                    <strong>{{ $m['plan_name'] }}</strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">${{ number_format($m['base_price'], 2) }} / mo</div>
                                </td>
                                <td>
                                    {{ number_format($m['total_usage']) }}
                                    <span style="font-size: 11px; color: var(--text-muted);">/ {{ number_format($m['included_units']) }}</span>
                                </td>
                                <td>{{ $m['customers_count'] }}</td>
                                <td>
                                    <span class="tag-pill">Row-Level Tenancy</span>
                                </td>
                                <td>
                                    <a href="{{ route('merchant.dashboard', ['id' => $m['id']]) }}" class="btn-inspect">Inspect &rarr;</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted);">No tenants registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: All Users Data ("Super admin can see all users data") -->
        <div class="card">
            <div class="card-header">
                <h2>All Platform Users (All Tenants)</h2>
                <span class="badge-live">{{ $allUsers->count() }} Total Users</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Assigned Company</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allUsers as $u)
                            <tr>
                                <td>#{{ $u->id }}</td>
                                <td><strong>{{ $u->name }}</strong></td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    @if($u->role === 'super_admin')
                                        <span class="tag-pill tag-super">Super Admin</span>
                                    @elseif($u->role === 'admin')
                                        <span class="tag-pill tag-admin">Company Admin</span>
                                    @else
                                        <span class="tag-pill tag-user">Tenant User</span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->merchant)
                                        {{ $u->merchant->company_name ?? $u->merchant->name }}
                                    @else
                                        <span style="color: var(--text-muted);">Platform Global</span>
                                    @endif
                                </td>
                                <td>{{ $u->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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

        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(currentTheme);
    </script>
</body>
</html>

