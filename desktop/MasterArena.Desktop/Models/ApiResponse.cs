// Master Arena Desktop - API Standard Response Envelope
// Comments strictly in ASCII only.

using System.Collections.Generic;
using System.Text.Json.Serialization;

namespace MasterArena.Desktop.Models
{
    public class ApiResponse<T>
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("message")]
        public string Message { get; set; } = string.Empty;

        [JsonPropertyName("data")]
        public T? Data { get; set; }

        [JsonPropertyName("errors")]
        public List<string>? Errors { get; set; }
    }
}
