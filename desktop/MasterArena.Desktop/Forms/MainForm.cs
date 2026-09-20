// Master Arena Desktop - Main Application Form
// Comments strictly in ASCII only.

using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using MasterArena.Desktop.Helpers;
using MasterArena.Desktop.Models;
using MasterArena.Desktop.Services;

namespace MasterArena.Desktop.Forms
{
    public class MainForm : Form
    {
        // Service Instances
        private readonly ArenaService _arenaService = new();
        private readonly ScheduleService _scheduleService = new();
        private readonly CashRegisterService _cashService = new();
        private readonly PaymentService _paymentService = new();
        private readonly CheckinService _checkinService = new();

        // Data caches
        private List<CourtDto> _cachedCourts = new();
        private CashRegisterStatusDto? _activeCashStatus;
        private System.Windows.Forms.Timer? _pixPollingTimer;
        private int _activePixBookingId = 0;

        // UI Controls - Header
        private Label _lblArenaHeader = null!;
        private Label _lblUserBadge = null!;
        private Label _lblCashStatusBadge = null!;
        private Button _btnLogout = null!;

        // UI Controls - Navigation
        private Panel _sidebarPanel = null!;
        private Panel _contentPanel = null!;
        private readonly Dictionary<string, Panel> _views = new();
        private string _activeViewKey = "GRADE";

        // View 1: Schedule Grid
        private DateTimePicker _dtpGrade = null!;
        private FlowLayoutPanel _flpGradeCourts = null!;
        private Label _lblGradeStatus = null!;

        // View 2: Counter POS (Frente de Caixa)
        private TextBox _txtPosBookingId = null!;
        private Label _lblPosClientInfo = null!;
        private Label _lblPosAmountInfo = null!;
        private PictureBox _picPixQr = null!;
        private TextBox _txtPixPayload = null!;
        private Label _lblPixStatus = null!;
        private Button _btnPosGeneratePix = null!;
        private Button _btnPosCashPay = null!;
        private Button _btnPosCardPay = null!;
        private BookingDetailDto? _selectedPosBooking;

        // View 3: Cash Management
        private Label _lblCashRegisterTitle = null!;
        private Label _lblCashSaldoAbertura = null!;
        private Label _lblCashTotalDinheiro = null!;
        private Label _lblCashTotalSuprimentos = null!;
        private Label _lblCashTotalSangrias = null!;
        private Label _lblCashSaldoEsperado = null!;
        private Label _lblCashTotalPix = null!;
        private Label _lblCashTotalCartao = null!;
        private Button _btnOpenCashShift = null!;
        private Button _btnSangria = null!;
        private Button _btnSuprimento = null!;
        private Button _btnCloseCashShift = null!;

        // View 4: Checkin Terminal
        private TextBox _txtCheckinCode = null!;
        private Panel _pnlCheckinResult = null!;
        private Label _lblCheckinIcon = null!;
        private Label _lblCheckinMessage = null!;
        private Label _lblCheckinDetails = null!;

        // View 5: Settings / Connectivity
        private Label _lblApiEndpoint = null!;
        private Label _lblConnectionHealth = null!;

        public MainForm()
        {
            InitializeComponent();
        }

        protected override async void OnLoad(EventArgs e)
        {
            base.OnLoad(e);
            UpdateHeaderLabels();
            await LoadCourtsAsync();
            await RefreshCashStatusAsync();
            await LoadScheduleGradeAsync();
        }

        private void InitializeComponent()
        {
            Text = "MASTER ARENA - Operacao Balcao & Frente de Caixa";
            Size = new Size(1280, 800);
            MinimumSize = new Size(1100, 700);
            StartPosition = FormStartPosition.CenterScreen;
            BackColor = ThemeColors.BgBody;
            ForeColor = ThemeColors.TextMain;
            Font = new Font("Segoe UI", 9.5F, FontStyle.Regular, GraphicsUnit.Point);

            // 1. Top Header Bar (Height: 70)
            var headerPanel = new Panel
            {
                Dock = DockStyle.Top,
                Height = 70,
                BackColor = ThemeColors.BgHeader,
                Padding = new Padding(20, 10, 20, 10)
            };

            var lblLogo = new Label
            {
                Text = "⚡ MASTER ARENA",
                Font = new Font("Segoe UI", 14F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(20, 12),
                AutoSize = true
            };

            _lblArenaHeader = new Label
            {
                Text = "Arena Operacional",
                Font = new Font("Segoe UI", 9F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(22, 38),
                AutoSize = true
            };

            _lblCashStatusBadge = new Label
            {
                Text = "CAIXA: VERIFICANDO...",
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentAmber,
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(10, 6, 10, 6),
                Location = new Point(500, 18),
                AutoSize = true
            };

            _lblUserBadge = new Label
            {
                Text = "Operador: Identificando...",
                Font = new Font("Segoe UI", 9F),
                ForeColor = ThemeColors.TextMain,
                Location = new Point(860, 24),
                AutoSize = true
            };

            _btnLogout = new Button
            {
                Text = "SAIR",
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                Size = new Size(80, 34),
                Location = new Point(1160, 18),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.AccentRed,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnLogout.FlatAppearance.BorderColor = ThemeColors.Border;
            _btnLogout.Click += (s, e) => Logout();

            headerPanel.Controls.Add(lblLogo);
            headerPanel.Controls.Add(_lblArenaHeader);
            headerPanel.Controls.Add(_lblCashStatusBadge);
            headerPanel.Controls.Add(_lblUserBadge);
            headerPanel.Controls.Add(_btnLogout);

            // 2. Sidebar Panel (Width: 230)
            _sidebarPanel = new Panel
            {
                Dock = DockStyle.Left,
                Width = 230,
                BackColor = ThemeColors.BgSidebar,
                Padding = new Padding(12, 16, 12, 16)
            };

            CreateSidebarButton("GRADE", "📅  Grade de Quadras", 16);
            CreateSidebarButton("PDV", "💳  Frente de Caixa (PDV)", 66);
            CreateSidebarButton("CAIXA", "💰  Gestao de Caixa", 116);
            CreateSidebarButton("CHECKIN", "🎫  Terminal de Check-in", 166);
            CreateSidebarButton("CONFIG", "⚙️  Conectividade & API", 216);

            // 3. Content Panel
            _contentPanel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(20)
            };

            // Setup Views
            SetupGradeView();
            SetupPosView();
            SetupCashView();
            SetupCheckinView();
            SetupConfigView();

            Controls.Add(_contentPanel);
            Controls.Add(_sidebarPanel);
            Controls.Add(headerPanel);

            SwitchView("GRADE");
        }

        private void CreateSidebarButton(string key, string text, int top)
        {
            var btn = new Button
            {
                Name = $"btnNav_{key}",
                Text = text,
                Tag = key,
                Top = top,
                Left = 10,
                Width = 210,
                Height = 42,
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(12, 0, 0, 0),
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Regular),
                ForeColor = ThemeColors.TextMuted,
                BackColor = ThemeColors.BgSidebar,
                Cursor = Cursors.Hand
            };
            btn.FlatAppearance.BorderSize = 0;
            btn.Click += (s, e) => SwitchView(key);

            _sidebarPanel.Controls.Add(btn);
        }

        private void SwitchView(string viewKey)
        {
            _activeViewKey = viewKey;

            foreach (Control ctrl in _sidebarPanel.Controls)
            {
                if (ctrl is Button b && b.Tag is string k)
                {
                    bool isActive = k == viewKey;
                    b.BackColor = isActive ? ThemeColors.BgCardHover : ThemeColors.BgSidebar;
                    b.ForeColor = isActive ? ThemeColors.AccentLime : ThemeColors.TextMuted;
                    b.Font = new Font("Segoe UI", 9.5F, isActive ? FontStyle.Bold : FontStyle.Regular);
                }
            }

            foreach (var kvp in _views)
            {
                kvp.Value.Visible = kvp.Key == viewKey;
            }

            // Auto-actions on switch
            if (viewKey == "CAIXA")
            {
                _ = RefreshCashStatusAsync();
            }
            else if (viewKey == "CHECKIN")
            {
                _txtCheckinCode.Focus();
            }
        }

        private void UpdateHeaderLabels()
        {
            var user = SessionContext.CurrentUser;
            var arena = SessionContext.ActiveArena;

            _lblArenaHeader.Text = arena != null
                ? $"{arena.Nome} ({arena.Cidade}/{arena.Estado})"
                : "Arena Principal Master Arena";

            _lblUserBadge.Text = user != null
                ? $"Operador: {user.Nome} [{user.Role}]"
                : "Operador Conectado";
        }

        // ==========================================
        // 1. VIEW: GRADE DE HORARIOS
        // ==========================================
        private void SetupGradeView()
        {
            var panel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody
            };

            // Top action bar
            var topBar = new Panel
            {
                Dock = DockStyle.Top,
                Height = 50,
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(12)
            };

            var lblDate = new Label
            {
                Text = "Data:",
                ForeColor = ThemeColors.TextMuted,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                Location = new Point(14, 15),
                AutoSize = true
            };

            _dtpGrade = new DateTimePicker
            {
                Location = new Point(56, 12),
                Size = new Size(140, 26),
                Format = DateTimePickerFormat.Short,
                Value = DateTime.Today
            };
            _dtpGrade.ValueChanged += async (s, e) => await LoadScheduleGradeAsync();

            var btnRefresh = new Button
            {
                Text = "↻ Atualizar Grade",
                Location = new Point(210, 11),
                Size = new Size(130, 28),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnRefresh.FlatAppearance.BorderColor = ThemeColors.Border;
            btnRefresh.Click += async (s, e) => await LoadScheduleGradeAsync();

            var btnNewBooking = new Button
            {
                Text = "+ Nova Reserva Balcao",
                Location = new Point(350, 11),
                Size = new Size(170, 28),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnNewBooking.FlatAppearance.BorderSize = 0;
            btnNewBooking.Click += (s, e) => OpenBookingModal();

            _lblGradeStatus = new Label
            {
                Text = "Carregando disponibilidade...",
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(540, 15),
                AutoSize = true
            };

            topBar.Controls.Add(lblDate);
            topBar.Controls.Add(_dtpGrade);
            topBar.Controls.Add(btnRefresh);
            topBar.Controls.Add(btnNewBooking);
            topBar.Controls.Add(_lblGradeStatus);

            _flpGradeCourts = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(10)
            };

            panel.Controls.Add(_flpGradeCourts);
            panel.Controls.Add(topBar);

            _views["GRADE"] = panel;
            _contentPanel.Controls.Add(panel);
        }

        private async Task LoadCourtsAsync()
        {
            var res = await _arenaService.GetCourtsAsync(SessionContext.ActiveArenaId);
            if (res.Success && res.Data?.Quadras != null)
            {
                _cachedCourts = res.Data.Quadras;
            }
        }

        private async Task LoadScheduleGradeAsync()
        {
            _lblGradeStatus.ForeColor = ThemeColors.AccentCyan;
            _lblGradeStatus.Text = "Consultando disponibilidade de quadras...";
            _flpGradeCourts.Controls.Clear();

            var dateStr = _dtpGrade.Value.ToString("yyyy-MM-dd");
            var res = await _scheduleService.GetGradeAsync(SessionContext.ActiveArenaId, dateStr);

            if (!res.Success || res.Data?.Quadras == null || res.Data.Quadras.Count == 0)
            {
                _lblGradeStatus.ForeColor = ThemeColors.AccentRed;
                _lblGradeStatus.Text = res.Message ?? "Nenhuma quadra ou horario disponivel para a data.";
                return;
            }

            _lblGradeStatus.ForeColor = ThemeColors.AccentLime;
            _lblGradeStatus.Text = $"Grade carregada com sucesso ({res.Data.Quadras.Count} quadras)";

            foreach (var court in res.Data.Quadras)
            {
                var courtCard = new GroupBox
                {
                    Text = $"  {court.QuadraNome}  [{court.ModalidadeNome}]  ",
                    Width = 320,
                    Height = 480,
                    Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                    ForeColor = ThemeColors.AccentLime,
                    BackColor = ThemeColors.BgCard,
                    Padding = new Padding(10),
                    Margin = new Padding(10)
                };

                var slotContainer = new FlowLayoutPanel
                {
                    Dock = DockStyle.Fill,
                    AutoScroll = true,
                    BackColor = ThemeColors.BgCard
                };

                foreach (var slot in court.Horarios)
                {
                    var isAvailable = slot.Status == "LIVRE";
                    var isBooked = slot.Status == "RESERVADO";

                    var btnSlot = new Button
                    {
                        Width = 280,
                        Height = 44,
                        Margin = new Padding(4),
                        FlatStyle = FlatStyle.Flat,
                        Cursor = Cursors.Hand,
                        TextAlign = ContentAlignment.MiddleLeft,
                        Font = new Font("Segoe UI", 8.5F)
                    };

                    if (isAvailable)
                    {
                        btnSlot.Text = $" {slot.HoraInicio} - {slot.HoraFim} | R$ {slot.Valor:N2} (LIVRE)";
                        btnSlot.BackColor = ThemeColors.BgCardHover;
                        btnSlot.ForeColor = ThemeColors.AccentLime;
                        btnSlot.FlatAppearance.BorderColor = ThemeColors.Border;
                        btnSlot.Click += (s, e) =>
                        {
                            OpenBookingModal(court.QuadraId, dateStr, slot.HoraInicio);
                        };
                    }
                    else if (isBooked)
                    {
                        var client = string.IsNullOrEmpty(slot.ClienteNome) ? "Cliente" : slot.ClienteNome;
                        btnSlot.Text = $" {slot.HoraInicio} - {slot.HoraFim} | {client}";
                        btnSlot.BackColor = Color.FromArgb(40, 20, 20);
                        btnSlot.ForeColor = ThemeColors.AccentRed;
                        btnSlot.FlatAppearance.BorderColor = ThemeColors.AccentRed;
                        btnSlot.Click += (s, e) =>
                        {
                            if (slot.AgendamentoId.HasValue)
                            {
                                LoadBookingToPos(slot.AgendamentoId.Value);
                            }
                        };
                    }
                    else
                    {
                        btnSlot.Text = $" {slot.HoraInicio} - {slot.HoraFim} | BLOQUEADO";
                        btnSlot.BackColor = ThemeColors.BgInput;
                        btnSlot.ForeColor = ThemeColors.TextMuted;
                        btnSlot.Enabled = false;
                    }

                    slotContainer.Controls.Add(btnSlot);
                }

                courtCard.Controls.Add(slotContainer);
                _flpGradeCourts.Controls.Add(courtCard);
            }
        }

        private void OpenBookingModal(int? courtId = null, string? date = null, string? start = null)
        {
            using var modal = new BookingModalForm(_cachedCourts, courtId, date, start);
            if (modal.ShowDialog(this) == DialogResult.OK && modal.CreatedBooking != null)
            {
                MessageBox.Show(
                    $"Reserva confirmada no balcao!\nCodigo: {modal.CreatedBooking.CodigoCheckin}\nValor: R$ {modal.CreatedBooking.ValorFinal:N2}",
                    "Reserva Registrada",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information
                );

                _ = LoadScheduleGradeAsync();

                // Direct to POS for checkout
                LoadBookingToPos(modal.CreatedBooking.Id);
                SwitchView("PDV");
            }
        }

        // ==========================================
        // 2. VIEW: FRENTE DE CAIXA (PDV BALCAO)
        // ==========================================
        private void SetupPosView()
        {
            var panel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(16)
            };

            var lblTitle = new Label
            {
                Text = "FRENTE DE CAIXA & PAGAMENTO BALCAO",
                Font = new Font("Segoe UI", 13F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(16, 12),
                AutoSize = true
            };

            // Left Box: Booking selection & summary
            var grpBooking = new GroupBox
            {
                Text = " 1. Identificar Agendamento / Cobranca ",
                Location = new Point(16, 50),
                Size = new Size(460, 600),
                BackColor = ThemeColors.BgCard,
                ForeColor = ThemeColors.TextMain,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                Padding = new Padding(16)
            };

            var lblSearch = new Label
            {
                Text = "ID DA RESERVA:",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 35),
                AutoSize = true
            };

            _txtPosBookingId = new TextBox
            {
                Location = new Point(20, 56),
                Size = new Size(260, 28),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle
            };

            var btnFindBooking = new Button
            {
                Text = "Buscar",
                Location = new Point(290, 55),
                Size = new Size(140, 30),
                BackColor = ThemeColors.AccentCyan,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnFindBooking.FlatAppearance.BorderSize = 0;
            btnFindBooking.Click += async (s, e) =>
            {
                if (int.TryParse(_txtPosBookingId.Text.Trim(), out var bId))
                {
                    await FetchBookingToPosAsync(bId);
                }
                else
                {
                    MessageBox.Show("Informe um numero valido de agendamento.", "Aviso", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
            };

            _lblPosClientInfo = new Label
            {
                Location = new Point(20, 110),
                Size = new Size(410, 140),
                Font = new Font("Segoe UI", 9.5F),
                ForeColor = ThemeColors.TextMain,
                BackColor = ThemeColors.BgInput,
                Padding = new Padding(12),
                Text = "Nenhum agendamento selecionado.\nSelecione um horario na grade ou informe o ID acima."
            };

            _lblPosAmountInfo = new Label
            {
                Location = new Point(20, 270),
                Size = new Size(410, 80),
                Font = new Font("Segoe UI", 16F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                BackColor = ThemeColors.BgCardHover,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "TOTAL: R$ 0,00"
            };

            // Payment method triggers
            _btnPosGeneratePix = new Button
            {
                Text = "⚡ GERAR PIX DINAMICO NA TELA",
                Location = new Point(20, 370),
                Size = new Size(410, 48),
                BackColor = ThemeColors.AccentCyan,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                Enabled = false
            };
            _btnPosGeneratePix.FlatAppearance.BorderSize = 0;
            _btnPosGeneratePix.Click += async (s, e) => await HandleGeneratePixAsync();

            _btnPosCashPay = new Button
            {
                Text = "💵 RECEBER EM DINHEIRO (GAVETA)",
                Location = new Point(20, 430),
                Size = new Size(410, 44),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                Enabled = false
            };
            _btnPosCashPay.FlatAppearance.BorderSize = 0;
            _btnPosCashPay.Click += async (s, e) => await HandleCashPaymentAsync();

            _btnPosCardPay = new Button
            {
                Text = "💳 RECEBER CARTAO NA MAQUININHA (POS)",
                Location = new Point(20, 485),
                Size = new Size(410, 44),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMain,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                Enabled = false
            };
            _btnPosCardPay.FlatAppearance.BorderColor = ThemeColors.Border;
            _btnPosCardPay.Click += async (s, e) => await HandleCardPaymentAsync();

            grpBooking.Controls.Add(lblSearch);
            grpBooking.Controls.Add(_txtPosBookingId);
            grpBooking.Controls.Add(btnFindBooking);
            grpBooking.Controls.Add(_lblPosClientInfo);
            grpBooking.Controls.Add(_lblPosAmountInfo);
            grpBooking.Controls.Add(_btnPosGeneratePix);
            grpBooking.Controls.Add(_btnPosCashPay);
            grpBooking.Controls.Add(_btnPosCardPay);

            // Right Box: PIX Display and Polling
            var grpPix = new GroupBox
            {
                Text = " 2. QR Code PIX para o Cliente Escanear ",
                Location = new Point(495, 50),
                Size = new Size(480, 600),
                BackColor = ThemeColors.BgCard,
                ForeColor = ThemeColors.TextMain,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                Padding = new Padding(16)
            };

            _picPixQr = new PictureBox
            {
                Location = new Point(120, 40),
                Size = new Size(240, 240),
                BackColor = Color.White,
                SizeMode = PictureBoxSizeMode.Zoom,
                BorderStyle = BorderStyle.FixedSingle
            };

            _lblPixStatus = new Label
            {
                Location = new Point(20, 295),
                Size = new Size(440, 35),
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentAmber,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "Aguardando geracao do PIX..."
            };

            var lblPixPayloadTitle = new Label
            {
                Text = "CODIGO PIX COPIA E COLA:",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 340),
                AutoSize = true
            };

            _txtPixPayload = new TextBox
            {
                Location = new Point(20, 360),
                Size = new Size(440, 70),
                Multiline = true,
                ReadOnly = true,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle,
                Font = new Font("Consolas", 8.5F)
            };

            var btnCopyPix = new Button
            {
                Text = "📋 Copiar Codigo PIX",
                Location = new Point(20, 440),
                Size = new Size(440, 36),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCopyPix.FlatAppearance.BorderColor = ThemeColors.Border;
            btnCopyPix.Click += (s, e) =>
            {
                if (!string.IsNullOrEmpty(_txtPixPayload.Text))
                {
                    Clipboard.SetText(_txtPixPayload.Text);
                    MessageBox.Show("Chave PIX copiada para a area de transferencia.", "Copiado", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            };

            grpPix.Controls.Add(_picPixQr);
            grpPix.Controls.Add(_lblPixStatus);
            grpPix.Controls.Add(lblPixPayloadTitle);
            grpPix.Controls.Add(_txtPixPayload);
            grpPix.Controls.Add(btnCopyPix);

            panel.Controls.Add(lblTitle);
            panel.Controls.Add(grpBooking);
            panel.Controls.Add(grpPix);

            _views["PDV"] = panel;
            _contentPanel.Controls.Add(panel);
        }

        private async void LoadBookingToPos(int bookingId)
        {
            _txtPosBookingId.Text = bookingId.ToString();
            await FetchBookingToPosAsync(bookingId);
        }

        private async Task FetchBookingToPosAsync(int bookingId)
        {
            _lblPosClientInfo.Text = "Carregando informacoes da reserva...";
            _btnPosGeneratePix.Enabled = false;
            _btnPosCashPay.Enabled = false;
            _btnPosCardPay.Enabled = false;

            var res = await _paymentService.GetBookingStatusAsync(bookingId);
            if (res.Success && res.Data != null)
            {
                _selectedPosBooking = res.Data;
                _lblPosClientInfo.Text =
                    $"Cliente: {_selectedPosBooking.ClienteNome}\n" +
                    $"Telefone: {_selectedPosBooking.ClienteTelefone}\n" +
                    $"Data/Hora: {_selectedPosBooking.Data} ({_selectedPosBooking.HoraInicio} as {_selectedPosBooking.HoraFim})\n" +
                    $"Status Atual: {_selectedPosBooking.Status}\n" +
                    $"Codigo Check-in: {_selectedPosBooking.CodigoCheckin}";

                _lblPosAmountInfo.Text = $"TOTAL: R$ {_selectedPosBooking.ValorFinal:N2}";

                bool isPending = _selectedPosBooking.Status == "PENDENTE";
                _btnPosGeneratePix.Enabled = isPending;
                _btnPosCashPay.Enabled = isPending;
                _btnPosCardPay.Enabled = isPending;

                if (!isPending)
                {
                    _lblPixStatus.ForeColor = ThemeColors.AccentLime;
                    _lblPixStatus.Text = $"Reserva ja se encontra {_selectedPosBooking.Status}!";
                }
            }
            else
            {
                _selectedPosBooking = null;
                _lblPosClientInfo.Text = res.Message ?? "Agendamento nao encontrado.";
                _lblPosAmountInfo.Text = "TOTAL: R$ 0,00";
            }
        }

        private async Task HandleGeneratePixAsync()
        {
            if (_selectedPosBooking == null) return;

            _btnPosGeneratePix.Enabled = false;
            _lblPixStatus.ForeColor = ThemeColors.AccentCyan;
            _lblPixStatus.Text = "Emitindo cobranca PIX via API...";

            var res = await _paymentService.GeneratePixAsync(_selectedPosBooking.Id);
            if (res.Success && res.Data?.Pix != null)
            {
                var copiaCola = res.Data.Pix.CopiaECola;
                _txtPixPayload.Text = copiaCola;

                // Render local QR code bitmap via QRCoder
                var qrBitmap = QrCodeRenderer.GenerateQrBitmap(copiaCola, 240);
                _picPixQr.Image?.Dispose();
                _picPixQr.Image = qrBitmap;

                _lblPixStatus.ForeColor = ThemeColors.AccentAmber;
                _lblPixStatus.Text = "Aguardando pagamento do cliente (polling ativo)...";

                // Start active polling timer
                _activePixBookingId = _selectedPosBooking.Id;
                StartPixPolling();
            }
            else
            {
                _btnPosGeneratePix.Enabled = true;
                _lblPixStatus.ForeColor = ThemeColors.AccentRed;
                _lblPixStatus.Text = res.Message ?? "Falha ao gerar cobranca PIX.";
            }
        }

        private void StartPixPolling()
        {
            _pixPollingTimer?.Stop();
            _pixPollingTimer?.Dispose();

            _pixPollingTimer = new System.Windows.Forms.Timer
            {
                Interval = 3000 // Poll every 3 seconds
            };

            _pixPollingTimer.Tick += async (s, e) =>
            {
                if (_activePixBookingId <= 0)
                {
                    _pixPollingTimer.Stop();
                    return;
                }

                var statusRes = await _paymentService.GetBookingStatusAsync(_activePixBookingId);
                if (statusRes.Success && statusRes.Data != null)
                {
                    if (statusRes.Data.Status == "CONFIRMADO" || statusRes.Data.Status == "PAGO")
                    {
                        _pixPollingTimer.Stop();
                        _lblPixStatus.ForeColor = ThemeColors.AccentLime;
                        _lblPixStatus.Text = "PAGAMENTO PIX CONFIRMADO COM SUCESSO!";

                        MessageBox.Show(
                            $"Pagamento PIX de R$ {statusRes.Data.ValorFinal:N2} aprovado!\nCheck-in liberado para a quadra.",
                            "PIX Aprovado",
                            MessageBoxButtons.OK,
                            MessageBoxIcon.Information
                        );

                        await RefreshCashStatusAsync();
                        _ = LoadScheduleGradeAsync();
                    }
                }
            };

            _pixPollingTimer.Start();
        }

        private async Task HandleCashPaymentAsync()
        {
            if (_selectedPosBooking == null) return;

            var confirm = MessageBox.Show(
                $"Confirmar recebimento de R$ {_selectedPosBooking.ValorFinal:N2} em DINHEIRO na gaveta?",
                "Baixa em Dinheiro",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirm != DialogResult.Yes) return;

            // Generate/get pending payment
            var pixRes = await _paymentService.GeneratePixAsync(_selectedPosBooking.Id);
            if (pixRes.Success && pixRes.Data?.Pagamento != null)
            {
                var payConfirm = await _paymentService.ConfirmPaymentAsync(pixRes.Data.Pagamento.Id);
                if (payConfirm.Success)
                {
                    MessageBox.Show("Pagamento em dinheiro confirmado e registrado no caixa!", "Sucesso", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    await RefreshCashStatusAsync();
                    _ = LoadScheduleGradeAsync();
                    await FetchBookingToPosAsync(_selectedPosBooking.Id);
                }
                else
                {
                    MessageBox.Show(payConfirm.Message ?? "Falha ao registrar baixa em dinheiro.", "Erro", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private async Task HandleCardPaymentAsync()
        {
            if (_selectedPosBooking == null) return;

            var confirm = MessageBox.Show(
                $"O cliente ja passou o cartao na maquininha POS no valor de R$ {_selectedPosBooking.ValorFinal:N2}?",
                "Confirmar Cartao",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirm != DialogResult.Yes) return;

            var pixRes = await _paymentService.GeneratePixAsync(_selectedPosBooking.Id);
            if (pixRes.Success && pixRes.Data?.Pagamento != null)
            {
                var payConfirm = await _paymentService.ConfirmPaymentAsync(pixRes.Data.Pagamento.Id);
                if (payConfirm.Success)
                {
                    MessageBox.Show("Pagamento em cartao confirmado com sucesso!", "Sucesso", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    await RefreshCashStatusAsync();
                    _ = LoadScheduleGradeAsync();
                    await FetchBookingToPosAsync(_selectedPosBooking.Id);
                }
                else
                {
                    MessageBox.Show(payConfirm.Message ?? "Falha ao confirmar cartao.", "Erro", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        // ==========================================
        // 3. VIEW: GESTAO DE CAIXA
        // ==========================================
        private void SetupCashView()
        {
            var panel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(16)
            };

            _lblCashRegisterTitle = new Label
            {
                Text = "GESTAO DE TURNOS DE CAIXA BALCAO",
                Font = new Font("Segoe UI", 13F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(16, 12),
                AutoSize = true
            };

            // Summary Card
            var card = new Panel
            {
                Location = new Point(16, 50),
                Size = new Size(800, 360),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(24)
            };

            _lblCashSaldoAbertura = CreateMetricLabel(card, "Fundo de Abertura (Troco Inicial)", "R$ 0,00", 20, 20);
            _lblCashTotalDinheiro = CreateMetricLabel(card, "Vendas em Dinheiro Balcao", "R$ 0,00", 280, 20);
            _lblCashTotalSuprimentos = CreateMetricLabel(card, "Suprimentos de Caixa (+)", "R$ 0,00", 540, 20);

            _lblCashTotalSangrias = CreateMetricLabel(card, "Sangrias Efetuadas (-)", "R$ 0,00", 20, 110, ThemeColors.AccentRed);
            _lblCashTotalPix = CreateMetricLabel(card, "Total Recebido em PIX", "R$ 0,00", 280, 110, ThemeColors.AccentCyan);
            _lblCashTotalCartao = CreateMetricLabel(card, "Total Recebido em Cartao", "R$ 0,00", 540, 110, ThemeColors.AccentCyan);

            // Bottom Expected Drawer Balance
            var pnlExpected = new Panel
            {
                Location = new Point(20, 210),
                Size = new Size(740, 90),
                BackColor = ThemeColors.BgCardHover
            };

            var lblExpectedTitle = new Label
            {
                Text = "SALDO ESPERADO EM DINHEIRO FISICO NA GAVETA",
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(16, 12),
                AutoSize = true
            };

            _lblCashSaldoEsperado = new Label
            {
                Text = "R$ 0,00",
                Font = new Font("Segoe UI", 22F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(14, 34),
                AutoSize = true
            };

            pnlExpected.Controls.Add(lblExpectedTitle);
            pnlExpected.Controls.Add(_lblCashSaldoEsperado);
            card.Controls.Add(pnlExpected);

            // Action Buttons
            _btnOpenCashShift = new Button
            {
                Text = "🔓 ABRIR TURNO DE CAIXA",
                Location = new Point(16, 430),
                Size = new Size(220, 48),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnOpenCashShift.FlatAppearance.BorderSize = 0;
            _btnOpenCashShift.Click += async (s, e) => await HandleOpenShiftAsync();

            _btnSangria = new Button
            {
                Text = "🔻 SANGRIA (RECOLHER)",
                Location = new Point(250, 430),
                Size = new Size(200, 48),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.AccentRed,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnSangria.FlatAppearance.BorderColor = ThemeColors.AccentRed;
            _btnSangria.Click += (s, e) => OpenCashMovementModal("SANGRIA");

            _btnSuprimento = new Button
            {
                Text = "🔺 SUPRIMENTO (TROCO)",
                Location = new Point(465, 430),
                Size = new Size(200, 48),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.AccentCyan,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnSuprimento.FlatAppearance.BorderColor = ThemeColors.AccentCyan;
            _btnSuprimento.Click += (s, e) => OpenCashMovementModal("SUPRIMENTO");

            _btnCloseCashShift = new Button
            {
                Text = "🔒 FECHAR CAIXA (CONFERENCIA)",
                Location = new Point(680, 430),
                Size = new Size(240, 48),
                BackColor = ThemeColors.AccentRed,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnCloseCashShift.FlatAppearance.BorderSize = 0;
            _btnCloseCashShift.Click += (s, e) => OpenCashCloseModal();

            panel.Controls.Add(_lblCashRegisterTitle);
            panel.Controls.Add(card);
            panel.Controls.Add(_btnOpenCashShift);
            panel.Controls.Add(_btnSangria);
            panel.Controls.Add(_btnSuprimento);
            panel.Controls.Add(_btnCloseCashShift);

            _views["CAIXA"] = panel;
            _contentPanel.Controls.Add(panel);
        }

        private Label CreateMetricLabel(Panel parent, string title, string initialValue, int x, int y, Color? valColor = null)
        {
            var pnl = new Panel
            {
                Location = new Point(x, y),
                Size = new Size(220, 70),
                BackColor = ThemeColors.BgInput
            };

            var lblT = new Label
            {
                Text = title,
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(8, 8),
                AutoSize = true
            };

            var lblV = new Label
            {
                Text = initialValue,
                Font = new Font("Segoe UI", 14F, FontStyle.Bold),
                ForeColor = valColor ?? ThemeColors.TextMain,
                Location = new Point(8, 30),
                AutoSize = true
            };

            pnl.Controls.Add(lblT);
            pnl.Controls.Add(lblV);
            parent.Controls.Add(pnl);

            return lblV;
        }

        private async Task RefreshCashStatusAsync()
        {
            var res = await _cashService.GetStatusAsync(SessionContext.ActiveArenaId);
            if (res.Success && res.Data != null)
            {
                _activeCashStatus = res.Data;
                bool isOpen = _activeCashStatus.StatusCaixa == "ABERTO";

                _lblCashStatusBadge.Text = isOpen
                    ? $"CAIXA ABERTO (Gaveta: R$ {_activeCashStatus.Balanco?.SaldoDinheiroEsperado ?? 0:N2})"
                    : "CAIXA FECHADO";
                _lblCashStatusBadge.ForeColor = isOpen ? ThemeColors.AccentLime : ThemeColors.AccentRed;

                _btnOpenCashShift.Enabled = !isOpen;
                _btnSangria.Enabled = isOpen;
                _btnSuprimento.Enabled = isOpen;
                _btnCloseCashShift.Enabled = isOpen;

                if (_activeCashStatus.Balanco != null)
                {
                    var b = _activeCashStatus.Balanco;
                    _lblCashSaldoAbertura.Text = $"R$ {b.SaldoAbertura:N2}";
                    _lblCashTotalDinheiro.Text = $"R$ {b.TotalEntradasDinheiro:N2}";
                    _lblCashTotalSuprimentos.Text = $"R$ {b.TotalSuprimentos:N2}";
                    _lblCashTotalSangrias.Text = $"R$ {b.TotalSangrias:N2}";
                    _lblCashSaldoEsperado.Text = $"R$ {b.SaldoDinheiroEsperado:N2}";
                    _lblCashTotalPix.Text = $"R$ {b.TotalPix:N2}";
                    _lblCashTotalCartao.Text = $"R$ {b.TotalCartao:N2}";
                }
            }
        }

        private async Task HandleOpenShiftAsync()
        {
            // Prompt operator for initial float
            var inputStr = Microsoft.VisualBasic.Interaction.InputBox(
                "Informe o valor do Fundo de Troco Inicial (R$):",
                "Abertura de Turno de Caixa",
                "100.00"
            );

            if (decimal.TryParse(inputStr, out var saldoAbertura) && saldoAbertura >= 0)
            {
                var res = await _cashService.OpenShiftAsync(SessionContext.ActiveArenaId, saldoAbertura);
                if (res.Success)
                {
                    MessageBox.Show("Turno de caixa aberto com sucesso!", "Caixa Aberto", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    await RefreshCashStatusAsync();
                }
                else
                {
                    MessageBox.Show(res.Message ?? "Falha ao abrir caixa.", "Erro", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void OpenCashMovementModal(string tipo)
        {
            using var modal = new CashMovementModalForm(tipo);
            if (modal.ShowDialog(this) == DialogResult.OK)
            {
                MessageBox.Show($"{tipo} registrada com sucesso!", "Sucesso", MessageBoxButtons.OK, MessageBoxIcon.Information);
                _ = RefreshCashStatusAsync();
            }
        }

        private void OpenCashCloseModal()
        {
            using var modal = new CashCloseModalForm();
            if (modal.ShowDialog(this) == DialogResult.OK)
            {
                _ = RefreshCashStatusAsync();
            }
        }

        // ==========================================
        // 4. VIEW: TERMINAL DE CHECK-IN
        // ==========================================
        private void SetupCheckinView()
        {
            var panel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(24)
            };

            var lblTitle = new Label
            {
                Text = "TERMINAL DE CHECK-IN DA RECEPCAO",
                Font = new Font("Segoe UI", 13F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(24, 16),
                AutoSize = true
            };

            var lblSub = new Label
            {
                Text = "Passe o leitor optico do voucher do cliente ou digite o codigo CHK-XXXXXX",
                Font = new Font("Segoe UI", 9F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(26, 44),
                AutoSize = true
            };

            // Input card
            var card = new Panel
            {
                Location = new Point(24, 80),
                Size = new Size(680, 100),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(16)
            };

            _txtCheckinCode = new TextBox
            {
                Location = new Point(20, 24),
                Size = new Size(460, 40),
                Font = new Font("Segoe UI", 18F, FontStyle.Bold),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.AccentLime,
                BorderStyle = BorderStyle.FixedSingle
            };
            _txtCheckinCode.KeyDown += async (s, e) =>
            {
                if (e.KeyCode == Keys.Enter)
                {
                    e.SuppressKeyPress = true;
                    await HandleValidateCheckinAsync();
                }
            };

            var btnVal = new Button
            {
                Text = "VALIDAR",
                Location = new Point(500, 24),
                Size = new Size(150, 48),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 11F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnVal.FlatAppearance.BorderSize = 0;
            btnVal.Click += async (s, e) => await HandleValidateCheckinAsync();

            card.Controls.Add(_txtCheckinCode);
            card.Controls.Add(btnVal);

            // Result presentation card
            _pnlCheckinResult = new Panel
            {
                Location = new Point(24, 200),
                Size = new Size(680, 280),
                BackColor = ThemeColors.BgCard,
                Visible = false,
                Padding = new Padding(24)
            };

            _lblCheckinIcon = new Label
            {
                Location = new Point(24, 24),
                Size = new Size(630, 45),
                Font = new Font("Segoe UI", 18F, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleCenter
            };

            _lblCheckinMessage = new Label
            {
                Location = new Point(24, 75),
                Size = new Size(630, 35),
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleCenter
            };

            _lblCheckinDetails = new Label
            {
                Location = new Point(24, 120),
                Size = new Size(630, 130),
                Font = new Font("Segoe UI", 10F),
                ForeColor = ThemeColors.TextMain,
                BackColor = ThemeColors.BgInput,
                Padding = new Padding(16)
            };

            _pnlCheckinResult.Controls.Add(_lblCheckinIcon);
            _pnlCheckinResult.Controls.Add(_lblCheckinMessage);
            _pnlCheckinResult.Controls.Add(_lblCheckinDetails);

            panel.Controls.Add(lblTitle);
            panel.Controls.Add(lblSub);
            panel.Controls.Add(card);
            panel.Controls.Add(_pnlCheckinResult);

            _views["CHECKIN"] = panel;
            _contentPanel.Controls.Add(panel);
        }

        private async Task HandleValidateCheckinAsync()
        {
            var code = _txtCheckinCode.Text.Trim();
            if (string.IsNullOrWhiteSpace(code)) return;

            _pnlCheckinResult.Visible = true;
            _lblCheckinIcon.Text = "⏳";
            _lblCheckinIcon.ForeColor = ThemeColors.AccentCyan;
            _lblCheckinMessage.Text = "Consultando codigo na API...";
            _lblCheckinMessage.ForeColor = ThemeColors.AccentCyan;
            _lblCheckinDetails.Text = "";

            var res = await _checkinService.ValidateCheckinAsync(SessionContext.ActiveArenaId, code);

            if (res.Success && res.Data?.Agendamento != null)
            {
                var b = res.Data.Agendamento;
                _lblCheckinIcon.Text = "✅ ACESSO LIBERADO";
                _lblCheckinIcon.ForeColor = ThemeColors.AccentLime;
                _lblCheckinMessage.Text = "Check-in realizado com sucesso na catraca/recepcao!";
                _lblCheckinMessage.ForeColor = ThemeColors.AccentLime;

                _lblCheckinDetails.Text =
                    $"ATLETA: {b.ClienteNome}\n" +
                    $"TELEFONE: {b.ClienteTelefone}\n" +
                    $"DATA: {b.Data} ({b.HoraInicio} as {b.HoraFim})\n" +
                    $"STATUS DA RESERVA: {b.Status}\n" +
                    $"CHECK-IN REGISTRADO EM: {res.Data.CheckinEm}";

                _txtCheckinCode.SelectAll();
            }
            else
            {
                _lblCheckinIcon.Text = "❌ ACESSO RECUSADO";
                _lblCheckinIcon.ForeColor = ThemeColors.AccentRed;
                _lblCheckinMessage.Text = res.Message ?? "Codigo de check-in invalido ou nao localizado.";
                _lblCheckinMessage.ForeColor = ThemeColors.AccentRed;
                _lblCheckinDetails.Text = "Verifique se a reserva esta paga ou se o codigo digitado confere com o comprovante.";
                _txtCheckinCode.SelectAll();
            }
        }

        // ==========================================
        // 5. VIEW: CONFIGURACOES & CONECTIVIDADE
        // ==========================================
        private void SetupConfigView()
        {
            var panel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = ThemeColors.BgBody,
                Padding = new Padding(24)
            };

            var lblTitle = new Label
            {
                Text = "CONFIGURACOES DE CONECTIVIDADE & SINCRONIZACAO",
                Font = new Font("Segoe UI", 13F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(24, 16),
                AutoSize = true
            };

            var card = new Panel
            {
                Location = new Point(24, 60),
                Size = new Size(680, 360),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(20)
            };

            var lblApiTitle = new Label
            {
                Text = "ENDPOINT ATIVO DA API V1 REST:",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 20),
                AutoSize = true
            };

            _lblApiEndpoint = new Label
            {
                Text = ApiClient.Instance.BaseUrl,
                Font = new Font("Consolas", 10F),
                ForeColor = ThemeColors.AccentCyan,
                Location = new Point(20, 42),
                AutoSize = true
            };

            var btnPing = new Button
            {
                Text = "⚡ Testar Conectividade com API (Ping)",
                Location = new Point(20, 80),
                Size = new Size(300, 38),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnPing.FlatAppearance.BorderColor = ThemeColors.Border;
            btnPing.Click += async (s, e) =>
            {
                _lblConnectionHealth.ForeColor = ThemeColors.AccentCyan;
                _lblConnectionHealth.Text = "Testando conexao...";
                var pingRes = await _arenaService.GetArenaInfoAsync(SessionContext.ActiveArenaId);
                if (pingRes.Success)
                {
                    _lblConnectionHealth.ForeColor = ThemeColors.AccentLime;
                    _lblConnectionHealth.Text = $"Conexao OK! Latencia estavel com a API ({ApiClient.Instance.BaseUrl})";
                }
                else
                {
                    _lblConnectionHealth.ForeColor = ThemeColors.AccentRed;
                    _lblConnectionHealth.Text = $"Falha de conexao: {pingRes.Message}";
                }
            };

            _lblConnectionHealth = new Label
            {
                Location = new Point(20, 130),
                Size = new Size(640, 30),
                ForeColor = ThemeColors.TextMuted,
                Text = "Clique no botao acima para verificar a comunicacao com a nuvem."
            };

            var lblRules = new Label
            {
                Text = "ARQUITETURA DE DADOS:\n" +
                       "- O aplicativo desktop comunica-se 100% via REST API V1 com JWT Bearer.\n" +
                       "- Nao existe conexao direta do cliente Windows com o banco de dados MySQL.\n" +
                       "- Todas as operacoes de reserva, faturamento e check-in sao sincronizadas em tempo real.",
                Font = new Font("Segoe UI", 9F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 180),
                Size = new Size(640, 120)
            };

            card.Controls.Add(lblApiTitle);
            card.Controls.Add(_lblApiEndpoint);
            card.Controls.Add(btnPing);
            card.Controls.Add(_lblConnectionHealth);
            card.Controls.Add(lblRules);

            panel.Controls.Add(lblTitle);
            panel.Controls.Add(card);

            _views["CONFIG"] = panel;
            _contentPanel.Controls.Add(panel);
        }

        private void Logout()
        {
            var confirm = MessageBox.Show(
                "Deseja realmente encerrar a sessao deste operador?",
                "Encerrar Sessao",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirm == DialogResult.Yes)
            {
                _pixPollingTimer?.Stop();
                _pixPollingTimer?.Dispose();
                SessionContext.Clear();

                Hide();
                var login = new LoginForm();
                login.FormClosed += (s, e) => Close();
                login.Show();
            }
        }
    }
}
