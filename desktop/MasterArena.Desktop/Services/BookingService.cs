// Master Arena Desktop - Booking and Counter Reservation Service
// Comments strictly in ASCII only.

using System.Threading.Tasks;
using MasterArena.Desktop.Models;

namespace MasterArena.Desktop.Services
{
    public class BookingService
    {
        // Create booking from reception counter
        public async Task<ApiResponse<BookingCreatedDto>> CreateBookingAsync(
            int arenaId,
            int courtId,
            string date,
            string startTime,
            string endTime,
            string clientName,
            string clientPhone,
            string? clientEmail = null,
            string? couponCode = null)
        {
            var payload = new
            {
                quadra_id = courtId,
                data = date,
                hora_inicio = startTime,
                hora_fim = endTime,
                cliente_nome = clientName,
                cliente_telefone = clientPhone,
                cliente_email = clientEmail ?? $"{clientPhone}@cliente.local",
                tipo_reserva = "AVULSO",
                cupom_codigo = couponCode
            };

            return await ApiClient.Instance.PostAsync<BookingCreatedDto>($"arenas/{arenaId}/agendamentos", payload);
        }

        // Cancel booking with reason recording
        public async Task<ApiResponse<object>> CancelBookingAsync(int bookingId, string reason)
        {
            var payload = new { motivo = reason };
            return await ApiClient.Instance.PostAsync<object>($"agendamentos/{bookingId}/cancelar", payload);
        }
    }
}
