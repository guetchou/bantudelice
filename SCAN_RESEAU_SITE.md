# SCAN RÉSEAU DU SITE
## Analyse Complète de la Configuration Réseau - BantuDelice/TheDrop247

**Date d'analyse :** 2025-01-27  
**Environnement :** Production  
**Type :** Plateforme de livraison de nourriture (Laravel)

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'Ensemble Réseau](#vue-densemble-réseau)
2. [Ports Exposés](#ports-exposés)
3. [Configuration Serveur Web](#configuration-serveur-web)
4. [Domaines et DNS](#domaines-et-dns)
5. [Endpoints API](#endpoints-api)
6. [Services Réseau](#services-réseau)
7. [Architecture Réseau](#architecture-réseau)
8. [Sécurité Réseau](#sécurité-réseau)
9. [Recommandations](#recommandations)

---

## 1. VUE D'ENSEMBLE RÉSEAU

### 1.1 Architecture Serveur

```
┌─────────────────────────────────────────────────────────────┐
│                    SERVEUR HÔTE                              │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         NGINX-PROXY (Docker Container)               │   │
│  │         Ports: 80, 443 (exposés)                     │   │
│  │         Reverse Proxy pour tous les sites             │   │
│  └──────────────────────────────────────────────────────┘   │
│                         │                                     │
│                         ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         PHP-FPM (Sur l'hôte)                          │   │
│  │         Port: 9000 (TCP) ou Socket Unix               │   │
│  │         Chemin: /run/php-fpm/www.sock                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                         │                                     │
│                         ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Application Laravel                           │   │
│  │         Chemin: /opt/bantudelice242                    │   │
│  │         Public: /opt/bantudelice242/public            │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Stack Réseau

- **Serveur Web :** Nginx (via Docker nginx-proxy)
- **Application :** Laravel 10.10 (PHP 8.1+)
- **PHP-FPM :** Sur l'hôte (port 9000 TCP ou socket Unix)
- **Base de données :** MySQL (port 3306 probablement)
- **Protocoles :** HTTP (80), HTTPS (443)

---

## 2. PORTS EXPOSÉS

### 2.1 Ports Identifiés (Scan Système)

| Port | Protocole | Service | État | Description |
|------|-----------|---------|------|-------------|
| **80** | TCP | HTTP | LISTEN | Redirection vers HTTPS |
| **443** | TCP | HTTPS | LISTEN | Serveur web principal |
| **22** | TCP | SSH | LISTEN | Accès administrateur |
| **3306** | TCP | MySQL | LISTEN | Base de données (probable) |
| **9000** | TCP | PHP-FPM | LISTEN | PHP-FPM (si TCP) |
| **9443** | TCP | ? | LISTEN | Service inconnu |
| **8282** | TCP | ? | LISTEN | Service inconnu |
| **8400** | TCP | ? | LISTEN | Service inconnu |
| **8448** | TCP | ? | LISTEN | Service inconnu |
| **4001** | TCP | ? | LISTEN | Service inconnu |
| **2000** | TCP | ? | LISTEN | Service inconnu |
| **1200** | TCP | ? | LISTEN | Service inconnu |
| **1188** | TCP | ? | LISTEN | Service inconnu |
| **1337** | TCP | ? | LISTEN | Service inconnu |
| **1530** | TCP | ? | LISTEN | Service inconnu |
| **3326** | TCP | ? | LISTEN | Service inconnu |
| **3310** | TCP | ? | LISTEN | Service inconnu |
| **53** | UDP | DNS | LISTEN | Résolution DNS locale |

### 2.2 Ports Application

#### Ports Web
- **80** : HTTP → Redirection 301 vers HTTPS
- **443** : HTTPS → Serveur principal avec SSL/TLS

#### Ports Backend
- **9000** : PHP-FPM (TCP) - Communication Nginx ↔ PHP-FPM
- **3306** : MySQL (probable) - Base de données

#### Ports Services
- **22** : SSH - Administration
- **53** : DNS - Résolution locale

---

## 3. CONFIGURATION SERVEUR WEB

### 3.1 Configuration Nginx

**Fichier de configuration :** `bantudelice-thedrop247.conf`

#### Domaines Configurés
```
server_name bantudelice.cg www.bantudelice.cg dev.bantudelice.cg;
```

#### Ports d'Écoute
- **Port 80** : Redirection HTTP → HTTPS
- **Port 443** : Serveur HTTPS principal (SSL/TLS)

#### Configuration SSL/TLS
```nginx
ssl_certificate     /etc/nginx/certs/bantudelice.cg/fullchain.pem;
ssl_certificate_key /etc/nginx/certs/bantudelice.cg/privkey.pem;
include /etc/nginx/conf.d/snippets/ssl-params.conf;
http2 on;  # HTTP/2 activé
```

#### PHP-FPM Configuration
```nginx
upstream thedrop247_php {
    server host.docker.internal:9000;  # TCP vers hôte
    # Alternative: server unix:/var/run/php-fpm/www.sock;  # Socket Unix
    keepalive 32;
}
```

#### Locations Configurées

| Location | Type | Description |
|----------|------|-------------|
| `/.well-known/acme-challenge/` | Directory | Let's Encrypt validation |
| `/api/` | API | Routes API Laravel |
| `~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot|map)$` | Static | Assets statiques (cache 1 an) |
| `^~ /storage/` | Storage | Fichiers uploadés (cache 30 jours) |
| `/` | Application | Routes Laravel principales |
| `~ \.php$` | PHP | Traitement PHP-FPM |

#### Paramètres FastCGI
```nginx
fastcgi_read_timeout 300;
fastcgi_send_timeout 300;
fastcgi_buffer_size 128k;
fastcgi_buffers 4 256k;
fastcgi_busy_buffers_size 256k;
```

#### Sécurité
- Blocage fichiers sensibles : `.env`, `.git`, `composer.json`, etc.
- Masquage `X-Powered-By`
- Cache-Control pour assets statiques

---

### 3.2 Configuration PHP-FPM

#### Mode de Communication
- **Option 1 (Actuel) :** TCP sur port 9000
  - `listen = 127.0.0.1:9000` ou `0.0.0.0:9000`
  - Nginx Docker se connecte via `host.docker.internal:9000`

- **Option 2 (Alternative) :** Socket Unix
  - `listen = /run/php-fpm/www.sock`
  - Nécessite montage du socket dans Docker

#### Fichier de Configuration
- **Chemin probable :** `/etc/php-fpm.d/www.conf`
- **Socket Unix :** `/run/php-fpm/www.sock`

---

## 4. DOMAINES ET DNS

### 4.1 Domaines Configurés

| Domaine | Type | Description |
|---------|------|-------------|
| `bantudelice.cg` | Production | Domaine principal |
| `www.bantudelice.cg` | Production | Variante www |
| `dev.bantudelice.cg` | Développement | Environnement dev |

### 4.2 Configuration DNS

**Enregistrements DNS nécessaires :**
```
A     bantudelice.cg          → IP_SERVEUR
A     www.bantudelice.cg      → IP_SERVEUR
A     dev.bantudelice.cg      → IP_SERVEUR
```

**Certificats SSL :**
- **Chemin :** `/etc/nginx/certs/bantudelice.cg/`
- **Fichiers :**
  - `fullchain.pem` : Certificat complet
  - `privkey.pem` : Clé privée

---

## 5. ENDPOINTS API

### 5.1 Routes API Principales

**Base URL :** `https://bantudelice.cg/api/` ou `https://dev.bantudelice.cg/api/`

#### Authentification
```
POST   /api/register
POST   /api/login
GET    /api/user_profile/{user}
POST   /api/update_profile/
POST   /api/forgot_password
```

#### Driver APIs
```
POST   /api/driver_register
POST   /api/driver_login
GET    /api/driver_profile/{driver}
POST   /api/driver_update_profile/
POST   /api/set_driver_online/{driver}
GET    /api/order_request/{driver}
POST   /api/order_accept_by_driver
```

#### Home & Search
```
POST   /api/home_data
GET    /api/product_detail/{product}
POST   /api/search_filters
POST   /api/search_by_keyword
GET    /api/restaurant_detail/{restaurant}
```

#### Cart APIs
```
POST   /api/add_to_cart
GET    /api/show_cart_details/{user}
POST   /api/update_cart_details
DELETE /api/delete_cart_product/{cart}
DELETE /api/delete_previous_cart/{user}
```

#### Order APIs
```
POST   /api/place_orders/
GET    /api/user_pending_orders/{user}
GET    /api/user_completed_order_history/{user}
POST   /api/complete_orders
```

#### Restaurant APIs
```
POST   /api/search_restaurant
GET    /api/restaurants_with_category/{cuisine}
GET    /api/get_filters
GET    /api/restaurants/popular
GET    /api/restaurants
GET    /api/restaurants/{id}/reviews
```

#### Checkout & Payment APIs
```
POST   /api/checkout
GET    /api/payments/{payment}
POST   /api/payments/{payment}/confirm
POST   /api/payments/callback/{provider}  # Callback public
```

#### Tracking & Delivery
```
GET    /api/order/{orderNo}/status
POST   /api/driver/{driverId}/location
GET    /api/orders/{order}/tracking
GET    /api/driver/deliveries
PATCH  /api/driver/deliveries/{delivery}/status
```

#### Rating APIs
```
POST   /api/orders/{order}/rating
GET    /api/orders/{order}/rating
GET    /api/orders/{order}/rating/check
```

### 5.2 Routes Web Principales

**Base URL :** `https://bantudelice.cg/` ou `https://dev.bantudelice.cg/`

#### Routes Publiques
```
GET    /
GET    /resturant/view/{id}
GET    /product/view/{id}
GET    /cart
GET    /checkout
GET    /search/
GET    /restaurants
GET    /restaurants/cuisine/{id}
```

#### Routes Authentification
```
GET    /login
POST   /login
GET    /logout
GET    /signup
POST   /signup
GET    /user/forgot
POST   /user/forgot-password
```

#### Routes Admin
```
GET    /admin
GET    /admin/restaurant
GET    /admin/driver
GET    /admin/all_orders
GET    /admin/api-configuration
```

#### Routes Restaurant
```
GET    /restaurant
GET    /restaurant/category
GET    /restaurant/product
GET    /restaurant/all_orders
```

---

## 6. SERVICES RÉSEAU

### 6.1 Services Identifiés

| Service | Port | Protocole | État | Description |
|---------|------|-----------|------|-------------|
| **Nginx** | 80, 443 | HTTP/HTTPS | ✅ Actif | Serveur web (Docker) |
| **PHP-FPM** | 9000 | TCP | ✅ Actif | Processeur PHP |
| **MySQL** | 3306 | TCP | ✅ Actif | Base de données |
| **SSH** | 22 | TCP | ✅ Actif | Administration |
| **DNS** | 53 | UDP | ✅ Actif | Résolution DNS |

### 6.2 Services Inconnus (À Vérifier)

| Port | Protocole | État | Action Requise |
|------|-----------|------|----------------|
| 9443 | TCP | LISTEN | Identifier le service |
| 8282 | TCP | LISTEN | Identifier le service |
| 8400 | TCP | LISTEN | Identifier le service |
| 8448 | TCP | LISTEN | Identifier le service |
| 4001 | TCP | LISTEN | Identifier le service |
| 2000 | TCP | LISTEN | Identifier le service |
| 1200 | TCP | LISTEN | Identifier le service |
| 1188 | TCP | LISTEN | Identifier le service |
| 1337 | TCP | LISTEN | Identifier le service |
| 1530 | TCP | LISTEN | Identifier le service |
| 3326 | TCP | LISTEN | Identifier le service |
| 3310 | TCP | LISTEN | Identifier le service |

**⚠️ Recommandation :** Identifier tous les services sur ces ports pour la sécurité.

---

## 7. ARCHITECTURE RÉSEAU

### 7.1 Flux de Requêtes

```
┌──────────────┐
│   Client     │
│  (Browser)   │
└──────┬───────┘
       │
       │ HTTPS (443)
       ▼
┌─────────────────────────────────────────────────────────────┐
│              NGINX-PROXY (Docker)                            │
│              Ports: 80, 443                                  │
│              Domaines: bantudelice.cg, dev.bantudelice.cg   │
└──────┬───────────────────────────────────────────────────────┘
       │
       │ FastCGI (TCP 9000 ou Socket Unix)
       ▼
┌─────────────────────────────────────────────────────────────┐
│              PHP-FPM (Sur l'hôte)                           │
│              Port: 9000 (TCP)                               │
│              Socket: /run/php-fpm/www.sock                  │
└──────┬───────────────────────────────────────────────────────┘
       │
       │ Requêtes Laravel
       ▼
┌─────────────────────────────────────────────────────────────┐
│              Application Laravel                             │
│              Chemin: /opt/bantudelice242                    │
│              Public: /opt/bantudelice242/public             │
└──────┬───────────────────────────────────────────────────────┘
       │
       │ Requêtes DB
       ▼
┌─────────────────────────────────────────────────────────────┐
│              MySQL Database                                  │
│              Port: 3306                                      │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 Architecture Docker

```
┌─────────────────────────────────────────────────────────────┐
│                    RÉSEAU DOCKER                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Container: nginx-proxy                              │   │
│  │  Ports exposés: 80:80, 443:443                        │   │
│  │  Volumes:                                             │   │
│  │    - /opt/nginx-docker/config:/etc/nginx/conf.d:ro   │   │
│  │    - /opt/bantudelice242/public:/var/www/thedrop247:ro│   │
│  │    - /run/php-fpm:/var/run/php-fpm:ro (si socket)    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ host.docker.internal:9000
                         │ (ou socket Unix monté)
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    HÔTE (Host)                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  PHP-FPM (Service système)                            │   │
│  │  Port: 9000 (TCP) ou Socket: /run/php-fpm/www.sock   │   │
│  └──────────────────────────────────────────────────────┘   │
│                         │                                     │
│                         ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Application Laravel                                  │   │
│  │  /opt/bantudelice242                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. SÉCURITÉ RÉSEAU

### 8.1 Mesures de Sécurité Implémentées

✅ **SSL/TLS :**
- Certificats SSL configurés
- HTTP/2 activé
- Redirection HTTP → HTTPS (301)

✅ **Protection Fichiers Sensibles :**
- Blocage `.env`, `.git`, `composer.json`
- Masquage `X-Powered-By`
- Blocage fichiers cachés (sauf `.well-known`)

✅ **Configuration FastCGI :**
- Timeouts configurés (300s)
- Buffers optimisés
- Headers sécurisés

### 8.2 Points d'Attention

⚠️ **Ports Ouverts :**
- Plusieurs ports inconnus ouverts (voir section 2.2)
- **Action :** Identifier et sécuriser tous les services

⚠️ **PHP-FPM :**
- Si TCP sur `0.0.0.0:9000`, accessible depuis réseau
- **Recommandation :** Utiliser `127.0.0.1:9000` ou socket Unix

⚠️ **Callbacks Paiements :**
- Route `/api/payments/callback/{provider}` est publique
- **Recommandation :** IP whitelist ou signature validation

⚠️ **Rate Limiting :**
- API rate limiting configuré (`throttle:60,1`)
- **Vérifier :** Si suffisant pour production

### 8.3 Recommandations Sécurité

1. **Firewall :**
   - Fermer tous les ports non nécessaires
   - Autoriser uniquement : 22 (SSH), 80 (HTTP), 443 (HTTPS)
   - Restreindre accès MySQL (3306) à localhost uniquement

2. **PHP-FPM :**
   - Utiliser socket Unix plutôt que TCP
   - Ou limiter TCP à `127.0.0.1:9000` uniquement

3. **SSL/TLS :**
   - Vérifier expiration certificats
   - Renouvellement automatique (Let's Encrypt)

4. **Monitoring :**
   - Surveiller logs Nginx
   - Alertes sur tentatives d'intrusion
   - Monitoring des ports ouverts

---

## 9. RECOMMANDATIONS

### 9.1 Court Terme

1. **Identifier Services Inconnus**
   ```bash
   # Pour chaque port inconnu
   sudo lsof -i :PORT
   sudo netstat -tulpn | grep PORT
   ```

2. **Sécuriser PHP-FPM**
   - Vérifier configuration : `/etc/php-fpm.d/www.conf`
   - S'assurer que `listen = 127.0.0.1:9000` (pas `0.0.0.0`)

3. **Vérifier Firewall**
   - Configurer UFW ou iptables
   - Fermer ports non nécessaires

### 9.2 Moyen Terme

1. **Monitoring Réseau**
   - Implémenter monitoring des ports
   - Alertes sur nouveaux services

2. **Documentation**
   - Documenter tous les services actifs
   - Maintenir inventaire des ports

3. **Tests de Sécurité**
   - Scan de vulnérabilités
   - Tests de pénétration réseau

### 9.3 Long Terme

1. **Load Balancing**
   - Prévoir pour haute disponibilité
   - Multi-serveurs

2. **CDN**
   - Assets statiques via CDN
   - Réduction charge serveur

3. **DDoS Protection**
   - Protection contre attaques
   - Rate limiting avancé

---

## 10. COMMANDES UTILES

### 10.1 Vérification Ports

```bash
# Liste tous les ports ouverts
sudo netstat -tuln
# ou
sudo ss -tuln

# Identifier service sur un port
sudo lsof -i :PORT
sudo netstat -tulpn | grep PORT

# Vérifier port spécifique
nc -zv localhost PORT
```

### 10.2 Vérification Nginx

```bash
# Tester configuration
docker exec nginx-proxy nginx -t

# Recharger configuration
docker exec nginx-proxy nginx -s reload

# Vérifier logs
docker logs nginx-proxy
tail -f /var/log/nginx/bantudelice-access.log
tail -f /var/log/nginx/bantudelice-error.log
```

### 10.3 Vérification PHP-FPM

```bash
# Statut service
systemctl status php-fpm

# Vérifier port
netstat -tuln | grep 9000

# Vérifier socket
ls -la /run/php-fpm/www.sock

# Test connexion
nc -zv 127.0.0.1 9000
```

### 10.4 Test Endpoints

```bash
# Test HTTP
curl -I http://bantudelice.cg

# Test HTTPS
curl -I https://bantudelice.cg

# Test API
curl -X GET https://bantudelice.cg/api/restaurants

# Test avec authentification
curl -X GET https://bantudelice.cg/api/user_profile/1 \
  -H "Authorization: Bearer TOKEN"
```

---

## 11. INVENTAIRE RÉSEAU

### 11.1 Résumé

| Élément | Valeur | Statut |
|---------|--------|--------|
| **Domaines** | 3 (bantudelice.cg, www, dev) | ✅ Configuré |
| **Ports Web** | 80, 443 | ✅ Actifs |
| **Ports Backend** | 9000 (PHP-FPM), 3306 (MySQL) | ✅ Actifs |
| **SSL/TLS** | Certificats configurés | ✅ Actif |
| **HTTP/2** | Activé | ✅ Actif |
| **Services Inconnus** | 12 ports à identifier | ⚠️ À vérifier |

### 11.2 Fichiers de Configuration

- **Nginx :** `bantudelice-thedrop247.conf`
- **PHP-FPM :** `/etc/php-fpm.d/www.conf` (à vérifier)
- **Docker :** Configuration nginx-proxy
- **SSL :** `/etc/nginx/certs/bantudelice.cg/`

---

**Document généré le :** 2025-01-27  
**Version :** 1.0

