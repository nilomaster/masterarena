// Master Arena Desktop - User Authentication Session Model
// Comments strictly in ASCII only.

using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class UserDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("nome")]
        public string Nome { get; set; } = string.Empty;

        [JsonPropertyName("email")]
        public string Email { get; set; } = string.Empty;

        [JsonPropertyName("perfil")]
        public string Perfil { get; set; } = string.Empty;

        public string Role => Perfil;

        [JsonPropertyName("arena_id")]
        public int? ArenaId { get; set; }

        [JsonPropertyName("arena")]
        public UserArenaDto? Arena { get; set; }
    }

    public class UserArenaDto
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("nome_arena")]
        public string NomeArena { get; set; } = string.Empty;

        public string Nome => NomeArena;

        [JsonPropertyName("slug")]
        public string Slug { get; set; } = string.Empty;

        [JsonPropertyName("cidade")]
        public string Cidade { get; set; } = "Curitiba";

        [JsonPropertyName("estado")]
        public string Estado { get; set; } = "PR";
    }

    public class LoginResponseDto
    {
        [JsonPropertyName("token")]
        public string Token { get; set; } = string.Empty;

        [JsonPropertyName("refresh_token")]
        public string RefreshToken { get; set; } = string.Empty;

        [JsonPropertyName("user")]
        public UserDto? User { get; set; }
    }

    // Singleton active runtime session state
    public static class SessionContext
    {
        public static string Token { get; set; } = string.Empty;
        public static string RefreshToken { get; set; } = string.Empty;
        public static UserDto? CurrentUser { get; set; }
        public static int CurrentArenaId { get; set; } = 1;
        public static string CurrentArenaName { get; set; } = "Master Arena";
        public static string ApiBaseUrl { get; set; } = "https://masterarena.esporte.ws/api/v1";

        public static int ActiveArenaId => CurrentArenaId;
        public static UserArenaDto? ActiveArena => CurrentUser?.Arena;

        public static bool IsAuthenticated => !string.IsNullOrWhiteSpace(Token) && CurrentUser != null;

        public static void Clear() => Logout();

        public static void Logout()
        {
            Token = string.Empty;
            RefreshToken = string.Empty;
            CurrentUser = null;
        }
    }
}
