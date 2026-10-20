<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use App\Models\Evenement;
use App\Models\Visite;
use Illuminate\Http\Request;

interface PluginInterface
{
    // Identité
    public function getIdentifiant(): string;

    public function getNom(): string;

    public function getVersion(): string;

    // Cycle de vie
    public function activer(): void;

    public function desactiver(): void;

    public function installer(): void;

    public function desinstaller(): void;

    public function enregistrer(): void;

    // UI : Onglets dashboard

    /**
     * Onglets ajoutés au dashboard.
     *
     * @return array<int, array{id: string, label: string, icone: string}>
     */
    public function getOnglets(): array;

    // UI : Réglages

    /**
     * Champs de configuration du plugin.
     *
     * @return array<int, array{cle: string, label: string, type: string}>
     */
    public function getReglages(): array;

    // UI : Pages et navigation

    /**
     * @return array<int, array{
     *     label: string,
     *     route: string,
     *     icone: string,
     *     sous_menus?: array<int, array{label: string, route: string}>
     * }>
     */
    public function getNavigationItems(): array;

    // Hooks système

    /** @return string[] */
    public function getHooks(): array;

    /**
     * @param  array<string, mixed>  $donnees
     */
    public function rendrePourHook(string $hook, array $donnees = []): string;

    // Événements Laravel

    /**
     * Écouteurs d'événements Laravel enregistrés par ce plugin.
     * Permet d'écouter VisiteEnregistree, EvenementEnregistre, etc. avec des écouteurs synchrones ou en file d'attente (ShouldQueue).
     *
     * @return array<class-string, array<int, class-string|callable>|class-string|callable>
     */
    public function getEcouteurs(): array;

    // Tracking : enrichissement des données

    /**
     * Traite les données de tracking AVANT enregistrement.
     * Permet au plugin d'ajouter/modifier des données.
     * Retourne les données modifiées, ou null pour annuler et ignorer l'enregistrement de ce tracking.
     *
     * @param  array<string, mixed>  $donnees  Données brutes du tracker
     * @param  Request  $request
     * @return array<string, mixed>|null Données enrichies ou null pour annuler
     */
    public function enrichirTracking(array $donnees, $request): ?array;

    /**
     * Appelé APRÈS l'enregistrement d'une visite (exécution synchrone).
     * Permet au plugin de stocker ses propres métadonnées.
     *
     * @param  Visite  $visite
     * @param  array<string, mixed>  $donnees
     * @param  Request  $request
     */
    public function apresVisite($visite, array $donnees, $request): void;

    /**
     * Appelé APRÈS l'enregistrement d'un événement (exécution synchrone).
     *
     * @param  Evenement  $evenement
     * @param  array<string, mixed>  $donnees
     * @param  Request  $request
     */
    public function apresEvenement($evenement, array $donnees, $request): void;

    // Tracking : données JS supplémentaires

    /**
     * Retourne du JavaScript à injecter dans tracker.js.
     * Permet au plugin de collecter des données côté client.
     * Retourne une chaîne JS ou null.
     */
    public function getTrackerJavaScript(): ?string;

    // Assets

    /**
     * Retourne le chemin vers un fichier CSS précompilé.
     * Sera inclus automatiquement dans le layout.
     */
    public function getCssPath(): ?string;

    // Manifest

    /** @return array<string, mixed> */
    public function getManifest(): array;
}
