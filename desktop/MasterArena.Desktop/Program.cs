// Master Arena Desktop - Entry Point
// Comments strictly in ASCII only.

using System;
using System.Windows.Forms;
using MasterArena.Desktop.Forms;

namespace MasterArena.Desktop
{
    static class Program
    {
        [STAThread]
        static void Main()
        {
            ApplicationConfiguration.Initialize();
            Application.Run(new LoginForm());
        }
    }
}