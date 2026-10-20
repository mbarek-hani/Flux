<?php

declare(strict_types=1);

namespace App\Core\Plugin;

class PluginAutoloader
{
    /**
     * @var array<string, string>
     */
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
        $prefix = trim($prefix, '\\').'\\';

        return isset($this->prefixes[$prefix]);
    }

    /**
     * Charge l'autoloader Composer tiers d'un plugin si présent.
     */
    public function chargerVendor(string $cheminDossier): bool
    {
        $vendorAutoload = rtrim($cheminDossier, '/').'/vendor/autoload.php';

        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;

            return true;
        }

        return false;
    }

    public function autoload(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDirectory) {
            if (! str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDirectory.str_replace('\\', '/', $relativeClass).'.php';

            if (file_exists($file)) {
                require_once $file;

                return;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }
}
