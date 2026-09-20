// Master Arena Desktop - Arena, Courts and Schedules Services
// Comments strictly in ASCII only.

using System.Collections.Generic;
using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class ArenaService
    {
        // Fetch operational courts for active arena
        public async Task<ApiResponse<CourtListDto>> GetCourtsAsync(int arenaId)
        {
            return await ApiClient.Instance.GetAsync<CourtListDto>($"arenas/{arenaId}/quadras");
        }

        // Fetch arena institutional details
        public async Task<ApiResponse<ArenaDto>> GetArenaInfoAsync(int arenaId)
        {
            return await ApiClient.Instance.GetAsync<ArenaDto>($"arenas/{arenaId}");
        }
    }

    public class ScheduleService
    {
        // Fetch visual availability grid for courts on a specific date (YYYY-MM-DD)
        public async Task<ApiResponse<GradeResponseDto>> GetGradeAsync(int arenaId, string date)
        {
            return await ApiClient.Instance.GetAsync<GradeResponseDto>($"arenas/{arenaId}/grade?data={date}");
        }
    }
}
