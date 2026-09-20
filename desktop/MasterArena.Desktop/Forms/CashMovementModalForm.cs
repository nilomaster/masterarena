// Master Arena Desktop - Cash Movement Modal Form (Sangria / Suprimento)
// Comments strictly in ASCII only.

using System;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using MasterArena.Desktop.Helpers;
using MasterArena.Desktop.Models;
using MasterArena.Desktop.Services;

namespace MasterArena.Desktop.Forms
{
    public class CashMovementModalForm : Form
    {
        private readonly CashRegisterService _cashService = new();
        private ComboBox _cboTipo = null!;
        private NumericUpDown _numValor = null!;
        private TextBox _txtMotivo = null!;
        private Button _btnConfirm = null!;
        private Button _btnCancel = null!;
        private Label _lblStatus = null!;

        public CashMovementModalForm(string defaultType = "SANGRIA")
        {
            InitializeComponent(defaultType);
        }

        private void InitializeComponent(string defaultType)
        {
            Text = "Movimentacao Avulsa de Caixa - Master Arena";
            Size = new Size(460, 420);
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
                Size = new Size(405, 340),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(20)
            };

            var lblTitle = new Label
            {
                Text = "SANGRIA OU SUPRIMENTO",
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(20, 15),
                AutoSize = true
            };

            var lblSubtitle = new Label
            {
                Text = "Registre saida para cofre ou entrada de troco no caixa",
                Font = new Font("Segoe UI", 8.5F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(22, 40),
                AutoSize = true
            };

            // Type selector
            var lblTipo = new Label
            {
                Text = "TIPO DE MOVIMENTACAO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 75),
                AutoSize = true
            };

            _cboTipo = new ComboBox
            {
                Location = new Point(20, 96),
                Size = new Size(365, 28),
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat
            };
            _cboTipo.Items.Add("SANGRIA (Retirada de Dinheiro para Cofre)");
            _cboTipo.Items.Add("SUPRIMENTO (Entrada de Troco / Reforco)");
            _cboTipo.SelectedIndex = defaultType.ToUpper() == "SUPRIMENTO" ? 1 : 0;

            // Amount
            var lblValor = new Label
            {
                Text = "VALOR EM DINHEIRO (R$)",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 135),
                AutoSize = true
            };

            _numValor = new NumericUpDown
            {
                Location = new Point(20, 156),
                Size = new Size(365, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                DecimalPlaces = 2,
                Maximum = 999999,
                Minimum = 0.01m,
                Value = 50.00m
            };

            // Reason
            var lblMotivo = new Label
            {
                Text = "JUSTIFICATIVA / MOTIVO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 195),
                AutoSize = true
            };

            _txtMotivo = new TextBox
            {
                Location = new Point(20, 216),
                Size = new Size(365, 26),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle,
                Text = "Recolhimento periodico para o cofre central"
            };

            // Status
            _lblStatus = new Label
            {
                Location = new Point(20, 250),
                Size = new Size(365, 25),
                ForeColor = ThemeColors.AccentAmber,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "Confirme o valor e a justificativa."
            };

            // Buttons
            _btnConfirm = new Button
            {
                Location = new Point(20, 280),
                Size = new Size(240, 38),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                Text = "CONFIRMAR",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnConfirm.FlatAppearance.BorderSize = 0;
            _btnConfirm.Click += async (s, e) => await HandleConfirmAsync();

            _btnCancel = new Button
            {
                Location = new Point(270, 280),
                Size = new Size(115, 38),
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
            panel.Controls.Add(lblTipo);
            panel.Controls.Add(_cboTipo);
            panel.Controls.Add(lblValor);
            panel.Controls.Add(_numValor);
            panel.Controls.Add(lblMotivo);
            panel.Controls.Add(_txtMotivo);
            panel.Controls.Add(_lblStatus);
            panel.Controls.Add(_btnConfirm);
            panel.Controls.Add(_btnCancel);

            Controls.Add(panel);
        }

        private async Task HandleConfirmAsync()
        {
            var tipo = _cboTipo.SelectedIndex == 1 ? "SUPRIMENTO" : "SANGRIA";
            var valor = _numValor.Value;
            var motivo = _txtMotivo.Text.Trim();

            if (valor <= 0)
            {
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = "O valor deve ser maior que zero.";
                return;
            }

            if (string.IsNullOrWhiteSpace(motivo))
            {
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = "A justificativa e obrigatoria.";
                return;
            }

            _btnConfirm.Enabled = false;
            _lblStatus.ForeColor = ThemeColors.AccentCyan;
            _lblStatus.Text = "Registrando na API...";

            var response = await _cashService.RegisterMovementAsync(SessionContext.ActiveArenaId, tipo, valor, motivo);

            if (response.Success)
            {
                DialogResult = DialogResult.OK;
                Close();
            }
            else
            {
                _btnConfirm.Enabled = true;
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = response.Message ?? "Falha ao registrar movimentacao.";
            }
        }
    }
}
