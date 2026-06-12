# Comptes démo BantuDelice

## Situation des bases de données

Le VPS héberge **deux bases MySQL** dans le container `bantudelice-db-new` (port 3336) :

| Base | Contenu | Usage |
|---|---|---|
| `bantudelice` | Vide — tables créées le 17/05/2026 | Base cible pour la future prod réelle |
| `bantudelice_repro` | 10 users, 8 restaurants, 10 livreurs | **Base de démonstration active** |

**Le `.env` doit pointer vers `bantudelice_repro` tant qu'il n'y a pas de clients réels.**

Le site est en phase pré-lancement — pas encore de clients réels. `bantudelice_repro` est
la base de démonstration qui permet de tester tous les flux.

---

## Comptes de démonstration actifs (base `bantudelice_repro`)

### Admin
| Rôle | Email | Téléphone | Mot de passe |
|---|---|---|---|
| Admin | `admin@bantudelice.cg` | `+242 06 000 00 00` | `BantuDemo2026!` |

### Client
| Nom | Email | Téléphone | Mot de passe |
|---|---|---|---|
| Client Test BantuDelice | `client@bantudelice.cg` | `+242 06 500 00 01` | `BantuDemo2026!` |

### Restaurants (8)
| Nom | Email | Téléphone |
|---|---|---|
| Mami Wata Restaurant | `mamiwata@bantudelice.cg` | `+242 06 600 00 01` |
| Chez Gaspard | `chezgaspard@bantudelice.cg` | `+242 06 600 00 02` |
| Le Hippopotame | `hippopotame@bantudelice.cg` | `+242 06 600 00 03` |
| Pili Pili | `pilipili@bantudelice.cg` | `+242 06 600 00 04` |
| La Mandarine | `mandarine@bantudelice.cg` | `+242 06 600 00 05` |
| Nganda Ya Mboka | `nganda@bantudelice.cg` | `+242 06 600 00 06` |
| Le Pescador | `pescador@bantudelice.cg` | `+242 06 600 00 07` |
| Espace Malebo | `malebo@bantudelice.cg` | `+242 06 600 00 08` |

Mot de passe commun : `BantuDemo2026!`

### Livreurs (10)
| Nom | Email | Téléphone |
|---|---|---|
| Jean-Paul Mboumba | `jean-paul.mboumba@bantudelice.cg` | `+242 06 700 00 01` |
| Patrick Ndoudi | `patrick.ndoudi@bantudelice.cg` | `+242 06 700 00 02` |
| Serge Makaya | `serge.makaya@bantudelice.cg` | `+242 06 700 00 03` |
| Alain Mouanda | `alain.mouanda@bantudelice.cg` | `+242 06 700 00 04` |
| David Malonga | `david.malonga@bantudelice.cg` | `+242 06 700 00 05` |
| Christian Nkoua | `christian.nkoua@bantudelice.cg` | `+242 06 700 00 06` |
| Fabrice Okemba | `fabrice.okemba@bantudelice.cg` | `+242 06 700 00 07` |
| Rodrigue Mbemba | `rodrigue.mbemba@bantudelice.cg` | `+242 06 700 00 08` |
| Hervé Ngoma | `hervé.ngoma@bantudelice.cg` | `+242 06 700 00 09` |
| Thierry Bakala | `thierry.bakala@bantudelice.cg` | `+242 06 700 00 10` |

Mot de passe commun : `BantuDemo2026!`
Authentification livreur : `POST /api/driver_login`

---

## Réinitialiser les comptes démo

```bash
ssh vps-ovh "cd /opt/bantudelice && php artisan demo:provision-accounts"
```

---

## Vérifier que Passport est initialisé

```bash
ssh vps-ovh "php /opt/bantudelice/artisan tinker --execute=\"
echo json_encode([
  'oauth_clients' => DB::table('oauth_clients')->count(),
  'oauth_personal' => DB::table('oauth_personal_access_clients')->count(),
]);
\""
```

Si `oauth_clients = 0`, initialiser :

```bash
ssh vps-ovh "cd /opt/bantudelice && php artisan passport:client --personal --name='BantuDelice Personal Access Client' --no-interaction"
```

---

## Passer en prod réelle (futur)

Quand de vrais clients s'inscrivent :
1. Changer `DB_DATABASE=bantudelice_repro` → `DB_DATABASE=bantudelice` dans `/opt/bantudelice/.env`
2. Lancer `php artisan migrate` sur la base `bantudelice`
3. Initialiser Passport sur la nouvelle base
4. Ne plus utiliser `bantudelice_repro` (la conserver en archive)
