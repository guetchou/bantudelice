# RQC - AUDIT INITIAL BANTUDELICE

**Date**: 2025-12-15  
**Auditeur**: RQC - Reviewer Qualité & Conformité Technique  
**Version Laravel**: 10.26.2  
**PHP**: 8.1.2  
**Statut**: EN COURS / NON VALIDÉ

---

## 1. CONTEXTE PROJET

### 1.1 Informations générales
- **Nom**: BantuDelice (theDrop247)
- **Framework**: Laravel 10.26.2
- **PHP**: 8.1.2-1ubuntu2.22
- **Répertoire**: `/opt/thedrop247`
- **Git**: ❌ **NON INITIALISÉ** (fatal: not a git repository)

### 1.2 Dépendances principales
```
laravel/framework       v10.26.2
laravel/passport        v11.9.1
laravel/sanctum         v3.3.1
laravel/socialite       v5.23.2
guzzlehttp/guzzle       7.8.0
```

**⚠️ RISQUE**: Composer.phar obsolète (>60 jours), exécuté en root.

---

## 2. ARCHITECTURE ROUTES

### 2.1 Inventaire routes
**Total**: 333 routes enregistrées

#### Routes Web (routes/web.php)
- **Publiques**: 87 routes (home, login, signup, cart, checkout, etc.)
- **Admin** (`middleware: ['auth', 'admin']`): 67 routes
- **Restaurant** (`middleware: ['auth', 'restaurant']`): 45 routes
- **Delivery** (`middleware: ['auth', 'delivery']`): 12 routes

#### Routes API (routes/api.php)
- **Publiques**: ~90 routes (register, login, restaurants, products, etc.)
- **Protégées** (`middleware: 'auth:sanctum'`): 5 routes
  - `driver/deliveries`
  - `driver/deliveries/{delivery}/status`
  - `orders/{order}/tracking`
  - `checkout`
  - `payments/{payment}`

### 2.2 Constats critiques

#### ❌ **CRITIQUE 1**: Routes API majoritairement publiques
- **Preuve**: `routes/api.php` lignes 20-121
- **Impact**: Pas de protection par défaut sur endpoints sensibles (orders, cart, payments)
- **Risque**: Manipulation de commandes, accès non autorisé aux données utilisateurs

#### ⚠️ **CRITIQUE 2**: Middleware `auth:sanctum` partiel
- **Preuve**: Seulement 5 routes protégées sur ~90 routes API
- **Impact**: Authentification inconsistante
- **Risque**: Accès non contrôlé aux ressources

#### ⚠️ **CRITIQUE 3**: Callback payment public
- **Preuve**: `routes/api.php:118` - `payments/callback/{provider}` sans middleware
- **Impact**: Endpoint sensible accessible sans authentification
- **Risque**: Manipulation de callbacks de paiement

---

## 3. SÉCURITÉ & AUTHENTIFICATION

### 3.1 Guards configurés
```php
// config/auth.php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'token', 'provider' => 'users', 'hash' => false],
]
```

**⚠️ PROBLÈME**: Guard `api` utilise `token` (déprécié) au lieu de `sanctum` ou `passport`.

### 3.2 Middlewares personnalisés

#### AdminMiddleware
```php
// app/Http/Middleware/AdminMiddleware.php:18
if (auth()->check() and auth()->user()->type === 'admin')
```
**⚠️ RISQUE**: Pas de gestion d'exception si `auth()->user()` est null.

#### RestaurantMiddleware
```php
// app/Http/Middleware/RestaurantMiddleware.php:22
if (auth()->user()->type === 'restaurant')
```
**✅ BON**: Vérification `auth()->check()` avant accès.

#### DeliveryMiddleware
```php
// app/Http/Middleware/DeliveryMiddleware.php:18
if (auth()->check() and auth()->user()->restaurant()->first()->services === 'delivery')
```
**❌ CRITIQUE**: 
- Pas de vérification si `restaurant()` retourne null
- `first()` peut retourner null → erreur fatale
- Pas de gestion d'exception

### 3.3 Policies & Gates
**❌ AUCUNE POLICY DÉTECTÉE**
- `AuthServiceProvider::policies` est vide
- Aucun fichier `*Policy.php` dans `app/`
- Pas d'utilisation de `Gate::` ou `authorize()` dans les contrôleurs

**Impact**: Pas de contrôle d'accès granulaire (RBAC).

### 3.4 CSRF Protection
```php
// app/Http/Middleware/VerifyCsrfToken.php:21
protected $except = []; // Vide
```
**✅ BON**: CSRF activé par défaut pour routes web.

---

## 4. BASE DE DONNÉES

### 4.1 Migrations
**Total**: 32 migrations détectées

#### Tables principales
- `users`, `restaurants`, `drivers`, `orders`, `products`, `carts`
- `payments`, `deliveries`, `ratings`, `loyalty_points`
- `charges`, `vouchers`, `working_hours`

### 4.2 Contraintes & Indexes
**✅ BON**: 
- Foreign keys présentes sur relations critiques
- Indexes sur colonnes uniques (`email`, `phone`, `user_name`)
- `onDelete('cascade')` configuré sur la plupart des FK

**⚠️ À VÉRIFIER**:
- Indexes manquants sur colonnes fréquemment requêtées (`orders.status`, `orders.user_id`, etc.)

### 4.3 État des migrations
**❌ IMPOSSIBLE DE VÉRIFIER**: 
```
could not find driver (Connection: mysql, SQL: select * from information_schema.tables...)
```
**Risque**: Extension PDO MySQL non installée ou configuration DB incorrecte.

---

## 5. VALIDATION & INPUT

### 5.1 Contrôleurs avec validation
**Total**: 53 contrôleurs utilisent `Request` ou `validate()`

**⚠️ À VÉRIFIER**: 
- Cohérence des règles de validation
- Sanitization des inputs
- Protection contre injection SQL (Eloquent devrait protéger)

---

## 6. SERVICES EXTERNES

### 6.1 Configuration (`config/external-services.php`)
- **Paiements**: MTN MoMo, Airtel Money, Stripe, PayPal
- **Notifications**: FCM, Twilio, Africa's Talking, SMS Local, BulkGate
- **Géolocalisation**: Google Maps, OpenStreetMap
- **Auth Sociale**: Google, Facebook, Apple
- **Stockage**: Cloudinary, AWS S3

**✅ BON**: Configuration centralisée via variables d'environnement.

**⚠️ RISQUE**: Secrets dans `.env` (non vérifiable, fichier filtré).

---

## 7. ANOMALIES CRITIQUES DÉTECTÉES

### 7.1 Blocages immédiats

#### ❌ **BLOCAGE 1**: Extension PHP manquante
```
Error: Class "DOMDocument" not found
```
**Impact**: `php artisan config:show` échoue  
**Cause**: Extension `php-xml` non installée  
**Priorité**: HAUTE

#### ❌ **BLOCAGE 2**: Driver MySQL non disponible
```
could not find driver (Connection: mysql)
```
**Impact**: Impossible d'exécuter migrations ou requêtes DB  
**Cause**: Extension `php-mysql` ou `pdo_mysql` non installée  
**Priorité**: CRITIQUE

#### ❌ **BLOCAGE 3**: Git non initialisé
```
fatal: not a git repository
```
**Impact**: Pas de versioning, pas de rollback possible  
**Priorité**: HAUTE

### 7.2 Risques sécurité

#### 🔴 **RISQUE 1**: Routes API non protégées
- **Score**: 9/10
- **Probabilité**: Élevée
- **Impact**: Élevé

#### 🔴 **RISQUE 2**: DeliveryMiddleware vulnérable
- **Score**: 8/10
- **Probabilité**: Moyenne
- **Impact**: Élevé (erreur fatale possible)

#### 🟡 **RISQUE 3**: Pas de Policies/Gates
- **Score**: 6/10
- **Probabilité**: Moyenne
- **Impact**: Moyen (contrôle d'accès basique uniquement)

#### 🟡 **RISQUE 4**: Guard API déprécié
- **Score**: 5/10
- **Probabilité**: Faible
- **Impact**: Moyen (obsolescence future)

---

## 8. RECOMMANDATIONS IMMÉDIATES

### 8.1 Actions critiques (P0)
1. ✅ Installer `php-xml` et `php-mysql` / `pdo_mysql`
2. ✅ Initialiser Git et créer commit initial
3. ✅ Corriger `DeliveryMiddleware` (gestion null)
4. ✅ Protéger routes API sensibles avec `auth:sanctum`

### 8.2 Actions importantes (P1)
1. Migrer guard `api` de `token` vers `sanctum`
2. Implémenter Policies pour ressources critiques
3. Ajouter validation stricte sur endpoints payment
4. Documenter secrets dans `.env.example` (sans valeurs)

### 8.3 Actions recommandées (P2)
1. Ajouter indexes manquants sur tables critiques
2. Implémenter rate limiting sur endpoints publics
3. Ajouter logging des actions sensibles
4. Créer tests unitaires pour middlewares

---

## 9. CRITÈRES D'ACCEPTATION

Le projet sera considéré "VALIDÉ" uniquement si :

- [ ] Toutes les extensions PHP requises sont installées
- [ ] Git est initialisé avec commit initial
- [ ] Toutes les routes API sensibles sont protégées
- [ ] DeliveryMiddleware gère les cas null
- [ ] Au moins 3 Policies critiques sont implémentées
- [ ] Tests de non-régression passent
- [ ] Documentation des secrets est à jour

---

## 10. STATUT FINAL

**STATUT**: ⚠️ **EN COURS / NON VALIDÉ**

**Raisons**:
- Blocages techniques (extensions PHP, driver MySQL)
- Risques sécurité non résolus
- Absence de versioning (Git)

**Prochaines étapes**: Voir `RQC_PLAN_REMEDIATION.md`

