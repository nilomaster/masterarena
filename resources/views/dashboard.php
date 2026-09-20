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
            cursor: pointer;
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

        /* Tab views animation */
        .tab-view {
            display: none;
            animation: fadeIn 0.25s ease forwards;
        }

        .tab-view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
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
            margin-bottom: 24px;
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

        .badge-status-maintenance {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-status-inactive {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .action-btn-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            color: #fff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn-pill:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--border-glow);
        }

        .action-btn-primary {
            background: linear-gradient(135deg, var(--accent-lime), var(--accent-green));
            border: none;
            color: #031509;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn-primary:hover {
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.4);
            transform: translateY(-1px);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
        }

        .info-item-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .info-item-val {
            font-size: 0.95rem;
            color: var(--text-main);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <a href="javascript:void(0)" onclick="switchTab('dashboard')" class="sidebar-brand">
            <span class="brand-icon">MA</span>
            <span>MASTER ARENA</span>
        </a>

        <ul class="sidebar-menu">
            <li class="menu-category">Principal</li>
            <li class="menu-item active" id="menu-dashboard">
                <a href="javascript:void(0)" onclick="switchTab('dashboard')">
                    <span class="menu-icon">&#128202;</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item" id="menu-quadras">
                <a href="javascript:void(0)" onclick="switchTab('quadras')">
                    <span class="menu-icon">&#127934;</span>
                    <span>Quadras & Esportes</span>
                </a>
            </li>
            <li class="menu-item" id="menu-arenas">
                <a href="javascript:void(0)" onclick="switchTab('arenas')">
                    <span class="menu-icon">&#127970;</span>
                    <span>Arenas & Unidades</span>
                </a>
            </li>
            <li class="menu-item" id="menu-agendamentos">
                <a href="javascript:void(0)" onclick="switchTab('agendamentos')">
                    <span class="menu-icon">&#128197;</span>
                    <span>Grade de Horarios</span>
                </a>
            </li>

            <li class="menu-category">Gestao & Operacao</li>
            <li class="menu-item" id="menu-financeiro">
                <a href="javascript:void(0)" onclick="switchTab('financeiro')">
                    <span class="menu-icon">&#128179;</span>
                    <span>Financeiro & Caixa</span>
                </a>
            </li>
            <li class="menu-item" id="menu-comandas">
                <a href="javascript:void(0)" onclick="switchTab('comandas')">
                    <span class="menu-icon">&#127865;</span>
                    <span>Bar & Comandas</span>
                </a>
            </li>
            <li class="menu-item" id="menu-whatsapp">
                <a href="javascript:void(0)" onclick="switchTab('whatsapp')">
                    <span class="menu-icon">&#128241;</span>
                    <span>WhatsApp</span>
                </a>
            </li>
            <li class="menu-item" id="menu-configuracoes">
                <a href="javascript:void(0)" onclick="switchTab('configuracoes')">
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

            <!-- TAB 1: DASHBOARD OVERVIEW -->
            <section id="view-dashboard" class="tab-view active">
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
                        <div class="metric-value" id="activeCourtsCount">...</div>
                        <span class="metric-trend" id="activeCourtsTrend">&#10004; Carregando quadras...</span>
                    </div>

                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="metric-label">Reservas Hoje</span>
                            <div class="metric-icon-box icon-cyan">&#128197;</div>
                        </div>
                        <div class="metric-value" id="todayBookingsCount">0</div>
                        <span class="metric-trend" id="todayBookingsTrend">&#10004; Carregando reservas...</span>
                    </div>


                    <div class="metric-card">
                        <div class="metric-header">
                            <span class="metric-label">Faturamento Hoje</span>
                            <div class="metric-icon-box icon-purple">&#128176;</div>
                        </div>
                        <div class="metric-value" id="todayRevenueVal">R$ 0,00</div>
                        <span class="metric-trend" id="todayRevenueTrend">&#10004; Carregando caixa...</span>
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
                    <!-- Left: Quadras Preview Table -->
                    <div class="panel-box">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Quadras & Modalidades da Arena</h2>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Gerenciamento Operacional em Tempo Real</span>
                            </div>
                            <button onclick="switchTab('quadras')" class="action-btn-pill">
                                Ver Todas &rarr;
                            </button>
                        </div>

                        <table class="arena-table">
                            <thead>
                                <tr>
                                    <th>Quadra</th>
                                    <th>Modalidade</th>
                                    <th>Capacidade</th>
                                    <th>Valor/Hora</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Acao</th>
                                </tr>
                            </thead>
                            <tbody id="dashboardCourtsBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                        Carregando quadras cadastradas...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right: Quick Actions -->
                    <div class="panel-box">
                        <div class="panel-header">
                            <h2 class="panel-title">Acoes Rapidas</h2>
                        </div>

                        <a href="javascript:void(0)" class="quick-action-btn" onclick="promptAddCourt()">
                            <span style="font-size: 1.3rem;">&#10010;</span>
                            <span>Cadastrar Nova Quadra</span>
                        </a>

                        <a href="javascript:void(0)" class="quick-action-btn" onclick="promptAddModalidade()">
                            <span style="font-size: 1.3rem;">&#127934;</span>
                            <span>Nova Modalidade Esportiva</span>
                        </a>

                        <a href="javascript:void(0)" class="quick-action-btn" onclick="switchTab('agendamentos')">
                            <span style="font-size: 1.3rem;">&#128197;</span>
                            <span>Visualizar Grade de Horarios</span>
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
            </section>

            <!-- TAB 2: QUADRAS & ESPORTES (DEDICATED FULL VIEW) -->
            <section id="view-quadras" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Gestao de Quadras e Modalidades Esportivas</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Cadastre quadras fisicas, defina capacidades, valores por hora e modalidades.</span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="promptAddModalidade()" class="action-btn-pill">
                                + Nova Modalidade
                            </button>
                            <button onclick="promptAddCourt()" class="action-btn-primary">
                                + Nova Quadra
                            </button>
                        </div>
                    </div>

                    <!-- Full Courts Table -->
                    <h3 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--accent-cyan); font-weight: 700;">Quadras Cadastradas</h3>
                    <table class="arena-table" style="margin-bottom: 32px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Quadra</th>
                                <th>Modalidade</th>
                                <th>Capacidade</th>
                                <th>Valor/Hora</th>
                                <th>Status Operacional</th>
                                <th style="text-align: right;">Acoes</th>
                            </tr>
                        </thead>
                        <tbody id="fullCourtsTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    Carregando quadras...
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Sports List Table -->
                    <h3 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--accent-lime); font-weight: 700;">Modalidades Esportivas Disponiveis</h3>
                    <table class="arena-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Modalidade</th>
                                <th>Descricao</th>
                                <th>Icone</th>
                                <th>Status</th>
                                <th style="text-align: right;">Acoes</th>
                            </tr>
                        </thead>
                        <tbody id="modalidadesTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    Carregando modalidades esportivas...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- TAB 3: ARENAS & UNIDADES -->
            <section id="view-arenas" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Complexos Esportivos & Arenas</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Dados da unidade atual e informacoes cadastrais multi-tenant.</span>
                        </div>
                    </div>

                    <div class="info-grid" id="arenaInfoGrid">
                        <div class="info-item">
                            <div class="info-item-label">Nome da Arena</div>
                            <div class="info-item-val" id="arenaDetailNome">Carregando...</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Slug de Acesso Publico</div>
                            <div class="info-item-val" id="arenaDetailSlug">Carregando...</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">WhatsApp de Contato</div>
                            <div class="info-item-val" id="arenaDetailWhatsapp">Carregando...</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Localizacao</div>
                            <div class="info-item-val" id="arenaDetailCidade">Carregando...</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Status Operacional</div>
                            <div class="info-item-val" id="arenaDetailStatus">Carregando...</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Isolamento Multi-Tenant</div>
                            <div class="info-item-val" style="color: var(--accent-lime);">&#10004; Tenant Shield Ativo</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 4: GRADE DE HORARIOS E DISPONIBILIDADE -->
            <section id="view-agendamentos" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header" style="flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h2 class="panel-title">Grade de Horarios & Disponibilidade de Quadras</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);" id="gradeHeaderSubtitle">
                                Visualize slots livres, precos dinamicos, reservas e bloqueios operacionais em tempo real.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="date" id="gradeDateInput" onchange="loadGrade()" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: #fff; padding: 8px 12px; border-radius: 8px; font-size: 0.88rem; outline: none;">
                            <button onclick="setGradeToday()" class="action-btn-pill">Hoje</button>
                            <button onclick="setGradeTomorrow()" class="action-btn-pill">Amanha</button>
                            <button onclick="promptCreateBlock()" class="action-btn-primary">+ Bloquear Horario</button>
                        </div>
                    </div>

                    <!-- Day info badge -->
                    <div id="gradeDaySummary" style="display: flex; align-items: center; gap: 12px; background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: 10px; padding: 12px 18px; margin-bottom: 24px; font-size: 0.88rem;">
                        <span style="font-size: 1.2rem;">&#128197;</span>
                        <span id="gradeDaySummaryText">Carregando dados da grade...</span>
                    </div>

                    <!-- Courts Grid Columns -->
                    <div id="gradeCourtsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        <div style="text-align: center; color: var(--text-muted); padding: 36px; grid-column: 1 / -1;">
                            Carregando grade de disponibilidade...
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 5: FINANCEIRO & CAIXA -->
            <section id="view-financeiro" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header" style="flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h2 class="panel-title">Financeiro, Pagamentos & Caixa Balcao</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Controle de fluxo de caixa, recebimentos via PIX, cartoes e conciliacao de agendamentos.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <button onclick="loadFinancialSummary()" class="action-btn-pill">&#8635; Atualizar</button>
                            <button id="btnOpenCaixa" onclick="openCaixaModal()" class="action-btn-primary">+ Abrir Caixa</button>
                            <button id="btnMovCaixa" onclick="openMovementModal()" class="action-btn-pill" style="display: none;">+ Sangria / Suprimento</button>
                            <button id="btnCloseCaixa" onclick="closeCaixaModal()" class="action-btn-pill" style="display: none; background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5;">Encerrar Caixa</button>
                        </div>
                    </div>

                    <!-- Caixa Shift Status Banner -->
                    <div id="caixaStatusBanner" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 1.4rem;">&#128188;</span>
                            <div>
                                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Status do Caixa da Arena</div>
                                <div id="caixaStatusText" style="font-size: 1.05rem; font-weight: 700; color: var(--accent-lime);">Consultando caixa...</div>
                            </div>
                        </div>
                        <div id="caixaDetailsBox" style="display: flex; gap: 20px; font-size: 0.88rem;">
                            <!-- Injected by JS -->
                        </div>
                    </div>

                    <!-- Financial Summary Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
                        <div class="info-item">
                            <div class="info-item-label">Faturamento Bruto</div>
                            <div class="info-item-val" id="finFaturamentoBruto" style="color: var(--accent-lime); font-size: 1.25rem;">R$ 0,00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Recebido via PIX</div>
                            <div class="info-item-val" id="finPixTotal" style="color: var(--accent-cyan);">R$ 0,00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Recebido em Dinheiro</div>
                            <div class="info-item-val" id="finDinheiroTotal">R$ 0,00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Cartao (Cred/Deb)</div>
                            <div class="info-item-val" id="finCartaoTotal">R$ 0,00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Despesas Operacionais</div>
                            <div class="info-item-val" id="finDespesasTotal" style="color: #f87171;">R$ 0,00</div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <h3 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--accent-lime); font-weight: 700;">Extrato de Lancamentos Recentes</h3>
                    <table class="arena-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Descricao</th>
                                <th>Cliente</th>
                                <th>Metodo</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="pagamentosTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    Carregando movimentacoes financeiras...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>


            <!-- TAB 6: BAR & COMANDAS -->
            <section id="view-comandas" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Bar, Lanchonete & Comandas</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Ponto de venda rapido para consumo de bebidas e locacao de equipamentos.</span>
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 36px; text-align: center;">
                        <div style="font-size: 2.5rem; margin-bottom: 12px;">&#127865;</div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">Ponto de Venda (PDV) e Comandas Eletronicas</h3>
                        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto; font-size: 0.92rem;">
                            Planejado para a <strong>ETAPA 9</strong> (Cardapio, controle de estoque e comandas por QR Code).
                        </p>
                    </div>
                </div>
            </section>

            <!-- TAB 7: CONFIGURACOES OPERACIONAIS -->
            <section id="view-configuracoes" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Configuracoes Operacionais da Arena</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Defina parametros de funcionamento, tolerancia de cancelamento e chaves PIX.</span>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-item-label">Horario Padrao de Abertura</div>
                            <div class="info-item-val">06:00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Horario Padrao de Fechamento</div>
                            <div class="info-item-val">23:00</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Intervalo de Slots</div>
                            <div class="info-item-val">60 minutos</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Seguranca de Acesso</div>
                            <div class="info-item-val" style="color: var(--accent-lime);">JWT HMAC-SHA256 Ativo</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 8: NOTIFICACOES AUTOMATICAS WHATSAPP -->
            <section id="view-whatsapp" class="tab-view">
                <div class="panel-box">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Notificacoes Automaticas via WhatsApp</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Configure o gateway (Evolution API, Z-API ou Simulador), habilite disparos de PIX, confirmacoes e lembretes de jogo.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="runWhatsAppReminders()" class="action-btn-pill" style="border-color: rgba(6, 182, 212, 0.4); color: var(--accent-cyan);">
                                &#128260; Rodar Varredura de Lembretes
                            </button>
                            <button onclick="saveWhatsAppConfig()" class="action-btn-primary">
                                &#128190; Salvar Configuracoes
                            </button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px; margin-top: 16px;">
                        <!-- Left: Gateway Parameters & Automatic Events -->
                        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 12px; padding: 20px;">
                            <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; color: var(--accent-lime); display: flex; align-items: center; gap: 8px;">
                                &#9881; Parametros do Gateway de WhatsApp
                            </h3>

                            <div style="margin-bottom: 14px;">
                                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Provedor / Driver</label>
                                <select id="wa_provider" style="width: 100%; padding: 10px 14px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.9rem;">
                                    <option value="SIMULATOR">Simulador de Testes (Mock Nativo - Sem envio real)</option>
                                    <option value="EVOLUTION_API">Evolution API (v1 / v2 Self-Hosted)</option>
                                    <option value="ZAPI">Z-API (SaaS Oficial)</option>
                                </select>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">URL da API</label>
                                    <input type="text" id="wa_api_url" placeholder="https://api.meuservidor.com" style="width: 100%; padding: 9px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.85rem;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Instancia / Session ID</label>
                                    <input type="text" id="wa_instance" placeholder="arena-matriz" style="width: 100%; padding: 9px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.85rem;">
                                </div>
                            </div>

                            <div style="margin-bottom: 18px;">
                                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Token de Autenticacao / API Key</label>
                                <input type="password" id="wa_api_token" placeholder="Bearer Token ou Global Key" style="width: 100%; padding: 9px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.85rem;">
                            </div>

                            <h4 style="font-size: 0.85rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; border-top: 1px solid var(--border); padding-top: 14px;">
                                Eventos de Disparo Automatico
                            </h4>

                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                                    <input type="checkbox" id="wa_notify_pix" style="accent-color: var(--accent-lime); width: 16px; height: 16px;">
                                    <span><strong>PIX Copia e Cola Gerado:</strong> Disparar chave e QR Code imediatamente</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                                    <input type="checkbox" id="wa_notify_confirmed" style="accent-color: var(--accent-lime); width: 16px; height: 16px;">
                                    <span><strong>Reserva Confirmada:</strong> Enviar comprovante e codigo de check-in</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                                    <input type="checkbox" id="wa_notify_cancelled" style="accent-color: var(--accent-lime); width: 16px; height: 16px;">
                                    <span><strong>Reserva Cancelada:</strong> Notificar liberacao do horario</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                                    <input type="checkbox" id="wa_notify_reminder" style="accent-color: var(--accent-lime); width: 16px; height: 16px;">
                                    <span><strong>Lembrete Previo de Jogo:</strong> Notificar atletas antes da partida</span>
                                </label>
                            </div>

                            <div style="margin-top: 14px; display: flex; align-items: center; gap: 12px;">
                                <label style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Antecedencia do Lembrete:</label>
                                <select id="wa_reminder_hours" style="padding: 6px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 6px; color: #fff; font-size: 0.85rem;">
                                    <option value="1">1 hora antes</option>
                                    <option value="2" selected>2 horas antes (Recomendado)</option>
                                    <option value="4">4 horas antes</option>
                                    <option value="24">24 horas antes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Fast Test Message Box -->
                        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; color: var(--accent-cyan); display: flex; align-items: center; gap: 8px;">
                                    &#128172; Teste de Disparo em Tempo Real
                                </h3>
                                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                                    Envie uma mensagem instantanea para verificar se sua instancia e credenciais estao conectadas corretamente.
                                </p>

                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Numero do Destinatario</label>
                                    <input type="text" id="wa_test_phone" placeholder="Ex: 11999998888 ou 5511999998888" style="width: 100%; padding: 9px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.85rem;">
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Mensagem de Teste</label>
                                    <textarea id="wa_test_message" rows="3" style="width: 100%; padding: 9px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.85rem; resize: vertical;">Ola! Esta e uma mensagem de teste do Master Arena SaaS.</textarea>
                                </div>

                                <button onclick="sendWhatsAppTest()" class="action-btn-pill" style="width: 100%; padding: 10px; background: rgba(6, 182, 212, 0.15); border-color: rgba(6, 182, 212, 0.4); color: var(--accent-cyan); font-weight: 700; font-size: 0.88rem;">
                                    &#128640; Disparar Mensagem de Teste
                                </button>
                            </div>

                            <div id="wa_test_result" style="margin-top: 14px; padding: 10px 14px; border-radius: 8px; font-size: 0.8rem; display: none;">
                                <!-- Feedback info -->
                            </div>
                        </div>
                    </div>

                    <!-- Notification Audit Logs Table -->
                    <div style="margin-top: 28px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <h3 style="font-size: 1.05rem; font-weight: 700;">
                                Historico e Auditoria de Notificacoes
                            </h3>
                            <button onclick="loadWhatsAppLogs()" class="action-btn-pill">
                                &#128260; Atualizar Extrato
                            </button>
                        </div>

                        <table class="arena-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data / Hora</th>
                                    <th>Destinatario</th>
                                    <th>Evento</th>
                                    <th>Provedor</th>
                                    <th>Status</th>
                                    <th>ID Externo / Resposta</th>
                                </tr>
                            </thead>
                            <tbody id="waLogsBody">
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                        Carregando historico de disparos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Client-side SPA Session and Interactive Logic -->
    <script>
        let currentArenaId = 1;
        let cachedModalidades = [];

        document.addEventListener('DOMContentLoaded', () => {
            const rawUser = sessionStorage.getItem('masterarena_user');
            const token = sessionStorage.getItem('masterarena_token');

            if (!rawUser || !token) {
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
                    currentArenaId = user.arena.id;
                } else if (user.arena_id) {
                    currentArenaId = user.arena_id;
                } else if (user.perfil === 'SUPERADMIN') {
                    document.getElementById('topbarArenaName').innerText = 'Superadmin Global (Arena 1)';
                    currentArenaId = 1;
                }

                // Check initial hash in URL (e.g. #quadras)
                const hash = window.location.hash.replace('#', '');
                if (hash && document.getElementById(`view-${hash}`)) {
                    switchTab(hash);
                } else {
                    loadCourts();
                    loadBookingStats();
                }

                loadArenaDetails();
            } catch (e) {
                // Ignore parse errors
            }
        });

        // Tab Navigation Switcher
        function switchTab(tabId) {
            // Update active class on menu items
            document.querySelectorAll('.sidebar-menu .menu-item').forEach(item => {
                item.classList.remove('active');
            });
            const selectedMenu = document.getElementById(`menu-${tabId}`);
            if (selectedMenu) {
                selectedMenu.classList.add('active');
            }

            // Show selected view and hide others
            document.querySelectorAll('.tab-view').forEach(view => {
                view.classList.remove('active');
            });
            const targetView = document.getElementById(`view-${tabId}`);
            if (targetView) {
                targetView.classList.add('active');
            }

            // Sync URL hash
            window.location.hash = tabId;

            // Trigger specific data fetching
            if (tabId === 'dashboard' || tabId === 'quadras') {
                loadCourts();
                loadModalidades();
                if (tabId === 'dashboard') {
                    loadBookingStats();
                }
            } else if (tabId === 'arenas') {
                loadArenaDetails();
            } else if (tabId === 'agendamentos') {
                loadGrade();
            } else if (tabId === 'financeiro') {
                loadFinancialSummary();
            } else if (tabId === 'whatsapp') {
                loadWhatsAppConfig();
                loadWhatsAppLogs();
            }
        }

        // Fetch real-time booking statistics for dashboard card
        async function loadBookingStats() {
            const token = sessionStorage.getItem('masterarena_token');
            if (!token || !currentArenaId) return;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/agendamentos/stats`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.data) {
                        const count = data.data.reservas_hoje ?? 0;
                        const total = data.data.total_reservas ?? 0;
                        const el = document.getElementById('todayBookingsCount');
                        const trendEl = document.getElementById('todayBookingsTrend');
                        if (el) el.innerText = count;
                        if (trendEl) trendEl.innerText = `${total} reservas no total`;
                    }
                }
            } catch (e) {
                // Ignore stats fetch error
            }

            // Also load financial summary for today revenue card
            loadFinancialSummary();
        }

        // Fetch financial metrics, cash register status and transactions
        async function loadFinancialSummary() {
            const token = sessionStorage.getItem('masterarena_token');
            if (!token || !currentArenaId) return;

            try {
                // 1. Financial summary metrics
                const resSum = await fetch(`/api/v1/arenas/${currentArenaId}/financeiro/resumo`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                if (resSum.ok) {
                    const data = await resSum.json();
                    const r = data.data && data.data.resumo ? data.data.resumo : null;
                    if (r) {
                        const fatBruto = parseFloat(r.faturamento_bruto || 0).toFixed(2).replace('.', ',');
                        const cardVal = document.getElementById('todayRevenueVal');
                        const cardTrend = document.getElementById('todayRevenueTrend');
                        if (cardVal) cardVal.innerText = `R$ ${fatBruto}`;
                        if (cardTrend) cardTrend.innerText = `${r.quantidade_transacoes_pagas || 0} transacoes liquidadas`;

                        const finBrutoEl = document.getElementById('finFaturamentoBruto');
                        if (finBrutoEl) finBrutoEl.innerText = `R$ ${fatBruto}`;

                        const metodos = r.metodos || {};
                        const pixEl = document.getElementById('finPixTotal');
                        if (pixEl) pixEl.innerText = `R$ ${parseFloat(metodos.PIX || 0).toFixed(2).replace('.', ',')}`;

                        const dinEl = document.getElementById('finDinheiroTotal');
                        if (dinEl) dinEl.innerText = `R$ ${parseFloat(metodos.DINHEIRO || 0).toFixed(2).replace('.', ',')}`;

                        const cartaoTotal = (parseFloat(metodos.CARTAO_CREDITO || 0) + parseFloat(metodos.CARTAO_DEBITO || 0)).toFixed(2).replace('.', ',');
                        const cardEl = document.getElementById('finCartaoTotal');
                        if (cardEl) cardEl.innerText = `R$ ${cartaoTotal}`;

                        const despEl = document.getElementById('finDespesasTotal');
                        if (despEl) despEl.innerText = `R$ ${parseFloat(r.total_despesas || 0).toFixed(2).replace('.', ',')}`;
                    }
                }

                // 2. Cash register status
                const resCaixa = await fetch(`/api/v1/arenas/${currentArenaId}/caixa/status`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                if (resCaixa.ok) {
                    const data = await resCaixa.json();
                    const c = data.data && data.data.caixa ? data.data.caixa : null;
                    const statusText = document.getElementById('caixaStatusText');
                    const detailsBox = document.getElementById('caixaDetailsBox');
                    const btnOpen = document.getElementById('btnOpenCaixa');
                    const btnMov = document.getElementById('btnMovCaixa');
                    const btnClose = document.getElementById('btnCloseCaixa');

                    if (c && c.caixa_aberto && c.sessao) {
                        if (statusText) {
                            statusText.innerText = `ABERTO (Turno #${c.sessao.id})`;
                            statusText.style.color = 'var(--accent-lime)';
                        }
                        if (btnOpen) btnOpen.style.display = 'none';
                        if (btnMov) btnMov.style.display = 'inline-block';
                        if (btnClose) btnClose.style.display = 'inline-block';

                        const b = c.balanco || {};
                        if (detailsBox) {
                            detailsBox.innerHTML = `
                                <div><strong style="color: var(--text-muted);">Operador:</strong> ${escapeHtml(c.sessao.usuario_abertura_nome || 'Equipe')}</div>
                                <div><strong style="color: var(--text-muted);">Fundo Inicial:</strong> R$ ${parseFloat(b.saldo_inicial || 0).toFixed(2).replace('.', ',')}</div>
                                <div><strong style="color: var(--accent-cyan);">Gaveta Esperada:</strong> R$ ${parseFloat(b.saldo_dinheiro_esperado || 0).toFixed(2).replace('.', ',')}</div>
                            `;
                        }
                    } else {
                        if (statusText) {
                            statusText.innerText = 'FECHADO (Nenhum turno aberto)';
                            statusText.style.color = '#94a3b8';
                        }
                        if (btnOpen) btnOpen.style.display = 'inline-block';
                        if (btnMov) btnMov.style.display = 'none';
                        if (btnClose) btnClose.style.display = 'none';
                        if (detailsBox) detailsBox.innerHTML = '<span style="color: var(--text-muted);">Clique em "+ Abrir Caixa" para iniciar as operacoes do turno.</span>';
                    }
                }

                // 3. Transactions table
                const resPag = await fetch(`/api/v1/arenas/${currentArenaId}/pagamentos?per_page=15`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                if (resPag.ok) {
                    const data = await resPag.json();
                    const pagamentos = data.data && data.data.pagamentos ? data.data.pagamentos : [];
                    const tbody = document.getElementById('pagamentosTableBody');
                    if (tbody) {
                        if (pagamentos.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Nenhuma transacao financeira registrada ate o momento.</td></tr>';
                        } else {
                            tbody.innerHTML = pagamentos.map(p => {
                                const isPago = p.status === 'PAGO';
                                const statusBg = isPago ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)';
                                const statusColor = isPago ? '#00f279' : '#fbbf24';
                                const isReceita = p.tipo === 'RECEITA';
                                const valColor = isReceita ? 'var(--text-main)' : '#f87171';
                                const sinal = isReceita ? '+' : '-';

                                return `
                                    <tr>
                                        <td><code>#${p.id}</code></td>
                                        <td style="color: var(--text-muted); font-size: 0.82rem;">${escapeHtml(p.created_at || '-')}</td>
                                        <td><strong>${escapeHtml(p.descricao || p.categoria)}</strong></td>
                                        <td>${escapeHtml(p.cliente_nome || 'Balcao / Geral')}</td>
                                        <td><span class="action-btn-pill" style="font-size: 0.72rem; padding: 2px 8px;">${p.metodo_pagamento}</span></td>
                                        <td style="font-weight: 700; color: ${valColor};">${sinal} R$ ${parseFloat(p.valor || 0).toFixed(2).replace('.', ',')}</td>
                                        <td>
                                            <span style="background: ${statusBg}; color: ${statusColor}; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700;">
                                                ${p.status}
                                            </span>
                                        </td>
                                    </tr>`;
                            }).join('');
                        }
                    }
                }
            } catch (e) {
                // Ignore financial load error
            }
        }

        // Open cash register shift
        async function openCaixaModal() {
            const valorInput = prompt('Informe o valor do Fundo de Troco inicial (ex: 100.00):', '100.00');
            if (valorInput === null) return;

            const saldoInicial = parseFloat(valorInput.replace(',', '.'));
            if (isNaN(saldoInicial) || saldoInicial < 0) {
                alert('Valor de saldo inicial invalido.');
                return;
            }

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/caixa/abrir`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ saldo_inicial: saldoInicial })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('Turno de caixa aberto com sucesso!');
                    loadFinancialSummary();
                } else {
                    alert(data.message || 'Erro ao abrir caixa.');
                }
            } catch (e) {
                alert('Erro de conexao ao abrir caixa.');
            }
        }

        // Cash register movement (Sangria / Suprimento)
        async function openMovementModal() {
            const tipo = prompt('Tipo de movimentacao (Digite SANGRIA para retirada ou SUPRIMENTO para reforco de troco):', 'SANGRIA');
            if (!tipo) return;
            const tipoUpper = tipo.trim().toUpperCase();
            if (!['SANGRIA', 'SUPRIMENTO', 'DESPESA'].includes(tipoUpper)) {
                alert('Tipo invalido. Escolha SANGRIA ou SUPRIMENTO.');
                return;
            }

            const valorInput = prompt('Informe o valor (ex: 50.00):', '50.00');
            if (valorInput === null) return;
            const valor = parseFloat(valorInput.replace(',', '.'));
            if (isNaN(valor) || valor <= 0) {
                alert('Valor invalido.');
                return;
            }

            const motivo = prompt('Informe o motivo ou justificativa:', tipoUpper === 'SANGRIA' ? 'Sangria para o cofre' : 'Reforco de troco');
            if (!motivo) return;

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/caixa/movimentacao`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ tipo: tipoUpper, valor, motivo })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('Movimentacao registrada com sucesso!');
                    loadFinancialSummary();
                } else {
                    alert(data.message || 'Erro ao registrar movimentacao.');
                }
            } catch (e) {
                alert('Erro de conexao ao movimentar caixa.');
            }
        }

        // Close cash register shift
        async function closeCaixaModal() {
            const valorInput = prompt('CONFERENCIA CEGA: Informe o valor em DINHEIRO FISICO contado na gaveta (ex: 350.00):', '0.00');
            if (valorInput === null) return;
            const saldoInformado = parseFloat(valorInput.replace(',', '.'));
            if (isNaN(saldoInformado) || saldoInformado < 0) {
                alert('Valor invalido.');
                return;
            }

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/caixa/fechar`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ saldo_informado: saldoInformado })
                });
                const data = await res.json();
                if (res.ok) {
                    const dif = parseFloat(data.data.diferenca || 0);
                    const statusDif = data.data.status_diferenca || 'EXATO';
                    let msg = `Turno encerrado com sucesso!\n\nSaldo Informado: R$ ${saldoInformado.toFixed(2)}\nSaldo Esperado pelo Sistema: R$ ${parseFloat(data.data.balanco.saldo_dinheiro_esperado || 0).toFixed(2)}\nResultado: ${statusDif} (Diferenca: R$ ${dif.toFixed(2)})`;
                    alert(msg);
                    loadFinancialSummary();
                } else {
                    alert(data.message || 'Erro ao encerrar caixa.');
                }
            } catch (e) {
                alert('Erro de conexao ao fechar caixa.');
            }
        }

        async function loadCourts() {

            const token = sessionStorage.getItem('masterarena_token');
            const dashTbody = document.getElementById('dashboardCourtsBody');
            const fullTbody = document.getElementById('fullCourtsTableBody');


            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/quadras`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                const quadras = data.data && data.data.quadras ? data.data.quadras : [];

                const activeCount = quadras.filter(q => q.status === 'ATIVO').length;
                document.getElementById('activeCourtsCount').innerText = activeCount;
                document.getElementById('activeCourtsTrend').innerText = `${activeCount} de ${quadras.length} operacionais`;

                if (quadras.length === 0) {
                    const emptyHtml = `
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                Nenhuma quadra cadastrada para esta arena. Clique em <strong>"+ Nova Quadra"</strong> para iniciar.
                            </td>
                        </tr>`;
                    if (dashTbody) dashTbody.innerHTML = emptyHtml;
                    if (fullTbody) fullTbody.innerHTML = emptyHtml;
                    return;
                }

                // Render Dashboard summary table
                if (dashTbody) {
                    dashTbody.innerHTML = quadras.slice(0, 5).map(q => {
                        const statusBg = q.status === 'ATIVO' ? 'rgba(16, 185, 129, 0.15)' : (q.status === 'MANUTENCAO' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(148, 163, 184, 0.15)');
                        const statusColor = q.status === 'ATIVO' ? '#00f279' : (q.status === 'MANUTENCAO' ? '#fbbf24' : '#94a3b8');
                        const nextStatus = q.status === 'ATIVO' ? 'MANUTENCAO' : 'ATIVO';
                        const btnLabel = q.status === 'ATIVO' ? 'Pausar' : 'Ativar';

                        return `
                            <tr>
                                <td><strong>${escapeHtml(q.nome)}</strong></td>
                                <td><span style="color: var(--accent-cyan); font-weight: 600;">${escapeHtml(q.modalidade_nome || 'Padrao')}</span></td>
                                <td>${q.capacidade || 4} atletas</td>
                                <td>R$ ${parseFloat(q.valor_padrao || 0).toFixed(2).replace('.', ',')}</td>
                                <td>
                                    <span style="background: ${statusBg}; color: ${statusColor}; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                        ${q.status}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button onclick="toggleCourtStatus(${q.id}, '${nextStatus}')" class="action-btn-pill">
                                        ${btnLabel}
                                    </button>
                                </td>
                            </tr>`;
                    }).join('');
                }

                // Render Full Courts table
                if (fullTbody) {
                    fullTbody.innerHTML = quadras.map(q => {
                        const statusBg = q.status === 'ATIVO' ? 'rgba(16, 185, 129, 0.15)' : (q.status === 'MANUTENCAO' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(148, 163, 184, 0.15)');
                        const statusColor = q.status === 'ATIVO' ? '#00f279' : (q.status === 'MANUTENCAO' ? '#fbbf24' : '#94a3b8');
                        const nextStatus = q.status === 'ATIVO' ? 'MANUTENCAO' : 'ATIVO';
                        const btnLabel = q.status === 'ATIVO' ? 'Pausar (Manutencao)' : 'Reativar';

                        return `
                            <tr>
                                <td><code>#${q.id}</code></td>
                                <td><strong>${escapeHtml(q.nome)}</strong></td>
                                <td><span style="color: var(--accent-cyan); font-weight: 600;">${escapeHtml(q.modalidade_nome || 'Padrao')}</span></td>
                                <td>${q.capacidade || 4} jogadores</td>
                                <td>R$ ${parseFloat(q.valor_padrao || 0).toFixed(2).replace('.', ',')}</td>
                                <td>
                                    <span style="background: ${statusBg}; color: ${statusColor}; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                        ${q.status}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button onclick="toggleCourtStatus(${q.id}, '${nextStatus}')" class="action-btn-pill" style="margin-right: 6px;">
                                        ${btnLabel}
                                    </button>
                                    <button onclick="deleteCourt(${q.id}, '${escapeHtml(q.nome)}')" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 5px 10px; border-radius: 6px; font-size: 0.78rem; cursor: pointer;">
                                        Excluir
                                    </button>
                                </td>
                            </tr>`;
                    }).join('');
                }
            } catch (err) {
                const errorHtml = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: #fca5a5; padding: 24px;">
                            Erro ao conectar com a API de quadras.
                        </td>
                    </tr>`;
                if (dashTbody) dashTbody.innerHTML = errorHtml;
                if (fullTbody) fullTbody.innerHTML = errorHtml;
            }
        }

        // Fetch and Render Modalidades
        async function loadModalidades() {
            const token = sessionStorage.getItem('masterarena_token');
            const tbody = document.getElementById('modalidadesTableBody');
            if (!tbody) return;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/modalidades`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                const modalidades = data.data && data.data.modalidades ? data.data.modalidades : [];
                cachedModalidades = modalidades;

                if (modalidades.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                Nenhuma modalidade esportiva cadastrada. Clique em <strong>"+ Nova Modalidade"</strong> acima.
                            </td>
                        </tr>`;
                    return;
                }

                tbody.innerHTML = modalidades.map(m => `
                    <tr>
                        <td><code>#${m.id}</code></td>
                        <td><strong>${escapeHtml(m.nome)}</strong></td>
                        <td>${escapeHtml(m.descricao || 'Sem descricao')}</td>
                        <td><code style="color: var(--accent-cyan);">${escapeHtml(m.icone || 'esporte')}</code></td>
                        <td>
                            <span class="badge-status-active">
                                ${m.ativo ? 'ATIVO' : 'INATIVO'}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="deleteModalidade(${m.id}, '${escapeHtml(m.nome)}')" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 5px 10px; border-radius: 6px; font-size: 0.78rem; cursor: pointer;">
                                Excluir
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; color: #fca5a5; padding: 20px;">
                            Erro ao carregar modalidades esportivas.
                        </td>
                    </tr>`;
            }
        }

        // Fetch Arena Details
        async function loadArenaDetails() {
            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                const arena = data.data && data.data.arena ? data.data.arena : null;

                if (arena) {
                    document.getElementById('arenaDetailNome').innerText = arena.nome_arena || 'Nao informado';
                    document.getElementById('arenaDetailSlug').innerText = arena.slug || 'Nao informado';
                    document.getElementById('arenaDetailWhatsapp').innerText = arena.whatsapp || 'Nao informado';
                    document.getElementById('arenaDetailCidade').innerText = `${arena.cidade || 'Sao Paulo'} / ${arena.estado || 'SP'}`;
                    document.getElementById('arenaDetailStatus').innerHTML = `<span class="badge-status-active">${arena.status || 'ATIVO'}</span>`;
                }
            } catch (e) {
                // Ignore
            }
        }

        // Toggle Court Status (ATIVO <-> MANUTENCAO)
        async function toggleCourtStatus(courtId, newStatus) {
            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/quadras/${courtId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                if (res.ok) {
                    loadCourts();
                } else {
                    const err = await res.json();
                    alert(err.message || 'Falha ao alterar status da quadra.');
                }
            } catch (e) {
                alert('Erro de comunicacao com a API REST.');
            }
        }

        // Delete Court
        async function deleteCourt(courtId, courtName) {
            if (!confirm(`Deseja realmente excluir a quadra "${courtName}"?`)) {
                return;
            }

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/quadras/${courtId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (res.ok) {
                    alert('Quadra excluida com sucesso!');
                    loadCourts();
                } else {
                    alert(data.message || 'Erro ao excluir quadra.');
                }
            } catch (e) {
                alert('Erro ao excluir quadra.');
            }
        }

        // Delete Modalidade
        async function deleteModalidade(modalidadeId, modalidadeNome) {
            if (!confirm(`Deseja realmente excluir a modalidade "${modalidadeNome}"?`)) {
                return;
            }

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/modalidades/${modalidadeId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (res.ok) {
                    alert('Modalidade esportiva excluida com sucesso!');
                    loadModalidades();
                    loadCourts();
                } else {
                    alert(data.message || 'Erro ao excluir modalidade.');
                }
            } catch (e) {
                alert('Erro ao excluir modalidade.');
            }
        }

        // Prompt Create Modalidade
        async function promptAddModalidade() {
            const nome = prompt('Nome da nova Modalidade Esportiva (ex: Beach Tennis, Society, Futevolei, Volei de Praia):');
            if (!nome || !nome.trim()) return;

            const descricao = prompt('Descricao da modalidade (opcional):', 'Quadras com iluminacao especial e piso padrao');
            const token = sessionStorage.getItem('masterarena_token');

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/modalidades`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        nome: nome.trim(),
                        descricao: descricao ? descricao.trim() : null,
                        icone: 'sport-ball',
                        ativo: 1
                    })
                });

                const data = await res.json();
                if (res.ok) {
                    alert('Modalidade esportiva cadastrada com sucesso!');
                    loadModalidades();
                    loadCourts();
                } else {
                    alert(data.message || 'Erro ao cadastrar modalidade.');
                }
            } catch (e) {
                alert('Erro ao conectar com a API.');
            }
        }

        // Prompt Create Court
        async function promptAddCourt() {
            const token = sessionStorage.getItem('masterarena_token');

            // Fetch available modalidades if cache empty
            if (cachedModalidades.length === 0) {
                try {
                    const resMod = await fetch(`/api/v1/arenas/${currentArenaId}/modalidades`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const modData = await resMod.json();
                    cachedModalidades = modData.data && modData.data.modalidades ? modData.data.modalidades : [];
                } catch (e) {}
            }

            if (cachedModalidades.length === 0) {
                const addNow = confirm('Ainda nao ha modalidades esportivas cadastradas nesta arena.\nDeseja cadastrar uma modalidade agora?');
                if (addNow) {
                    await promptAddModalidade();
                }
                return;
            }

            const nome = prompt('Nome da Quadra / Campo (ex: Quadra 1 Central Beach):');
            if (!nome || !nome.trim()) return;

            // List available modalidades for selection
            const modOptions = cachedModalidades.map(m => `${m.id}: ${m.nome}`).join('\n');
            const selectedModIdStr = prompt(`Informe o ID da Modalidade desejada:\n\n${modOptions}`, cachedModalidades[0].id);
            const modalidadeId = parseInt(selectedModIdStr, 10) || cachedModalidades[0].id;

            const capacidadeStr = prompt('Capacidade de jogadores simultaneos (ex: 4):', '4');
            const capacidade = parseInt(capacidadeStr, 10) || 4;

            const valorStr = prompt('Valor padrao por hora em Reais (ex: 120.00):', '120.00');
            const valor = parseFloat(valorStr) || 120.00;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/quadras`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        nome: nome.trim(),
                        modalidade_id: modalidadeId,
                        capacidade: capacidade,
                        valor_padrao: valor,
                        status: 'ATIVO'
                    })
                });

                const data = await res.json();
                if (res.ok) {
                    alert('Quadra cadastrada com sucesso!');
                    loadCourts();
                } else {
                    alert(data.message || 'Erro ao cadastrar quadra.');
                }
            } catch (e) {
                alert('Erro ao cadastrar quadra.');
            }
        }

        // Schedule & Availability Grid Interactive Functions
        function getSelectedGradeDate() {
            const input = document.getElementById('gradeDateInput');
            if (input && input.value) return input.value;
            const today = new Date().toISOString().split('T')[0];
            if (input) input.value = today;
            return today;
        }

        function setGradeToday() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('gradeDateInput').value = today;
            loadGrade();
        }

        function setGradeTomorrow() {
            const d = new Date();
            d.setDate(d.getDate() + 1);
            const tomorrow = d.toISOString().split('T')[0];
            document.getElementById('gradeDateInput').value = tomorrow;
            loadGrade();
        }

        async function loadGrade() {
            const date = getSelectedGradeDate();
            const token = sessionStorage.getItem('masterarena_token');
            const gridContainer = document.getElementById('gradeCourtsGrid');
            const summaryText = document.getElementById('gradeDaySummaryText');

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/grade?data=${date}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const json = await res.json();
                const gradeData = json.data && json.data.grade ? json.data.grade : null;

                if (!gradeData || !gradeData.quadras) {
                    gridContainer.innerHTML = '<div style="color: #fca5a5; padding: 24px; text-align: center; grid-column: 1 / -1;">Falha ao carregar dados da grade.</div>';
                    return;
                }

                // Update day summary header
                summaryText.innerHTML = `<strong>${escapeHtml(gradeData.dia_semana_nome)} (${gradeData.data})</strong> &bull; Funcionamento: ${gradeData.horario_funcionamento.hora_inicio} as ${gradeData.horario_funcionamento.hora_fim} &bull; ${gradeData.total_quadras} quadra(s) monitorada(s)`;

                if (gradeData.quadras.length === 0) {
                    gridContainer.innerHTML = '<div style="color: var(--text-muted); padding: 36px; text-align: center; grid-column: 1 / -1;">Nenhuma quadra cadastrada para exibir na grade. Cadastre quadras na aba "Quadras & Esportes".</div>';
                    return;
                }

                // Render Court columns with time slots
                gridContainer.innerHTML = gradeData.quadras.map(court => {
                    const slotsHtml = court.slots.map(slot => {
                        let statusColor = '#00f279';
                        let statusBg = 'rgba(16, 185, 129, 0.12)';
                        let statusBorder = 'rgba(16, 185, 129, 0.3)';
                        let extraAction = '';

                        if (slot.status === 'BLOQUEADO') {
                            statusColor = '#fbbf24';
                            statusBg = 'rgba(245, 158, 11, 0.15)';
                            statusBorder = 'rgba(245, 158, 11, 0.4)';
                            if (slot.bloqueio_id) {
                                extraAction = `<button onclick="deleteBlock(${slot.bloqueio_id})" style="background: none; border: none; color: #fca5a5; cursor: pointer; font-size: 0.72rem; text-decoration: underline;" title="Desbloquear">&#10006; Desbloquear</button>`;
                            }
                        } else if (slot.status === 'RESERVADO') {
                            statusColor = '#f87171';
                            statusBg = 'rgba(239, 68, 68, 0.15)';
                            statusBorder = 'rgba(239, 68, 68, 0.4)';
                        } else if (slot.status === 'MANUTENCAO') {
                            statusColor = '#94a3b8';
                            statusBg = 'rgba(148, 163, 184, 0.15)';
                            statusBorder = 'rgba(148, 163, 184, 0.3)';
                        } else {
                            // LIVRE
                            extraAction = `<button onclick="quickBlockSlot(${court.id}, '${slot.hora_inicio}', '${slot.hora_fim}')" style="background: none; border: none; color: var(--accent-cyan); cursor: pointer; font-size: 0.72rem;" title="Bloquear slot">&#128274; Bloquear</button>`;
                        }

                        return `
                            <div style="background: ${statusBg}; border: 1px solid ${statusBorder}; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.92rem; letter-spacing: -0.3px;">${slot.hora_inicio.substring(0, 5)} - ${slot.hora_fim.substring(0, 5)}</div>
                                    <div style="font-size: 0.75rem; color: ${statusColor}; font-weight: 600;">
                                        ${slot.status}${slot.motivo ? ' &bull; ' + escapeHtml(slot.motivo) : ''}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 800; font-size: 0.95rem; color: #fff;">R$ ${parseFloat(slot.valor || 0).toFixed(2).replace('.', ',')}</div>
                                    <div>${extraAction}</div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    return `
                        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 20px; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 14px;">
                                <div>
                                    <h4 style="font-size: 1.05rem; font-weight: 800;">${escapeHtml(court.nome)}</h4>
                                    <span style="font-size: 0.8rem; color: var(--accent-cyan); font-weight: 600;">${escapeHtml(court.modalidade_nome)}</span>
                                </div>
                                <span style="font-size: 0.75rem; background: rgba(255,255,255,0.06); padding: 4px 8px; border-radius: 6px;">${court.capacidade} atletas</span>
                            </div>
                            <div style="max-height: 520px; overflow-y: auto; padding-right: 4px;">
                                ${slotsHtml}
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (err) {
                gridContainer.innerHTML = '<div style="color: #fca5a5; padding: 24px; text-align: center; grid-column: 1 / -1;">Erro ao carregar a grade de horarios.</div>';
            }
        }

        async function quickBlockSlot(courtId, horaInicio, horaFim) {
            const date = getSelectedGradeDate();
            const motivo = prompt(`Informe o motivo do bloqueio para o horario ${horaInicio.substring(0,5)} as ${horaFim.substring(0,5)}:`, 'Manutencao preventiva');
            if (!motivo || !motivo.trim()) return;

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/bloqueios`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        quadra_id: courtId,
                        data_inicio: date,
                        data_fim: date,
                        hora_inicio: horaInicio,
                        hora_fim: horaFim,
                        motivo: motivo.trim()
                    })
                });

                if (res.ok) {
                    loadGrade();
                } else {
                    const data = await res.json();
                    alert(data.message || 'Erro ao criar bloqueio.');
                }
            } catch (e) {
                alert('Erro de comunicacao com a API.');
            }
        }

        async function promptCreateBlock() {
            const date = getSelectedGradeDate();
            const token = sessionStorage.getItem('masterarena_token');

            // Fetch available courts for prompt
            const resCourts = await fetch(`/api/v1/arenas/${currentArenaId}/quadras`, {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            });
            const courtData = await resCourts.json();
            const courts = courtData.data && courtData.data.quadras ? courtData.data.quadras : [];

            if (courts.length === 0) {
                alert('Cadastre primeiro ao menos uma quadra.');
                return;
            }

            const courtOptions = courts.map(c => `${c.id}: ${c.nome}`).join('\n');
            const courtIdStr = prompt(`Informe o ID da quadra para o bloqueio:\n\n${courtOptions}`, courts[0].id);
            const courtId = parseInt(courtIdStr, 10);
            if (!courtId) return;

            const horaInicio = prompt('Hora de inicio (ex: 14:00:00):', '14:00:00');
            if (!horaInicio) return;

            const horaFim = prompt('Hora de fim (ex: 18:00:00):', '18:00:00');
            if (!horaFim) return;

            const motivo = prompt('Motivo do bloqueio (ex: Torneio Interno, Aulas, Manutencao):', 'Manutencao geral');
            if (!motivo) return;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/bloqueios`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        quadra_id: courtId,
                        data_inicio: date,
                        data_fim: date,
                        hora_inicio: horaInicio.trim(),
                        hora_fim: horaFim.trim(),
                        motivo: motivo.trim()
                    })
                });

                if (res.ok) {
                    alert('Bloqueio cadastrado com sucesso!');
                    loadGrade();
                } else {
                    const data = await res.json();
                    alert(data.message || 'Erro ao criar bloqueio.');
                }
            } catch (e) {
                alert('Erro ao criar bloqueio.');
            }
        }

        async function deleteBlock(blockId) {
            if (!confirm('Deseja realmente remover este bloqueio e liberar o horario na grade?')) {
                return;
            }

            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/bloqueios/${blockId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    loadGrade();
                } else {
                    const data = await res.json();
                    alert(data.message || 'Erro ao remover bloqueio.');
                }
            } catch (e) {
                alert('Erro ao remover bloqueio.');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // WhatsApp Notifications Logic (ASCII comments only)
        async function loadWhatsAppConfig() {
            const token = sessionStorage.getItem('masterarena_token');
            if (!token || !currentArenaId) return;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/notificacoes/whatsapp/config`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();
                const cfg = data.data || {};

                const provEl = document.getElementById('wa_provider');
                if (provEl) provEl.value = cfg.whatsapp_provider || 'SIMULATOR';

                const urlEl = document.getElementById('wa_api_url');
                if (urlEl) urlEl.value = cfg.whatsapp_api_url || '';

                const tokenEl = document.getElementById('wa_api_token');
                if (tokenEl) tokenEl.value = cfg.whatsapp_api_token || '';

                const instEl = document.getElementById('wa_instance');
                if (instEl) instEl.value = cfg.whatsapp_instance || '';

                const pixVal = cfg.whatsapp_notify_pix !== undefined ? cfg.whatsapp_notify_pix : cfg.whatsapp_notify_pix_pending;
                const pixEl = document.getElementById('wa_notify_pix');
                if (pixEl) pixEl.checked = pixVal === undefined || pixVal === true || String(pixVal) === '1';

                const confVal = cfg.whatsapp_notify_confirmed !== undefined ? cfg.whatsapp_notify_confirmed : cfg.whatsapp_notify_booking_confirmed;
                const confEl = document.getElementById('wa_notify_confirmed');
                if (confEl) confEl.checked = confVal === undefined || confVal === true || String(confVal) === '1';

                const cancVal = cfg.whatsapp_notify_cancelled !== undefined ? cfg.whatsapp_notify_cancelled : cfg.whatsapp_notify_booking_cancelled;
                const cancEl = document.getElementById('wa_notify_cancelled');
                if (cancEl) cancEl.checked = cancVal === undefined || cancVal === true || String(cancVal) === '1';

                const remVal = cfg.whatsapp_notify_reminder !== undefined ? cfg.whatsapp_notify_reminder : cfg.whatsapp_notify_game_reminder;
                const remEl = document.getElementById('wa_notify_reminder');
                if (remEl) remEl.checked = remVal === undefined || remVal === true || String(remVal) === '1';

                const hrsEl = document.getElementById('wa_reminder_hours');
                if (hrsEl) hrsEl.value = cfg.whatsapp_reminder_hours || cfg.whatsapp_reminder_hours_before || '2';
            } catch (e) {
                // Ignore config fetch errors
            }
        }

        async function saveWhatsAppConfig() {
            const token = sessionStorage.getItem('masterarena_token');
            if (!token || !currentArenaId) return;

            const payload = {
                whatsapp_provider: document.getElementById('wa_provider').value,
                whatsapp_api_url: document.getElementById('wa_api_url').value.trim(),
                whatsapp_api_token: document.getElementById('wa_api_token').value.trim(),
                whatsapp_instance: document.getElementById('wa_instance').value.trim(),
                whatsapp_notify_pix: document.getElementById('wa_notify_pix').checked ? 1 : 0,
                whatsapp_notify_pix_pending: document.getElementById('wa_notify_pix').checked ? 1 : 0,
                whatsapp_notify_confirmed: document.getElementById('wa_notify_confirmed').checked ? 1 : 0,
                whatsapp_notify_booking_confirmed: document.getElementById('wa_notify_confirmed').checked ? 1 : 0,
                whatsapp_notify_cancelled: document.getElementById('wa_notify_cancelled').checked ? 1 : 0,
                whatsapp_notify_booking_cancelled: document.getElementById('wa_notify_cancelled').checked ? 1 : 0,
                whatsapp_notify_reminder: document.getElementById('wa_notify_reminder').checked ? 1 : 0,
                whatsapp_notify_game_reminder: document.getElementById('wa_notify_reminder').checked ? 1 : 0,
                whatsapp_reminder_hours: parseInt(document.getElementById('wa_reminder_hours').value, 10) || 2,
                whatsapp_reminder_hours_before: parseInt(document.getElementById('wa_reminder_hours').value, 10) || 2
            };

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/notificacoes/whatsapp/config`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    alert('Configuracoes de WhatsApp salvas com sucesso!');
                } else {
                    alert(data.message || 'Erro ao salvar configuracoes de WhatsApp.');
                }
            } catch (e) {
                alert('Falha de comunicacao ao salvar configuracoes.');
            }
        }

        async function sendWhatsAppTest() {
            const token = sessionStorage.getItem('masterarena_token');
            const phone = (document.getElementById('wa_test_phone').value || '').trim();
            const message = (document.getElementById('wa_test_message').value || '').trim();
            const resultBox = document.getElementById('wa_test_result');

            if (!phone) {
                alert('Informe um numero de telefone com DDD para teste.');
                return;
            }

            if (resultBox) {
                resultBox.style.display = 'block';
                resultBox.style.background = 'rgba(6, 182, 212, 0.15)';
                resultBox.style.color = 'var(--accent-cyan)';
                resultBox.innerText = 'Enviando disparo de teste...';
            }

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/notificacoes/whatsapp/testar`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ telefone: phone, mensagem: message })
                });

                const data = await res.json();
                if (res.ok) {
                    if (resultBox) {
                        resultBox.style.background = 'rgba(16, 185, 129, 0.15)';
                        resultBox.style.color = 'var(--accent-lime)';
                        resultBox.innerText = `Sucesso! Mensagem disparada. Provedor: ${data.data.provider}. ID Externo: ${data.data.external_message_id || 'N/A'}`;
                    }
                    loadWhatsAppLogs();
                } else {
                    if (resultBox) {
                        resultBox.style.background = 'rgba(239, 68, 68, 0.15)';
                        resultBox.style.color = '#fca5a5';
                        resultBox.innerText = `Erro: ${data.message || 'Falha ao disparar'}`;
                    }
                }
            } catch (e) {
                if (resultBox) {
                    resultBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    resultBox.style.color = '#fca5a5';
                    resultBox.innerText = 'Erro de comunicacao com a API.';
                }
            }
        }

        async function runWhatsAppReminders() {
            const token = sessionStorage.getItem('masterarena_token');
            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/notificacoes/whatsapp/processar-lembretes`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    const count = data.data && data.data.lembretes_enviados ? data.data.lembretes_enviados : 0;
                    alert(`Varredura concluida com sucesso! Lembretes enviados: ${count}`);
                    loadWhatsAppLogs();
                } else {
                    alert(data.message || 'Erro ao processar lembretes.');
                }
            } catch (e) {
                alert('Falha de conexao ao executar varredura.');
            }
        }

        async function loadWhatsAppLogs() {
            const token = sessionStorage.getItem('masterarena_token');
            const tbody = document.getElementById('waLogsBody');
            if (!token || !currentArenaId || !tbody) return;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArenaId}/notificacoes/whatsapp?limit=25`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();
                const logs = data.data && data.data.notificacoes ? data.data.notificacoes : [];

                if (logs.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                Nenhuma notificacao de WhatsApp enviada ate o momento para esta arena.
                            </td>
                        </tr>`;
                    return;
                }

                tbody.innerHTML = logs.map(l => {
                    const isSuccess = l.status === 'ENVIADO';
                    const badgeBg = isSuccess ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)';
                    const badgeColor = isSuccess ? '#00f279' : '#fca5a5';
                    const dt = l.created_at ? l.created_at.substring(0, 19).replace('T', ' ') : 'N/A';

                    let extId = '-';
                    if (l.gateway_response) {
                        try {
                            const parsed = typeof l.gateway_response === 'string' ? JSON.parse(l.gateway_response) : l.gateway_response;
                            extId = parsed.mock_message_id || parsed.id || parsed.messageId || l.gateway_response;
                        } catch(e) {
                            extId = l.gateway_response;
                        }
                    } else if (l.erro) {
                        extId = l.erro;
                    } else if (l.mensagem_id_externo) {
                        extId = l.mensagem_id_externo;
                    }

                    const phone = l.telefone || l.destinatario_telefone || '-';
                    const tipo = l.tipo || l.tipo_evento || 'TESTE';
                    const provider = l.gateway_provider || l.provider || 'SIMULATOR';

                    return `
                        <tr>
                            <td><code>#${l.id}</code></td>
                            <td>${dt}</td>
                            <td><strong>${escapeHtml(phone)}</strong></td>
                            <td><span style="color: var(--accent-cyan); font-weight: 600;">${escapeHtml(tipo)}</span></td>
                            <td><span style="font-size: 0.8rem; color: #cbd5e1;">${escapeHtml(provider)}</span></td>
                            <td>
                                <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    ${escapeHtml(l.status)}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; color: var(--text-muted); word-break: break-all;">
                                    ${escapeHtml(extId)}
                                </span>
                            </td>
                        </tr>`;
                }).join('');
            } catch (e) {
                // Ignore logs fetch error
            }
        }

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
                    // Ignore network errors on logout
                }
            }
            sessionStorage.removeItem('masterarena_token');
            sessionStorage.removeItem('masterarena_user');
            window.location.href = '/';
        }
    </script>
</body>
</html>
