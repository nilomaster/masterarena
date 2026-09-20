// Master Arena Desktop - Payment, PIX and Check-in Services
// Comments strictly in ASCII only.

using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class PaymentService
    {
        // Generate PIX billing for a booking
        public async Task<ApiResponse<PixBillingDto>> GeneratePixAsync(int bookingId)
        {
            return await ApiClient.Instance.PostAsync<PixBillingDto>($"agendamentos/{bookingId}/pix");
        }

        // Direct confirmation of cash or POS card payment
        public async Task<ApiResponse<object>> ConfirmPaymentAsync(int paymentId)
        {
            return await ApiClient.Instance.PostAsync<object>($"pagamentos/{paymentId}/confirmar");
        }

        // Poll public booking and payment status
        public async Task<ApiResponse<BookingDetailDto>> GetBookingStatusAsync(int bookingId)
        {
            return await ApiClient.Instance.GetAsync<BookingDetailDto>($"agendamentos/{bookingId}/public-status");
        }
    }

    public class CheckinService
    {
        // Validate customer check-in code at reception terminal
        public async Task<ApiResponse<CheckinResponseDto>> ValidateCheckinAsync(int arenaId, string checkinCode)
        {
            var payload = new
            {
                codigo_checkin = checkinCode.Trim(),
                origem = "DESKTOP"
            };
            return await ApiClient.Instance.PostAsync<CheckinResponseDto>($"arenas/{arenaId}/checkin", payload);
        }
    }
}
