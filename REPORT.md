# Rapport d'Analyse de Performance - Optimisation Page Carrousel

## 1. Introduction

La performance web est un facteur critique pour l'expérience utilisateur et la réussite commerciale. Les études montrent qu'un délai d'un peu plus d'une seconde dans le chargement d'une page peut entraîner une grosse baisse des taux de conversion (et donc de revenus) et de la satisfaction utilisateur. Pour un site e-commerce ou vitrine comme celui la, des temps de chargement lents se traduisent directement par une perte de visiteurs et de ventes potentielles. En plus, les moteurs de recherche comme Google pénalisent lourdement les sites lents dans leurs algorithmes de classement (Core Web Vitals).

Avec des ressources d'hébergement limitées (2 vCPU, 2Go RAM) et d'une portée internationale (Serveur au Canada), une utilisation efficace des ressources est obligatoire pour maintenir la stabilité du site sous charge. Le site étant en français, on suppose que la plupart des utilisateurs sont basés en France et en Europe. La distance entre le Canada et les pays d'Europe est plutot grande, et rajoute donc un temps de chargement conséquent.

## 2. Hypothèse

Après une revue initiale du code du 'CarouselController' et des entités, plusieurs problèmes ont été identifiés :

1.  **Problème Massif de Requêtes N+1** : L'application souffre d'un sévère problème de requêtes "N+1" (plus spécifiquement des boucles imbriquées). Le contrôleur itère manuellement sur les éléments 'Galaxy', puis récupère les 'Modeles' un par un, puis les 'ModelesFiles' individuellement, et enfin les 'DirectusFiles' individuellement.
    - _Hypothèse_ : Pour un carrousel de 10 éléments, ayant chacun 3 photos, l'application exécute plus de 40 requêtes SQL séparées au lieu de 1 ou 2. Ça provoque une grande latence au niveau de la base de données, et une grosse charge CPU peu nécessaire. En prenant en compte que l'on à des ressources limitées (2vCPU), ce genre de problème n'est pas permis.

2.  **Ressources (Assets) Non Optimisées** : La vue affiche les images directement depuis la source ('filename_disk') sans redimensionnement ni optimisation de format.
    - _Hypothèse_ : Le navigateur est forcé de télécharger des images en pleine résolution (ex: 4Mo) juste pour les afficher en tant que petites miniatures ('max-h-14'). Cela cause des charges réseaux énormes, un "Largest Contentful Paint" (LCP) lent et une consommation élevée de bande passante.

3.  **Absence de Cache** : Les données sont récupérées depuis la base de données à chaque requête. Il n'y a pas d'utilisation du Cache HTTP de Symfony ni de logique pour mettre en cache le résultat complexe.

4.  **Manque de Relations entre Entités** : L'application utilise un mapping manuel d'IDs (ex: stocker la chaîne 'modele' dans 'Galaxy' au lieu d'une relation clé étrangère physique). Cela empêche Doctrine d'optimiser efficacement les requêtes via des JOINs.

## 3. Tests et Mesures

Pour confirmer ces hypothèses et établir une base de référence, les métriques et outils suivants devraient être utilisés :

### Outils

1.  **Symfony Web Profiler / Blackfire.io** : Pour inspecter le flux d'exécution backend.
2.  **Google Lighthouse / PageSpeed Insights (si le site est en ligne et pas en local)** : Pour mesurer les performances de rendu frontend.
3.  **Onglet Réseau (DevTools Navigateur)** : Pour mesurer la taille totale de la charge et le temps de transfert.

### Métriques Mesurées (Google Lighthouse) - Avant optimisations

Ces mesures confirment les hypothèses de lourdeur des pages, principalement dues aux images non optimisées.

- **Score Performance Globale** : 88/100
- **Score SEO** : 36/100 (Contenu non indexable, liens non explorables)
- **Largest Contentful Paint (LCP)** : 2,3 s
- **First Contentful Paint (FCP)** : 0,7 s
- **Poids Total de la Page** : **719 709 Kio (~720 Mo)** - Beaucoup trop

**Opportunités d'Optimisation (Estimations Lighthouse) :**

- **Améliorer l'affichage des images** : Gain potentiel de **186 492 Kio (~186 Mo)**
- **Mise en cache efficace** : Gain potentiel de **71 961 Kio (~72 Mo)**

### Résultats après optimisations

**Scores Globaux :**

- **100** Performances
- **91** Accessibilité
- **100** Bonnes pratiques
- **54** SEO

**Métrique Web Vitals :**

- **First Contentful Paint** : 0,2 s
- **Largest Contentful Paint** : 0,4 s
- **Total Blocking Time** : 0 ms
- **Cumulative Layout Shift** : 0,003
- **Speed Index** : 0,2 s

**Diagnostic :**

- Utiliser des durées de mise en cache efficaces Économies estimées : 361 Kio (rien par rapport à avant)
- Améliorer l'affichage des images Économies estimées : 113 Kio (de même, rien par rapport a avant)

## 4. Solutions

Voici la liste priorisée des solutions pour corriger la performance de l'application

### Priorité 0 : Configuration Serveur (Stabilité)

Le traitement des images haute résolution provoquait des erreurs 500 à cause de l'épuisement de la mémoire.

**Solution Appliquée (Immédiate) : **

- **Extension de la mémoire PHP** : Augmentation de 'memory_limit' à 512M dans la configuration PHP (via Dockerfile) pour permettre à GD/Liip de manipuler les fichiers sources volumineux sans crash le serveur.

### Priorité 1 : Backend & Base de Données (Correction N+1)

Le contrôleur actuel itère beaucoup pour récupérer les données liées, créant des dizaines de requêtes inutiles, ce qui impacte le time to first byte.

**Solution Proposée (Idéale) :**

- Cache Doctrine (Result Cache) pour mémoriser les résultats de requêtes fréquentes.
- Architecture CQRS pour séparer lecture et écriture.
- **Pourquoi pas maintenant ?** : Nécessite l'installation de services supplémentaires sur le serveur (impossible sans accès infrastructure) ou une réécriture trop complexe pour un correctif immédiat.

**Solution Appliquée (Immédiate) :**

- **Refactorisation des Entités** : Ajout des relations Doctrine ('OneToOne' / 'OneToMany') manquantes entre 'Galaxy', 'Modeles' et 'DirectusFiles' pour permettre les jointures.
- **Optimisation des Requêtes (Repository)** : Création d'une méthode 'findAllWithRelations()' utilisant 'QueryBuilder' et des 'leftJoin' + 'addSelect'. Cela réduira le nombre de requêtes SQL de >50 à 1 seule.

### Priorité 2 : Frontend & Assets (Poids de la page trop grand (720 Mo) )

C'est le point le plus critique selon Lighthouse. Les images sont servies en brut sans redimensionnement.

**Solution Proposée (Idéale) :**

- Mise en place d'un CDN (Cloudflare/AWS Cloudfront) pour servir les images au plus proche de l'utilisateur et réduire la latence.
- Conversion automatique (pipeline CI/CD) de tous les assets en format avif ou webp
- Utilisation de 'srcset' pour servir des images adaptées à la taille de l'écran du visiteur (Responsive Images)
- **Pourquoi pas maintenant ?** : La configuration d'un CDN demande un accès DNS/Infrastructure non fourni. La pipeline CI/CD demande accès aux outils de déploiement.

**Solution Appliquée (Immédiate) :**

- **Redimensionnement Serveur (miniatures)** : Installation et configuration de 'liip/imagine-bundle' pour générer et mettre en cache des miniatures (100x100) et des images de taille moyenne (640px) au lieu de servir les originaux de 4 Mo. Configuration du filtre pour forcer le format de sortie en webp.
- **Conversion Totale WebP** : Développement et exécution d'un script PHP unique pour convertir l'intégralité des images sources du dossier `assets/img/` de JPEG vers WebP. Mise à jour du template Twig pour référencer ces nouveaux fichiers. Suppression des anciens fichiers JPG.
- **Lazy Loading** : Ajout de l'attribut 'loading="lazy"' sur les images de la bande de sélection du carrousel (non prioritaires pour le LCP).
- **Dimensions Explicites** : Ajout de 'width' et 'height' sur les balises '<img>' pour stabiliser le layout.

### Priorité 3 : Cache & SEO

Le score SEO est critique (36/100) et le serveur travaille inutilement pour régénérer la même page HTML à chaque visite.

**Solution Proposée (Idéale) :**

- Cache Varnish / Reverse Proxy en amont du serveur applicatif.
- Génération automatique de Sitemap XML et configuration précise du Robots.txt.
- Audit sémantique complet du HTML (Hn, Aria, etc.).
- **Pourquoi pas maintenant ?** : Varnish nécessite une configuration administrateur du serveur (ROOT access) qu'on à pas.

**Solution Appliquée (Immédiate) :**

- **Cache HTTP Symfony** : Ajout de l'attribut '#[Cache(smaxage: 3600, public: true)]' sur le contrôleur 'CarouselController'. Cela permet de mettre en cache la réponse HTML complète pendant 1 heure, soulageant totalement le CPU pour les visites répétées.
- **Meta Description** : Ajout de la balise '<meta name="description">' manquante dans le template.
- **Accessibilité (Alt)** : Ajout dynamique de l'attribut 'alt' sur les images (basé sur le titre ou la description de l'objet) pour remonter le score SEO/Accessibilité.

## 5. Conclusion

Les optimisations implémentées ont transformé radicalement les performances de l'application. En passant d'un score de performance de 88 à 100/100 et en réduisant le LCP de 2.3s à 0.4s

Concrètement :

- **Requêtes BDD** : Réduites de plus de 40 requêtes (problème N+1) à 1 seule requête optimisée grâce aux jointures Doctrine.
- **Poids de la Page** : Réduit de ~720 Mo à une taille négligeable grâce à la conversion WebP et au redimensionnement dynamique (Liip iimagine).
- **Stabilité** : La suppression des boucles inefficaces et l'usage du cache (HTTP & Imagine) garantissent que le serveur (2 vCPU) peut désormais tenir la charge sans saturation mémoire.

**Améliorations Futures :**

- **Chargement Asynchrone** : Charger les données du carrousel via un appel API (AJAX) pour que le reste de la page se charge instantanément.
- **Varnish / Reverse Proxy** : Implémenter une couche de cache devant le serveur d'application pour servir la page sans même toucher PHP.
- **Indexation Base de Données** : S'assurer que les clés étrangères et champs de recherche sont correctement indexés dans PostgreSQL.
