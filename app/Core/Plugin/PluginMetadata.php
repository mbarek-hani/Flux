<?php

declare(strict_types=1);

namespace App\Core\Plugin;

class PluginMetadata
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(
        public readonly string $identifiant,
        public readonly string $nom,
        public readonly string $version,
        public readonly string $classe,
        public readonly string $cheminDossier,
        public readonly string $namespace,
        public readonly string $description = '',
        public readonly string $auteur = 'Inconnu',
        public readonly string $licence = 'Non spécifiée',
        public readonly ?string $url = null,
        public readonly array $manifest = [],
    ) {}

    /**
     * Crée une instance de métadonnées à partir du manifest décodé.
     *
     * @param  array<string, mixed>  $manifest
     */
    public static function fromManifest(string $cheminDossier, array $manifest): ?self
    {
        $classe = $manifest['classe'] ?? null;
        if (! is_string($classe) || trim($classe) === '') {
            return null;
        }

        $classe = trim($classe, '\\');
        $namespace = self::extraireNamespace($classe);
        if ($namespace === null) {
            return null;
        }

        $identifiant = (string) ($manifest['identifiant'] ?? basename($cheminDossier));
        $nom = (string) ($manifest['nom'] ?? $identifiant);
        $version = (string) ($manifest['version'] ?? '1.0.0');
        $description = (string) ($manifest['description'] ?? '');
        $auteur = (string) ($manifest['auteur'] ?? $manifest['author'] ?? 'Inconnu');
        $licence = (string) ($manifest['licence'] ?? 'Non spécifiée');
        $url = isset($manifest['url']) && is_string($manifest['url']) ? $manifest['url'] : null;

        return new self(
            identifiant: $identifiant,
            nom: $nom,
            version: $version,
            classe: $classe,
            cheminDossier: rtrim($cheminDossier, '/'),
            namespace: $namespace,
            description: $description,
            auteur: $auteur,
            licence: $licence,
            url: $url,
            manifest: $manifest,
        );
    }

    /**
     * Reconstitue les métadonnées depuis un tableau exporté (cache).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        if (
            empty($data['identifiant']) ||
            empty($data['nom']) ||
            empty($data['classe']) ||
            empty($data['cheminDossier']) ||
            empty($data['namespace'])
        ) {
            return null;
        }

        return new self(
            identifiant: (string) $data['identifiant'],
            nom: (string) $data['nom'],
            version: (string) ($data['version'] ?? '1.0.0'),
            classe: (string) $data['classe'],
            cheminDossier: (string) $data['cheminDossier'],
            namespace: (string) $data['namespace'],
            description: (string) ($data['description'] ?? ''),
            auteur: (string) ($data['auteur'] ?? 'Inconnu'),
            licence: (string) ($data['licence'] ?? 'Non spécifiée'),
            url: isset($data['url']) ? (string) $data['url'] : null,
            manifest: is_array($data['manifest'] ?? null) ? $data['manifest'] : [],
        );
    }

    /**
     * Exporte les métadonnées pour la mise en cache PHP.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identifiant' => $this->identifiant,
            'nom' => $this->nom,
            'version' => $this->version,
            'classe' => $this->classe,
            'cheminDossier' => $this->cheminDossier,
            'namespace' => $this->namespace,
            'description' => $this->description,
            'auteur' => $this->auteur,
            'licence' => $this->licence,
            'url' => $this->url,
            'manifest' => $this->manifest,
        ];
    }

    public function getIdentifiant(): string
    {
        return $this->identifiant;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuteur(): string
    {
        return $this->auteur;
    }

    public function getLicence(): string
    {
        return $this->licence;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * @return array<int, array{id: string, label: string, icone: string}>
     */
    public function getOnglets(): array
    {
        $onglets = $this->manifest['onglets'] ?? [];

        return is_array($onglets) ? $onglets : [];
    }

    /**
     * @return string[]
     */
    public function getHooks(): array
    {
        $hooks = $this->manifest['hooks'] ?? [];

        return is_array($hooks) ? $hooks : [];
    }

    /**
     * @return array<int, array{label: string, route: string, icone: string}>
     */
    public function getNavigationItems(): array
    {
        $nav = $this->manifest['navigation'] ?? [];

        return is_array($nav) ? $nav : [];
    }

    /**
     * @return array<int, array{cle: string, label: string, type: string}>
     */
    public function getReglages(): array
    {
        $reglages = $this->manifest['reglages'] ?? [];

        return is_array($reglages) ? $reglages : [];
    }

    public static function extraireNamespace(string $classePlugin): ?string
    {
        $parties = explode('\\', trim($classePlugin, '\\'));

        if (count($parties) < 2 || $parties[0] !== 'Plugins') {
            return null;
        }

        return $parties[0].'\\'.$parties[1];
    }
}
