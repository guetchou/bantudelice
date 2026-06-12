# Incident 2026-03-19 - BantuDelice indisponible

## Resume
Le site public retournait une erreur 500 car l'application Laravel ne pouvait plus joindre la base de donnees attendue par BantuDelice.

## Symptomes constates
- erreur 500 sur la page d'accueil
- erreurs Laravel `SQLSTATE[HY000] [2002] No such file or directory`
- impossible de charger les donnees dynamiques (`restaurants`, `users`, etc.)

## Diagnostic technique
- le vhost Nginx pointe correctement vers `php8.3-fpm`
- la configuration Laravel actuelle pointe vers :
  - `DB_HOST=localhost`
  - `DB_PORT=3306`
  - `DB_DATABASE=bantudelice`
  - `DB_USERNAME=bantudelice`
- les tests PDO ont montre :
  - `localhost:3306` : socket MySQL introuvable
  - `127.0.0.1:3306` : connexion refusee
  - `127.0.0.1:3326` : une base repond, mais il s'agit du conteneur `mosala-db`
- le conteneur `mosala-db` n'a pas le schema attendu par BantuDelice

## Conclusion
La vraie base BantuDelice n'est pas accessible depuis l'application actuelle. Le probleme n'est pas le frontend mais l'absence ou la perte de la configuration de la base transactionnelle d'origine.

## Mesures immediates prises
- mise en place d'un mode degrade sur l'accueil
- protection du layout public contre les appels DB critiques
- maintien d'un site vitrine accessible pendant l'incident

## Actions restantes
1. retrouver les vrais acces DB de BantuDelice
2. ou restaurer un dump SQL valide de BantuDelice
3. realigner `.env` sur la bonne base
4. purger les caches Laravel de config
5. verifier les flux critiques apres restauration
