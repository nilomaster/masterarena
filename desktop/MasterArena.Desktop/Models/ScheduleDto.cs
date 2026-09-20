// Master Arena Desktop - Availability Grid and Bookings Models
// Comments strictly in ASCII only.

using System.Collections.Generic;
using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class GradeSlotDto
    {
        [JsonPropertyName("hora_inicio")]
        public string HoraInicio { get; set; } = string.Empty;

        [JsonPropertyName("hora_fim")]
        public string HoraFim { get; set; } = string.Empty;

        [JsonPropertyName("status")]
        public string Status { get; set; } = "LIVRE"; // LIVRE, RESERVADO, BLOQUEADO

        [JsonPropertyName("valor")]
        public decimal Valor { get; set; }

        [JsonPropertyName("agendamento_id")]
        public int? AgendamentoId { get; set; }

        [JsonPropertyName("cliente_nome")]
        public string? ClienteNome { get; set; }

        [JsonPropertyName("codigo_checkin")]
        public string? CodigoCheckin { get; set; }
    }

    public class CourtGradeDto
    {
        [JsonPropertyName("quadra_id")]
        public int QuadraId { get; set; }

        [JsonPropertyName("quadra_nome")]
        public string QuadraNome { get; set; } = string.Empty;

        [JsonPropertyName("modalidade_nome")]
        public string ModalidadeNome { get; set; } = string.Empty;

        [JsonPropertyName("horarios")]
        public List<GradeSlotDto> Horarios { get; set; } = new();
    }

    public class GradeResponseDto
    {
        [JsonPropertyName("data")]
        public string Data { get; set; } = string.Empty;

        [JsonPropertyName("quadras")]
        public List<CourtGradeDto> Quadras { get; set; } = new();
    }

    public class BookingCreatedDto
    {
        [JsonPropertyName("agendamento")]
        public BookingDetailDto? Agendamento { get; set; }

        [JsonPropertyName("cliente")]
        public object? Cliente { get; set; }
    }

    public class BookingDetailDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("arena_id")]
        public int ArenaId { get; set; }

        [JsonPropertyName("quadra_id")]
        public int QuadraId { get; set; }

        [JsonPropertyName("cliente_nome")]
        public string ClienteNome { get; set; } = string.Empty;

        [JsonPropertyName("cliente_telefone")]
        public string ClienteTelefone { get; set; } = string.Empty;

        [JsonPropertyName("data")]
        public string Data { get; set; } = string.Empty;

        [JsonPropertyName("hora_inicio")]
        public string HoraInicio { get; set; } = string.Empty;

        [JsonPropertyName("hora_fim")]
        public string HoraFim { get; set; } = string.Empty;

        [JsonPropertyName("status")]
        public string Status { get; set; } = "PENDENTE";

        [JsonPropertyName("valor_final")]
        public decimal ValorFinal { get; set; }

        [JsonPropertyName("codigo_checkin")]
        public string? CodigoCheckin { get; set; }
    }
}
