# 🔧 DÉPANNAGE ERREUR 500 - RÉSUMÉ

**Date :** 2025-12-02 10:00 UTC

---

## ❌ PROBLÈME

**Erreur :** HTTP 500 Internal Server Error sur https://dev.bantudelice.cg

---

## ✅ CORRECTIONS APPLIQUÉES

### 1. Cache Laravel
- ✅ Cache de routes supprimé
- ✅ Cache de configuration supprimé  
- ✅ Cache d'application supprimé
- ✅ Fichiers dans `/opt/thedrop247/bootstrap/cache/` nettoyés

### 2. Configuration Nginx
- ✅ SCRIPT_FILENAME mappé vers `/opt/thedrop247/public$fastcgi_script_name`
- ✅ DOCUMENT_ROOT défini à `/opt/thedrop247/public`
- ✅ PATH_INFO ajouté

### 3. Services
- ✅ PHP-FPM : Active (running)
- ✅ Nginx-proxy : Active
- ✅ Socket Unix : Créé et accessible

---

## ⚠️ PROBLÈME RESTANT

**Logs Nginx :**
```
FastCGI sent in stderr: "Primary script unknown"
```

**Cause probable :** Mapping de chemin entre Nginx Docker et PHP-FPM hôte

---

## 🔍 DIAGNOSTIC À FAIRE

1. **Vérifier les logs Laravel récents**
   ```bash
   tail -50 /opt/thedrop247/storage/logs/laravel-$(date +%Y-%m-%d).log
   ```

2. **Tester PHP directement**
   ```bash
   php /opt/thedrop247/public/index.php
   ```

3. **Vérifier les permissions**
   ```bash
   ls -la /opt/thedrop247/public/
   ls -la /run/php-fpm/www.sock
   ```

4. **Vérifier le chemin envoyé à PHP-FPM**
   - Activer les logs PHP-FPM pour voir le chemin reçu
   - Vérifier que le chemin correspond au système de fichiers de l'hôte

---

## 📊 ÉTAT ACTUEL

| Élément | Status |
|---------|--------|
| Site accessible | ✅ OUI |
| HTTPS | ✅ Actif |
| PHP-FPM | ✅ Actif |
| Nginx | ✅ Actif |
| Application | ❌ Erreur 500 |

**Le site est accessible mais retourne une erreur 500.**

---

## 🔧 PROCHAINES ÉTAPES

1. Vérifier les logs Laravel détaillés
2. Tester PHP-FPM directement
3. Vérifier le mapping de chemin exact
4. Possible solution : Utiliser TCP au lieu du socket Unix pour simplifier

