# Configuration des API Externes - BantuDelice

Ce document décrit toutes les API externes disponibles et comment les configurer.

## 📱 Mobile Money

### MTN MoMo
Configuration pour les paiements MTN Mobile Money.

```env
MTN_MOMO_ENABLED=true
MTN_MOMO_API_KEY=votre_api_key
MTN_MOMO_API_USER=votre_api_user
MTN_MOMO_API_SECRET=votre_api_secret
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key
MTN_MOMO_ENVIRONMENT=sandbox  # ou production
MTN_MOMO_CALLBACK_URL=https://votresite.com/api/payments/callback/mtn_momo
```

**Obtenir les clés :** [MTN MoMo Developer Portal](https://momodeveloper.mtn.com)

### Airtel Money
Configuration pour les paiements Airtel Money.

```env
AIRTEL_MONEY_ENABLED=true
AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_ENVIRONMENT=sandbox  # ou production
AIRTEL_MONEY_CALLBACK_URL=https://votresite.com/api/payments/callback/airtel_money
```

**Obtenir les clés :** [Airtel Developer Portal](https://developers.airtel.africa)

---

## 💳 Paiements par Carte

### Stripe
```env
STRIPE_ENABLED=true
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

**Obtenir les clés :** [Stripe Dashboard](https://dashboard.stripe.com/apikeys)

### PayPal
```env
PAYPAL_ENABLED=true
PAYPAL_CLIENT_ID=xxx
PAYPAL_SECRET=xxx
PAYPAL_MODE=sandbox  # ou live
```

**Obtenir les clés :** [PayPal Developer](https://developer.paypal.com)

---

## 📲 SMS & OTP

### Twilio (Recommandé)
```env
TWILIO_ENABLED=true
TWILIO_SID=ACxxxxx
TWILIO_TOKEN=xxxxx
TWILIO_FROM=+1234567890
TWILIO_VERIFY_SID=VAxxx  # Pour les OTP
```

**Obtenir les clés :** [Twilio Console](https://console.twilio.com)

### Africa's Talking (Afrique)
```env
AFRICASTALKING_ENABLED=true
AFRICASTALKING_USERNAME=votre_username
AFRICASTALKING_API_KEY=votre_api_key
AFRICASTALKING_FROM=BantuDelice
```

**Obtenir les clés :** [Africa's Talking](https://africastalking.com)

### Provider Local Congo
```env
SMS_LOCAL_ENABLED=true
SMS_LOCAL_API_KEY=xxx
SMS_LOCAL_SENDER_ID=BANTUDELICE
SMS_LOCAL_API_URL=https://api.provider-local.cg
```

---

## 🔔 Notifications Push

### Firebase Cloud Messaging (FCM)
```env
FCM_ENABLED=true
FCM_SERVER_KEY=AAAAxxx
FCM_SENDER_ID=123456789
FCM_PROJECT_ID=mon-projet-firebase
```

**Configuration :**
1. Créer un projet sur [Firebase Console](https://console.firebase.google.com)
2. Aller dans Paramètres > Cloud Messaging
3. Copier la "Server key" (clé serveur)

---

## 🗺️ Géolocalisation

### Google Maps API
```env
GOOGLE_MAPS_ENABLED=true
GOOGLE_MAPS_API_KEY=AIzaSyxxx
```

**APIs à activer :**
- Maps JavaScript API
- Geocoding API
- Directions API
- Distance Matrix API

**Obtenir les clés :** [Google Cloud Console](https://console.cloud.google.com/google/maps-apis)

### OpenStreetMap (Gratuit)
```env
OSM_ENABLED=true
OSRM_URL=https://router.project-osrm.org
```

Aucune clé API requise. Limité en nombre de requêtes.

---

## 🔐 Authentification Sociale

### Google Sign-In
```env
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=/auth/google/callback
```

**Configuration :**
1. [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Créer "OAuth 2.0 Client ID"
3. Ajouter les URIs de redirection autorisés

### Facebook Login
```env
FACEBOOK_AUTH_ENABLED=true
FACEBOOK_CLIENT_ID=xxx
FACEBOOK_CLIENT_SECRET=xxx
FACEBOOK_REDIRECT_URI=/auth/facebook/callback
```

**Configuration :**
1. [Facebook Developers](https://developers.facebook.com)
2. Créer une application
3. Configurer "Facebook Login"

### Apple Sign-In
```env
APPLE_AUTH_ENABLED=true
APPLE_CLIENT_ID=xxx
APPLE_CLIENT_SECRET=xxx
APPLE_REDIRECT_URI=/auth/apple/callback
```

---

## 📧 Email

### SendGrid
```env
SENDGRID_ENABLED=true
SENDGRID_API_KEY=SG.xxx
```

### Mailgun
```env
MAILGUN_ENABLED=true
MAILGUN_DOMAIN=mg.votredomaine.com
MAILGUN_SECRET=xxx
```

---

## 📊 Analytics

### Google Analytics
```env
GA_ENABLED=true
GA_TRACKING_ID=UA-xxx  # Universal Analytics
GA_MEASUREMENT_ID=G-xxx  # GA4
```

### Mixpanel
```env
MIXPANEL_ENABLED=true
MIXPANEL_TOKEN=xxx
```

---

## ☁️ Stockage Cloud

### Cloudinary (Images)
```env
CLOUDINARY_ENABLED=true
CLOUDINARY_CLOUD_NAME=xxx
CLOUDINARY_API_KEY=xxx
CLOUDINARY_API_SECRET=xxx
```

### AWS S3
```env
AWS_S3_ENABLED=true
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=bantudelice
```

---

## 🛠️ Utilisation des Services

### Exemple: Envoyer un SMS OTP
```php
use App\Services\SmsService;

// Envoyer un OTP
$result = SmsService::sendOtp('+242064000000', 'login');

// Vérifier un OTP
$verified = SmsService::verifyOtp('+242064000000', '123456', 'login');
```

### Exemple: Paiement Mobile Money
```php
use App\Services\MobileMoneyService;

// Initier un paiement
$result = MobileMoneyService::initiatePayment($payment, '+242064000000', 'mtn');

// Vérifier le statut
$status = MobileMoneyService::checkPaymentStatus('mtn_momo', $reference);
```

### Exemple: Calculer une distance
```php
use App\Services\GeolocationService;

// Distance entre deux points
$km = GeolocationService::calculateDistance(-4.2634, 15.2429, -4.3000, 15.3000);

// Temps de trajet
$route = GeolocationService::calculateRoute(-4.2634, 15.2429, -4.3000, 15.3000);
```

### Exemple: Auth sociale
```php
use App\Services\SocialAuthService;

// Obtenir l'URL Google
$url = SocialAuthService::getGoogleAuthUrl();

// Traiter le callback
$result = SocialAuthService::handleGoogleCallback($code);
$user = $result['user'];
$token = $result['token'];
```

---

## 🔒 Mode Démo

Tous les services fonctionnent en **mode démo** si les clés API ne sont pas configurées:
- Les SMS ne sont pas réellement envoyés (logs uniquement)
- Les paiements Mobile Money sont simulés
- Les OTP sont générés et affichés dans les logs

Pour activer les services réels, configurez les clés API correspondantes dans `.env`.

---

## 📞 Support

Pour toute question sur la configuration des API:
- Email: dev@bantudelice.cg
- Documentation: https://dev.bantudelice.cg/docs

