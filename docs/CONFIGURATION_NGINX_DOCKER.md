# 🔧 CONFIGURATION NGINX DOCKER POUR THEDROP247

**Date :** $(date)

---

## 📊 SITUATION ACTUELLE

### Architecture Découverte

1. **Nginx Principal Docker** (`nginx-proxy`)
   - Container qui reverse proxy pour TOUS les sites
   - Ports 80/443 exposés
   - Config dans `/opt/nginx-docker/config/conf.d/`
   - Réseau Docker séparé

2. **BantuDelice Actuel**
   - Backend NestJS dans Docker (`bantudelice_backend:3001`)
   - Frontend statique dans `/var/www/bantudelice` (monté dans Docker)
   - Domaine : `dev.bantudelice.cg`

3. **TheDrop247 (Laravel)**
   - Application Laravel sur l'hôte (`/opt/thedrop247`)
   - PHP-FPM sur l'hôte (socket Unix `/run/php-fpm/www.sock`)
   - Doit remplacer BantuDelice

---

## 🎯 PROBLÈME À RÉSOUDRE

**Défi :** Le nginx-proxy Docker doit servir Laravel qui tourne sur l'hôte avec PHP-FPM.

### Options de Connexion PHP-FPM ↔ Nginx Docker

#### Option 1 : Socket Unix (Complexe)
- Monter le socket Unix dans Docker
- Configuration plus complexe
- ✅ Performances meilleures

#### Option 2 : TCP (Plus Simple) ✅ RECOMMANDÉ
- Configurer PHP-FPM pour écouter sur TCP (port 9000)
- Nginx Docker se connecte via `host.docker.internal:9000` ou IP hôte
- ✅ Plus simple à configurer
- ✅ Fonctionne bien

---

## 🔧 SOLUTION RECOMMANDÉE : PHP-FPM via TCP

### Étape 1 : Configurer PHP-FPM pour TCP

Éditer `/etc/php-fpm.d/www.conf` :
```ini
# Changer de socket Unix à TCP
listen = 127.0.0.1:9000
# ou
listen = 0.0.0.0:9000  # Pour accepter depuis Docker
```

### Étape 2 : Configurer Nginx Docker

Utiliser `host.docker.internal:9000` ou IP hôte Docker gateway

### Étape 3 : Monter le répertoire Laravel dans Docker

Ajouter un volume dans nginx-proxy :
```yaml
/srv/topcenter/thedrop247/public:/var/www/thedrop247:ro
```

---

## 📝 PLAN D'ACTION

1. ✅ Analyser la configuration actuelle
2. ⏳ Configurer PHP-FPM pour TCP
3. ⏳ Créer la config Nginx pour thedrop247
4. ⏳ Monter le volume dans nginx-proxy
5. ⏳ Remplacer bantudelice.conf
6. ⏳ Recharger nginx-proxy

---

**Prochaine étape :** Configurer PHP-FPM pour TCP

