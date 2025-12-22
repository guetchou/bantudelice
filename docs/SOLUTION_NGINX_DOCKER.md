# ✅ SOLUTION COMPLÈTE - NGINX DOCKER POUR THEDROP247

**Date :** $(date)

---

## 🎯 PROBLÈME

- Port 9000 utilisé par Portainer
- PHP-FPM utilise socket Unix `/run/php-fpm/www.sock`
- Nginx Docker doit communiquer avec PHP-FPM sur l'hôte

---

## 🔧 SOLUTION : Utiliser un autre port pour PHP-FPM

### Option 1 : Configurer PHP-FPM sur port 9001 (RECOMMANDÉ)

1. **Configurer PHP-FPM pour écouter sur port 9001**

2. **Monter le répertoire Laravel dans nginx-proxy Docker**

3. **Créer la config Nginx qui pointe vers PHP-FPM sur l'hôte**

---

## 📋 PLAN D'ACTION DÉTAILLÉ

### Étape 1 : Configurer PHP-FPM sur port 9001

Modifier `/etc/php-fpm.d/www.conf` :
- Changer `listen = /run/php-fpm/www.sock` 
- Vers `listen = 127.0.0.1:9001`

### Étape 2 : Ajouter le volume dans nginx-proxy

Ajouter dans la config Docker de nginx-proxy :
- `/opt/thedrop247/public:/var/www/thedrop247:ro`

### Étape 3 : Créer la config Nginx

Créer `/opt/nginx-docker/config/conf.d/bantudelice.conf` avec:
- Upstream vers `host.docker.internal:9001` ou IP hôte
- Root vers `/var/www/thedrop247`
- Configuration PHP-FPM

### Étape 4 : Recharger nginx-proxy

---

**Prêt à implémenter !**

