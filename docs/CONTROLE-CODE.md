Voici les **modules à contrôler avant production** pour BantuDelice, dans une logique d’audit strict avant mise en ligne.

## 1. Socle technique Laravel

| Module               | Contrôle obligatoire                                                      | Preuve attendue                                         |
| -------------------- | ------------------------------------------------------------------------- | ------------------------------------------------------- |
| Environnement `.env` | `APP_ENV=production`, `APP_DEBUG=false`, URLs correctes, clés API isolées | Capture ou extrait contrôlé                             |
| Routes               | Aucune route cassée, aucune méthode inexistante, aucun `500`              | `php artisan route:list`                                |
| Migrations           | Base propre, migrations rejouables, pas de colonne manquante              | `php artisan migrate:status`                            |
| Seeders              | Données de base nécessaires uniquement                                    | Admin, rôles, statuts, catégories                       |
| Cache production     | Config, routes, vues optimisées                                           | `php artisan config:cache`, `route:cache`, `view:cache` |
| Logs                 | Logs activés, lisibles, sans erreur critique                              | `storage/logs/laravel.log` vérifié                      |

## 2. Authentification et sécurité

| Module                  | Contrôle obligatoire                                              |
| ----------------------- | ----------------------------------------------------------------- |
| Connexion / inscription | Validation stricte, messages propres, pas d’énumération d’e-mails |
| Mot de passe oublié     | Token sécurisé, expiration, pas de fuite d’existence de compte    |
| Rôles et permissions    | Admin, restaurant, driver, client strictement séparés             |
| Middleware              | Chaque espace protégé par middleware adapté                       |
| Sessions                | Expiration correcte, logout fiable                                |
| CSRF                    | Tous les formulaires sensibles protégés                           |
| Accès direct URL        | Un utilisateur ne doit jamais accéder aux ressources d’un autre   |

Point bloquant : si un client peut lire une commande d’un autre client, production interdite.

## 3. Module utilisateurs / profils

| Rôle       | Contrôles                                                                 |
| ---------- | ------------------------------------------------------------------------- |
| Client     | Profil, commandes, adresses, téléphone, historique                        |
| Restaurant | Profil restaurant, informations légales, horaires, statut ouvert/fermé    |
| Driver     | Profil livreur, disponibilité, documents, téléphone, statut actif/inactif |
| Admin      | Profil admin, changement mot de passe, accès global contrôlé              |

À vérifier : aucune route profil manquante, notamment pour le driver.

## 4. Module restaurants

Contrôler :

* Création restaurant.
* Modification restaurant.
* Activation / désactivation.
* Horaires d’ouverture.
* Images / logo / bannière.
* Localisation.
* Statut disponible / indisponible.
* Attribution au bon propriétaire.
* Affichage côté client.

Preuve : créer un restaurant test, modifier ses données, vérifier côté client et côté admin.

## 5. Module catalogue / menus / produits

Contrôler :

* Catégories.
* Produits.
* Prix.
* Images.
* Disponibilité produit.
* Variantes / options si présentes.
* Produits supprimés ou désactivés.
* Affichage responsive.
* Protection contre prix manipulé depuis le frontend.

Point critique : le prix final doit toujours être recalculé côté serveur, jamais faire confiance au panier côté navigateur.

## 6. Module panier

Contrôler :

| Action            | Risque                                                             |
| ----------------- | ------------------------------------------------------------------ |
| Ajouter au panier | Produit inexistant, indisponible, mauvais prix                     |
| Modifier quantité | Quantité négative, quantité énorme, accès panier autre utilisateur |
| Supprimer article | Suppression article d’un autre client                              |
| Vider panier      | Action non autorisée                                               |
| Total panier      | Mauvais calcul TVA/livraison/remise                                |

Preuve : tests sur utilisateur A et utilisateur B pour vérifier l’isolation.

## 7. Module commande

Contrôler tout le cycle :

1. Création commande.
2. Validation panier.
3. Attribution restaurant.
4. Statut initial.
5. Acceptation restaurant.
6. Refus restaurant.
7. Préparation.
8. Affectation livreur.
9. Ramassage.
10. Livraison.
11. Annulation.
12. Reçu client.
13. Historique.

Points critiques :

* Une commande doit appartenir à un client.
* Un restaurant ne doit voir que ses commandes.
* Un driver ne doit voir que ses livraisons affectées.
* L’admin doit voir tout, mais ses actions doivent être tracées.
* Une commande annulée ne doit plus être modifiable comme commande active.

## 8. Module paiement

À contrôler avant production :

| Élément          | Contrôle                                     |
| ---------------- | -------------------------------------------- |
| Paiement espèces | Statut clair : payé / non payé / à encaisser |
| Mobile Money     | Callback sécurisé, signature vérifiée        |
| Carte bancaire   | Jamais stocker les données carte             |
| Échec paiement   | Commande non confirmée abusivement           |
| Double paiement  | Protection idempotence                       |
| Reçu             | Montants exacts                              |
| Remboursement    | Statut et traçabilité                        |

Point bloquant : aucune route paiement ne doit pointer vers une méthode inexistante ou retourner une confirmation fictive.

## 9. Module livraison / drivers

Contrôler :

* Disponibilité livreur.
* Affectation manuelle.
* Affectation automatique si prévue.
* Acceptation mission.
* Refus mission.
* Départ restaurant.
* Livraison terminée.
* Historique driver.
* Revenus driver.
* Sécurité : un driver ne peut pas prendre une commande non affectée sans règle validée.

Preuve : scénario complet client → restaurant → driver → livraison.

## 10. Module tracking GPS

Contrôler :

| Élément               | Contrôle                                      |
| --------------------- | --------------------------------------------- |
| Envoi position driver | Position reçue seulement pour commande active |
| WebSocket / polling   | Mise à jour visible client/admin              |
| Carte                 | Position cohérente                            |
| Permissions           | Client ne voit que sa commande                |
| Historique            | Pas de fuite de données GPS inutiles          |
| Fallback              | Si WebSocket échoue, système alternatif prévu |

Point critique : ne pas exposer toutes les positions de tous les drivers au frontend.

## 11. Module notifications

Contrôler :

* Notification nouvelle commande restaurant.
* Notification acceptation commande client.
* Notification refus commande.
* Notification affectation driver.
* Notification livraison.
* Notification annulation.
* Notification paiement.
* E-mail si prévu.
* SMS / WhatsApp si prévu.
* Notifications admin.

Preuve : tester chaque événement métier avec un compte réel ou sandbox.

## 12. Module admin

À contrôler impérativement :

| Zone admin  | Contrôles                          |
| ----------- | ---------------------------------- |
| Dashboard   | Données exactes, pas de faux KPI   |
| Commandes   | Filtrage, statut, détails, actions |
| Restaurants | Activation, suspension, édition    |
| Clients     | Consultation contrôlée             |
| Drivers     | Activation, affectation, suivi     |
| Paiements   | États cohérents                    |
| Annulations | Motif obligatoire                  |
| Logs        | Actions sensibles tracées          |

Point bloquant : aucun bouton admin ne doit déclencher une action sans contrôle serveur.

## 13. Module dashboard / KPIs

Contrôler :

* Nombre de commandes.
* Commandes en attente.
* Commandes acceptées.
* Commandes annulées.
* Revenus.
* Commissions.
* Revenus restaurant.
* Revenus driver.
* Filtre par date.
* Filtre par workspace si le système existe.
* Cohérence avec la base de données.

Preuve : comparer les KPI affichés avec des requêtes SQL simples.

## 14. Module revenus / commissions

Contrôler :

| Module                | Contrôle                            |
| --------------------- | ----------------------------------- |
| Restaurant payout     | Montants dus au restaurant          |
| Driver payout         | Montants dus au livreur             |
| Commission plateforme | Calcul exact                        |
| Frais livraison       | Attribution correcte                |
| Devise                | FCFA par défaut, USD/€ si supportés |
| Historique            | Paiements traçables                 |

Point critique : pas de calcul financier uniquement côté interface.

## 15. Module fichiers / images

Contrôler :

* Upload sécurisé.
* Taille maximale.
* Extensions autorisées.
* Images optimisées.
* Suppression fichier ancien.
* Fichiers privés non exposés publiquement.
* Pas d’exécution de fichiers uploadés.

Interdire : `.php`, `.js`, `.sh`, fichiers exécutables.

## 16. Module API

Contrôler :

* Endpoints documentés.
* Authentification API.
* Rate limiting.
* Validation des payloads.
* Réponses JSON propres.
* Codes HTTP corrects : `200`, `201`, `400`, `401`, `403`, `404`, `422`, `500`.
* Pas d’erreur brute Laravel exposée.

Preuve : collection Postman / Insomnia ou tests automatisés.

## 17. Module UI / UX

Contrôler toutes les pages critiques :

* Login.
* Register.
* Accueil.
* Liste restaurants.
* Détail restaurant.
* Produit.
* Panier.
* Checkout.
* Paiement.
* Confirmation commande.
* Suivi commande.
* Profil client.
* Dashboard restaurant.
* Dashboard driver.
* Admin dashboard.

États obligatoires :

* Loading.
* Empty state.
* Error state.
* Success state.
* Validation form.
* Mobile responsive.
* Table compacte.
* Drawer / modal si utilisé.
* Boutons désactivés pendant traitement.

## 18. Module légal / conformité

Contrôler :

* Conditions générales d’utilisation.
* Politique de confidentialité.
* Mentions légales.
* Politique de remboursement.
* Règles d’annulation.
* Gestion des données personnelles.
* Consentement notifications.
* Conservation des logs.

Même si tout n’est pas juridiquement finalisé, il faut au minimum des pages propres et non vides.

## 19. Module performance

Contrôler :

| Élément         | Contrôle                              |
| --------------- | ------------------------------------- |
| Pages publiques | Chargement rapide                     |
| Images          | Compression                           |
| Requêtes SQL    | Pas de N+1 évident                    |
| Cache           | Configuré                             |
| Pagination      | Obligatoire sur grandes listes        |
| Dashboard       | Ne doit pas charger toute la base     |
| Queue           | Notifications lourdes en arrière-plan |

Commandes utiles :

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work
```

## 20. Module sauvegarde / rollback

Avant production, contrôler :

* Backup base de données.
* Backup fichiers uploadés.
* Procédure rollback.
* Accès serveur.
* Accès base.
* Accès logs.
* Restauration testée.
* Version Git taguée.

Production interdite sans sauvegarde restaurable.

## 21. Module tests avant validation finale

Tests minimum :

```bash
composer install --no-dev --optimize-autoloader
php artisan test
php artisan route:list
php artisan migrate:status
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan optimize
```

Contrôles manuels :

* Connexion admin.
* Connexion client.
* Connexion restaurant.
* Connexion driver.
* Création commande complète.
* Acceptation restaurant.
* Affectation driver.
* Livraison.
* Paiement.
* Annulation.
* Reçu.
* Dashboard.
* Déconnexion.

## Ordre de contrôle recommandé

1. Environnement production.
2. Base de données.
3. Authentification.
4. Rôles et permissions.
5. Routes.
6. Panier.
7. Commandes.
8. Paiement.
9. Restaurant.
10. Driver.
11. Livraison.
12. Tracking.
13. Notifications.
14. Dashboard admin.
15. Revenus / commissions.
16. UI mobile.
17. Sécurité.
18. Performance.
19. Logs.
20. Backup.
21. Tests finaux.

## Critères de blocage production

La production doit être bloquée si au moins un de ces cas existe :

* `APP_DEBUG=true`.
* Route cassée.
* Méthode contrôleur inexistante.
* Erreur `500` sur un parcours critique.
* Utilisateur pouvant voir ou modifier les données d’un autre.
* Paiement confirmé sans preuve serveur.
* Commande validée avec prix manipulable.
* Admin action sans vérification serveur.
* Aucun backup restaurable.
* Logs remplis d’erreurs non traitées.
* Dashboard affichant de faux chiffres.
* Driver sans route profil ou sans contrôle d’accès.
* Restaurant pouvant agir sur une commande qui ne lui appartient pas.

## Format de rapport à exiger avant production

```markdown
# Rapport de contrôle avant production — BantuDelice

## 1. Périmètre contrôlé
- Date :
- Branche Git :
- Commit :
- Environnement :
- Responsable contrôle :

## 2. Modules vérifiés
| Module | Statut | Preuve | Observations |
|---|---:|---|---|
| Auth | OK / KO | Capture / commande / test | |
| Rôles | OK / KO | | |
| Panier | OK / KO | | |
| Commandes | OK / KO | | |
| Paiement | OK / KO | | |
| Restaurant | OK / KO | | |
| Driver | OK / KO | | |
| Livraison | OK / KO | | |
| Tracking | OK / KO | | |
| Admin | OK / KO | | |
| Dashboard | OK / KO | | |
| Notifications | OK / KO | | |
| Sécurité | OK / KO | | |
| Performance | OK / KO | | |
| Backup | OK / KO | | |

## 3. Anomalies bloquantes
| ID | Module | Problème | Gravité | Correction attendue |
|---|---|---|---|---|

## 4. Anomalies non bloquantes
| ID | Module | Problème | Correction future |
|---|---|---|---|

## 5. Décision
- Production autorisée : Oui / Non
- Conditions :
- Signature technique :
```

## Prompt directif pour Cursor

```text
Tu vas réaliser un audit avant production du projet BantuDelice Laravel.

Règles strictes :
1. Ne modifie aucun fichier sans diagnostic préalable.
2. Commence par scanner l’existant : routes, controllers, models, migrations, middlewares, policies, views, jobs, events, listeners, config, tests.
3. Ne suppose rien. Chaque affirmation doit être prouvée par un fichier, une commande, une route, une requête ou un test.
4. Identifie les modules critiques : auth, rôles, profils, restaurants, catalogue, panier, commandes, paiement, livraison, drivers, tracking GPS, notifications, admin, dashboards, revenus, logs, sécurité, performance, backup.
5. Pour chaque module, indique :
   - fichiers concernés ;
   - routes concernées ;
   - contrôleurs concernés ;
   - modèles concernés ;
   - risques détectés ;
   - preuves ;
   - statut OK / KO ;
   - correction proposée.
6. Ne déclare jamais “prêt pour production” tant que les tests critiques ne sont pas passés.
7. Si une erreur est détectée, classe-la :
   - CRITIQUE : bloque production ;
   - ÉLEVÉE : corriger avant production si liée aux données, paiement, sécurité ou commande ;
   - MOYENNE : corriger avant ou juste après selon impact ;
   - FAIBLE : amélioration.
8. Génère un rapport Markdown final nommé :
   docs/audit-production-bantudelice.md

Commandes minimales à exécuter ou documenter :
- php artisan route:list
- php artisan migrate:status
- php artisan test
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache
- composer install --no-dev --optimize-autoloader

Objectif :
Produire un diagnostic fiable, prouvé, exploitable, sans approximation.
```

La priorité absolue : **commandes, paiement, permissions, panier, restaurants, drivers, livraison, tracking, dashboard admin, sauvegarde**.
Si ces modules ne sont pas maîtrisés, la mise en production ne tient pas.
