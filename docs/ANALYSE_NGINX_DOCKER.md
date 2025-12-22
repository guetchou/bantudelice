# 🔍 ANALYSE NGINX DOCKER - MIGRATION BANTUDELICE → THEDROP247

**Date :** $(date)

---

## 📊 ARCHITECTURE DÉCOUVERTE

### Nginx Principal Docker
- **Container :** `nginx-proxy`
- **Ports :** 80, 443 (host) → reverse proxy pour tous les sites
- **Config host :** `/opt/nginx-docker/config/conf.d/`
- **Config container :** `/etc/nginx/conf.d/` (mount en read-only)

### Configuration Actuelle BantuDelice
- **Fichier :** `/opt/nginx-docker/config/conf.d/bantudelice.conf`
- **Domaine :** `bantudelice.cg`, `www.bantudelice.cg`, `dev.bantudelice.cg`
- **Backend actuel :** `bantudelice_backend:3001` (container Docker)
- **Certificat SSL :** `/etc/nginx/certs/bantudelice.cg/` (dans container)

---

## 🎯 STRATÉGIE DE MIGRATION

Pour **remplacer bantudelice par thedrop247** :

### Option 1 : Modifier la config existante (RECOMMANDÉ)
- Modifier `/opt/nginx-docker/config/conf.d/bantudelice.conf`
- Changer le backend pour pointer vers PHP-FPM local
- Garder le même domaine (dev.bantudelice.cg)
- Ne pas affecter les autres sites

### Option 2 : Créer une nouvelle config
- Créer `/opt/nginx-docker/config/conf.d/thedrop247.conf`
- Utiliser un nouveau domaine (si disponible)
- Plus complexe car changement de domaine

**✅ Recommandation :** Option 1 - Modifier la config existante

---

## 📝 PLAN D'ACTION

1. ✅ Sauvegarder la config actuelle de bantudelice
2. ⏳ Lire la config complète de bantudelice.conf
3. ⏳ Créer une nouvelle config pour thedrop247 (Laravel + PHP-FPM)
4. ⏳ Remplacer bantudelice.conf par la nouvelle config
5. ⏳ Recharger nginx-proxy Docker
6. ⏳ Tester

---

## ⚠️ IMPORTANT

- **Ne pas supprimer** les autres fichiers de conf (chatwoot, lvaclean, etc.)
- **Sauvegarder** avant toute modification
- **Tester** avant de recharger nginx

---

**Prochaine étape :** Lire et analyser bantudelice.conf complet

