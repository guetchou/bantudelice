# RQC - INVENTAIRE SÉCURITÉ BANTUDELICE

**Date**: 2025-12-15  
**Statut**: ⚠️ **NON CONFORME**

---

## 1. AUTHENTIFICATION

### 1.1 Guards configurés

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'token',  // ⚠️ DÉPRÉCIÉ
        'provider' => 'users',
        'hash' => false,
    ],
]
```

**❌ PROBLÈME**: Guard `api` utilise `token` (déprécié depuis Laravel 7) au lieu de `sanctum` ou `passport`.

**Impact**: 
- Obsolescence future
- Pas de support pour tokens révocables
- Pas de gestion de scopes

**Recommandation**: Migrer vers `sanctum` ou `passport`.

### 1.2 Packages d'authentification

**Installés**:
- `laravel/passport` v11.9.1 ✅
- `laravel/sanctum` v3.3.1 ✅

**Utilisation**:
- Passport: Routes OAuth présentes (17 routes)
- Sanctum: Utilisé partiellement (5 routes API seulement)

**⚠️ INCOHÉRENCE**: Deux systèmes d'auth installés mais utilisation partielle.

---

## 2. MIDDLEWARES

### 2.1 Middlewares personnalisés

#### AdminMiddleware
```php
// app/Http/Middleware/AdminMiddleware.php
public function handle($request, Closure $next)
{
    if (auth()->check() and auth()->user()->type === 'admin')
        return $next($request);
    
    return redirect()->back();
}
```

**⚠️ RISQUES**:
- Pas de gestion si `auth()->user()` est null (improbable mais possible)
- Redirection `back()` peut créer une boucle si utilisateur non authentifié
- Pas de message d'erreur explicite

**Recommandation**: Ajouter gestion d'exception et message d'erreur.

#### RestaurantMiddleware
```php
// app/Http/Middleware/RestaurantMiddleware.php
public function handle($request, Closure $next)
{
    if (!auth()->check()) {
        return redirect()->route('login')->with('error', 'Veuillez vous connecter...');
    }
    
    if (auth()->user()->type === 'restaurant') {
        return $next($request);
    }
    
    return redirect('/')->with('error', 'Accès refusé...');
}
```

**✅ BON**: Vérification `auth()->check()` avant accès à `user()`.

#### DeliveryMiddleware
```php
// app/Http/Middleware/DeliveryMiddleware.php
public function handle($request, Closure $next)
{
    if (auth()->check() and auth()->user()->restaurant()->first()->services === 'delivery')
        return $next($request);
    
    return redirect()->back();
}
```

**❌ CRITIQUE**: 
- `restaurant()` peut retourner null
- `first()` peut retourner null → **erreur fatale**
- Pas de gestion d'exception

**Exemple d'erreur possible**:
```
Error: Call to a member function services() on null
```

**Priorité**: HAUTE - Correction immédiate requise.

**Fix recommandé**:
```php
if (auth()->check()) {
    $restaurant = auth()->user()->restaurant()->first();
    if ($restaurant && $restaurant->services === 'delivery') {
        return $next($request);
    }
}
return redirect()->back()->with('error', 'Accès refusé');
```

### 2.2 CSRF Protection

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = []; // Vide
```

**✅ BON**: CSRF activé par défaut pour routes web.

**⚠️ À VÉRIFIER**: Routes API doivent être exemptées si utilisées par mobile/app externe (mais avec authentification token).

---

## 3. POLICIES & GATES

### 3.1 État actuel

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    // 'App\Model' => 'App\Policies\ModelPolicy',
];
```

**❌ AUCUNE POLICY DÉTECTÉE**

**Recherche effectuée**:
- `find app -name "*Policy.php"` → 0 résultat
- `grep -r "Gate::" app` → 0 résultat
- `grep -r "authorize(" app` → 0 résultat

**Impact**: 
- Pas de contrôle d'accès granulaire (RBAC)
- Vérifications d'autorisation faites manuellement dans contrôleurs
- Risque d'incohérence et d'oubli

**Recommandation**: Implémenter Policies pour:
1. `OrderPolicy` (user peut voir/modifier ses propres commandes)
2. `RestaurantPolicy` (restaurant peut modifier ses propres données)
3. `DriverPolicy` (driver peut voir ses propres livraisons)

---

## 4. VALIDATION & SANITIZATION

### 4.1 Validation des inputs

**Statistiques**:
- 53 contrôleurs utilisent `Request` ou `validate()`
- 324 occurrences de `validate`, `Request`, `rules` dans les contrôleurs

**✅ BON**: Validation présente dans la majorité des contrôleurs.

**⚠️ À VÉRIFIER**: 
- Cohérence des règles de validation
- Sanitization des inputs (XSS)
- Protection contre injection SQL (Eloquent devrait protéger)

### 4.2 Exemple de validation

```php
// Exemple typique (à vérifier dans chaque contrôleur)
$request->validate([
    'email' => 'required|email',
    'password' => 'required|min:8',
]);
```

**Recommandation**: Centraliser les règles dans FormRequest classes.

---

## 5. SÉCURITÉ DES ROUTES

### 5.1 Routes API non protégées

**Problème**: 61% des routes API sont publiques (~90/120 routes).

**Routes critiques non protégées**:
- `POST /api/place_orders/` - Création de commande
- `POST /api/add_to_cart` - Modification panier
- `GET /api/user_profile/{user}` - Profil utilisateur (ID exposé)
- `POST /api/update_profile/` - Modification profil
- `POST /api/complete_orders` - Finalisation commande

**Risque**: Manipulation de commandes, accès non autorisé aux données.

### 5.2 Exposition d'IDs utilisateurs

**Problème**: Routes utilisant `{user}` ou `{driver}` dans l'URL sans vérification.

**Exemples**:
- `GET /api/user_profile/{user}`
- `GET /api/show_cart_details/{user}`
- `GET /api/driver_profile/{driver}`

**Risque**: Accès aux données d'autres utilisateurs si ID deviné.

**Recommandation**: 
1. Protéger avec `auth:sanctum`
2. Vérifier que `$user->id === auth()->id()` dans le contrôleur

### 5.3 Callback Payment

```php
// routes/api.php:118
Route::post('payments/callback/{provider}', 'PaymentCallbackController@handle')
    ->name('api.payments.callback');
```

**⚠️ RISQUE**: Endpoint sensible sans authentification.

**Recommandation**: 
- Protéger par IP whitelist (middleware custom)
- Ou vérifier signature HMAC du provider
- Ajouter rate limiting

---

## 6. SESSIONS & COOKIES

### 6.1 Configuration session

```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'file'),
'lifetime' => env('SESSION_LIFETIME', 120), // 2 heures
'secure' => env('SESSION_SECURE_COOKIE', false),
```

**⚠️ RISQUES**:
- `SESSION_SECURE_COOKIE` par défaut à `false` → cookies non sécurisés en HTTPS
- Lifetime de 2h peut être trop long pour certaines actions sensibles

**Recommandation**: 
- Activer `SESSION_SECURE_COOKIE=true` en production
- Réduire lifetime pour actions sensibles (paiement)

### 6.2 Cookies

**Configuration**: Via `EncryptCookies` middleware (activé par défaut).

**✅ BON**: Cookies chiffrés.

---

## 7. SECRETS & CONFIGURATION

### 7.1 Variables d'environnement

**Fichier**: `.env` (filtré, non accessible)

**⚠️ RISQUE**: Impossible de vérifier si secrets sont committés.

**Recommandation**: 
- Vérifier `.gitignore` contient `.env`
- Créer `.env.example` avec structure (sans valeurs)

### 7.2 Services externes

**Configuration**: `config/external-services.php`

**✅ BON**: Configuration centralisée via variables d'environnement.

**Services configurés**:
- Paiements: MTN MoMo, Airtel Money, Stripe, PayPal
- Notifications: FCM, Twilio, Africa's Talking, SMS Local, BulkGate
- Géolocalisation: Google Maps, OpenStreetMap
- Auth Sociale: Google, Facebook, Apple
- Stockage: Cloudinary, AWS S3

**⚠️ À VÉRIFIER**: Secrets stockés dans `.env` (non accessible).

---

## 8. PROTECTION CONTRE INJECTIONS

### 8.1 SQL Injection

**Protection**: Eloquent ORM (paramètres liés par défaut).

**✅ BON**: Risque faible si utilisation correcte d'Eloquent.

**⚠️ À VÉRIFIER**: 
- Pas d'utilisation de `DB::raw()` non sécurisé
- Pas de concaténation de requêtes SQL

### 8.2 XSS (Cross-Site Scripting)

**Protection**: Blade échappe automatiquement `{{ }}`.

**⚠️ À VÉRIFIER**: 
- Utilisation de `{!! !!}` (non échappé) uniquement si nécessaire et sécurisé
- Sanitization des inputs utilisateur

### 8.3 CSRF

**Protection**: `VerifyCsrfToken` middleware activé pour routes web.

**✅ BON**: CSRF activé.

---

## 9. RATE LIMITING

### 9.1 Configuration

```php
// app/Http/Kernel.php:45
'api' => [
    'throttle:60,1',  // 60 requêtes par minute
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**✅ BON**: Rate limiting activé pour routes API (60 req/min).

**⚠️ À VÉRIFIER**: 
- Limite adaptée pour endpoints sensibles (login, payment)
- Rate limiting par IP ou par utilisateur authentifié

---

## 10. LOGGING & MONITORING

### 10.1 Logs d'authentification

**⚠️ NON VÉRIFIÉ**: 
- Logs des tentatives de connexion échouées
- Logs des accès aux routes sensibles
- Alertes sur activités suspectes

**Recommandation**: Implémenter logging des actions critiques.

### 10.2 Monitoring

**⚠️ NON VÉRIFIÉ**: 
- Monitoring des erreurs
- Alertes sur anomalies
- Dashboard de sécurité

---

## 11. ANOMALIES CRITIQUES

### 11.1 Blocages immédiats

#### ❌ **BLOCAGE 1**: Extension PHP manquante
```
Error: Class "DOMDocument" not found
```
**Impact**: `php artisan config:show` échoue  
**Priorité**: HAUTE

#### ❌ **BLOCAGE 2**: Driver MySQL non disponible
```
could not find driver (Connection: mysql)
```
**Impact**: Impossible d'exécuter migrations ou requêtes DB  
**Priorité**: CRITIQUE

### 11.2 Risques sécurité

#### 🔴 **RISQUE 1**: DeliveryMiddleware vulnérable
- **Score**: 9/10
- **Probabilité**: Moyenne
- **Impact**: Élevé (erreur fatale possible)

#### 🔴 **RISQUE 2**: Routes API non protégées
- **Score**: 9/10
- **Probabilité**: Élevée
- **Impact**: Élevé

#### 🟡 **RISQUE 3**: Pas de Policies/Gates
- **Score**: 6/10
- **Probabilité**: Moyenne
- **Impact**: Moyen

#### 🟡 **RISQUE 4**: Guard API déprécié
- **Score**: 5/10
- **Probabilité**: Faible
- **Impact**: Moyen

#### 🟡 **RISQUE 5**: IDs utilisateurs exposés
- **Score**: 7/10
- **Probabilité**: Moyenne
- **Impact**: Élevé

---

## 12. RECOMMANDATIONS

### 12.1 Actions critiques (P0)
1. ✅ Installer extensions PHP manquantes (`php-xml`, `php-mysql`)
2. ✅ Corriger `DeliveryMiddleware` (gestion null)
3. ✅ Protéger routes API sensibles avec `auth:sanctum`
4. ✅ Vérifier autorisation sur routes avec `{user}` ou `{driver}`

### 12.2 Actions importantes (P1)
1. Migrer guard `api` de `token` vers `sanctum`
2. Implémenter Policies pour ressources critiques
3. Protéger callback payment par IP whitelist
4. Activer `SESSION_SECURE_COOKIE` en production

### 12.3 Actions recommandées (P2)
1. Implémenter logging des actions sensibles
2. Ajouter rate limiting différencié par endpoint
3. Créer dashboard de monitoring sécurité
4. Documenter procédures de réponse aux incidents

---

## 13. CRITÈRES D'ACCEPTATION

Le projet sera considéré "CONFORME" uniquement si :

- [ ] Toutes les extensions PHP requises sont installées
- [ ] DeliveryMiddleware gère les cas null
- [ ] Toutes les routes API sensibles sont protégées
- [ ] Vérification d'autorisation sur routes avec IDs utilisateurs
- [ ] Au moins 3 Policies critiques sont implémentées
- [ ] Guard API migré vers `sanctum`
- [ ] Callback payment protégé
- [ ] `SESSION_SECURE_COOKIE` activé en production

---

## 14. STATUT FINAL

**STATUT**: ⚠️ **NON CONFORME**

**Raisons**:
- Blocages techniques (extensions PHP)
- Risques sécurité critiques non résolus
- Absence de Policies/Gates
- Routes API non protégées

**Prochaines étapes**: Voir `RQC_PLAN_REMEDIATION.md`

