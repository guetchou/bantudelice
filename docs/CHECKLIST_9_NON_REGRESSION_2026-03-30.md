# 9. Checklist de non-regression

Date: 2026-03-30
Base verifiee: `https://bantudelice.cg` et `vps-ovh:/opt/bantudelice`

## Resultat

- Home publique recharge en `200` apres correction des permissions Laravel sur `storage` et `bootstrap/cache`.
- Routes publiques critiques verifiees en live:
  - `/` -> `200`
  - `/restaurants` -> `200`
  - `/track-order` -> `200` puis redirection login
  - `/livraison-colis` -> `200`
  - `/suivi-colis` -> `200`
  - `/transport/taxi` -> `200`
  - `/cart` -> `200`
  - `/checkout` -> `200` puis redirection login
  - `/admin` -> `200` puis redirection login
  - `/driver` -> `200` vers inscription livreur
- Syntaxe PHP de `config/sites.php` validee sur le VPS.
- Recherche residuelle confirmee sur le code deploye:
  - `resources/views/frontend/layouts/app-modern.blade.php`
  - `resources/views/frontend/legal_notices.blade.php`
  - `resources/views/frontend/colis/landing.blade.php`
  - `resources/lang/fr/ui.php`
  - `app/Services/CmsStaticPageService.php`

## Points valides

- La homepage charge.
- Le header desktop est visible et rend les entrees `Restaurants`, `Offres`, `Nos Plateformes`, `Partenaires`.
- Le footer home est visible dans le rendu navigateur.
- Le menu mobile est accessible via le bouton burger et expose `Restaurants`, `Offres`, `Nos Plateformes`, `Partenaires`, `Connexion`.
- Le listing restaurants est accessible.
- Le panier est accessible.
- Le checkout reste joignable via redirection d'authentification.
- Les routes taxi et colis restent accessibles.
- Aucune erreur de syntaxe PHP detectee sur la config des plateformes.
- Aucune variable de vue manquante sur les pages testees en live apres correction permissions.

## Points encore ouverts

- La preuve live d'une fiche restaurant concrete et d'une fiche produit concrete n'est pas encore capturee.
- Le lien partenaire reste a surveiller seulement comme route d'inscription, meme si la home live expose bien `/partner/registration`.
- La verticale colis garde naturellement son wording colis sur sa propre landing, ce qui est normal hors home food.

## Incident critique constate

- Rechute du site en `500` sur toutes les pages pendant la verification.
- Cause reobservee sur le VPS:
  - `/opt/bantudelice/storage` en `root:root 700`
  - `/opt/bantudelice/bootstrap/cache` en `root:root 700`
- Correction reappliquee:
  - `chown -R www-data:www-data /opt/bantudelice/storage /opt/bantudelice/bootstrap/cache`
  - permissions dossiers `775`
  - permissions fichiers `664`

## Conclusion

- Non-regression serveur et routes publiques critiques: globalement validee.
- Non-regression header/footer/menu mobile/desktop: validee apres verification navigateur le `2026-03-31`.
- Non-regression UI/branding final: validee sur les points publics principaux.
- Passage a l'etape suivante: possible sans nouvelle passe UI publique immediate.
