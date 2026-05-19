# Audit 6.6 - Services et Kosunga

## Portee

Verification cible de l'existence reelle de traces metier pour:

- services a la demande
- artisans / prestataires / booking
- sante / rendez-vous / consultation
- teleconsultation
- doctor / patient / clinic / appointment
- `Salisa`
- `Kosunga`

## Conclusion rapide

- Aucun module metier `services` dedie n'est prouve dans le codebase.
- Aucun module metier `Kosunga` / sante / rendez-vous / teleconsultation n'est prouve dans le codebase.
- `Salisa` et `Kosunga` existent uniquement comme:
  - libelles UI
  - cartes d'ecosysteme
  - promesses de plateforme soeur
- La bonne representation actuelle est donc:
  - lien externe / carte ecosysteme
  - pas de tunnel interne
  - pas de fausse promesse d'integration metier

## 1. Ce qui existe reellement

### Traces UI / branding

Dans `resources/views/frontend/index-modern.blade.php`:

- dropdown `Nos Plateformes`
  - `Salisa` -> "Plateforme freelance congolaise"
  - `Kosunga` -> "Consultations medicales en ligne"
- bloc ecosysteme:
  - `Salisa`
  - `Kosunga`
  - description `Rendez-vous medicaux et teleconsultation...`

Constat:

- presence editoriale reelle
- absence de route ou de tunnel metier associe dans ce fichier

### Traces de configuration site

Dans `config/sites.php`:

- seulement `main`
- seulement `market`

Constat:

- aucune entree `salisa`
- aucune entree `kosunga`
- aucune configuration de domaine ou theme dedie pour ces plateformes

### Traces CMS / pages statiques

Dans `app/Services/CmsStaticPageService.php`:

- pages par defaut multi-services
- wording sur plateforme locale multi-services
- aucune page metier `kosunga`
- aucune structure CMS `appointment`, `doctor`, `patient`, `clinic`

Constat:

- le CMS transporte du discours produit
- pas de preuve de brique fonctionnelle sante/services

## 2. Ce qui n'existe pas

Recherche effectuee sur:

- `app`
- `routes`
- `resources`
- `database`

Mots verifies:

- `doctor`
- `patient`
- `clinic`
- `appointment`
- `teleconsult`
- `consultation`
- `rendez-vous`
- `rdv`
- `medical`
- `sante`
- `Kosunga`
- `Salisa`

### Resultat

- aucune route publique ou API dediee sante
- aucun controleur `Doctor`, `Patient`, `Clinic`, `Appointment`
- aucun modele domaine sante
- aucune migration de tables sante / rendez-vous
- aucun workflow de booking services generique
- aucun dashboard metier services/kosunga

## 3. Faux positifs a ne pas confondre

### "services" ne veut pas dire module `services`

Le mot `service` apparait dans le code pour:

- `service_fee`
- `service_level` colis
- `set_service_charges`
- `external-services`
- textes marketing "services"

Constat:

- ces occurrences ne prouvent pas un module `services a la demande`
- elles sont soit:
  - comptables
  - techniques
  - marketing
  - parametres restaurant

### "sante" ne prouve pas un produit `Kosunga`

Occurrence trouvee:

- `resources/views/layouts/app.blade.php`
  - menu admin `Modules & sante`

Constat:

- preuve d'etiquetage admin uniquement
- pas de profondeur metier associee visible dans l'audit

## 4. Qualification exploitable

| Sujet | Qualification |
|---|---|
| `Salisa` | branding / ecosysteme uniquement |
| `Kosunga` | branding / ecosysteme uniquement |
| services a la demande | non prouves comme module metier |
| sante / rendez-vous / teleconsultation | non prouves |
| lien externe / carte ecosysteme | representation correcte aujourd'hui |

## 5. Recommandation produit minimale

### Ce qui est legitime

- afficher `Salisa` et `Kosunga` dans un bloc ecosysteme
- les presenter comme plateformes soeurs / a venir
- les faire pointer vers des liens externes quand les destinations existent

### Ce qu'il ne faut pas faire

- ajouter de routes internes factices
- creer des CTA "Prendre rendez-vous" sans backend reel
- faire croire a une integration technique complete
- brancher des formulaires non soutenus par modele/controleur/table

## Conclusion

Le codebase ne contient pas aujourd'hui de produit metier `services` ou `Kosunga`. La seule representation honnete et non destructive est editoriale: cartes d'ecosysteme, plateformes soeurs, liens externes ou etat "bientot", sans tunnel applicatif interne.
