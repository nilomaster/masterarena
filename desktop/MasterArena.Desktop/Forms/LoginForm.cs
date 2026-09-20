// Master Arena Desktop - Operator Login Screen
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
    public class LoginForm : Form
    {
        private readonly AuthService _authService = new();

        private TextBox _txtEmail = null!;
        private TextBox _txtPassword = null!;
        private ComboBox _cboEnvironment = null!;
        private Button _btnLogin = null!;
        private Label _lblStatus = null!;

        public LoginForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            Text = "MASTER ARENA — Login do Operador";
            Size = new Size(480, 560);
            StartPosition = FormStartPosition.CenterScreen;
            FormBorderStyle = FormBorderStyle.FixedSingle;
            MaximizeBox = false;
            BackColor = ThemeColors.BgBody;
            ForeColor = ThemeColors.TextMain;
            Font = new Font("Segoe UI", 10F, FontStyle.Regular, GraphicsUnit.Point);

            // Main Container Card
            var card = new Panel
            {
                Location = new Point(30, 24),
                Size = new Size(405, 465),
                BackColor = ThemeColors.BgCard,
                Padding = new Padding(24)
            };

            // Header Brand
            var lblBrand = new Label
            {
                Text = "⚡ MASTER ARENA",
                Font = new Font("Segoe UI", 16F, FontStyle.Bold),
                ForeColor = ThemeColors.AccentLime,
                Location = new Point(20, 20),
                AutoSize = true
            };

            var lblSubtitle = new Label
            {
                Text = "Frente de Caixa & Terminal de Operacao Balcao",
                Font = new Font("Segoe UI", 9F, FontStyle.Regular),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(22, 54),
                AutoSize = true
            };

            // Environment Selector
            var lblEnv = new Label
            {
                Text = "AMBIENTE DA API",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 95),
                AutoSize = true
            };

            _cboEnvironment = new ComboBox
            {
                Location = new Point(20, 118),
                Size = new Size(365, 30),
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                FlatStyle = FlatStyle.Flat
            };
            _cboEnvironment.Items.Add("Nuvem Producao (https://masterarena.esporte.ws/api/v1)");
            _cboEnvironment.Items.Add("Servidor Local (http://localhost/masterarena/api/v1)");
            _cboEnvironment.SelectedIndex = 0;
            _cboEnvironment.SelectedIndexChanged += (s, e) =>
            {
                if (_cboEnvironment.SelectedIndex == 0)
                    ApiClient.Instance.SetBaseUrl("https://masterarena.esporte.ws/api/v1");
                else
                    ApiClient.Instance.SetBaseUrl("http://localhost/masterarena/api/v1");
            };

            // Email Field
            var lblEmail = new Label
            {
                Text = "EMAIL DO OPERADOR",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 160),
                AutoSize = true
            };

            _txtEmail = new TextBox
            {
                Location = new Point(20, 183),
                Size = new Size(365, 30),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle,
                Text = "atendente@masterarena.com.br"
            };

            // Password Field
            var lblPassword = new Label
            {
                Text = "SENHA DE ACESSO",
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = ThemeColors.TextMuted,
                Location = new Point(20, 225),
                AutoSize = true
            };

            _txtPassword = new TextBox
            {
                Location = new Point(20, 248),
                Size = new Size(365, 30),
                BackColor = ThemeColors.BgInput,
                ForeColor = ThemeColors.TextMain,
                BorderStyle = BorderStyle.FixedSingle,
                PasswordChar = '•',
                Text = "Staff@123456"
            };

            // Status Label
            _lblStatus = new Label
            {
                Location = new Point(20, 290),
                Size = new Size(365, 38),
                Font = new Font("Segoe UI", 8.5F),
                ForeColor = ThemeColors.AccentAmber,
                TextAlign = ContentAlignment.MiddleCenter,
                Text = "Aguardando autenticacao..."
            };

            // Login Button
            _btnLogin = new Button
            {
                Location = new Point(20, 335),
                Size = new Size(365, 45),
                BackColor = ThemeColors.AccentLime,
                ForeColor = ThemeColors.TextDark,
                Font = new Font("Segoe UI", 11F, FontStyle.Bold),
                Text = "ENTRAR NO SISTEMA →",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            _btnLogin.FlatAppearance.BorderSize = 0;
            _btnLogin.Click += async (s, e) => await HandleLoginAsync();

            // Quick Fill Button for Admin
            var btnAdminQuick = new Button
            {
                Location = new Point(20, 395),
                Size = new Size(365, 32),
                BackColor = ThemeColors.BgCardHover,
                ForeColor = ThemeColors.TextMuted,
                Font = new Font("Segoe UI", 8F),
                Text = "Preencher como Administrador (admin@masterarena.com.br)",
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnAdminQuick.FlatAppearance.BorderColor = ThemeColors.Border;
            btnAdminQuick.Click += (s, e) =>
            {
                _txtEmail.Text = "admin@masterarena.com.br";
                _txtPassword.Text = "Arena@123456";
            };

            card.Controls.Add(lblBrand);
            card.Controls.Add(lblSubtitle);
            card.Controls.Add(lblEnv);
            card.Controls.Add(_cboEnvironment);
            card.Controls.Add(lblEmail);
            card.Controls.Add(_txtEmail);
            card.Controls.Add(lblPassword);
            card.Controls.Add(_txtPassword);
            card.Controls.Add(_lblStatus);
            card.Controls.Add(_btnLogin);
            card.Controls.Add(btnAdminQuick);

            Controls.Add(card);

            AcceptButton = _btnLogin;
        }

        private async Task HandleLoginAsync()
        {
            var email = _txtEmail.Text.Trim();
            var password = _txtPassword.Text.Trim();

            if (string.IsNullOrWhiteSpace(email) || string.IsNullOrWhiteSpace(password))
            {
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = "Informe email e senha para continuar.";
                return;
            }

            _btnLogin.Enabled = false;
            _lblStatus.ForeColor = ThemeColors.AccentCyan;
            _lblStatus.Text = "Conectando e validando com a API V1...";

            var result = await _authService.LoginAsync(email, password);

            if (result.Success && SessionContext.IsAuthenticated)
            {
                _lblStatus.ForeColor = ThemeColors.AccentLime;
                _lblStatus.Text = $"Bem-vindo, {SessionContext.CurrentUser?.Nome}!";

                await Task.Delay(400);

                var mainForm = new MainForm();
                Hide();
                mainForm.FormClosed += (s, e) => Close();
                mainForm.Show();
            }
            else
            {
                _btnLogin.Enabled = true;
                _lblStatus.ForeColor = ThemeColors.AccentRed;
                _lblStatus.Text = result.Message ?? "Falha na autenticacao. Verifique as credenciais.";
            }
        }
    }
}
