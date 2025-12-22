# 🚀 Guide pour Ajouter vos API - BantuDelice

## ✅ Ce qui est Prêt

Tous les services sont créés et fonctionnent en **mode démo**. Vous pouvez maintenant ajouter vos clés API réelles.

## 📍 Étape 1: Accéder à la Page de Configuration

1. Connectez-vous en tant qu'administrateur
2. Allez sur: `/admin/api-configuration`
3. Vous verrez le statut de chaque service

## 🔑 Étape 2: Ajouter les Clés API

### Option A: Via l'Interface (Recommandé pour tester)

Utilisez la page d'administration pour tester chaque service sans modifier le `.env` immédiatement.

### Option B: Modifier le fichier `.env`

Éditez le fichier `.env` à la racine du projet et ajoutez vos clés:

```env
# ============================================
# GÉOLOCALISATION
# ============================================
GOOGLE_MAPS_ENABLED=true
GOOGLE_MAPS_API_KEY=votre_cle_google_maps_ici

# Note: OpenStreetMap fonctionne déjà gratuitement si vous ne configurez pas Google Maps

# ============================================
# SMS - TWILIO
# ============================================
TWILIO_ENABLED=true
TWILIO_SID=ACxxxxxxxxxxxxx
TWILIO_TOKEN=votre_token_secret
TWILIO_FROM=+242064000000
TWILIO_VERIFY_SID=VAxxxxxxxxxxxxx

# ============================================
# SMS - AFRICA'S TALKING (Alternative)
# ============================================
AFRICASTALKING_ENABLED=false
AFRICASTALKING_USERNAME=votre_username
AFRICASTALKING_API_KEY=votre_api_key
AFRICASTALKING_FROM=BantuDelice

# ============================================
# MOBILE MONEY - MTN MoMo
# ============================================
MTN_MOMO_ENABLED=true
MTN_MOMO_API_KEY=votre_api_key
MTN_MOMO_API_USER=votre_api_user
MTN_MOMO_API_SECRET=votre_api_secret
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key
MTN_MOMO_ENVIRONMENT=sandbox
MTN_MOMO_CALLBACK_URL=https://dev.bantudelice.cg/api/payments/callback/mtn_momo

# ============================================
# MOBILE MONEY - AIRTEL MONEY
# ============================================
AIRTEL_MONEY_ENABLED=false
AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_ENVIRONMENT=sandbox
AIRTEL_MONEY_CALLBACK_URL=https://dev.bantudelice.cg/api/payments/callback/airtel_money

# ============================================
# AUTHENTIFICATION SOCIALE - GOOGLE
# ============================================
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=votre_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI=/auth/google/callback

# ============================================
# AUTHENTIFICATION SOCIALE - FACEBOOK
# ============================================
FACEBOOK_AUTH_ENABLED=false
FACEBOOK_CLIENT_ID=votre_app_id
FACEBOOK_CLIENT_SECRET=votre_app_secret
FACEBOOK_REDIRECT_URI=/auth/facebook/callback
```

## 🔄 Étape 3: Vider le Cache

Après avoir modifié le `.env`, exécutez:

```bash
php artisan config:clear
php artisan cache:clear
```

Ou utilisez le bouton "Vider le cache" dans la page d'administration.

## 🧪 Étape 4: Tester les Services

Sur la page `/admin/api-configuration`, testez chaque service:

### 1. Géolocalisation
- ✅ Fonctionne déjà avec OpenStreetMap (gratuit)
- Ajoutez Google Maps pour plus de précision (optionnel)

### 2. SMS
- Testez l'envoi de SMS
- Testez l'envoi d'OTP
- En mode démo, vous verrez le code OTP généré

### 3. Mobile Money
- Testez MTN MoMo
- Testez Airtel Money
- En mode démo, les paiements sont simulés

### 4. Social Auth
- Vérifiez le statut de configuration
- Suivez les liens pour créer les applications

## 📚 Documentation Complète

- **Guide détaillé**: `docs/GUIDE_CONFIGURATION_API.md`
- **Configuration**: `config/external-services.php`
- **Résumé des services**: `RESUME_API_EXTERNES.md`

## 🆘 Dépannage

### Les tests échouent?
1. Vérifiez que vous avez vidé le cache
2. Vérifiez que les clés API sont correctes dans `.env`
3. Vérifiez les logs: `storage/logs/laravel.log`

### Mode démo toujours actif?
- Les services fonctionnent en mode démo si les clés ne sont pas configurées
- C'est normal et sécurisé pour le développement

## ✨ Services Actuellement Fonctionnels

- ✅ **Géolocalisation**: OpenStreetMap (gratuit, actif maintenant)
- 🔄 **SMS**: Mode démo (ajoutez Twilio ou Africa's Talking)
- 🔄 **Mobile Money**: Mode démo (ajoutez MTN/Airtel)
- 🔄 **Social Auth**: Prêt à configurer (ajoutez Google/Facebook)

---

**Prêt à commencer?** Allez sur `/admin/api-configuration` ! 🚀

