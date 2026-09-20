// Master Arena Desktop - Payment, PIX and Check-in Models
// Comments strictly in ASCII only.

using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class PixBillingDto
    {
        [JsonPropertyName("pix")]
        public PixDataDto? Pix { get; set; }

        [JsonPropertyName("pagamento")]
        public PaymentItemDto? Pagamento { get; set; }

        [JsonPropertyName("agendamento")]
        public BookingDetailDto? Agendamento { get; set; }
    }

    public class PixDataDto
    {
        [JsonPropertyName("txid")]
        public string Txid { get; set; } = string.Empty;

        [JsonPropertyName("copia_e_cola")]
        public string CopiaECola { get; set; } = string.Empty;

        [JsonPropertyName("valor")]
        public decimal Valor { get; set; }

        [JsonPropertyName("expiracao_segundos")]
        public int ExpiracaoSegundos { get; set; } = 900;
    }

    public class PaymentItemDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("valor")]
        public decimal Valor { get; set; }

        [JsonPropertyName("forma_pagamento")]
        public string FormaPagamento { get; set; } = "PIX";

        [JsonPropertyName("status")]
        public string Status { get; set; } = "PENDENTE"; // PENDENTE, APROVADO, ESTORNADO
    }

    public class CheckinResponseDto
    {
        [JsonPropertyName("agendamento")]
        public BookingDetailDto? Agendamento { get; set; }

        [JsonPropertyName("checkin_em")]
        public string CheckinEm { get; set; } = string.Empty;
    }
}
