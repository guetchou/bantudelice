# 🔧 Guide de Configuration des API Externes

## 📍 Étape 1: Ajouter les Clés API dans `.env`

Ouvrez le fichier `.env` et ajoutez vos clés API :

### Géolocalisation (Optionnel - OpenStreetMap fonctionne déjà)
```env
GOOGLE_MAPS_ENABLED=true
GOOGLE_MAPS_API_KEY=votre_cle_google_maps
```

### SMS - Twilio (Recommandé)
```env
TWILIO_ENABLED=true
TWILIO_SID=ACxxxxxxxxxxxxx
TWILIO_TOKEN=votre_token_twilio
TWILIO_FROM=+242064000000
TWILIO_VERIFY_SID=VAxxxxxxxxxxxxx
```

### SMS - Africa's Talking (Alternative)
```env
AFRICASTALKING_ENABLED=true
AFRICASTALKING_USERNAME=votre_username
AFRICASTALKING_API_KEY=votre_api_key
AFRICASTALKING_FROM=BantuDelice
```

### Mobile Money - MTN MoMo
```env
MTN_MOMO_ENABLED=true
MTN_MOMO_API_KEY=votre_api_key
MTN_MOMO_API_USER=votre_api_user
MTN_MOMO_API_SECRET=votre_api_secret
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key
MTN_MOMO_ENVIRONMENT=sandbox
MTN_MOMO_CALLBACK_URL=https://dev.bantudelice.cg/api/payments/callback/mtn_momo
```

### Mobile Money - Airtel Money
```env
AIRTEL_MONEY_ENABLED=true
AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_ENVIRONMENT=sandbox
AIRTEL_MONEY_CALLBACK_URL=https://dev.bantudelice.cg/api/payments/callback/airtel_money
```

### Authentification Sociale - Google
```env
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=votre_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI=/auth/google/callback
```

### Authentification Sociale - Facebook
```env
FACEBOOK_AUTH_ENABLED=true
FACEBOOK_CLIENT_ID=votre_app_id
FACEBOOK_CLIENT_SECRET=votre_app_secret
FACEBOOK_REDIRECT_URI=/auth/facebook/callback
```

## 🔍 Étape 2: Vider le Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## 🧪 Étape 3: Tester les Services

Utilisez les commandes Artisan ou la page d'administration pour tester chaque service.

---

## 📖 Documentation Complète

Consultez `config/external-services.php` pour voir toutes les options disponibles.

