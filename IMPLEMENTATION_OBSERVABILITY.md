# ✅ Implémentation : Observabilité (Sprint 4)

**Date :** 2025-12-17  
**Sprint :** 4/6 - Devenir Leader  
**Statut :** ✅ TERMINÉ

---

## 📋 Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **`app/Services/MetricsService.php`** (NOUVEAU)
   - Service de calcul des métriques et KPIs
   - Méthodes : `getRealtimeMetrics()`, `getHistoricalMetrics()`, `generateDailyMetrics()`
   - Cache pour performance (1 min pour temps réel, 1h pour historique)

2. **`app/Services/AlertingService.php`** (NOUVEAU)
   - Service d'alerting (email/SMS)
   - Logs structurés JSON
   - Envoi email pour alertes critiques

3. **`app/Http/Controllers/admin/MetricsController.php`** (NOUVEAU)
   - Controller pour dashboard métriques
   - Endpoints : `index()`, `realtime()`, `historical()`

4. **`app/Console/Commands/GenerateDailyMetrics.php`** (NOUVEAU)
   - Commande Artisan pour générer métriques quotidiennes
   - Usage: `php artisan metrics:generate-daily [--date=2025-12-17]`

5. **`database/migrations/2025_12_17_193110_create_daily_metrics_table.php`** (NOUVEAU)
   - Table pour stocker métriques quotidiennes
   - Colonnes : `date`, `orders_count`, `revenue`, `avg_delivery_time`, etc.

6. **`resources/views/admin/metrics/dashboard.blade.php`** (NOUVEAU)
   - Vue dashboard métriques admin
   - Affichage KPIs temps réel + historique

### Fichiers Modifiés

1. **`routes/web.php`** (MODIFIÉ)
   - Route ajoutée : `GET /admin/metrics` → `MetricsController@index`

2. **`routes/api.php`** (MODIFIÉ)
   - Routes API ajoutées :
     - `GET /api/admin/metrics/realtime`
     - `GET /api/admin/metrics/historical?days=30`

3. **`app/Console/Kernel.php`** (MODIFIÉ)
   - Scheduler ajouté :
     - Génération métriques quotidiennes : chaque jour à 1h
     - Vérification alertes : toutes les 5 minutes

---

## 🔄 Workflow Complet

### 1. Calcul Métriques Temps Réel

```
Requête GET /admin/metrics
  ↓
MetricsController@index()
  ↓
MetricsService::getRealtimeMetrics()
  ↓
Cache (1 min) ou calcul :
  - Commandes (aujourd'hui, hier, 7j, 30j, pending, in_progress)
  - Revenus (aujourd'hui, hier, 7j, 30j)
  - Livraisons (pending, assigned, in_progress, avg_time)
  - Paiements (pending, paid, failed, success_rate)
  - Utilisateurs (total, nouveaux, actifs)
  - Restaurants (total, actifs)
  - Alertes (commandes >30min, livraisons >10min, paiements échoués)
  ↓
Affichage dashboard
```

### 2. Génération Métriques Quotidiennes (scheduler)

```
Chaque jour à 1h
  ↓
php artisan metrics:generate-daily
  ↓
MetricsService::generateDailyMetrics(yesterday)
  ↓
Calcul métriques pour hier
  ↓
Enregistrement dans daily_metrics
```

### 3. Vérification Alertes (scheduler)

```
Toutes les 5 minutes
  ↓
AlertingService::checkAndSendAlerts()
  ↓
MetricsService::getRealtimeMetrics()['alerts']
  ↓
Pour chaque alerte :
  - Logger (logs structurés JSON)
  - Envoyer email si critique
  ↓
Alertes envoyées
```

---

## 🧪 Tests Manuels

### Test 1 : Dashboard métriques

```bash
# Accéder au dashboard (nécessite auth admin)
# https://bantudelice.cg/admin/metrics
```

**Vérifier :**
- KPIs affichés (commandes, revenus, livraisons, paiements)
- Alertes affichées si présentes
- Données temps réel

### Test 2 : API métriques temps réel

```bash
# Récupérer un token (si auth:sanctum)
# Puis :
curl -X GET "https://bantudelice.cg/api/admin/metrics/realtime" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse attendue :**
```json
{
  "status": true,
  "data": {
    "orders": {
      "today": 15,
      "yesterday": 12,
      "pending": 3,
      "in_progress": 5,
      "completed_today": 7
    },
    "revenue": {
      "today": 125000,
      "yesterday": 98000
    },
    "deliveries": {
      "pending": 2,
      "avg_delivery_time": 45.5
    },
    "alerts": [...]
  },
  "timestamp": "2025-12-17T19:30:00+00:00"
}
```

### Test 3 : Génération métriques quotidiennes

```bash
# Générer pour hier
php artisan metrics:generate-daily

# Générer pour une date spécifique
php artisan metrics:generate-daily --date=2025-12-16
```

**Résultat attendu :**
```
✅ Métriques générées avec succès:
  - Commandes: 15
  - Revenus: 125 000 FCFA
  - Temps moyen livraison: 45 min
  - Taux succès paiement: 95.5%
```

### Test 4 : Vérifier alertes

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log | grep "ALERT"

# Vérifier les emails (si configuré)
# Les alertes critiques sont envoyées à l'email admin
```

---

## 📊 KPIs Disponibles

### Commandes
- Aujourd'hui / Hier / 7j / 30j
- En attente / En cours / Complétées

### Revenus
- Aujourd'hui / Hier / 7j / 30j
- En FCFA

### Livraisons
- En attente d'assignation
- Assignées / En cours
- Temps moyen de livraison (minutes)

### Paiements
- En attente / Payés / Échoués
- Taux de succès (%)

### Utilisateurs
- Total / Nouveaux aujourd'hui
- Actifs (30 derniers jours)

### Restaurants
- Total / Actifs aujourd'hui

---

## 🚨 Système d'Alertes

### Alertes Détectées Automatiquement

1. **Commandes en attente > 30 minutes**
   - Type: `warning`
   - Sévérité: `medium`

2. **Livraisons en attente > 10 minutes (> 5 livraisons)**
   - Type: `warning`
   - Sévérité: `high`

3. **Paiements échoués > 10 dans la dernière heure**
   - Type: `error`
   - Sévérité: `high`

4. **Taux de succès paiement < 80%**
   - Type: `warning`
   - Sévérité: `medium`

### Envoi d'Alertes

- **Logs structurés** : Toutes les alertes sont loggées en JSON
- **Email** : Alertes `error` ou `warning` avec `severity=high`
- **SMS** : TODO (à implémenter si besoin)

---

## ⚙️ Configuration

### Variables d'environnement

```env
# Email admin pour alertes
CONTACT_EMAIL=admin@bantudelice.cg

# SMS (optionnel, à implémenter)
SMS_ALERTS_ENABLED=false
SMS_API_KEY=...
```

### Scheduler (Cron)

Pour que le scheduler s'exécute automatiquement :

```bash
* * * * * cd /opt/bantudelice242 && php artisan schedule:run >> /dev/null 2>&1
```

### Logs Structurés

Les alertes sont loggées dans `storage/logs/laravel-YYYY-MM-DD.log` au format JSON :

```json
{
  "type": "alert",
  "alert_type": "warning",
  "message": "5 livraison(s) en attente d'assignation",
  "severity": "high",
  "timestamp": "2025-12-17T19:30:00+00:00",
  "context": {
    "count": 5
  }
}
```

---

## 🐛 Dépannage

### Problème : Métriques non affichées

**Vérifier :**
```bash
# Vérifier les logs
tail -f storage/logs/laravel.log | grep "metrics"

# Vérifier le cache
php artisan cache:clear

# Tester le service directement
php artisan tinker
>>> (new \App\Services\MetricsService())->getRealtimeMetrics();
```

### Problème : Alertes non envoyées

**Vérifier :**
```bash
# Vérifier la configuration email
php artisan tinker --execute="echo env('MAIL_MAILER');"

# Vérifier les logs
tail -f storage/logs/laravel.log | grep "ALERT"
```

### Problème : Métriques quotidiennes non générées

**Vérifier :**
```bash
# Vérifier le scheduler
php artisan schedule:list

# Générer manuellement
php artisan metrics:generate-daily
```

---

## ✅ Checklist de Validation

- [x] Service `MetricsService` créé
- [x] Service `AlertingService` créé
- [x] Controller `MetricsController` créé
- [x] Migration `daily_metrics` créée
- [x] Vue dashboard créée
- [x] Routes web et API ajoutées
- [x] Scheduler configuré (métriques quotidiennes + alertes)
- [x] Commande Artisan créée
- [ ] **Test manuel :** Accéder dashboard `/admin/metrics`
- [ ] **Test manuel :** Appeler API `/api/admin/metrics/realtime`
- [ ] **Test manuel :** Générer métriques quotidiennes
- [ ] **Test manuel :** Vérifier alertes (logs + email)

---

## 🎯 Prochaine Étape

**Sprint 5 : Scalabilité**
- Queue jobs pour actions lourdes
- Cache avancé
- Optimisations DB

---

## 📝 Notes Techniques

- **Performance :** Cache 1 min pour temps réel, 1h pour historique
- **Scalabilité :** Métriques quotidiennes stockées en DB pour requêtes rapides
- **Traçabilité :** Logs structurés JSON pour analyse
- **Alerting :** Email automatique pour alertes critiques

