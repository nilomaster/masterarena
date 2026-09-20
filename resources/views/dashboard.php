<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Master Arena SaaS - Administrative Web Dashboard -->
    <!-- Comments strictly in ASCII only. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MASTER ARENA — Painel do Gestor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0a0e17;
            --bg-sidebar: #0f1626;
            --bg-card: rgba(18, 26, 43, 0.7);
            --bg-card-hover: rgba(26, 36, 60, 0.85);
            --accent-green: #10b981;
            --accent-lime: #00f279;
            --accent-cyan: #06b6d4;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(16, 185, 129, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Navigation */
        aside.sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.5px;
        }

        .brand-icon {
            background: linear-gradient(135deg, var(--accent-lime), var(--accent-green));
            color: #031509;
            font-size: 0.85rem;
            font-weight: 900;
            padding: 5px 10px;
            border-radius: 8px;
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.3);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 12px;
            flex: 1;
            overflow-y: auto;
        }

        .menu-category {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.8px;
            padding: 12px 14px 6px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .menu-item a:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.04);
        }

        .menu-item.active a {
            color: #fff;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(6, 182, 212, 0.15));
            border: 1px solid var(--border-glow);
            font-weight: 600;
        }

        .menu-icon {
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.15);
        }

        .user-preview {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .user-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role-badge {
            font-size: 0.72rem;
            color: var(--accent-lime);
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Main Workspace Content */
        main.main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Topbar Header */
        header.topbar {
            height: 72px;
            background: rgba(10, 14, 23, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .arena-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--border-glow);
            color: var(--accent-lime);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-lime);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent-lime);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.25);
            color: #fff;
        }

        /* Dashboard Body Content */
        .content-body {
            padding: 32px;
            flex: 1;
        }

        .welcome-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.1));
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-title {
            font-size: 1.65rem;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .welcome-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Metrics Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            backdrop-filter: blur(12px);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-glow);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .metric-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .metric-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .icon-green { background: rgba(16, 185, 129, 0.15); color: var(--accent-lime); }
        .icon-cyan { background: rgba(6, 182, 212, 0.15); color: var(--accent-cyan); }
        .icon-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .icon-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        .metric-value {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .metric-trend {
            font-size: 0.78rem;
            color: var(--accent-lime);
            font-weight: 600;
        }

        /* Two Columns Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .panel-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--border-glow);
            transform: translateX(4px);
        }

        /* Table styles */
        table.arena-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        table.arena-table th {
            padding: 10px 12px;
            text-align: left;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.arena-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .badge-status-active {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-lime);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <a href="/dashboard" class="sidebar-brand">
            <span class="brand-icon">MA</span>
            <span>MASTER ARENA</span>
        </a>

        <ul class="sidebar-menu">
            <li class="menu-category">Principal</li>
            <li class="menu-item active">
                <a href="/dashboard">
                    <span class="menu-icon">&#128202;</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#arenas">
                    <span class="menu-icon">&#127970;</span>
                    <span>Arenas & Unidades</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#quadras">
                    <span class="menu-icon">&#127934;</span>
                    <span>Quadras & Esportes</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#agendamentos">
                    <span class="menu-icon">&#128197;</span>
                    <span>Grade de Horarios</span>
                </a>
            </li>

            <li class="menu-category">Gestao & Operacao</li>
            <li class="menu-item">
                <a href="#financeiro">
                    <span class="menu-icon">&#128179;</span>
                    <span>Financeiro & Caixa</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#comandas">
                    <span class="menu-icon">&#127865;</span>
                    <span>Bar & Comandas</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#configuracoes">
                    <span class="menu-icon">&#9881;</span>
                    <span>Configuracoes</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-preview">
                <span class="user-name" id="sidebarUserName">Carregando...</span>
                <span class="user-role-badge" id="sidebarUserRole">GESTOR</span>
            </div>
            <button class="btn-logout" onclick="handleLogout()" title="Encerrar sessao">&#10140;</button>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <div class="arena-pill">
                    <span class="pulse-dot"></span>
                    <span id="topbarArenaName">Arena Master Beach</span>
                </div>
            </div>

            <div class="topbar-right">
                <span style="font-size: 0.88rem; color: var(--text-muted);" id="topbarUserEmail">usuario@masterarena.com.br</span>
                <button class="btn-logout" onclick="handleLogout()">Sair do Sistema</button>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div>
                    <h1 class="welcome-title">Ola, <span id="bannerGreetingName">Gestor</span>!</h1>
                    <p class="welcome-subtitle">Bem-vindo ao centro de operacoes do Master Arena SaaS.</p>
                </div>
                <div>
                    <a href="/" target="_blank" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;">
                        Ver Portal Publico &rarr;
                    </a>
                </div>
            </div>

            <!-- Metrics Cards Grid -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-label">Quadras Ativas</span>
                        <div class="metric-icon-box icon-green">&#127934;</div>
                    </div>
                    <div class="metric-value">4</div>
                    <span class="metric-trend">&#10004; 100% operacionais</span>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-label">Reservas Hoje</span>
                        <div class="metric-icon-box icon-cyan">&#128197;</div>
                    </div>
                    <div class="metric-value">12</div>
                    <span class="metric-trend">&#8593; 85% de ocupacao</span>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-label">Previsao de Caixa</span>
                        <div class="metric-icon-box icon-purple">&#128176;</div>
                    </div>
                    <div class="metric-value">R$ 1.840</div>
                    <span class="metric-trend">&#10004; Pagamentos via PIX</span>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-label">Status SaaS</span>
                        <div class="metric-icon-box icon-amber">&#9889;</div>
                    </div>
                    <div class="metric-value" style="font-size: 1.4rem; color: var(--accent-lime);">ONLINE</div>
                    <span class="metric-trend">API V1 REST Conectada</span>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="dashboard-grid">
                <!-- Left: Arenas / Quadras Table -->
                <div class="panel-box" id="arenas">
                    <div class="panel-header">
                        <h2 class="panel-title">Complexos Esportivos & Arenas</h2>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Multi-Tenancy Ativo</span>
                    </div>

                    <table class="arena-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Arena</th>
                                <th>Slug da URL</th>
                                <th>Cidade/UF</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="arenasTableBody">
                            <tr>
                                <td>#1</td>
                                <td><strong>Arena Master Beach & Sports</strong></td>
                                <td><code>arena-master-beach</code></td>
                                <td>Sao Paulo / SP</td>
                                <td><span class="badge-status-active">ATIVO</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right: Quick Actions -->
                <div class="panel-box">
                    <div class="panel-header">
                        <h2 class="panel-title">Acoes Rapidas</h2>
                    </div>

                    <a href="#nova-reserva" class="quick-action-btn" onclick="alert('Modulo de Agendamento em desenvolvimento na ETAPA 5.')">
                        <span style="font-size: 1.3rem;">&#10010;</span>
                        <span>Novo Agendamento</span>
                    </a>

                    <a href="#bloqueio" class="quick-action-btn" onclick="alert('Modulo de Bloqueio em desenvolvimento na ETAPA 5.')">
                        <span style="font-size: 1.3rem;">&#128274;</span>
                        <span>Bloquear Horario de Quadra</span>
                    </a>

                    <a href="/setup/users" class="quick-action-btn">
                        <span style="font-size: 1.3rem;">&#128101;</span>
                        <span>Gerenciar Usuarios & Senhas</span>
                    </a>

                    <a href="/api/v1/health" target="_blank" class="quick-action-btn">
                        <span style="font-size: 1.3rem;">&#9881;</span>
                        <span>Diagnostico da API REST</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Client-side Session Validation and Hydration -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rawUser = sessionStorage.getItem('masterarena_user');
            const token = sessionStorage.getItem('masterarena_token');

            if (!rawUser || !token) {
                // If not authenticated, redirect to login home
                window.location.href = '/';
                return;
            }

            try {
                const user = JSON.parse(rawUser);
                document.getElementById('sidebarUserName').innerText = user.nome || 'Usuario';
                document.getElementById('sidebarUserRole').innerText = user.perfil || 'GESTOR';
                document.getElementById('topbarUserEmail').innerText = user.email || '';
                document.getElementById('bannerGreetingName').innerText = (user.nome || '').split(' ')[0] || 'Gestor';

                if (user.arena && user.arena.nome_arena) {
                    document.getElementById('topbarArenaName').innerText = user.arena.nome_arena;
                } else if (user.perfil === 'SUPERADMIN') {
                    document.getElementById('topbarArenaName').innerText = 'Superadmin Global (Todas Arenas)';
                }
            } catch (e) {
                // Ignore parse errors
            }
        });

        async function handleLogout() {
            const token = sessionStorage.getItem('masterarena_token');
            if (token) {
                try {
                    await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });
                } catch (e) {
                    // ignore network errors on logout
                }
            }
            sessionStorage.removeItem('masterarena_token');
            sessionStorage.removeItem('masterarena_user');
            window.location.href = '/';
        }
    </script>
</body>
</html>
