# 🔍 AUDIT DÉTAILLÉ - PROBLÈME PHP-FPM

**Date :** 2025-12-02 09:50 UTC

---

## ❌ PROBLÈME IDENTIFIÉ

**Erreur actuelle :**
```
ERROR: unable to bind listening socket for address '/run/php-fpm/www.sock': Permission denied (13)
ERROR: FPM initialization failed
```

**Statut du service :** `failed (Result: exit-code)`

---

## 📊 ANALYSE DÉTAILLÉE

### 1. Configuration PHP-FPM

- **Utilisateur d'exécution :** `apache` (UID: 48, GID: 48)
- **Socket configuré :** `/run/php-fpm/www.sock`
- **Permissions ACL :** `apache,nginx` (configuré dans www.conf)
- **Mode socket :** `0660` (par défaut)

### 2. Répertoire du socket

- **Répertoire :** `/run/php-fpm/`
- **Propriétaire :** `root:root`
- **Permissions :** `drwxr-xr-x` (755)
- **Contexte SELinux :** `httpd_sys_rw_content_t`
- **Problème :** L'utilisateur `apache` ne peut pas créer de fichiers dans ce répertoire

### 3. SELinux

- **Statut :** `Enforcing`
- **Policy :** `targeted`
- **Impact :** Peut bloquer l'accès même si les permissions sont correctes

### 4. Tentatives précédentes

1. ✅ Tentative de changement vers TCP (port 9001) - Échoué (port déjà utilisé par Portainer)
2. ✅ Tentative de modification des permissions - Échoué (permissions réinitialisées)
3. ❌ Erreur "Address already in use" - Socket fantôme résolu
4. ❌ Erreur "Permission denied" - Problème actuel

---

## 🔧 SOLUTIONS IDENTIFIÉES (Recherche Web)

### Solution 1 : Configurer systemd-tmpfiles (RECOMMANDÉ)

Créer un fichier de configuration systemd pour gérer le répertoire avec les bonnes permissions.

### Solution 2 : Changer le répertoire du socket

Utiliser `/var/run/php-fpm/` ou `/tmp/php-fpm/` qui sont plus appropriés.

### Solution 3 : Utiliser TCP avec un port différent

Configurer PHP-FPM pour écouter sur un port TCP non utilisé.

### Solution 4 : Configurer SELinux correctement

Autoriser PHP-FPM à créer des sockets dans /run/php-fpm avec SELinux.

---

## ✅ SOLUTION CHOSIE : systemd-tmpfiles + Permissions

**Approche :**
1. Créer un fichier systemd-tmpfiles pour gérer le répertoire
2. Configurer les permissions appropriées
3. Configurer SELinux si nécessaire
4. Redémarrer PHP-FPM

---

**Prochaine étape :** Implémenter la solution

