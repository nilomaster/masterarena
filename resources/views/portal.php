<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Quadras — Master Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #090d16;
            --bg-surface: #111827;
            --bg-card: rgba(24, 33, 49, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(56, 189, 248, 0.4);
            --primary: #0284c7;
            --primary-light: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.25);
            --accent: #10b981;
            --accent-glow: rgba(16, 185, 129, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            line-height: 1.5;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(2, 132, 199, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
        }

        h1, h2, h3, h4, .brand-title {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1rem 4rem;
        }

        /* Header Arena */
        .arena-header {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .arena-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .arena-badge {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #0369a1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 8px 16px var(--primary-glow);
        }

        .arena-meta h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .arena-location {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.2rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-portal {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid var(--border);
            padding: 0.65rem 1.2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-portal:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-portal.primary {
            background: linear-gradient(135deg, var(--primary), #0284c7);
            border: none;
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        .btn-portal.primary:hover {
            background: linear-gradient(135deg, #0369a1, #0284c7);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.4);
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 2rem;
        }

        .section-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary-light);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Date selector carousel */
        .dates-wrapper {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        .dates-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .dates-wrapper::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 999px;
        }

        .date-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.75rem 1.25rem;
            min-width: 90px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            user-select: none;
        }

        .date-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .date-card.active {
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.25), rgba(56, 189, 248, 0.15));
            border-color: var(--primary-light);
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .date-card .weekday {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .date-card .day-number {
            font-size: 1.4rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            color: #fff;
            margin: 0.2rem 0;
        }

        .date-card .month-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .date-card.active .weekday,
        .date-card.active .month-label {
            color: var(--primary-light);
        }

        /* Sports filters */
        .sports-filter {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 1.25rem;
        }

        .sport-pill {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 0.45rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .sport-pill:hover {
            color: #fff;
            border-color: var(--border-hover);
        }

        .sport-pill.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary-light);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        /* Courts Grid */
        .courts-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .court-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .court-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .court-title-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .court-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            border: 1px solid var(--border);
        }

        .court-title-area h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .court-title-area span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .court-tag {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 6px;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Slots row */
        .slots-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
            gap: 0.75rem;
        }

        .slot-btn {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.75rem 0.6rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            user-select: none;
        }

        .slot-btn.livre {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.05);
        }

        .slot-btn.livre:hover {
            background: rgba(16, 185, 129, 0.15);
            border-color: #10b981;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px var(--accent-glow);
        }

        .slot-btn.reservado {
            background: rgba(30, 41, 59, 0.4);
            border-color: rgba(255, 255, 255, 0.04);
            opacity: 0.55;
            cursor: not-allowed;
        }

        .slot-btn.bloqueado {
            background: rgba(239, 68, 68, 0.05);
            border-color: rgba(239, 68, 68, 0.2);
            opacity: 0.6;
            cursor: not-allowed;
        }

        .slot-time {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            font-family: 'Outfit', sans-serif;
        }

        .slot-price {
            font-size: 0.8rem;
            font-weight: 600;
            color: #34d399;
            margin-top: 0.2rem;
        }

        .slot-status-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            margin-top: 0.2rem;
            letter-spacing: 0.04em;
        }

        .slot-btn.livre .slot-status-label { color: #34d399; }
        .slot-btn.reservado .slot-status-label { color: #94a3b8; }
        .slot-btn.bloqueado .slot-status-label { color: #f87171; }

        /* Modal Structure */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: #111827;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            transform: scale(0.95);
            transition: transform 0.25s ease;
        }

        .modal-overlay.open .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-close:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }

        .form-control {
            width: 100%;
            background: #090d16;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.8rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .booking-summary-box {
            background: rgba(2, 132, 199, 0.08);
            border: 1px solid rgba(56, 189, 248, 0.25);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
            color: var(--text-muted);
        }

        .summary-row.total {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 0.6rem;
            margin-top: 0.6rem;
            margin-bottom: 0;
        }

        .summary-row.total .price-highlight {
            color: #34d399;
            font-size: 1.3rem;
            font-family: 'Outfit', sans-serif;
        }

        /* PIX Modal Area */
        .pix-display-area {
            text-align: center;
            padding: 1rem 0;
        }

        .qr-frame {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            margin-bottom: 1.25rem;
        }

        .qr-frame img {
            width: 200px;
            height: 200px;
            display: block;
        }

        .copia-cola-box {
            background: #090d16;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.75rem;
            font-family: monospace;
            font-size: 0.75rem;
            color: #94a3b8;
            word-break: break-all;
            margin-bottom: 1rem;
            max-height: 70px;
            overflow-y: auto;
            text-align: left;
        }

        .pulse-loader {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-light);
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-light);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.2; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        /* Success screen */
        .success-box {
            text-align: center;
            padding: 2rem 1rem;
        }

        .success-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .checkin-code-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            margin: 1.25rem 0;
            box-shadow: 0 10px 25px var(--accent-glow);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .arena-header { padding: 1.25rem; }
            .arena-meta h1 { font-size: 1.3rem; }
            .slots-container { grid-template-columns: repeat(2, 1fr); }
            .modal-content { padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Arena Header Section -->
        <header class="arena-header">
            <div class="arena-info">
                <div class="arena-badge" id="arenaBadge">🏟️</div>
                <div class="arena-meta">
                    <h1 id="arenaName">Carregando Arena...</h1>
                    <p class="arena-location" id="arenaLocation">📍 Conectando ao complexo esportivo...</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-portal" onclick="openMyBookingsModal()">
                    📅 Minhas Reservas
                </button>
                <a id="totemLink" href="#" class="btn-portal primary" target="_blank" style="display: none;">
                    🖥️ Modo Totem
                </a>
            </div>
        </header>

        <!-- Date and Sport Selection -->
        <section class="filter-section">
            <div class="section-label">
                <span>1. Escolha a Data do Jogo</span>
            </div>
            <div class="dates-wrapper" id="datesContainer">
                <!-- Injected via JavaScript -->
            </div>

            <div style="margin-top: 1.75rem;">
                <div class="section-label">
                    <span>2. Filtre por Modalidade</span>
                </div>
                <div class="sports-filter" id="sportsContainer">
                    <button class="sport-pill active" onclick="filterSport(null)">Todas as Modalidades</button>
                    <!-- Injected via JavaScript -->
                </div>
            </div>
        </section>

        <!-- Availability Grid & Courts -->
        <section>
            <div class="section-label" style="margin-bottom: 1rem;">
                <span>3. Escolha o Horario e a Quadra Desejada</span>
            </div>
            <div class="courts-grid" id="courtsContainer">
                <!-- Courts and availability slots injected dynamically -->
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <div class="pulse-loader"><div class="pulse-dot"></div> Carregando grade de disponibilidade em tempo real...</div>
                </div>
            </div>
        </section>
    </div>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeBookingModal()">✕</button>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 0.3rem;">Confirmar Reserva</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">Preencha seus dados para gerar o pagamento PIX instantaneo.</p>

            <div class="booking-summary-box">
                <div class="summary-row">
                    <span>Quadra / Modalidade:</span>
                    <strong style="color: #fff;" id="sumCourt">Quadra 1 (Beach Tennis)</strong>
                </div>
                <div class="summary-row">
                    <span>Data e Horario:</span>
                    <strong style="color: #fff;" id="sumDateTime">Hoje, 18:00 as 19:00</strong>
                </div>
                <div class="summary-row">
                    <span>Valor Original:</span>
                    <span id="sumOriginalPrice">R$ 90,00</span>
                </div>
                <div class="summary-row" id="sumDiscountRow" style="display: none; color: #34d399;">
                    <span>Desconto Cupom:</span>
                    <span id="sumDiscountPrice">- R$ 0,00</span>
                </div>
                <div class="summary-row total">
                    <span>Total a Pagar:</span>
                    <span class="price-highlight" id="sumFinalPrice">R$ 90,00</span>
                </div>
            </div>

            <form id="bookingForm" onsubmit="handleBookingSubmit(event)">
                <div class="form-group">
                    <label class="form-label" for="clientName">Seu Nome Completo *</label>
                    <input type="text" id="clientName" class="form-control" placeholder="Ex: Lucas Ferreira" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="clientPhone">WhatsApp / Celular (com DDD) *</label>
                    <input type="tel" id="clientPhone" class="form-control" placeholder="(11) 99999-9999" required maxlength="15" oninput="maskPhone(this)">
                </div>

                <div class="form-group">
                    <label class="form-label" for="couponCode">Cupom de Desconto (Opcional)</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="couponCode" class="form-control" placeholder="Ex: BEMVINDO10" style="text-transform: uppercase;">
                        <button type="button" class="btn-portal" onclick="applyCoupon()" id="btnApplyCoupon" style="flex-shrink: 0;">Aplicar</button>
                    </div>
                    <div id="couponFeedback" style="font-size: 0.8rem; margin-top: 0.35rem; display: none;"></div>
                </div>

                <button type="submit" class="btn-portal primary" id="btnConfirmBooking" style="width: 100%; justify-content: center; padding: 0.9rem; font-size: 1rem; margin-top: 0.5rem;">
                    ⚡ Pagar com PIX & Garantir Reserva
                </button>
            </form>
        </div>
    </div>

    <!-- PIX Payment & Confirmation Modal -->
    <div class="modal-overlay" id="pixModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closePixModal()">✕</button>
            
            <div id="pixStepPayment">
                <h2 style="font-size: 1.35rem; font-weight: 800; text-align: center;">Pagamento PIX Instantaneo</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-bottom: 1.25rem;">
                    Escaneie o QR Code ou copie o codigo. A liberacao e automatica!
                </p>

                <div class="pix-display-area">
                    <div class="qr-frame">
                        <img id="pixQrImage" src="" alt="QR Code PIX">
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Codigo PIX Copia e Cola:</div>
                    <div class="copia-cola-box" id="pixCopiaColaText">...</div>

                    <button type="button" class="btn-portal primary" onclick="copyPixCode()" id="btnCopyPix" style="width: 100%; justify-content: center; margin-bottom: 1rem;">
                        📋 Copiar Codigo PIX
                    </button>

                    <div class="pulse-loader">
                        <div class="pulse-dot"></div>
                        <span>Aguardando confirmacao do banco...</span>
                    </div>
                </div>
            </div>

            <!-- Success Confirmed State -->
            <div id="pixStepSuccess" style="display: none;">
                <div class="success-box">
                    <div class="success-icon">🎉</div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: #34d399; margin-bottom: 0.5rem;">Reserva Confirmada!</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Seu pagamento foi aprovado com sucesso.</p>

                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 1.5rem;">SEU CODIGO DE CHECK-IN:</div>
                    <div class="checkin-code-badge" id="confirmedCheckinCode">CHK-0000</div>

                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem;">
                        Apresente este codigo no Totem de Autoatendimento ao chegar na arena para liberar sua entrada.
                    </p>

                    <button type="button" class="btn-portal primary" onclick="location.reload()" style="width: 100%; justify-content: center;">
                        Concluir e Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- My Bookings Modal -->
    <div class="modal-overlay" id="myBookingsModal">
        <div class="modal-content" style="max-width: 580px;">
            <button class="modal-close" onclick="closeMyBookingsModal()">✕</button>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 0.3rem;">Minhas Reservas</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">Digite seu WhatsApp para consultar seus agendamentos.</p>

            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
                <input type="tel" id="searchPhone" class="form-control" placeholder="(11) 99999-9999" maxlength="15" oninput="maskPhone(this)">
                <button type="button" class="btn-portal primary" onclick="searchMyBookings()" id="btnSearchBookings">Buscar</button>
            </div>

            <div id="myBookingsList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 2rem 0;">
                    Informe seu telefone acima para visualizar suas reservas ativas.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Extract arena slug from URL
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        let arenaSlug = pathParts[pathParts.length - 1] || 'arena-master-beach';
        if (arenaSlug === 'portal' || arenaSlug === 'arena') {
            arenaSlug = 'arena-master-beach';
        }

        let currentArena = null;
        let selectedDate = new Date().toISOString().split('T')[0];
        let selectedSportId = null;
        let currentModalSlot = null;
        let appliedCoupon = null;
        let activePollingInterval = null;
        let activeBookingId = null;

        // Mask phone input
        function maskPhone(input) {
            let v = input.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (v.length > 6) {
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            }
            input.value = v;
        }

        // Initialize portal on load
        document.addEventListener('DOMContentLoaded', async () => {
            renderDateCards();
            await loadPortalInfo();
        });

        // Generate 14-days date selector
        function renderDateCards() {
            const container = document.getElementById('datesContainer');
            container.innerHTML = '';

            const today = new Date();
            const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

            for (let i = 0; i < 14; i++) {
                const d = new Date();
                d.setDate(today.getDate() + i);

                const dateStr = d.toISOString().split('T')[0];
                const isFirst = i === 0;

                const card = document.createElement('div');
                card.className = `date-card ${isFirst ? 'active' : ''}`;
                card.dataset.date = dateStr;
                card.onclick = () => selectDate(dateStr, card);

                card.innerHTML = `
                    <div class="weekday">${i === 0 ? 'Hoje' : (i === 1 ? 'Amanha' : weekDays[d.getDay()])}</div>
                    <div class="day-number">${String(d.getDate()).padStart(2, '0')}</div>
                    <div class="month-label">${months[d.getMonth()]}</div>
                `;

                container.appendChild(card);
            }
        }

        function selectDate(dateStr, element) {
            selectedDate = dateStr;
            document.querySelectorAll('.date-card').forEach(c => c.classList.remove('active'));
            element.classList.add('active');
            loadAvailabilityGrid();
        }

        function filterSport(sportId) {
            selectedSportId = sportId;
            document.querySelectorAll('.sport-pill').forEach(p => p.classList.remove('active'));
            event.target.classList.add('active');
            loadAvailabilityGrid();
        }

        // Load Arena base information and modalities
        async function loadPortalInfo() {
            try {
                const res = await fetch(`/api/v1/arenas/slug/${arenaSlug}/portal-info`);
                const data = await res.json();

                if (!data.success) {
                    document.getElementById('arenaName').innerText = 'Arena Nao Encontrada';
                    document.getElementById('arenaLocation').innerText = 'Verifique o endereco acessado.';
                    return;
                }

                currentArena = data.data.arena;
                document.getElementById('arenaName').innerText = currentArena.nome;
                document.getElementById('arenaLocation').innerText = `📍 ${currentArena.endereco || 'Endereco principal'} — ${currentArena.cidade || ''}/${currentArena.estado || ''}`;

                // Totem link
                const totemBtn = document.getElementById('totemLink');
                totemBtn.href = `/totem/${currentArena.slug}`;
                totemBtn.style.display = 'inline-flex';

                // Render sport pills
                const sportsBox = document.getElementById('sportsContainer');
                data.data.modalidades.forEach(m => {
                    const pill = document.createElement('button');
                    pill.className = 'sport-pill';
                    pill.innerText = `${m.icone || '⚽'} ${m.nome}`;
                    pill.onclick = (e) => filterSport(m.id);
                    sportsBox.appendChild(pill);
                });

                // Load initial grid
                await loadAvailabilityGrid();
            } catch (err) {
                console.error(err);
            }
        }

        // Load availability grid for selected date and arena
        async function loadAvailabilityGrid() {
            if (!currentArena) return;

            const courtsBox = document.getElementById('courtsContainer');
            courtsBox.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <div class="pulse-loader"><div class="pulse-dot"></div> Atualizando disponibilidade...</div>
                </div>
            `;

            try {
                const res = await fetch(`/api/v1/arenas/${currentArena.id}/grade?data=${selectedDate}`);
                const data = await res.json();

                let rawCourts = [];
                if (data.data && data.data.grade) {
                    if (data.data.grade.quadras) rawCourts = data.data.grade.quadras;
                    else if (Array.isArray(data.data.grade)) rawCourts = data.data.grade;
                }

                if (!data.success || rawCourts.length === 0) {
                    courtsBox.innerHTML = `
                        <div class="court-card" style="text-align: center; padding: 2.5rem;">
                            <p style="color: var(--text-muted);">Nenhuma quadra disponivel para a data selecionada.</p>
                        </div>
                    `;
                    return;
                }

                let filteredCourts = rawCourts;
                if (selectedSportId) {
                    filteredCourts = filteredCourts.filter(c => c.modalidade_id == selectedSportId);
                }

                if (filteredCourts.length === 0) {
                    courtsBox.innerHTML = `
                        <div class="court-card" style="text-align: center; padding: 2.5rem;">
                            <p style="color: var(--text-muted);">Nenhuma quadra encontrada para esta modalidade na data selecionada.</p>
                        </div>
                    `;
                    return;
                }

                courtsBox.innerHTML = '';

                filteredCourts.forEach(court => {
                    const card = document.createElement('div');
                    card.className = 'court-card';

                    const courtId = court.id || court.quadra_id;
                    const courtName = court.nome || court.quadra_nome || 'Quadra';
                    const sportName = court.modalidade_nome || 'Esporte';
                    const slots = court.slots || court.horarios || [];

                    let slotsHtml = '';
                    slots.forEach(slot => {
                        const statusClass = slot.status.toLowerCase();
                        const isClickable = slot.status === 'LIVRE';
                        const priceVal = slot.valor || slot.preco || court.valor_padrao || 0;
                        const priceFormatted = Number(priceVal).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

                        slotsHtml += `
                            <div class="slot-btn ${statusClass}" ${isClickable ? `onclick="openBookingModal(${courtId}, '${courtName}', '${sportName}', '${slot.hora_inicio}', '${slot.hora_fim}', ${priceVal})"` : ''}>
                                <div class="slot-time">${slot.hora_inicio}</div>
                                ${slot.status === 'LIVRE' ? `<div class="slot-price">${priceFormatted}</div>` : ''}
                                <div class="slot-status-label">${slot.status === 'LIVRE' ? 'Disponivel' : (slot.status === 'RESERVADO' ? 'Ocupado' : 'Indisponivel')}</div>
                            </div>
                        `;
                    });

                    card.innerHTML = `
                        <div class="court-header">
                            <div class="court-title-area">
                                <div class="court-icon">⚡</div>
                                <div>
                                    <h3>${courtName}</h3>
                                    <span>${sportName} • ${court.coberta ? 'Quadra Coberta' : 'Quadra Aberta'} • Piso: ${court.tipo_piso || 'Padrao'}</span>
                                </div>
                            </div>
                            <span class="court-tag">Ativa</span>
                        </div>
                        <div class="slots-container">
                            ${slotsHtml}
                        </div>
                    `;

                    courtsBox.appendChild(card);
                });
            } catch (err) {
                courtsBox.innerHTML = `<div style="color: #ef4444; padding: 2rem; text-align: center;">Erro ao carregar horarios: ${err.message}</div>`;
            }
        }

        // Open booking modal
        function openBookingModal(courtId, courtName, sportName, horaInicio, horaFim, price) {
            currentModalSlot = {
                courtId,
                courtName,
                sportName,
                horaInicio,
                horaFim,
                price: Number(price)
            };
            appliedCoupon = null;

            document.getElementById('sumCourt').innerText = `${courtName} (${sportName})`;
            document.getElementById('sumDateTime').innerText = `${formatDateBr(selectedDate)}, ${horaInicio} as ${horaFim}`;
            document.getElementById('sumOriginalPrice').innerText = Number(price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            document.getElementById('sumFinalPrice').innerText = Number(price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            document.getElementById('sumDiscountRow').style.display = 'none';
            document.getElementById('couponCode').value = '';
            document.getElementById('couponFeedback').style.display = 'none';

            document.getElementById('bookingModal').classList.add('open');
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('open');
            currentModalSlot = null;
        }

        // Apply discount coupon
        async function applyCoupon() {
            const code = document.getElementById('couponCode').value.trim().toUpperCase();
            const feedback = document.getElementById('couponFeedback');
            const btn = document.getElementById('btnApplyCoupon');

            if (!code) {
                feedback.style.display = 'block';
                feedback.style.color = '#f87171';
                feedback.innerText = 'Digite um codigo de cupom.';
                return;
            }

            btn.innerText = 'Validando...';

            try {
                const res = await fetch(`/api/v1/arenas/${currentArena.id}/cupons/validar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        codigo: code,
                        valor_total: currentModalSlot.price
                    })
                });
                const data = await res.json();

                if (!data.success) {
                    appliedCoupon = null;
                    feedback.style.display = 'block';
                    feedback.style.color = '#f87171';
                    feedback.innerText = data.message || 'Cupom invalido ou expirado.';
                    document.getElementById('sumDiscountRow').style.display = 'none';
                    document.getElementById('sumFinalPrice').innerText = currentModalSlot.price.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                } else {
                    appliedCoupon = data.data;
                    feedback.style.display = 'block';
                    feedback.style.color = '#34d399';
                    feedback.innerText = `Cupom ${code} aplicado com sucesso! Desconto: R$ ${Number(data.data.desconto).toFixed(2)}`;

                    document.getElementById('sumDiscountRow').style.display = 'flex';
                    document.getElementById('sumDiscountPrice').innerText = `- R$ ${Number(data.data.desconto).toFixed(2)}`;
                    document.getElementById('sumFinalPrice').innerText = Number(data.data.valor_final).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                }
            } catch (err) {
                feedback.style.display = 'block';
                feedback.style.color = '#f87171';
                feedback.innerText = 'Erro na conexao com o servidor.';
            } finally {
                btn.innerText = 'Aplicar';
            }
        }

        // Submit Booking and generate PIX
        async function handleBookingSubmit(e) {
            e.preventDefault();
            if (!currentModalSlot || !currentArena) return;

            const name = document.getElementById('clientName').value.trim();
            const phone = document.getElementById('clientPhone').value.trim();
            const btn = document.getElementById('btnConfirmBooking');

            if (!name || !phone) {
                alert('Preencha seu nome e telefone.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Criando Reserva & Gerando PIX...';

            try {
                // 1. Create booking
                const bookingRes = await fetch(`/api/v1/arenas/${currentArena.id}/agendamentos`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        quadra_id: currentModalSlot.courtId,
                        data: selectedDate,
                        hora_inicio: currentModalSlot.horaInicio,
                        hora_fim: currentModalSlot.horaFim,
                        cliente_nome: name,
                        cliente_telefone: phone,
                        cupom: appliedCoupon ? appliedCoupon.cupom.codigo : null
                    })
                });

                const bookingData = await bookingRes.json();
                if (!bookingData.success) {
                    alert(bookingData.message || 'Falha ao criar agendamento.');
                    btn.disabled = false;
                    btn.innerText = '⚡ Pagar com PIX & Garantir Reserva';
                    return;
                }

                const bookingId = bookingData.data.agendamento.id;
                activeBookingId = bookingId;

                // 2. Generate PIX for booking
                const pixRes = await fetch(`/api/v1/agendamentos/${bookingId}/pix`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const pixData = await pixRes.json();
                if (!pixData.success) {
                    alert('Reserva criada, mas houve falha ao gerar o codigo PIX. Procure a recepcao.');
                    btn.disabled = false;
                    return;
                }

                // Close booking modal and open PIX modal
                closeBookingModal();
                openPixModal(pixData.data.pix.copia_e_cola, bookingId);
            } catch (err) {
                alert('Erro na comunicacao com a arena: ' + err.message);
                btn.disabled = false;
                btn.innerText = '⚡ Pagar com PIX & Garantir Reserva';
            }
        }

        // Open PIX Modal and start polling
        function openPixModal(copiaECola, bookingId) {
            document.getElementById('pixStepPayment').style.display = 'block';
            document.getElementById('pixStepSuccess').style.display = 'none';

            document.getElementById('pixCopiaColaText').innerText = copiaECola;
            
            // Generate QR Code image using reliable public API
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(copiaECola)}`;
            document.getElementById('pixQrImage').src = qrUrl;

            document.getElementById('pixModal').classList.add('open');

            // Start polling for payment settlement
            startPaymentPolling(bookingId);
        }

        function closePixModal() {
            if (activePollingInterval) clearInterval(activePollingInterval);
            document.getElementById('pixModal').classList.remove('open');
            activeBookingId = null;
        }

        function copyPixCode() {
            const text = document.getElementById('pixCopiaColaText').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btnCopyPix');
                const orig = btn.innerText;
                btn.innerText = '✓ Codigo Copiado!';
                btn.style.background = '#10b981';
                setTimeout(() => {
                    btn.innerText = orig;
                    btn.style.background = '';
                }, 2000);
            });
        }

        // Polling payment status every 3 seconds
        function startPaymentPolling(bookingId) {
            if (activePollingInterval) clearInterval(activePollingInterval);

            activePollingInterval = setInterval(async () => {
                try {
                    const res = await fetch(`/api/v1/agendamentos/${bookingId}/public-status`);
                    const data = await res.json();

                    if (data.success && data.data.agendamento.status === 'CONFIRMADO') {
                        clearInterval(activePollingInterval);
                        showPaymentSuccess(data.data.agendamento);
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }, 3000);
        }

        function showPaymentSuccess(booking) {
            document.getElementById('pixStepPayment').style.display = 'none';
            document.getElementById('pixStepSuccess').style.display = 'block';
            document.getElementById('confirmedCheckinCode').innerText = booking.codigo_checkin || `CHK-${booking.id}`;
        }

        // My Bookings lookup
        function openMyBookingsModal() {
            document.getElementById('myBookingsModal').classList.add('open');
        }

        function closeMyBookingsModal() {
            document.getElementById('myBookingsModal').classList.remove('open');
        }

        async function searchMyBookings() {
            const phone = document.getElementById('searchPhone').value.trim();
            const list = document.getElementById('myBookingsList');
            const btn = document.getElementById('btnSearchBookings');

            if (!phone) {
                alert('Informe seu WhatsApp.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Buscando...';

            try {
                const res = await fetch(`/api/v1/arenas/${currentArena.id}/clientes/minhas-reservas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ telefone: phone })
                });

                const data = await res.json();
                if (!data.success || data.data.agendamentos.length === 0) {
                    list.innerHTML = `
                        <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                            Nenhuma reserva ativa encontrada para este numero.
                        </div>
                    `;
                    return;
                }

                list.innerHTML = '';
                data.data.agendamentos.forEach(b => {
                    const isConfirmed = b.status === 'CONFIRMADO';
                    const item = document.createElement('div');
                    item.style.cssText = 'background: #090d16; border: 1px solid var(--border); border-radius: 10px; padding: 1rem;';
                    item.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <div>
                                <strong style="color: #fff; font-size: 1rem;">${b.quadra_nome || 'Quadra'}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">${b.modalidade_nome || 'Esporte'}</div>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px; ${isConfirmed ? 'background: rgba(16, 185, 129, 0.2); color: #34d399;' : 'background: rgba(234, 179, 8, 0.2); color: #facc15;'}">
                                ${b.status}
                            </span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            📅 ${formatDateBr(b.data)} as ${b.hora_inicio.substring(0, 5)}
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 0.5rem; font-size: 0.85rem;">
                            <span style="color: var(--text-muted);">Codigo Check-in:</span>
                            <code style="background: #1e293b; color: #38bdf8; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: bold;">${b.codigo_checkin || 'CHK-' + b.id}</code>
                        </div>
                    `;
                    list.appendChild(item);
                });
            } catch (err) {
                list.innerHTML = `<div style="color: #ef4444; padding: 1rem;">Erro ao consultar reservas: ${err.message}</div>`;
            } finally {
                btn.disabled = false;
                btn.innerText = 'Buscar';
            }
        }

        function formatDateBr(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
            return dateStr;
        }
    </script>
</body>
</html>
