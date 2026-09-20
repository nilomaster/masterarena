// Master Arena Desktop - Centralized REST API HTTP Client
// Comments strictly in ASCII only.

using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class ApiClient
    {
        private static readonly Lazy<ApiClient> _instance = new(() => new ApiClient());
        public static ApiClient Instance => _instance.Value;

        private readonly HttpClient _httpClient;
        private readonly JsonSerializerOptions _jsonOptions;

        private ApiClient()
        {
            var handler = new HttpClientHandler
            {
                // Allow custom/intermediate SSL certificates in shared hosting environments
                ServerCertificateCustomValidationCallback = (sender, cert, chain, sslPolicyErrors) => true
            };

            _httpClient = new HttpClient(handler)
            {
                Timeout = TimeSpan.FromSeconds(20)
            };
            _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("User-Agent", "MasterArena-Desktop/1.0");

            _jsonOptions = new JsonSerializerOptions
            {
                PropertyNameCaseInsensitive = true
            };
        }

        // Configure or switch API base endpoint URL
        public string BaseUrl => SessionContext.ApiBaseUrl;

        public void SetBaseUrl(string baseUrl)
        {
            SessionContext.ApiBaseUrl = baseUrl.TrimEnd('/');
        }

        // Execute asynchronous GET request
        public async Task<ApiResponse<T>> GetAsync<T>(string endpoint)
        {
            return await SendRequestAsync<T>(HttpMethod.Get, endpoint);
        }

        // Execute asynchronous POST request
        public async Task<ApiResponse<T>> PostAsync<T>(string endpoint, object? body = null)
        {
            return await SendRequestAsync<T>(HttpMethod.Post, endpoint, body);
        }

        // Execute asynchronous PUT request
        public async Task<ApiResponse<T>> PutAsync<T>(string endpoint, object? body = null)
        {
            return await SendRequestAsync<T>(HttpMethod.Put, endpoint, body);
        }

        // Execute asynchronous DELETE request
        public async Task<ApiResponse<T>> DeleteAsync<T>(string endpoint)
        {
            return await SendRequestAsync<T>(HttpMethod.Delete, endpoint);
        }

        // Internal HTTP dispatch helper
        private async Task<ApiResponse<T>> SendRequestAsync<T>(HttpMethod method, string endpoint, object? body = null)
        {
            try
            {
                var fullUrl = $"{SessionContext.ApiBaseUrl}/{endpoint.TrimStart('/')}";
                using var request = new HttpRequestMessage(method, fullUrl);

                if (!string.IsNullOrWhiteSpace(SessionContext.Token))
                {
                    request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", SessionContext.Token);
                }

                if (body != null)
                {
                    var jsonContent = JsonSerializer.Serialize(body, _jsonOptions);
                    request.Content = new StringContent(jsonContent, Encoding.UTF8, "application/json");
                }

                using var response = await _httpClient.SendAsync(request);
                var rawJson = await response.Content.ReadAsStringAsync();

                if (!string.IsNullOrWhiteSpace(rawJson))
                {
                    var apiResp = JsonSerializer.Deserialize<ApiResponse<T>>(rawJson, _jsonOptions);
                    if (apiResp != null)
                    {
                        return apiResp;
                    }
                }

                return new ApiResponse<T>
                {
                    Success = response.IsSuccessStatusCode,
                    Message = response.IsSuccessStatusCode ? "Operacao realizada com sucesso." : $"Erro HTTP {(int)response.StatusCode}."
                };
            }
            catch (Exception ex)
            {
                return new ApiResponse<T>
                {
                    Success = false,
                    Message = $"Falha de conexao com a API: {ex.Message}"
                };
            }
        }
    }
}
