# Flux

Flux est une application modulaire de suivi analytique et d'événements pour sites web, développée avec **Laravel 13**, **Alpine.js**, et **Vanilla CSS** (CSS sémantique personnalisé).

Elle permet de collecter, analyser et visualiser les statistiques de vos sites internet avec un système de tracking performant et une interface élégante.

## Fonctionnalités principales

- **Tracking de visites et d'événements :** Enregistrement des données de navigation et événements personnalisés via un script client (`tracker.js`).
- **Tableau de bord :** Interface moderne et réactive pour visualiser les statistiques de base.
- **Architecture modulaire :** Fonctionnalités étendues via un système de plugins robustes, sans jamais avoir besoin de modifier le code cœur (core) de l'application.

## Système de Plugins

Flux a été conçu pour être facilement extensible. Les développeurs peuvent créer des plugins pour :
- Ajouter des onglets au tableau de bord.
- Créer des pages complètes avec leur propre navigation.
- Gérer leurs propres tables de base de données.
- Injecter des scripts additionnels dans le tracker.
- Enrichir les données de visite ou réagir via des hooks.

**Découvrez comment créer vos propres extensions :**
[Guide de Développement de Plugins](docs/guide_de_developpement_de_plugins.md)
**Voici une plugin exemple :** [AiAnalytics](https://github.com/mbarek-hani/AiAnalytics)

## Stack Technique

- **Backend :** Laravel 13 (PHP)
- **Frontend :** Blade, Alpine.js, Vanilla CSS
- **Icônes :** Heroicons

## Licence

Ce projet est sous licence [MIT](https://opensource.org/licenses/MIT).
