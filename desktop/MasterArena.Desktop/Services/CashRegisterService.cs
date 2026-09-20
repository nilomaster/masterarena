// Master Arena Desktop - Cash Register (Caixa Balcao) Service
// Comments strictly in ASCII only.

using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class CashRegisterService
    {
        // Query active cash register status, active shift and balances
        public async Task<ApiResponse<CashRegisterStatusDto>> GetStatusAsync(int arenaId)
        {
            return await ApiClient.Instance.GetAsync<CashRegisterStatusDto>($"arenas/{arenaId}/caixa/status");
        }

        // Open new cash register shift with initial float
        public async Task<ApiResponse<CashSessionDto>> OpenShiftAsync(int arenaId, decimal saldoAbertura)
        {
            var payload = new { saldo_abertura = saldoAbertura };
            return await ApiClient.Instance.PostAsync<CashSessionDto>($"arenas/{arenaId}/caixa/abrir", payload);
        }

        // Register cash movement (SANGRIA for safe deposit or SUPRIMENTO for float refill)
        public async Task<ApiResponse<object>> RegisterMovementAsync(int arenaId, string tipo, decimal valor, string motivo)
        {
            var payload = new
            {
                tipo = tipo.ToUpper(),
                valor = valor,
                motivo = motivo
            };
            return await ApiClient.Instance.PostAsync<object>($"arenas/{arenaId}/caixa/movimentacao", payload);
        }

        // Close cash register shift with blind reconciliation count
        public async Task<ApiResponse<object>> CloseShiftAsync(int arenaId, decimal saldoInformado)
        {
            var payload = new { saldo_informado = saldoInformado };
            return await ApiClient.Instance.PostAsync<object>($"arenas/{arenaId}/caixa/fechar", payload);
        }
    }
}
