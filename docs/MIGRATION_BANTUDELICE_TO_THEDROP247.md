# 🔄 MIGRATION BANTUDELICE → THEDROP247

**Date :** $(date)

---

## 📋 CONTEXTE

TheDrop247 doit **remplacer** BantuDelice sur le même domaine :
- **Domaine actuel :** `bantudelice.cg` / `dev.bantudelice.cg`
- **Nouvelle application :** TheDrop247 (Laravel)
- **Ancienne application :** BantuDelice (NestJS + Vite)

---

## 🎯 PLAN DE MIGRATION

### 1. Configuration Nginx
- ✅ Analyser la config actuelle de bantudelice
- ⏳ Créer la config pour thedrop247
- ⏳ Utiliser les certificats SSL existants
- ⏳ Configurer le domaine

### 2. Configuration Application
- ⏳ Mettre à jour APP_URL dans .env
- ⏳ Configurer le domaine dans Laravel
- ⏳ Adapter les variables d'environnement

### 3. Assets et Compilation
- ⏳ Compiler les assets frontend
- ⏳ Vérifier les chemins statiques

### 4. Utilisateur Admin
- ⏳ Créer un utilisateur admin

### 5. Tests
- ⏳ Tester l'application
- ⏳ Vérifier HTTPS

---

## 📊 INFORMATIONS RÉCUPÉRÉES

### Domaine
- **Production :** `bantudelice.cg`
- **Développement :** `dev.bantudelice.cg`
- **API :** `api.dev.bantudelice.cg`

### Certificats SSL
- **Let's Encrypt :** `/etc/letsencrypt/live/bantudelice.cg/`
- Certificats valides disponibles

### Configuration Actuelle
- Backend NestJS sur port 3001
- Frontend Vite sur port 9595
- WebSockets supportés
- HTTPS configuré

---

## 🚀 PROCHAINES ÉTAPES

1. Créer la configuration Nginx pour thedrop247
2. Configurer le domaine dans .env
3. Compiler les assets
4. Créer utilisateur admin
5. Tester

---

**Document en cours de création...**



