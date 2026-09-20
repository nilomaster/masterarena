// Master Arena Desktop - Booking Modal Form (Reception Counter)
// Comments strictly in ASCII only.

using System;
using System.Collections.Generic;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using MasterArena.Desktop.Helpers;
using MasterArena.Desktop.Models;
using MasterArena.Desktop.Services;

namespace MasterArena.Desktop.Forms
{
    public class BookingModalForm : Form
    {
        private readonly BookingService _bookingService = new();
        private readonly List<CourtDto> _courts;
        private readonly string? _preselectedDate;
        private readonly string? _preselectedStart;
        private readonly int? _preselectedCourtId;

        private ComboBox _cboCourts = null!;
        private DateTimePicker _dtpDate = null!;
        private ComboBox _cboStartTime = null!;
        private ComboBox _cboEndTime = null!;
        private TextBox _txtClientName = null!;
        private TextBox _txtClientPhone = null!;
        private TextBox _txtClientEmail = null!;
        private TextBox _txtCoupon = null!;
        private Button _btnSave = null!;
        private Button _btnCancel = null!;
        private Label _lblStatus = null!;

        public BookingDetailDto? CreatedBooking { get; private set; }

        public BookingModalForm(
            List<CourtDto> courts,
            int? preselectedCourtId = null,
            string? preselectedDate = null,
            string? preselectedStart = null)
        {
            _courts = courts ?? new List<CourtDto>();
            _preselectedCourtId = preselectedCourtId;
            _preselectedDate = preselectedDate;
            _preselectedStart = preselectedStart;

            InitializeComponent();
        }

        private void InitializeComponent()
        {
            Text = "Nova Reserva de Balcao - Master Arena";
            Size = new Size(540, 660);
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MinimizeBox = false;
            BackColor = ThemeColors.BgBody;
            ForeColor = ThemeColors.TextMain;
            Font = new Font("Segoe UI", 9.5F, FontStyle.Regular, GraphicsUnit.Point);

            var panel = new Panel
            {
                Location = new Point(20, 20),
                Size = new Size(485, 580),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(20)
            };

            var lblTitle = new Label
            {
                Text = "REGISTRAR RESERVA DE BALCAO",
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(20, 15),
                AutoSize = true
            };

            var lblSubtitle = new Label
            {
                Text = "Cadastre a locacao avulsa para faturamento imediato ou PIX",
                Font = new Font("Segoe UI", 8.5F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(22, 42),
                AutoSize = true
            };

            // Court Selection
            var lblCourt = new Label
            {
                Text = "QUADRA / ESPACO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 75),
                AutoSize = true
            };

            _cboCourts = new ComboBox
            {
                Location = new Point(20, 96),
                Size = new Size(445, 28),
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat
            };

            foreach (var court in _courts)
            {
                _cboCourts.Items.Add(new ComboBoxItem(court.Id, $"{court.Nome} ({court.ModalidadeNome})"));
            }

            if (_cboCourts.Items.Count > 0)
            {
                int selectIdx = 0;
                if (_preselectedCourtId.HasValue)
                {
                    for (int i = 0; i < _cboCourts.Items.Count; i++)
                    {
                        if (_cboCourts.Items[i] is ComboBoxItem item && item.Id == _preselectedCourtId.Value)
                        {
                            selectIdx = i;
                            break;
                        }
                    }
                }
                _cboCourts.SelectedIndex = selectIdx;
            }

            // Date Selection
            var lblDate = new Label
            {
                Text = "DATA DO JOGO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 135),
                AutoSize = true
            };

            _dtpDate = new DateTimePicker
            {
                Location = new Point(20, 156),
                Size = new Size(215, 26),
                Format = DateTimePickerFormat.Short,
                CalendarMonthBackground = ThemeColors.BgInput,
                CalendarForeColor = ThemeColors.TextMain
            };

            if (!string.IsNullOrEmpty(_preselectedDate) && DateTime.TryParse(_preselectedDate, out var parsedDate))
            {
                _dtpDate.Value = parsedDate;
            }

            // Hours
            var lblStart = new Label
            {
                Text = "INICIO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(250, 135),
                AutoSize = true
            };

            _cboStartTime = new ComboBox
            {
                Location = new Point(250, 156),
                Size = new Size(100, 28),
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat
            };

            var lblEnd = new Label
            {
                Text = "FIM",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(365, 135),
                AutoSize = true
            };

            _cboEndTime = new ComboBox
            {
                Location = new Point(365, 156),
                Size = new Size(100, 28),
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat
            };

            for (int h = 6; h <= 23; h++)
            {
                var timeStr = $"{h:D2}:00";
                _cboStartTime.Items.Add(timeStr);
                _cboEndTime.Items.Add(timeStr);
            }
            _cboEndTime.Items.Add("23:59");

            _cboStartTime.SelectedIndex = 12; // 18:00
            _cboEndTime.SelectedIndex = 13;   // 19:00

            if (!string.IsNullOrEmpty(_preselectedStart))
            {
                var idx = _cboStartTime.Items.IndexOf(_preselectedStart);
                if (idx >= 0)
                {
                    _cboStartTime.SelectedIndex = idx;
                    if (idx + 1 < _cboEndTime.Items.Count)
                    {
                        _cboEndTime.SelectedIndex = idx + 1;
                    }
                }
            }

            // Customer Name
            var lblClient = new Label
            {
                Text = "NOME DO CLIENTE / ATLETA",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 200),
                AutoSize = true
            };

            _txtClientName = new TextBox
            {
                Location = new Point(20, 221),
                Size = new Size(445, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle
            };

            // Customer Phone & Email
            var lblPhone = new Label
            {
                Text = "WHATSAPP / TELEFONE",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 260),
                AutoSize = true
            };

            _txtClientPhone = new TextBox
            {
                Location = new Point(20, 281),
                Size = new Size(215, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle
            };

            var lblEmail = new Label
            {
                Text = "EMAIL (OPCIONAL)",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(250, 260),
                AutoSize = true
            };

            _txtClientEmail = new TextBox
            {
                Location = new Point(250, 281),
                Size = new Size(215, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle
            };

            // Coupon Code
            var lblCoupon = new Label
            {
                Text = "CUPOM DE DESCONTO (OPCIONAL)",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 320),
                AutoSize = true
            };

            _txtCoupon = new TextBox
            {
                Location = new Point(20, 341),
                Size = new Size(215, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle
            };

            // Status message
            _lblStatus = new Label
            {
                Location = new Point(20, 385),
                Size = new Size(445, 30),
                ForeColor = ThemeColors.AccentAmber,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "Preencha os dados da reserva balcao."
            };

            // Buttons
            _btnSave = new Button
            {
                Location = new Point(20, 430),
                Size = new Size(445, 42),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                Text = "CONFIRMAR RESERVA NO BALCAO",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.Click += async (s, e) => await HandleSaveAsync();

            _btnCancel = new Button
            {
                Location = new Point(20, 485),
                Size = new Size(445, 34),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMuted,
                Font = new Font("Segoe UI", 9F),
                Text = "Cancelar",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnCancel.FlatAppearance.BorderColor = ThemeColors.Border;
            _btnCancel.Click += (s, e) => DialogResult = DialogResult.Cancel;

            panel.Controls.Add(lblTitle);
            panel.Controls.Add(lblSubtitle);
            panel.Controls.Add(lblCourt);
            panel.Controls.Add(_cboCourts);
            panel.Controls.Add(lblDate);
            panel.Controls.Add(_dtpDate);
            panel.Controls.Add(lblStart);
            panel.Controls.Add(_cboStartTime);
            panel.Controls.Add(lblEnd);
            panel.Controls.Add(_cboEndTime);
            panel.Controls.Add(lblClient);
            panel.Controls.Add(_txtClientName);
            panel.Controls.Add(lblPhone);
            panel.Controls.Add(_txtClientPhone);
            panel.Controls.Add(lblEmail);
            panel.Controls.Add(_txtClientEmail);
            panel.Controls.Add(lblCoupon);
            panel.Controls.Add(_txtCoupon);
            panel.Controls.Add(_lblStatus);
            panel.Controls.Add(_btnSave);
            panel.Controls.Add(_btnCancel);

            Controls.Add(panel);
        }

        private async Task HandleSaveAsync()
        {
            if (_cboCourts.SelectedItem is not ComboBoxItem selectedCourt)
            {
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = "Selecione uma quadra para reservar.";
                return;
            }

            var clientName = _txtClientName.Text.Trim();
            var clientPhone = _txtClientPhone.Text.Trim();

            if (string.IsNullOrWhiteSpace(clientName) || string.IsNullOrWhiteSpace(clientPhone))
            {
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = "Nome e WhatsApp do cliente sao obrigatorios.";
                return;
            }

            var date = _dtpDate.Value.ToString("yyyy-MM-dd");
            var start = _cboStartTime.SelectedItem?.ToString() ?? "18:00";
            var end = _cboEndTime.SelectedItem?.ToString() ?? "19:00";
            var email = string.IsNullOrWhiteSpace(_txtClientEmail.Text) ? null : _txtClientEmail.Text.Trim();
            var coupon = string.IsNullOrWhiteSpace(_txtCoupon.Text) ? null : _txtCoupon.Text.Trim();

            _btnSave.Enabled = false;
            _lblStatus.ForeColor = ThemeColors.AccentCyan;
            _lblStatus.Text = "Registrando agendamento na API...";

            var response = await _bookingService.CreateBookingAsync(
                SessionContext.ActiveArenaId,
                selectedCourt.Id,
                date,
                start,
                end,
                clientName,
                clientPhone,
                email,
                coupon
            );

            if (response.Success && response.Data?.Agendamento != null)
            {
                CreatedBooking = response.Data.Agendamento;
                DialogResult = DialogResult.OK;
                Close();
            }
            else
            {
                _btnSave.Enabled = true;
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = response.Message ?? "Falha ao registrar reserva.";
            }
        }

        private class ComboBoxItem
        {
            public int Id { get; }
            public string Name { get; }

            public ComboBoxItem(int id, string name)
            {
                Id = id;
                Name = name;
            }

            public override string ToString() => Name;
        }
    }
}
