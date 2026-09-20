// Master Arena Desktop - Cash Close Modal Form (Blind Reconciliation)
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
    public class CashCloseModalForm : Form
    {
        private readonly CashRegisterService _cashService = new();
        private NumericUpDown _numSaldoInformado = null!;
        private Button _btnCloseCash = null!;
        private Button _btnCancel = null!;
        private Label _lblStatus = null!;

        public CashCloseModalForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            Text = "Fechamento de Turno de Caixa - Master Arena";
            Size = new Size(460, 360);
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
                Size = new Size(405, 280),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(20)
            };

            var lblTitle = new Label
            {
                Text = "FECHAR TURNO DE CAIXA",
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentRed,
                Location = new Point(20, 15),
                AutoSize = true
            };

            var lblSubtitle = new Label
            {
                Text = "Conferencia cega: conte o dinheiro na gaveta e informe abaixo.",
                Font = new Font("Segoe UI", 8.5F),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(22, 40),
                Size = new Size(365, 36)
            };

            var lblValor = new Label
            {
                Text = "TOTAL DE DINHEIRO FISICO APURADO (R$)",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 90),
                AutoSize = true
            };

            _numSaldoInformado = new NumericUpDown
            {
                Location = new Point(20, 112),
                Size = new Size(365, 28),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.AccentLime,
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                DecimalPlaces = 2,
                Maximum = 999999,
                Minimum = 0,
                Value = 0.00m
            };

            _lblStatus = new Label
            {
                Location = new Point(20, 155),
                Size = new Size(365, 30),
                ForeColor = ThemeColors.AccentAmber,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "Apos fechar, este turno sera encerrado na nuvem."
            };

            _btnCloseCash = new Button
            {
                Location = new Point(20, 195),
                Size = new Size(240, 42),
                BackColor = ThemeColors.AccentRed,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                Text = "ENCERRAR TURNO",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnCloseCash.FlatAppearance.BorderSize = 0;
            _btnCloseCash.Click += async (s, e) => await HandleCloseAsync();

            _btnCancel = new Button
            {
                Location = new Point(270, 195),
                Size = new Size(115, 42),
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
            panel.Controls.Add(lblValor);
            panel.Controls.Add(_numSaldoInformado);
            panel.Controls.Add(_lblStatus);
            panel.Controls.Add(_btnCloseCash);
            panel.Controls.Add(_btnCancel);

            Controls.Add(panel);
        }

        private async Task HandleCloseAsync()
        {
            var saldo = _numSaldoInformado.Value;

            var confirm = MessageBox.Show(
                $"Deseja realmente fechar o caixa com o saldo de R$ {saldo:N2}?",
                "Confirmacao de Fechamento",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Warning
            );

            if (confirm != DialogResult.Yes)
                return;

            _btnCloseCash.Enabled = false;
            _lblStatus.ForeColor = ThemeColors.AccentCyan;
            _lblStatus.Text = "Enviando apuracao para a API...";

            var response = await _cashService.CloseShiftAsync(SessionContext.ActiveArenaId, saldo);

            if (response.Success)
            {
                MessageBox.Show(
                    "Turno de caixa encerrado com sucesso!\nO relatorio foi gravado no sistema.",
                    "Caixa Encerrado",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information
                );
                DialogResult = DialogResult.OK;
                Close();
            }
            else
            {
                _btnCloseCash.Enabled = true;
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = response.Message ?? "Falha ao fechar o caixa.";
            }
        }
    }
}
