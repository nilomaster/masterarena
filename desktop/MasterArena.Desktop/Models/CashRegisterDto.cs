// Master Arena Desktop - Cash Register (Caixa Balcao) Models
// Comments strictly in ASCII only.

using System.Collections.Generic;
using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class CashRegisterStatusDto
    {
        [JsonPropertyName("status_caixa")]
        public string StatusCaixa { get; set; } = "FECHADO"; // ABERTO, FECHADO

        [JsonPropertyName("sessao")]
        public CashSessionDto? Sessao { get; set; }

        [JsonPropertyName("balanco")]
        public CashBalanceDto? Balanco { get; set; }
    }

    public class CashSessionDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("arena_id")]
        public int ArenaId { get; set; }

        [JsonPropertyName("operador_id")]
        public int OperadorId { get; set; }

        [JsonPropertyName("saldo_abertura")]
        public decimal SaldoAbertura { get; set; }

        [JsonPropertyName("aberto_em")]
        public string AbertoEm { get; set; } = string.Empty;

        [JsonPropertyName("status")]
        public string Status { get; set; } = "ABERTO";
    }

    public class CashBalanceDto
    {
        [JsonPropertyName("saldo_abertura")]
        public decimal SaldoAbertura { get; set; }

        [JsonPropertyName("total_entradas_dinheiro")]
        public decimal TotalEntradasDinheiro { get; set; }

        [JsonPropertyName("total_suprimentos")]
        public decimal TotalSuprimentos { get; set; }

        [JsonPropertyName("total_sangrias")]
        public decimal TotalSangrias { get; set; }

        [JsonPropertyName("saldo_dinheiro_esperado")]
        public decimal SaldoDinheiroEsperado { get; set; }

        [JsonPropertyName("total_pix")]
        public decimal TotalPix { get; set; }

        [JsonPropertyName("total_cartao")]
        public decimal TotalCartao { get; set; }

        [JsonPropertyName("total_geral_recebido")]
        public decimal TotalGeralRecebido { get; set; }
    }
}
