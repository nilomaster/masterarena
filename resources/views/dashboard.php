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
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Financeiro & Fluxo de Caixa</h2>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Cobrancas PIX e integracao com gateway Mercado Pago / Efí.</span>
                        </div>
                    </div>
                    <div style="background: rgba(168, 85, 247, 0.08); border: 1px dashed rgba(168, 85, 247, 0.3); border-radius: 12px; padding: 36px; text-align: center;">
                        <div style="font-size: 2.5rem; margin-bottom: 12px;">&#128179;</div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">Modulo Financeiro e Pagamentos PIX Automáticos</h3>
                        <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto; font-size: 0.92rem;">
                            Planejado para a <strong>ETAPA 8</strong> (Integracao com Gateway de Pagamentos, Webhooks e Split de Recebimento).
                        </p>
                    </div>
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
            } else if (tabId === 'arenas') {
                loadArenaDetails();
            } else if (tabId === 'agendamentos') {
                loadGrade();
            }
        }async function loadCourts() {
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
