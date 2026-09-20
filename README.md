# MASTER ARENA — Sistema SaaS de Gestão de Arenas e Complexos Esportivos

Sistema profissional SaaS (Software as a Service) para administração de complexos esportivos, quadras e arenas (Beach Tennis, Futebol Society, Futevôlei, Vôlei de Areia, Tênis e Quadras Poliesportivas).

Desenvolvido por **MasterDev Solutions**.

---

## 🏛️ Regra Fundamental de Arquitetura

O sistema adota uma arquitetura em camadas totalmente desacoplada. Os clientes (web ou desktop) **nunca** acessam o banco de dados diretamente:

```text
PORTAL DO CLIENTE / TOTEM  ───►  REST API V1 (PHP)  ───►  BANCO DE DADOS (MYSQL)
APLICATIVO DESKTOP C#      ───►  REST API V1 (PHP)  ───►  BANCO DE DADOS (MYSQL)
PAINEL ADMINISTRATIVO      ───►  REST API V1 (PHP)  ───►  BANCO DE DADOS (MYSQL)
```

- **Backend:** PHP 8.1+ / 8.3+, PDO com transações atômicas, MySQL 8+ / MariaDB 10.4+.
- **Autenticação:** JWT (JSON Web Token - HS256) com suporte a Bearer Token e controle de perfil de acesso (RBAC).
- **Web Frontend:** Interface moderna Dark Mode, responsiva, construída em Vanilla CSS e JavaScript puro (Fetch API).
- **Desktop Windows:** Aplicativo C# .NET 8.0 Windows Forms corporativo para operação local no balcão e recepção.
- **Multi-Tenant:** Isolamento rigoroso de registros por `arena_id` em todas as consultas e operações.

---

## 🚀 Principais Módulos e Recursos

### 1. Multi-Tenant e Cadastro de Arenas
- Suporte a múltiplas arenas no mesmo banco de dados com isolamento estrito via chave estrangeira `arena_id`.
- Busca pública por slug amigável (`/arenas/by-slug/{slug}`) para personalização do portal de cada unidade.
- Configurações personalizáveis por arena: horários de funcionamento, regras de cancelamento, intervalo entre jogos e dados do PIX.

### 2. Controle de Acesso e Autenticação (RBAC)
- Autenticação stateless via JWT com expiração configurável e suporte a refresh token.
- Níveis hierárquicos de permissão:
  - `SUPERADMIN`: Acesso irrestrito a todas as arenas e configurações globais da plataforma.
  - `ADMIN`: Gestores da arena (acesso a financeiro, relatórios, parâmetros, usuários e quadras da unidade).
  - `FUNCIONARIO`: Operadores de balcão e recepcionistas (gestão de grade, agendamentos rápidos e frente de caixa).
  - `CLIENTE`: Atletas e usuários finais que utilizam o portal de autoatendimento.

### 3. Gestão de Esportes e Quadras
- Cadastro de modalidades com capacidade mínima/máxima de atletas.
- Cadastro de quadras com especificação de piso (areia, grama sintética, saibro, taco) e iluminação para jogos noturnos.
- Ativação, inativação e manutenção preventiva de espaços esportivos.

### 4. Motor de Grade Operacional e Preços Dinâmicos
- Grade de disponibilidade em tempo real consolidando horários livres, reservados e bloqueados.
- Tabela de preços diferenciados por:
  - Dia da semana (ex: tarifas especiais para fins de semana).
  - Faixa de horário (ex: tarifa noturna com refletores inclusos).
- Bloqueios operacionais para manutenção, aulas, torneios ou eventos corporativos.

### 5. Motor de Reservas e Prevenção de Conflitos
- Agendamento de jogos avulsos e mensais com prevenção atômica de sobreposição de horários (`SELECT ... FOR UPDATE`).
- Sistema de Cupons de Desconto com regras de validação:
  - Desconto percentual (`%`) ou fixo em reais (`R$`).
  - Limite de usos totais e controle de vigência (data início e fim).
  - Valor mínimo de locação para aplicação do benefício.
- Emissão de código de voucher alfanumérico único para cada agendamento (`CHK-XXXXXX`).

### 6. Módulo Financeiro, Frente de Caixa (PDV) e Pagamentos PIX
- Emissão dinâmica de cobranças PIX padrão Banco Central (EMVCo BR Code Copia-e-Cola e QR Code nativo com cálculo CRC16 CCITT).
- Webhook assíncrono para baixa automática de recebimentos (`/api/v1/webhooks/pix`).
- Polling de verificação instantânea no balcão e no portal web.
- Baixas manuais imediatas em dinheiro físico ou cartão na maquininha POS.
- Gestão completa de turnos de caixa de balcão:
  - Abertura com declaração de fundo de troco inicial.
  - Sangria com justificativa para retirada de valores ao cofre central.
  - Suprimento com justificativa para reforço de troco.
  - Fechamento cego com conferência do dinheiro em gaveta vs saldo calculado pelo sistema.

### 7. Portal Web do Cliente & Totem de Autoatendimento
- Grade pública interativa de horários para consulta de disponibilidade sem necessidade de login prévio.
- Fluxo de agendamento ágil em 3 passos: Escolha da quadra/horário -> Dados do atleta -> Pagamento imediato com PIX.
- Área "Meus Agendamentos" para atletas acompanharem status, vouchers e horários por telefone/WhatsApp.
- Modo Totem de recepção com validação de check-in através de leitor de QR Code ou digitação de código.

### 8. Automação de Notificações via WhatsApp
- Notificação instantânea com dados do agendamento, endereço da arena e link do voucher.
- Envio imediato da chave PIX Copia-e-Cola e valor assim que a reserva é criada.
- Disparo de confirmação automática após a aprovação do pagamento.
- Robô de lembrete de jogo enviado automaticamente 2 horas antes da partida.
- Histórico completo de envios com status (`ENVIADO`, `PENDENTE`, `FALHA`) e controle de reenvio.

### 9. Aplicativo Desktop C# .NET Windows Forms
- Solução nativa corporativa (.NET 8.0 LTS) desenvolvida em Dark Mode para operadores de balcão.
- Conexão configurável entre Nuvem Produção (`https://masterarena.esporte.ws/api/v1`) e Servidor Local (`http://localhost/masterarena/api/v1`).
- Renderização local de QR Code PIX via biblioteca nativa `QRCoder`.
- Terminal de check-in com leitor óptico USB ou digitação rápida.

---

## 📁 Estrutura de Diretórios do Projeto

```text
masterarena/
├── api/
│   └── v1/                      # Ponto de roteamento de requisicoes REST
├── app/
│   ├── Controllers/
│   │   └── Api/V1/              # Controladores REST API V1
│   │       ├── ApiController.php
│   │       ├── ArenaController.php
│   │       ├── AuthController.php
│   │       ├── BookingController.php
│   │       ├── CashRegisterController.php
│   │       ├── CupomController.php
│   │       ├── ModalidadeController.php
│   │       ├── PaymentController.php
│   │       ├── PortalController.php
│   │       ├── QuadraController.php
│   │       ├── ScheduleController.php
│   │       └── WhatsAppNotificationController.php
│   ├── Core/                    # Framework Core (Router, Request, Response, Autoloader)
│   ├── Middleware/              # Middlewares de seguranca, CORS, JSON e RBAC
│   ├── Models/                  # Modelos e mapeamentos das tabelas do banco de dados
│   │   ├── Agendamento.php
│   │   ├── Arena.php
│   │   ├── Bloqueio.php
│   │   ├── CaixaMovimentacao.php
│   │   ├── CaixaSessao.php
│   │   ├── Cliente.php
│   │   ├── Cupom.php
│   │   ├── Horario.php
│   │   ├── Modalidade.php
│   │   ├── NotificacaoWhatsApp.php
│   │   ├── Pagamento.php
│   │   ├── Quadra.php
│   │   ├── User.php
│   │   └── ValorHorario.php
│   └── Services/                # Regras de negocio e logicas de integracao
│       ├── AuditLogger.php
│       ├── BookingService.php
│       ├── CashRegisterService.php
│       ├── CheckinService.php
│       ├── JwtService.php
│       ├── PaymentService.php
│       ├── PixPayloadService.php
│       ├── ScheduleService.php
│       └── WhatsAppService.php
├── config/                      # Arquivos de configuracao (Database, App, JWT)
├── database/
│   └── migrations/              # Scripts SQL estruturados por etapa do projeto
├── desktop/
│   └── MasterArena.Desktop/     # Aplicativo Windows Forms (.NET 8.0)
│       ├── Forms/               # Formularios da interface desktop
│       ├── Helpers/             # Paleta de cores (ThemeColors) e QR Code Renderer
│       ├── Models/              # DTOs de comunicacao com a API
│       ├── Services/            # Camada de servicos HTTP (ApiClient)
│       ├── MasterArena.Desktop.csproj
│       └── Program.cs
├── public/                      # Entrada web publica (index.php, .htaccess)
├── resources/
│   └── views/                   # Interfaces Web (Portal do Cliente, Totem, Dashboard)
│       ├── portal.php
│       ├── totem.php
│       ├── dashboard.php
│       └── landing.php
├── routes/
│   └── api.php                  # Registro centralizado de todas as rotas da API
├── storage/
│   └── logs/                    # Logs operacionais e de auditoria
└── .env                         # Variaveis de ambiente e credenciais
```

---

## 📡 Catálogo Resumido de Endpoints da API REST V1

| Método | Endpoint | Descrição | Permissão |
|---|---|---|---|
| `GET` | `/api/v1/health` | Verificação de integridade da API | Público |
| `POST` | `/api/v1/auth/login` | Autenticação e emissão de token JWT | Público |
| `POST` | `/api/v1/auth/refresh` | Renovação de token de acesso expirado | Autenticado |
| `GET` | `/api/v1/auth/me` | Dados do usuário conectado | Autenticado |
| `GET` | `/api/v1/arenas/by-slug/{slug}` | Dados da arena pelo slug público | Público |
| `GET` | `/api/v1/arenas/{id}/quadras` | Listagem de quadras da arena | Público / Opcional |
| `GET` | `/api/v1/arenas/{id}/grade` | Grade de horários e disponibilidade por data | Público / Opcional |
| `POST` | `/api/v1/arenas/{id}/cupons/validar` | Validação de cupom de desconto | Público |
| `POST` | `/api/v1/arenas/{id}/agendamentos` | Criação de reserva de quadra | Público / Opcional |
| `POST` | `/api/v1/agendamentos/{id}/pix` | Emissão de cobrança PIX (Copia-e-Cola e QR) | Público / Opcional |
| `GET` | `/api/v1/agendamentos/{id}/public-status` | Consulta pública de status do agendamento | Público |
| `POST` | `/api/v1/pagamentos/{id}/confirmar` | Baixa de pagamento no balcão (dinheiro/cartão) | Staff / Admin |
| `GET` | `/api/v1/arenas/{id}/caixa/status` | Situação da sessão atual do caixa | Staff / Admin |
| `POST` | `/api/v1/arenas/{id}/caixa/abrir` | Abertura de turno de caixa | Staff / Admin |
| `POST` | `/api/v1/arenas/{id}/caixa/movimentacao` | Registro de Sangria ou Suprimento | Staff / Admin |
| `POST` | `/api/v1/arenas/{id}/caixa/fechar` | Fechamento com conferência cega | Staff / Admin |
| `POST` | `/api/v1/arenas/{id}/checkin` | Validação de check-in (Totem / Desktop) | Público / Opcional |
| `POST` | `/api/v1/webhooks/pix` | Webhook de liquidação bancária PIX | Público (Assinatura) |
| `GET` | `/api/v1/arenas/{id}/notificacoes/whatsapp` | Relatório de disparos de WhatsApp | Staff / Admin |
| `POST` | `/api/v1/arenas/{id}/notificacoes/whatsapp/testar` | Envio de mensagem de teste via API | Admin |

---

## 🛠️ Instalação e Execução

### 1. Requisitos de Servidor
- **PHP:** 8.1 ou superior (com extensões `pdo`, `pdo_mysql`, `curl`, `mbstring`, `openssl`).
- **Web Server:** Apache 2.4+ com módulo `mod_rewrite` habilitado.
- **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+.
- **Desktop:** Windows 10/11 com .NET 8.0 Runtime instalado.

### 2. Configuração do Backend
1. Clone o repositório na sua máquina ou servidor.
2. Copie o arquivo `.env.example` para `.env`:
   ```bash
   cp .env.example .env
   ```
3. Configure os parâmetros do banco de dados, chave JWT e provedores de PIX / WhatsApp no `.env`.
4. Importe as migrations localizadas na pasta `database/migrations/`.

### 3. Compilação do Aplicativo Desktop
Abra o terminal no diretório do projeto e execute:
```powershell
dotnet build desktop/MasterArena.Desktop/MasterArena.Desktop.csproj -c Release
```
O executável será gerado em:
`desktop/MasterArena.Desktop/bin/Release/net8.0-windows/MasterArena.Desktop.exe`

---

## 👤 Credenciais Padrão de Homologação

| Perfil | Email | Senha Padrão |
|---|---|---|
| **Super Administrador** | `superadmin@masterarena.com.br` | `Super@123456` |
| **Administrador da Arena** | `admin@masterarena.com.br` | `Arena@123456` |
| **Operador de Caixa / Staff** | `atendente@masterarena.com.br` | `Staff@123456` |

---

## 📄 Licença
Propriedade exclusiva de **MasterDev Solutions**. Todos os direitos reservados.
