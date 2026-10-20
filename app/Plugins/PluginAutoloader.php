<?php

declare(strict_types=1);

namespace App\Plugins;

final class PluginAutoloader
{
    /** @var array<string, string> */
    private array $prefixes = [];

    public function __construct()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    public function addNamespace(string $prefix, string $baseDirectory): void
    {
        $prefix = trim($prefix, '\\').'\\';
        $baseDirectory = rtrim($baseDirectory, '/').'/';

        $this->prefixes[$prefix] = $baseDirectory;
    }

    public function hasNamespace(string $prefix): bool
    {
        return isset($this->prefixes[trim($prefix, '\\').'\\']);
    }

    public function loadVendor(string $pluginPath): bool
    {
        $autoload = rtrim($pluginPath, '/').'/vendor/autoload.php';

        if (! is_file($autoload)) {
            return false;
        }

        require_once $autoload;

        return true;
    }

    public function autoload(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDirectory) {
            if (! str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDirectory.str_replace('\\', '/', $relativeClass).'.php';

            if (is_file($file)) {
                require_once $file;

                return;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->prefixes;
    }
}
