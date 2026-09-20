// Master Arena Desktop - Authentication and Session Service
// Comments strictly in ASCII only.

using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class AuthService
    {
        // Authenticate operator or administrator credentials
        public async Task<ApiResponse<LoginResponseDto>> LoginAsync(string email, string senha)
        {
            var payload = new { email, senha };
            var response = await ApiClient.Instance.PostAsync<LoginResponseDto>("auth/login", payload);

            if (response.Success && response.Data != null)
            {
                SessionContext.Token = response.Data.Token;
                SessionContext.RefreshToken = response.Data.RefreshToken;
                SessionContext.CurrentUser = response.Data.User;

                if (response.Data.User?.Arena != null)
                {
                    SessionContext.CurrentArenaId = response.Data.User.Arena.Id;
                    SessionContext.CurrentArenaName = response.Data.User.Arena.NomeArena;
                }
                else if (response.Data.User?.ArenaId.HasValue == true)
                {
                    SessionContext.CurrentArenaId = response.Data.User.ArenaId.Value;
                }
            }

            return response;
        }

        // Invalidate session on server and locally
        public async Task<bool> LogoutAsync()
        {
            try
            {
                if (SessionContext.IsAuthenticated)
                {
                    await ApiClient.Instance.PostAsync<object>("auth/logout");
                }
            }
            finally
            {
                SessionContext.Logout();
            }

            return true;
        }
    }
}
