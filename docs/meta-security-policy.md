# Politique de Sécurité Backend — BantuDelice
**Applicable à** : Intégration Facebook Login (Meta) — App ID utilisé sur bantudelice.cg  
**Date** : Juin 2026 | **Version** : 1.0  
**Contact sécurité** : gess198@gmail.com

---

## 1. Périmètre

Cette politique décrit les procédures de sécurité appliquées à l'environnement backend de la plateforme BantuDelice, qui traite les données utilisateurs transmises via Facebook Login (Meta). Elle couvre :

- Le serveur d'application (Laravel 10 / PHP 8.2, VPS OVH)
- La base de données (MySQL 8)
- Les API REST exposées aux applications mobiles et web
- Les tokens d'accès et données de profil reçus depuis Facebook Login

---

## 2. Tests de sécurité — Au moins une fois tous les 12 mois

> **[ Condition requise — surlignée ]**  
> **BantuDelice effectue un audit de sécurité complet au moins une fois par an (cycle annuel, en janvier de chaque année).**

### 2.1 Périmètre des tests annuels

| Domaine | Méthode | Fréquence |
|---------|---------|-----------|
| Analyse des vulnérabilités OWASP Top 10 | Revue de code manuelle + outils automatisés | Annuelle (janvier) |
| Tests de pénétration sur les endpoints API | Simulation d'attaque (OWASP ZAP, Burp Suite Community) | Annuelle |
| Audit des dépendances (composer audit) | `composer audit` + vérification CVE | Mensuelle + annuelle complète |
| Revue des permissions et contrôles d'accès | Audit RBAC (rôles : admin, restaurant, livreur, client) | Annuelle |
| Vérification des configurations (HTTPS, headers, cookies) | Scan automatisé + revue manuelle | Annuelle |
| Audit des logs et anomalies d'accès | Analyse Sentry + logs applicatifs | Mensuelle |

### 2.2 Déclencheurs additionnels (tests hors cycle)

Un test de sécurité est également déclenché :
- Lors de tout changement majeur de l'architecture (nouvelle API, nouveau fournisseur d'auth)
- Après un incident de sécurité (même mineur)
- Lors de l'ajout d'une intégration tierce accédant aux données utilisateurs (ex. : Facebook Login)

---

## 3. Processus de triage — Classement par gravité

> **[ Condition requise — surlignée ]**  
> **Chaque vulnérabilité identifiée est classée par gravité selon le standard CVSS v3.1 et traitée selon les délais définis ci-dessous.**

### 3.1 Niveaux de gravité et SLA de correction

| Niveau | Score CVSS | Description | Délai de correction |
|--------|-----------|-------------|---------------------|
| **Critique** | 9.0 – 10.0 | Accès non autorisé aux données utilisateurs, RCE, SQLi exploitable | **72 heures** |
| **Élevé** | 7.0 – 8.9 | IDOR, fuite de tokens, contournement d'authentification | **7 jours** |
| **Moyen** | 4.0 – 6.9 | XSS réflexif, mauvaise configuration CORS, absence de rate-limiting | **30 jours** |
| **Faible** | 0.1 – 3.9 | Informations de débogage exposées, headers manquants | **90 jours** |

### 3.2 Workflow de triage

```
Découverte → Évaluation CVSS → Classification → Assignation responsable →
Correction → Test de régression → Clôture documentée
```

1. **Découverte** : via audit annuel, monitoring Sentry, rapport externe ou bug bounty interne
2. **Évaluation** : calcul du score CVSS v3.1 par le responsable technique
3. **Classification** : niveau Critique / Élevé / Moyen / Faible
4. **Assignation** : le responsable technique prend en charge les corrections critiques/élevées dans les délais définis
5. **Correction** : déployée via pipeline rsync + tests de non-régression
6. **Clôture** : documentée dans le registre des vulnérabilités (fichier interne horodaté)

---

## 4. Correction des vulnérabilités critiques — Accès non autorisé aux données

> **[ Condition requise — surlignée ]**  
> **Les vulnérabilités critiques susceptibles d'entraîner un accès non autorisé aux données de la plateforme sont corrigées dans un délai maximum de 72 heures après leur identification.**

### 4.1 Procédure de réponse immédiate (vulnérabilités critiques)

En cas de vulnérabilité critique identifiée :

1. **Isolation immédiate** : si possible, l'endpoint ou la fonctionnalité affectée est désactivée temporairement (feature flag ou retrait de route)
2. **Notification interne** : alerte envoyée dans les 4 heures au responsable technique et au propriétaire de l'application
3. **Correction en urgence** : développement, test et déploiement du correctif sous 72 heures
4. **Révocation des tokens compromis** : si des tokens Facebook Login ou sessions utilisateurs sont potentiellement exposés, ils sont invalidés immédiatement via l'API Laravel Passport et Facebook
5. **Notification Meta** : en cas de compromission confirmée de données reçues via Facebook Login, Meta est notifié via le Data Incident Report Portal dans les 72 heures

### 4.2 Mesures techniques en place pour prévenir l'accès non autorisé

| Mesure | Implémentation |
|--------|---------------|
| Authentification | Laravel Passport (OAuth2), tokens à durée limitée |
| Contrôle d'accès | RBAC strict — chaque rôle ne peut accéder qu'à ses propres données |
| Protection CSRF | Token CSRF Laravel sur tous les formulaires |
| HTTPS obligatoire | TLS 1.2+ sur tous les endpoints, HSTS activé |
| Rate limiting | Limitation des tentatives de connexion (5 essais / 15 min) |
| Tokens Facebook | Validation côté serveur via l'API Graph avant création de session |
| Logs d'accès | Sentry (erreurs) + logs Laravel structurés avec conservation 90 jours |
| Secrets | Variables d'environnement (`.env`) hors dépôt Git, jamais exposées |

### 4.3 Protection spécifique des données Facebook Login

Les données reçues via Facebook Login (ID utilisateur, email, nom de profil) sont :
- Stockées chiffrées en transit (HTTPS)
- Associées uniquement au compte utilisateur correspondant (pas de partage)
- Accessibles uniquement par l'utilisateur authentifié ou les administrateurs autorisés (RBAC)
- Jamais transmises à des tiers
- Supprimables sur demande de l'utilisateur (droit à l'effacement)

---

## 5. Conformité et révision

Cette politique est révisée annuellement (janvier) et mise à jour immédiatement en cas de changement significatif de l'architecture ou suite à un incident de sécurité.

**Responsable** : Équipe technique BantuDelice  
**Contact** : gess198@gmail.com  
**Dernière révision** : Juin 2026

---

*Document établi pour la vérification de l'application Facebook Login — Meta App Review.*
