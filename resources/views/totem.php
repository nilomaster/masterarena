<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-select=none">
    <title>Totem Autoatendimento — Master Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-kiosk: #070a12;
            --surface-kiosk: #0f172a;
            --card-kiosk: #1e293b;
            --primary: #0284c7;
            --primary-neon: #38bdf8;
            --accent: #10b981;
            --accent-neon: #34d399;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --glow-cyan: 0 0 30px rgba(56, 189, 248, 0.35);
            --glow-green: 0 0 30px rgba(16, 185, 129, 0.35);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-kiosk);
            color: var(--text-main);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(2, 132, 199, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(16, 185, 129, 0.12) 0%, transparent 50%);
        }

        h1, h2, h3, h4, .kiosk-title {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        /* Kiosk Top Bar */
        .kiosk-header {
            padding: 1.5rem 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
        }

        .kiosk-brand {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .kiosk-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), #0284c7);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: var(--glow-cyan);
        }

        .kiosk-brand h1 {
            font-size: 1.8rem;
            font-weight: 800;
        }

        .kiosk-brand span {
            font-size: 0.95rem;
            color: var(--text-muted);
            display: block;
        }

        .kiosk-clock {
            text-align: right;
        }

        .kiosk-time {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--primary-neon);
            line-height: 1;
        }

        .kiosk-date {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        /* Screen Viewport */
        .kiosk-body {
            flex: 1;
            padding: 2.5rem 3rem;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .view-section {
            display: none;
            width: 100%;
            height: 100%;
            animation: fadeIn 0.3s ease forwards;
        }

        .view-section.active {
            display: flex;
            flex-direction: column;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Attract Screen (Screensaver) */
        .attract-screen {
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
        }

        .pulse-circle {
            width: 160px;
            height: 160px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4.5rem;
            box-shadow: 0 0 60px rgba(56, 189, 248, 0.5);
            animation: pulseGlow 2.5s infinite;
            margin-bottom: 2.5rem;
        }

        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); box-shadow: 0 0 50px rgba(56, 189, 248, 0.4); }
            50% { transform: scale(1.08); box-shadow: 0 0 90px rgba(16, 185, 129, 0.7); }
        }

        .attract-title {
            font-size: 3.2rem;
            font-weight: 900;
            margin-bottom: 0.8rem;
            background: linear-gradient(135deg, #fff, var(--primary-neon));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .attract-subtitle {
            font-size: 1.4rem;
            color: var(--text-muted);
            animation: blink 2s infinite ease-in-out;
        }

        @keyframes blink {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        /* Main Menu Screen (2 Gigantic Buttons) */
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            height: 100%;
            align-items: center;
        }

        .menu-action-card {
            background: var(--surface-kiosk);
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            height: 75%;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-action-card:active {
            transform: scale(0.97);
        }

        .menu-action-card.checkin {
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: var(--glow-green);
        }

        .menu-action-card.checkin:hover {
            border-color: var(--accent-neon);
            background: rgba(16, 185, 129, 0.08);
        }

        .menu-action-card.reserva {
            border-color: rgba(2, 132, 199, 0.4);
            box-shadow: var(--glow-cyan);
        }

        .menu-action-card.reserva:hover {
            border-color: var(--primary-neon);
            background: rgba(2, 132, 199, 0.08);
        }

        .card-icon-big {
            font-size: 5.5rem;
            margin-bottom: 1.75rem;
        }

        .card-action-title {
            font-size: 2.4rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.8rem;
        }

        .card-action-desc {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 380px;
        }

        /* Checkin / Keypad Screen */
        .numpad-container {
            display: flex;
            gap: 3rem;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .numpad-info {
            max-width: 440px;
        }

        .numpad-display-box {
            background: #000;
            border: 2px solid var(--primary-neon);
            border-radius: 20px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: center;
            box-shadow: var(--glow-cyan);
        }

        .numpad-display-val {
            font-family: 'Outfit', sans-serif;
            font-size: 2.6rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            color: #fff;
            min-height: 50px;
        }

        .numpad-grid {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 1.25rem;
        }

        .numpad-btn {
            width: 100px;
            height: 90px;
            background: var(--surface-kiosk);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
        }

        .numpad-btn:active {
            transform: scale(0.92);
            background: var(--primary);
            border-color: var(--primary-neon);
        }

        .numpad-btn.action-btn {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            font-size: 1.5rem;
            color: #fca5a5;
        }

        .numpad-btn.submit-btn {
            background: linear-gradient(135deg, var(--accent), #059669);
            border: none;
            font-size: 2rem;
            box-shadow: var(--glow-green);
        }

        .numpad-btn.submit-btn:active {
            background: #059669;
        }

        /* Success Display */
        .result-view {
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
        }

        .result-box {
            background: var(--surface-kiosk);
            border: 2px solid var(--accent-neon);
            box-shadow: var(--glow-green);
            border-radius: 32px;
            padding: 3.5rem;
            max-width: 760px;
            width: 100%;
        }

        .result-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .result-title {
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--accent-neon);
            margin-bottom: 1rem;
        }

        .court-badge-huge {
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid var(--accent);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin: 2rem 0;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .court-badge-huge .item {
            text-align: center;
        }

        .court-badge-huge .item span {
            font-size: 0.95rem;
            color: var(--text-muted);
            display: block;
            margin-bottom: 0.4rem;
        }

        .court-badge-huge .item strong {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            color: #fff;
        }

        /* Back button */
        .btn-kiosk-back {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.2s;
            align-self: flex-start;
            margin-bottom: 1.5rem;
        }

        .btn-kiosk-back:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>

    <!-- Header bar -->
    <header class="kiosk-header">
        <div class="kiosk-brand">
            <div class="kiosk-logo">🏟️</div>
            <div>
                <h1 id="kioskArenaName">Master Arena</h1>
                <span id="kioskArenaSubtitle">Totem de Autoatendimento</span>
            </div>
        </div>
        <div class="kiosk-clock">
            <div class="kiosk-time" id="kioskClock">12:00</div>
            <div class="kiosk-date" id="kioskDate">20 de Setembro</div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="kiosk-body">

        <!-- 1. ATTRACT / SCREENSAVER VIEW -->
        <section class="view-section attract-screen active" id="viewAttract" onclick="goToMenu()">
            <div class="pulse-circle">⚡</div>
            <h2 class="attract-title">BEM-VINDO A ARENA</h2>
            <p class="attract-subtitle">👉 Toque na tela para iniciar o autoatendimento</p>
        </section>

        <!-- 2. MAIN MENU VIEW -->
        <section class="view-section" id="viewMenu">
            <button class="btn-kiosk-back" onclick="goToAttract()">← Voltar ao Inicio</button>
            <div class="menu-grid">
                <!-- Action 1: Check-in -->
                <div class="menu-action-card checkin" onclick="openCheckinView()">
                    <div class="card-icon-big">⚡</div>
                    <h3 class="card-action-title">CHECK-IN NA QUADRA</h3>
                    <p class="card-action-desc">Ja reservou? Valide seu acesso agora digitando seu WhatsApp ou codigo de reserva.</p>
                </div>

                <!-- Action 2: Quick Booking -->
                <div class="menu-action-card reserva" onclick="openQuickBookingView()">
                    <div class="card-icon-big">🎟️</div>
                    <h3 class="card-action-title">RESERVA RAPIDA</h3>
                    <p class="card-action-desc">Alugue uma quadra livre para jogar hoje e pague no PIX direto na tela.</p>
                </div>
            </div>
        </section>

        <!-- 3. CHECK-IN NUMPAD VIEW -->
        <section class="view-section" id="viewCheckin">
            <button class="btn-kiosk-back" onclick="goToMenu()">← Voltar ao Menu</button>
            <div class="numpad-container">
                <div class="numpad-info">
                    <h2 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 0.5rem;">Fazer Check-in</h2>
                    <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.5;">
                        Digite seu <strong>WhatsApp com DDD</strong> ou o <strong>Codigo de Check-in</strong> da sua reserva:
                    </p>

                    <div class="numpad-display-box">
                        <div class="numpad-display-val" id="numpadDisplay">...</div>
                    </div>

                    <div id="checkinErrorFeedback" style="display: none; color: #f87171; font-size: 1.1rem; font-weight: 700; text-align: center; margin-top: 0.5rem;">
                        <!-- Error feedback -->
                    </div>
                </div>

                <!-- Virtual Touch Numpad -->
                <div class="numpad-grid">
                    <button class="numpad-btn" onclick="pressNumpad('1')">1</button>
                    <button class="numpad-btn" onclick="pressNumpad('2')">2</button>
                    <button class="numpad-btn" onclick="pressNumpad('3')">3</button>
                    <button class="numpad-btn" onclick="pressNumpad('4')">4</button>
                    <button class="numpad-btn" onclick="pressNumpad('5')">5</button>
                    <button class="numpad-btn" onclick="pressNumpad('6')">6</button>
                    <button class="numpad-btn" onclick="pressNumpad('7')">7</button>
                    <button class="numpad-btn" onclick="pressNumpad('8')">8</button>
                    <button class="numpad-btn" onclick="pressNumpad('9')">9</button>
                    <button class="numpad-btn action-btn" onclick="clearNumpad()">C</button>
                    <button class="numpad-btn" onclick="pressNumpad('0')">0</button>
                    <button class="numpad-btn submit-btn" onclick="submitCheckin()" id="btnSubmitCheckin">✓</button>
                </div>
            </div>
        </section>

        <!-- 4. CHECK-IN SUCCESS RESULT VIEW -->
        <section class="view-section result-view" id="viewCheckinSuccess">
            <div class="result-box">
                <div class="result-icon">🎉</div>
                <h2 class="result-title">CHECK-IN CONFIRMADO!</h2>
                <p style="font-size: 1.25rem; color: var(--text-muted);" id="resClientName">Ola, Lucas!</p>

                <div class="court-badge-huge">
                    <div class="item">
                        <span>QUADRA</span>
                        <strong id="resCourtName">Quadra 1</strong>
                    </div>
                    <div class="item">
                        <span>MODALIDADE</span>
                        <strong id="resSportName">Beach Tennis</strong>
                    </div>
                    <div class="item">
                        <span>HORARIO</span>
                        <strong id="resTime">18:00 as 19:00</strong>
                    </div>
                </div>

                <p style="font-size: 1.2rem; color: var(--accent-neon); font-weight: 700; margin-bottom: 2rem;">
                    ✓ Entrada autorizada. Bom jogo!
                </p>

                <button class="btn-kiosk-back" onclick="goToAttract()" style="align-self: center; margin: 0 auto;">
                    Concluir e Liberar Totem
                </button>
            </div>
        </section>

        <!-- 5. QUICK BOOKING VIEW FOR TODAY -->
        <section class="view-section" id="viewQuickBooking">
            <button class="btn-kiosk-back" onclick="goToMenu()">← Voltar ao Menu</button>
            <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Horarios Livres de Hoje</h2>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 1.5rem;">Toque no horario que deseja reservar agora:</p>

            <div id="kioskSlotsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.25rem; overflow-y: auto; max-height: 70vh;">
                <!-- Dynamically loaded slots -->
            </div>
        </section>

    </main>

    <script>
        // Extract arena slug from URL
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        let arenaSlug = pathParts[pathParts.length - 1] || 'arena-master-beach';
        if (arenaSlug === 'totem' || arenaSlug === 'kiosk') {
            arenaSlug = 'arena-master-beach';
        }

        let arenaData = null;
        let numpadValue = '';
        let inactivityTimer = null;

        // Digital Clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('kioskClock').innerText = `${hours}:${minutes}`;

            const options = { day: 'numeric', month: 'long', weekday: 'short' };
            document.getElementById('kioskDate').innerText = now.toLocaleDateString('pt-BR', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Inactivity Reset (60 seconds)
        function resetInactivityTimer() {
            if (inactivityTimer) clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                goToAttract();
            }, 60000);
        }
        document.addEventListener('touchstart', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);

        // Navigation between views
        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
            document.getElementById(viewId).classList.add('active');
            resetInactivityTimer();
        }

        function goToAttract() {
            clearNumpad();
            switchView('viewAttract');
        }

        function goToMenu() {
            switchView('viewMenu');
        }

        function openCheckinView() {
            clearNumpad();
            switchView('viewCheckin');
        }

        // Touch Numpad logic
        function pressNumpad(digit) {
            if (numpadValue.length < 15) {
                numpadValue += digit;
                updateNumpadDisplay();
            }
        }

        function clearNumpad() {
            numpadValue = '';
            updateNumpadDisplay();
            document.getElementById('checkinErrorFeedback').style.display = 'none';
        }

        function updateNumpadDisplay() {
            const display = document.getElementById('numpadDisplay');
            if (!numpadValue) {
                display.innerText = 'Digite aqui...';
                display.style.opacity = '0.4';
            } else {
                display.innerText = numpadValue;
                display.style.opacity = '1';
            }
        }

        // Submit Checkin
        async function submitCheckin() {
            if (!numpadValue || !arenaData) {
                showCheckinError('Digite seu telefone ou codigo de reserva.');
                return;
            }

            const btn = document.getElementById('btnSubmitCheckin');
            btn.innerText = '...';
            btn.disabled = true;

            try {
                const res = await fetch(`/api/v1/arenas/${arenaData.id}/checkin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        identificador: numpadValue,
                        origem: 'TOTEM'
                    })
                });

                const data = await res.json();

                if (!data.success) {
                    showCheckinError(data.message || 'Check-in nao autorizado.');
                } else {
                    // Show success
                    const det = data.data.detalhes;
                    document.getElementById('resClientName').innerText = `Ola, ${det.cliente}!`;
                    document.getElementById('resCourtName').innerText = det.quadra;
                    document.getElementById('resSportName').innerText = det.modalidade;
                    document.getElementById('resTime').innerText = det.horario;

                    switchView('viewCheckinSuccess');

                    // Auto return to attract screen after 12 seconds
                    setTimeout(() => {
                        goToAttract();
                    }, 12000);
                }
            } catch (err) {
                showCheckinError('Erro de conexao: ' + err.message);
            } finally {
                btn.innerText = '✓';
                btn.disabled = false;
            }
        }

        function showCheckinError(msg) {
            const errBox = document.getElementById('checkinErrorFeedback');
            errBox.innerText = msg;
            errBox.style.display = 'block';
        }

        // Quick Booking
        async function openQuickBookingView() {
            switchView('viewQuickBooking');
            const box = document.getElementById('kioskSlotsContainer');
            box.innerHTML = '<div style="color: var(--text-muted); font-size: 1.3rem; padding: 2rem;">Carregando horarios livres...</div>';

            try {
                const today = new Date().toISOString().split('T')[0];
                const res = await fetch(`/api/v1/arenas/${arenaData.id}/grade?data=${today}`);
                const data = await res.json();

                let rawCourts = [];
                if (data.data && data.data.grade) {
                    if (data.data.grade.quadras) rawCourts = data.data.grade.quadras;
                    else if (Array.isArray(data.data.grade)) rawCourts = data.data.grade;
                }

                if (!data.success || rawCourts.length === 0) {
                    box.innerHTML = '<div style="color: var(--text-muted); font-size: 1.2rem;">Nenhum horario disponivel para hoje.</div>';
                    return;
                }

                box.innerHTML = '';
                let hasSlots = false;

                rawCourts.forEach(c => {
                    const courtName = c.nome || c.quadra_nome || 'Quadra';
                    const courtSlots = c.slots || c.horarios || [];

                    courtSlots.filter(h => h.status === 'LIVRE').forEach(slot => {
                        hasSlots = true;
                        const priceVal = slot.valor || slot.preco || c.valor_padrao || 0;
                        const btn = document.createElement('div');
                        btn.style.cssText = 'background: var(--surface-kiosk); border: 2px solid var(--accent); border-radius: 20px; padding: 1.5rem; text-align: center; cursor: pointer; box-shadow: var(--glow-green);';
                        btn.innerHTML = `
                            <div style="font-size: 1.6rem; font-weight: 900; font-family: Outfit; color: #fff;">${slot.hora_inicio}</div>
                            <div style="font-size: 1rem; color: var(--accent-neon); font-weight: 700; margin: 0.3rem 0;">R$ ${Number(priceVal).toFixed(2)}</div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">${courtName}</div>
                        `;
                        btn.onclick = () => {
                            window.location.href = `/arena/${arenaSlug}`;
                        };
                        box.appendChild(btn);
                    });
                });

                if (!hasSlots) {
                    box.innerHTML = '<div style="color: var(--text-muted); font-size: 1.2rem;">Todas as quadras ja estao ocupadas para hoje!</div>';
                }
            } catch (err) {
                box.innerHTML = '<div style="color: #f87171;">Erro ao carregar horarios livres.</div>';
            }
        }

        // Initialize Kiosk
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await fetch(`/api/v1/arenas/slug/${arenaSlug}/portal-info`);
                const data = await res.json();
                if (data.success) {
                    arenaData = data.data.arena;
                    document.getElementById('kioskArenaName').innerText = arenaData.nome;
                    document.getElementById('kioskArenaSubtitle').innerText = `Autoatendimento • ${arenaData.cidade || 'Arena'}`;
                }
            } catch (e) {
                console.error('Kiosk init error:', e);
            }
        });
    </script>
</body>
</html>
