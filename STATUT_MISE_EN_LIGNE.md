# STATUT MISE EN LIGNE - bantudelice.cg et dev.bantudelice.cg

**Date :** 2025-12-16  
**Statut :** En cours de finalisation

---

## ✅ RÉALISÉ

### 1. Configuration Nginx
- ✅ Configuration Nginx mise à jour (`/opt/nginx-docker/config/conf.d/bantudelice.conf`)
- ✅ Socket PHP-FPM corrigé : `/var/run/php/php8.1-fpm.sock`
- ✅ Root Laravel configuré : `/var/www/bantudelice242`
- ✅ Domaines configurés : `bantudelice.cg`, `www.bantudelice.cg`, `dev.bantudelice.cg`

### 2. Docker
- ✅ Volume monté : `/opt/bantudelice242/public:/var/www/bantudelice242:ro`
- ✅ Socket PHP monté : `/run/php:/var/run/php:ro`
- ✅ Container nginx-proxy redémarré

### 3. Services
- ✅ PHP-FPM 8.1 actif (`php8.1-fpm.service`)
- ✅ Nginx-proxy container actif
- ✅ Socket PHP-FPM accessible dans container

### 4. Tests
- ✅ `bantudelice.cg` : **HTTP 200** (fonctionne)
- ⚠️ `dev.bantudelice.cg` : **HTTP 404** (problème à résoudre)

---

## ⚠️ PROBLÈMES IDENTIFIÉS

### 1. dev.bantudelice.cg renvoie 404
**Symptôme :** Le site répond mais avec "File not found"

**Causes possibles :**
- Configuration Nginx en conflit (plusieurs configs pour dev.bantudelice.cg)
- Chemin des fichiers incorrect dans fastcgi_param
- Permissions sur les fichiers

**Actions à effectuer :**
```bash
# Vérifier les logs
docker exec nginx-proxy tail -f /var/log/nginx/bantudelice-error.log

# Vérifier la config chargée
docker exec nginx-proxy nginx -T | grep -A 20 "dev.bantudelice.cg"

# Vérifier les fichiers
docker exec nginx-proxy ls -la /var/www/bantudelice242/
docker exec nginx-proxy test -f /var/www/bantudelice242/index.php
```

### 2. Assets non compilés pour développement
**Symptôme :** Erreur babel-loader lors de la compilation

**Cause :** Incompatibilité de versions entre babel-loader et webpack

**Solution temporaire :** Les assets existants sont utilisés

**Solution à long terme :** 
```bash
cd /opt/bantudelice242
export PATH="/root/.local/share/pnpm:$PATH"
pnpm install --force
pnpm run dev
```

---

## 🔧 ACTIONS RESTANTES

### 1. Résoudre le problème 404 sur dev.bantudelice.cg

**Étape 1 :** Vérifier s'il y a plusieurs configs
```bash
ls -la /opt/nginx-docker/config/conf.d/ | grep -i bantudelice
```

**Étape 2 :** Vérifier quelle config est utilisée
```bash
docker exec nginx-proxy nginx -T 2>&1 | grep -B 5 -A 15 "server_name.*dev.bantudelice"
```

**Étape 3 :** Vérifier les logs PHP-FPM
```bash
tail -f /var/log/php8.1-fpm.log
```

### 2. Compiler les assets pour développement

**Option 1 :** Utiliser npm au lieu de pnpm
```bash
cd /opt/bantudelice242
npm install
npm run dev
```

**Option 2 :** Corriger les dépendances pnpm
```bash
cd /opt/bantudelice242
export PATH="/root/.local/share/pnpm:$PATH"
pnpm install --force
pnpm run dev
```

**Option 3 :** Utiliser les assets existants (temporaire)
- Les assets sont déjà compilés dans `public/js/app.js` et `public/css/app.css`

### 3. Vérifier les permissions

```bash
# Vérifier permissions sur les fichiers
ls -la /opt/bantudelice242/public/
ls -la /opt/bantudelice242/storage/

# Vérifier permissions dans container
docker exec nginx-proxy ls -la /var/www/bantudelice242/
```

---

## 📋 COMMANDES UTILES

### Vérifier l'état
```bash
# Container Nginx
docker ps | grep nginx-proxy

# PHP-FPM
systemctl status php8.1-fpm

# Test sites
curl -I https://bantudelice.cg
curl -I https://dev.bantudelice.cg
```

### Recharger Nginx
```bash
docker exec nginx-proxy nginx -t
docker exec nginx-proxy nginx -s reload
# ou
cd /opt/nginx-docker && docker compose restart nginx-proxy
```

### Voir les logs
```bash
# Logs Nginx
docker exec nginx-proxy tail -f /var/log/nginx/bantudelice-error.log
docker exec nginx-proxy tail -f /var/log/nginx/bantudelice-access.log

# Logs PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

---

## ✅ RÉSUMÉ

**bantudelice.cg :** ✅ **EN LIGNE** (HTTP 200)  
**dev.bantudelice.cg :** ⚠️ **PROBLÈME** (HTTP 404 - File not found)

**Prochaine étape :** Résoudre le problème 404 sur dev.bantudelice.cg en vérifiant les configs Nginx et les chemins des fichiers.

---

**Document généré le :** 2025-12-16

