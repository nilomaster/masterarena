// Master Arena Desktop - QR Code Generation Helper
// Comments strictly in ASCII only.

using System;
using System.Drawing;
using QRCoder;

namespace MasterArena.Desktop.Helpers
{
    public static class QrCodeRenderer
    {
        // Render QR Code Bitmap from raw string data such as PIX Copia e Cola
        public static Bitmap GenerateQrCodeBitmap(string payload, int pixelsPerModule = 6)
        {
            if (string.IsNullOrWhiteSpace(payload))
            {
                return new Bitmap(180, 180);
            }

            using var qrGenerator = new QRCodeGenerator();
            using var qrCodeData = qrGenerator.CreateQrCode(payload, QRCodeGenerator.ECCLevel.Q);
            using var qrCode = new QRCode(qrCodeData);

            return qrCode.GetGraphic(pixelsPerModule, Color.Black, Color.White, true);
        }

        // Convenience overload for direct size rendering
        public static Bitmap GenerateQrBitmap(string payload, int size = 240)
        {
            return GenerateQrCodeBitmap(payload, 8);
        }
    }
}
