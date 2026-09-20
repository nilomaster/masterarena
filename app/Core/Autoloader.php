<?php
// Master Arena SaaS - PSR-4 Autoloader
// Comments strictly in ASCII only.

namespace App\Core;

class Autoloader
{
    private static array $prefixes = [];

    // Register autoloader with SPL
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'loadClass']);

        // Default namespace prefixes
        self::addNamespace('App\\', dirname(__DIR__) . '/');
        self::addNamespace('Database\\', dirname(__DIR__, 2) . '/database/');
    }

    // Add a base directory for a namespace prefix
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }

        self::$prefixes[$prefix][] = $baseDir;
    }

    // Load class file based on namespace and class name
    public static function loadClass(string $class): bool
    {
        $prefix = $class;

        while (false !== ($pos = strrpos($prefix, '\\'))) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            if (isset(self::$prefixes[$prefix])) {
                foreach (self::$prefixes[$prefix] as $baseDir) {
                    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

                    if (file_exists($file)) {
                        require_once $file;
                        return true;
                    }
                }
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }
}
