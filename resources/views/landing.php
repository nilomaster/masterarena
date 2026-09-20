<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Master Arena SaaS - Official Landing Page -->
    <!-- Comments strictly in ASCII only. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MASTER ARENA — Gestao Inteligente para Complexos e Arenas Esportivas</title>
    <meta name="description" content="Sistema SaaS completo para administracao, agendamento de quadras e gestao financeira de complexos esportivos.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-card: rgba(18, 24, 38, 0.75);
            --bg-card-hover: rgba(28, 36, 56, 0.85);
            --accent-green: #10b981;
            --accent-lime: #00f279;
            --accent-cyan: #06b6d4;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-glow: rgba(16, 185, 129, 0.2);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(16, 185, 129, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 60%, rgba(6, 182, 212, 0.1) 0%, transparent 45%),
                radial-gradient(circle at 50% 90%, rgba(0, 242, 121, 0.08) 0%, transparent 50%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header Navigation */
        header {
            padding: 24px 0;
            border-bottom: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(10, 14, 23, 0.8);
        }

        .nav-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.5px;
        }

        .logo-badge {
            background: linear-gradient(135deg, var(--accent-lime), var(--accent-green));
            color: #05130b;
            font-size: 0.9rem;
            font-weight: 900;
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
        }

        .nav-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 22px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            border: none;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--glass-border);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-lime), var(--accent-green));
            color: #031509;
            box-shadow: 0 4px 18px rgba(16, 185, 129, 0.35);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 24px rgba(16, 185, 129, 0.5);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero-section {
            padding: 90px 0 60px;
            text-align: center;
        }

        .pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--accent-lime);
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 24px;
        }

        .pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-lime);
            box-shadow: 0 0 10px var(--accent-lime);
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.2px;
            margin-bottom: 20px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--accent-lime), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 720px;
            margin: 0 auto 36px;
            font-weight: 400;
        }

        .sports-tags {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .sport-chip {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            color: #cbd5e1;
        }

        .hero-cta-group {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 60px;
        }

        /* Features Grid */
        .features-section {
            padding: 40px 0 80px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .section-desc {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 32px 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .feature-card:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-glow);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--accent-lime);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Login Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(5, 8, 15, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-box {
            background: #111726;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 36px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            transform: scale(0.95);
            transition: all 0.25s ease;
        }

        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.4rem;
            cursor: pointer;
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--text-main);
        }

        .modal-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .modal-title {
            font-size: 1.45rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #cbd5e1;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            background: #090d16;
            border: 1px solid var(--glass-border);
            color: #fff;
            font-size: 0.95rem;
            transition: border 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .feedback-msg {
            font-size: 0.88rem;
            margin-bottom: 14px;
            padding: 10px 14px;
            border-radius: 8px;
            display: none;
        }

        .feedback-error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: block;
        }

        .feedback-success {
            background: rgba(16, 185, 129, 0.15);
            color: #86efac;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: block;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--glass-border);
            padding: 36px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            .hero-cta-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header>
        <div class="container nav-wrapper">
            <a href="/" class="brand-logo">
                <span class="logo-badge">MA</span>
                <span>MASTER ARENA</span>
            </a>
            <div class="nav-actions">
                <button class="btn btn-outline" onclick="openLoginModal()">Acesso do Gestor</button>
                <a href="#features" class="btn btn-primary">Conhecer Sistema</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main>
        <section class="hero-section">
            <div class="container">
                <div class="pill-tag">
                    <span class="pill-dot"></span>
                    SaaS Esportivo Multiarena V1.0
                </div>

                <h1 class="hero-title">
                    A plataforma definitiva para <span>gestao e agendamento</span> de complexos esportivos.
                </h1>

                <p class="hero-subtitle">
                    Automatize reservas, controle financeiro, comandas e mensalistas com alta performance e isolamento total por arena.
                </p>

                <div class="sports-tags">
                    <span class="sport-chip">Beach Tennis</span>
                    <span class="sport-chip">Futebol Society</span>
                    <span class="sport-chip">Futevolei</span>
                    <span class="sport-chip">Volei de Praia</span>
                    <span class="sport-chip">Quadras Cobertas</span>
                </div>

                <div class="hero-cta-group">
                    <button class="btn btn-primary" onclick="openLoginModal()" style="font-size: 1.05rem; padding: 14px 32px;">
                        Entrar no Painel do Gestor
                    </button>
                    <a href="/api/v1/ping" class="btn btn-outline" style="font-size: 1.05rem; padding: 14px 28px;">
                        Status da API REST
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="features-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Construido para a Rotina Real da sua Arena</h2>
                    <p class="section-desc">Tecnologia desenvolvida especificamente para alta demanda e zero conflito de horarios.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">&#128197;</div>
                        <h3>Grade Digital em Tempo Real</h3>
                        <p>Visao intuitiva das quadras com bloqueio instantaneo de choques de horario e suporte a reservas avulsas e mensalistas.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">&#127970;</div>
                        <h3>Multi-Tenant com Isolamento</h3>
                        <p>Cada arena possui seu proprio ambiente isolado com URL exclusiva, regras proprias e personalizacao completa.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">&#128179;</div>
                        <h3>Gestao Financeira & Comandas</h3>
                        <p>Controle de bar, lanchonete, rateio de partidas entre amigos e fechamento de caixa simplificado com relatorios.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">&#9889;</div>
                        <h3>Arquitetura Hibrida & API REST</h3>
                        <p>Seguranca com JWT nativo, banco isolado e sincronizacao veloz entre o portal web e o aplicativo desktop para totens.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal-overlay" onclick="closeLoginModal(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeLoginModal()">&times;</button>
            <div class="modal-header">
                <h3 class="modal-title">Acesso ao Master Arena</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Insira suas credenciais para entrar no sistema</p>
            </div>

            <div id="feedbackBox" class="feedback-msg"></div>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label class="form-label" for="loginEmail">E-mail</label>
                    <input class="form-input" type="email" id="loginEmail" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="loginPassword">Senha</label>
                    <input class="form-input" type="password" id="loginPassword" placeholder="Sua senha de acesso" required>
                </div>

                <button type="submit" id="loginSubmitBtn" class="btn btn-primary" style="width: 100%; margin-top: 8px; padding: 12px;">
                    Entrar no Sistema
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 <strong>Master Arena SaaS</strong> &bull; Desenvolvido por MasterDev Solutions.</p>
            <p style="font-size: 0.8rem; margin-top: 6px; color: #64748b;">Conexao Criptografada e Segura &bull; Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.getElementById('loginEmail').focus();
        }

        function closeLoginModal(e) {
            if (!e || e.target === document.getElementById('loginModal') || e.target.classList.contains('modal-close')) {
                document.getElementById('loginModal').classList.remove('active');
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('loginSubmitBtn');
            const feedback = document.getElementById('feedbackBox');
            const email = document.getElementById('loginEmail').value.trim();
            const senha = document.getElementById('loginPassword').value;

            btn.disabled = true;
            btn.innerText = 'Autenticando...';
            feedback.className = 'feedback-msg';
            feedback.style.display = 'none';

            try {
                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, senha })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    feedback.className = 'feedback-msg feedback-success';
                    feedback.innerText = 'Login realizado com sucesso! Redirecionando...';
                    // Store token safely
                    if (data.data && data.data.token) {
                        sessionStorage.setItem('masterarena_token', data.data.token);
                        sessionStorage.setItem('masterarena_user', JSON.stringify(data.data.user));
                    }
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                } else {
                    feedback.className = 'feedback-msg feedback-error';
                    feedback.innerText = data.message || 'Falha na autenticacao. Verifique seus dados.';
                }
            } catch (err) {
                feedback.className = 'feedback-msg feedback-error';
                feedback.innerText = 'Nao foi possivel conectar ao servidor da API.';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Entrar no Sistema';
            }
        }
    </script>
</body>
</html>
