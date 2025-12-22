# Résolution de l'erreur HTTP 500 - TheDrop247

## Date : 2025-12-02

## Problèmes identifiés et résolus

### 1. ✅ Configuration Nginx/PHP-FPM
- **Problème** : Mapping incorrect des chemins entre Docker (Nginx) et l'hôte (PHP-FPM)
- **Solution** : Correction des paramètres FastCGI dans `/opt/nginx-docker/config/conf.d/bantudelice.conf`
  - `SCRIPT_FILENAME` pointant vers `/opt/thedrop247/public/index.php`
  - `DOCUMENT_ROOT` configuré sur `/opt/thedrop247/public`
  - Handler spécifique pour `/index.php` après `try_files`

### 2. ✅ Permissions SELinux
- **Problème** : Nginx Docker ne pouvait pas accéder au socket PHP-FPM
- **Solution** : Configuration SELinux pour permettre l'accès au socket
  - `setsebool -P httpd_can_network_connect_anon_apps 1`
  - Configuration du contexte pour `/run/php-fpm`

### 3. ✅ Routes en conflit
- **Problème** : Route dupliquée avec le nom "index"
- **Solution** : Commentaire de `Route::get('/', 'HomeController@index')->name('index');` dans `routes/web.php`

### 4. ✅ Namespace Delivery incorrect
- **Problème** : Namespace `'Delivery'` (majuscule) ne correspondait pas au dossier `delivery` (minuscule)
- **Solution** : Correction dans `routes/web.php` ligne 161 : `'namespace' => 'delivery'`

### 5. ✅ Permissions des répertoires Laravel
- **Problème** : Répertoires `storage` et `bootstrap/cache` non accessibles en écriture
- **Solution** : 
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R apache:apache storage bootstrap/cache
  ```

### 6. ✅ Contexte SELinux
- **Problème** : SELinux bloquait l'écriture dans `storage` et `bootstrap/cache`
- **Solution** : Configuration du contexte SELinux approprié
  ```bash
  chcon -R -t httpd_sys_rw_content_t /opt/thedrop247/storage /opt/thedrop247/bootstrap/cache
  semanage fcontext -a -t httpd_sys_rw_content_t "/opt/thedrop247/storage(/.*)?"
  semanage fcontext -a -t httpd_sys_rw_content_t "/opt/thedrop247/bootstrap/cache(/.*)?"
  restorecon -Rv /opt/thedrop247/storage /opt/thedrop247/bootstrap/cache
  ```

### 7. ✅ Permissions fichier .env
- **Problème** : PHP-FPM (utilisateur apache) ne pouvait pas lire le fichier `.env`
- **Solution** : 
  ```bash
  chmod 644 /opt/thedrop247/.env
  chown apache:apache /opt/thedrop247/.env
  ```
  
### Tests effectués

1. ✅ PHP-FPM fonctionne correctement (test avec phpinfo)
2. ✅ Laravel fonctionne en CLI (`php artisan` fonctionne)
3. ✅ Index.php est accessible et exécutable
4. ✅ Permissions corrigées sur storage et bootstrap/cache
5. ✅ Configuration Nginx validée
6. ✅ Connexion base de données validée
7. ✅ Site accessible via HTTPS (code 200)

## 🎉 RÉSOLUTION COMPLÈTE - Site en ligne !

Le site **TheDrop247** est maintenant accessible à l'adresse :
- **URL** : https://dev.bantudelice.cg
- **Status** : HTTP 200 OK
- **Date de résolution** : 2025-12-02 14:30 UTC

## Configuration finale

### Nginx Configuration
- **Fichier** : `/opt/nginx-docker/config/conf.d/bantudelice.conf`
- **Socket PHP-FPM** : `unix:/var/run/php-fpm/www.sock`
- **Root Laravel** : `/opt/thedrop247/public`

### PHP-FPM Configuration
- **Utilisateur** : apache
- **Socket** : `/run/php-fpm/www.sock`
- **ACL** : apache, nginx

### Permissions
- **Storage** : `apache:apache` avec permissions `775`
- **Bootstrap/cache** : `apache:apache` avec permissions `775`

### Routes corrigées
- Route `/` commentée pour éviter le conflit de nom "index"
- Namespace Delivery corrigé en minuscules

## Notes importantes

- Le site est accessible sur : https://dev.bantudelice.cg
- L'erreur 500 nécessite une investigation plus approfondie des logs Laravel
- Les caches ont été nettoyés : route, config, application, vue
- Les permissions ont été corrigées sur tous les répertoires critiques

