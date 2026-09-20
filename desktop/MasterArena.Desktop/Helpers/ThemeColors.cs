// Master Arena Desktop - Visual Theme and Color Palette
// Comments strictly in ASCII only.

using System.Drawing;

namespace MasterArena.Desktop.Helpers
{
    public static class ThemeColors
    {
        // Primary Backgrounds
        public static readonly Color BgBody = Color.FromArgb(10, 14, 23);         // #0A0E17
        public static readonly Color BgHeader = Color.FromArgb(15, 22, 38);       // #0F1626
        public static readonly Color BgSidebar = Color.FromArgb(15, 22, 38);      // #0F1626
        public static readonly Color BgCard = Color.FromArgb(18, 26, 43);         // #121A2B
        public static readonly Color BgCardHover = Color.FromArgb(26, 36, 60);    // #1A243C
        public static readonly Color BgInput = Color.FromArgb(12, 18, 30);        // #0C121E

        // Accents and Highlights
        public static readonly Color AccentLime = Color.FromArgb(0, 242, 121);    // #00F279
        public static readonly Color AccentGreen = Color.FromArgb(16, 185, 129);  // #10B981
        public static readonly Color AccentCyan = Color.FromArgb(6, 182, 212);    // #06B6D4
        public static readonly Color AccentAmber = Color.FromArgb(245, 158, 11);  // #F59E0B
        public static readonly Color AccentRed = Color.FromArgb(239, 68, 68);     // #EF4444

        // Typography
        public static readonly Color TextMain = Color.FromArgb(248, 250, 252);    // #F8FAFC
        public static readonly Color TextMuted = Color.FromArgb(148, 163, 184);   // #94A3B8
        public static readonly Color TextDark = Color.FromArgb(3, 21, 9);         // #031509

        // Borders and Dividers
        public static readonly Color Border = Color.FromArgb(35, 46, 71);         // #232E47
        public static readonly Color BorderGlow = Color.FromArgb(16, 185, 129);   // #10B981
    }
}
