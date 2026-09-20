// Master Arena Desktop - Arena, Courts and Sports Models
// Comments strictly in ASCII only.

using System.Collections.Generic;
using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class ArenaDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("nome_arena")]
        public string NomeArena { get; set; } = string.Empty;

        [JsonPropertyName("slug")]
        public string Slug { get; set; } = string.Empty;

        [JsonPropertyName("telefone")]
        public string Telefone { get; set; } = string.Empty;

        [JsonPropertyName("email")]
        public string Email { get; set; } = string.Empty;

        [JsonPropertyName("status")]
        public string Status { get; set; } = "ATIVO";
    }

    public class CourtDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("arena_id")]
        public int ArenaId { get; set; }

        [JsonPropertyName("modalidade_id")]
        public int ModalidadeId { get; set; }

        [JsonPropertyName("nome")]
        public string Nome { get; set; } = string.Empty;

        [JsonPropertyName("modalidade_nome")]
        public string ModalidadeNome { get; set; } = string.Empty;

        [JsonPropertyName("capacidade")]
        public int Capacidade { get; set; } = 4;

        [JsonPropertyName("valor_padrao")]
        public decimal ValorPadrao { get; set; }

        [JsonPropertyName("status")]
        public string Status { get; set; } = "ATIVO";
    }

    public class CourtListDto
    {
        [JsonPropertyName("quadras")]
        public List<CourtDto> Quadras { get; set; } = new();
    }
}
