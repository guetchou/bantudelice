# 📋 GUIDE COMPLET - MIGRATION NGINX DOCKER

**Date :** $(date)

---

## ✅ ÉTAT ACTUEL

- ✅ PHP-FPM fonctionne avec socket Unix `/run/php-fpm/www.sock`
- ✅ Configuration Nginx créée dans `/opt/nginx-docker/config/conf.d/bantudelice.conf`
- ⏳ Volumes Docker à ajouter au container nginx-proxy

---

## 🔧 ACTIONS REQUISES

### 1. Ajouter les Volumes dans nginx-proxy Docker

Le container `nginx-proxy` doit avoir accès à :
- `/opt/thedrop247/public` → `/var/www/thedrop247` (read-only)
- `/run/php-fpm` → `/var/run/php-fpm` (read-only, pour le socket)

**Méthode :** Redémarrer le container avec les nouveaux volumes

### 2. Vérifier la Configuration

```bash
docker exec nginx-proxy nginx -t
```

### 3. Recharger Nginx

```bash
docker exec nginx-proxy nginx -s reload
```

Ou redémarrer :
```bash
docker restart nginx-proxy
```

---

## 📝 COMMANDES COMPLÈTES

### Option A : Redémarrer avec nouveaux volumes

```bash
# Arrêter le container
docker stop nginx-proxy

# Redémarrer avec volumes ajoutés
docker start nginx-proxy
# (Les volumes doivent être ajoutés dans la config Docker)
```

### Option B : Utiliser docker-compose (si disponible)

Modifier la configuration docker-compose du nginx-proxy pour ajouter les volumes.

---

## ⚠️ IMPORTANT

**Avant de redémarrer nginx-proxy :**
1. Vérifier que tous les autres sites fonctionnent encore
2. Tester la config : `docker exec nginx-proxy nginx -t`
3. Sauvegarder l'état actuel

---

## 🎯 PROCHAINES ÉTAPES

1. ⏳ Ajouter les volumes au container nginx-proxy
2. ⏳ Tester la configuration
3. ⏳ Recharger nginx-proxy
4. ⏳ Compiler les assets
5. ⏳ Créer utilisateur admin
6. ⏳ Tester l'application

---

**Document prêt pour la migration !**

