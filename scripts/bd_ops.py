#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import os
import shlex
import subprocess
import sys
from pathlib import Path


ROOT_DIR = Path(__file__).resolve().parent.parent
SCRIPTS_DIR = ROOT_DIR / "scripts"
DEPLOY_SCRIPT = SCRIPTS_DIR / "deploy.sh"
REPAIR_SCRIPT = SCRIPTS_DIR / "repair_auth_runtime.sh"
SMOKE_SCRIPT = SCRIPTS_DIR / "prod_auth_smoke.py"
AUTH_RUNTIME_PATHS = [
    "config/auth.php",
    "routes/api.php",
    "app/Http/Kernel.php",
    "app/Http/Middleware/EnsureUserRole.php",
    "app/Policies/ShipmentPolicy.php",
    "app/Policies/TransportBookingPolicy.php",
    "app/Http/Controllers/Api/DriverDeliveriesController.php",
    "app/Http/Controllers/Api/V1/Admin/AdminShipmentController.php",
    "app/Http/Controllers/Api/V1/Courier/CourierShipmentController.php",
    "app/Http/Controllers/api/Transport/DriverTransportController.php",
    "app/Support/Auth/ActorType.php",
    "app/Support/Auth/AuthenticatedDriverResolver.php",
    "app/Support/Auth/AuthenticatedUserResolver.php",
]
PLAYWRIGHT_FOOD_SPEC = ROOT_DIR / "tests" / "e2e" / "food-production-flow.spec.js"


def env(name: str, default: str = "") -> str:
    return os.environ.get(name, default).strip()


def fail(message: str) -> int:
    print(f"[ERROR] {message}", file=sys.stderr)
    return 1


def run(cmd: list[str], extra_env: dict[str, str] | None = None) -> int:
    merged_env = os.environ.copy()
    if extra_env:
        merged_env.update({key: value for key, value in extra_env.items() if value is not None})

    process = subprocess.run(cmd, cwd=str(ROOT_DIR), env=merged_env)
    return process.returncode


def run_capture(cmd: list[str], extra_env: dict[str, str] | None = None) -> tuple[int, str, str]:
    merged_env = os.environ.copy()
    if extra_env:
        merged_env.update({key: value for key, value in extra_env.items() if value is not None})

    process = subprocess.run(
        cmd,
        cwd=str(ROOT_DIR),
        env=merged_env,
        capture_output=True,
        text=True,
    )
    return process.returncode, process.stdout, process.stderr


def remote_bash_script(target_host: str, script: str) -> list[str]:
    return ["ssh", target_host, f"bash -lc {shlex.quote(script)}"]


def require_file(path: Path) -> None:
    if not path.is_file():
        raise FileNotFoundError(f"Fichier introuvable: {path}")


def add_smoke_credentials(parser: argparse.ArgumentParser) -> None:
    parser.add_argument("--base-url", default=env("BD_BASE_URL", "https://bantudelice.cg"))
    parser.add_argument("--user-phone", default=env("BD_USER_PHONE"))
    parser.add_argument("--user-password", default=env("BD_USER_PASSWORD"))
    parser.add_argument("--admin-phone", default=env("BD_ADMIN_PHONE"))
    parser.add_argument("--admin-password", default=env("BD_ADMIN_PASSWORD"))
    parser.add_argument("--driver-phone", default=env("BD_DRIVER_PHONE"))
    parser.add_argument("--driver-password", default=env("BD_DRIVER_PASSWORD"))


def add_remote_target_options(parser: argparse.ArgumentParser) -> None:
    parser.add_argument("--target-host", default=env("BD_TARGET_HOST", "vps-ovh"))
    parser.add_argument("--target-path", default=env("BD_TARGET_PATH", "/opt/bantudelice"))


def smoke_env_from_args(args: argparse.Namespace) -> dict[str, str]:
    return {
        "BD_BASE_URL": args.base_url,
        "BD_USER_PHONE": args.user_phone,
        "BD_USER_PASSWORD": args.user_password,
        "BD_ADMIN_PHONE": args.admin_phone,
        "BD_ADMIN_PASSWORD": args.admin_password,
        "BD_DRIVER_PHONE": args.driver_phone,
        "BD_DRIVER_PASSWORD": args.driver_password,
    }


def resolve_remote_backup_dir(backup_dir: str) -> str:
    return backup_dir.strip()


def provision_food_fixtures(args: argparse.Namespace) -> dict[str, object]:
    quoted_target_path = shlex.quote(args.target_path)
    quoted_php_bin = shlex.quote(args.php_bin)
    quoted_password = shlex.quote(args.food_e2e_password)
    script = f"""set -euo pipefail
cd {quoted_target_path}
{quoted_php_bin} artisan e2e:provision-food-flow --password={quoted_password} --json
"""
    code, stdout, stderr = run_capture(remote_bash_script(args.target_host, script))
    if code != 0:
        raise RuntimeError(stderr.strip() or stdout.strip() or "Provision food E2E failed")

    payload = stdout.strip()
    start = payload.find("{")
    end = payload.rfind("}")
    if start == -1 or end == -1 or end < start:
        raise RuntimeError(f"Provision food E2E did not return JSON: {payload}")

    return json.loads(payload[start:end + 1])


def command_preflight(args: argparse.Namespace) -> int:
    """Vérifie la connectivité SSH, l'espace disque et la version PHP avant tout déploiement."""
    import shlex as _shlex

    target_host = args.target_host
    target_path = args.target_path
    php_bin = args.php_bin
    min_mb = 500

    script = f"""set -euo pipefail
echo "[PREFLIGHT] SSH OK"
free_mb=$(df -m {_shlex.quote(target_path)} 2>/dev/null | awk 'NR==2{{print $4}}' || echo 0)
if [ "$free_mb" -lt {min_mb} ]; then
  echo "[WARN] Espace disque faible: ${{free_mb}} Mo (seuil {min_mb} Mo)" >&2
else
  echo "[OK] Espace disque: ${{free_mb}} Mo disponibles"
fi
php_ver=$({_shlex.quote(php_bin)} -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "inconnu")
echo "[OK] PHP: $php_ver"
if [ -f {_shlex.quote(target_path + "/artisan")} ]; then
  echo "[OK] artisan présent"
else
  echo "[WARN] artisan absent dans {target_path}" >&2
fi
"""
    return run(remote_bash_script(target_host, script))


def command_deploy(args: argparse.Namespace) -> int:
    require_file(DEPLOY_SCRIPT)

    deploy_env = {
        "RUN_AUTH_SMOKE": "1" if args.run_auth_smoke else "0",
        "DRY_RUN": "1" if args.dry_run else "0",
        "PHP_BIN": args.php_bin,
        "WEB_USER": args.web_user,
    }

    if args.run_auth_smoke:
        deploy_env.update(smoke_env_from_args(args))

    cmd = [str(DEPLOY_SCRIPT), args.target_host, args.target_path]
    return run(cmd, deploy_env)


def command_repair(args: argparse.Namespace) -> int:
    require_file(REPAIR_SCRIPT)
    repair_env = {
        "PHP_BIN": args.php_bin,
        "WEB_USER": args.web_user,
        "PASSPORT_PERSONAL_CLIENT_NAME": args.passport_personal_client_name,
    }
    cmd = [str(REPAIR_SCRIPT), args.app_root]
    return run(cmd, repair_env)


def command_smoke(args: argparse.Namespace) -> int:
    require_file(SMOKE_SCRIPT)
    smoke_env = smoke_env_from_args(args)
    cmd = [sys.executable, str(SMOKE_SCRIPT), "--base-url", args.base_url]
    if args.unauth_only:
        cmd.append("--unauth-only")
    return run(cmd, smoke_env)


def command_backup_auth_runtime(args: argparse.Namespace) -> int:
    quoted_target_path = shlex.quote(args.target_path)
    timestamp = shlex.quote(args.timestamp) if args.timestamp else '"${BACKUP_TIMESTAMP:-$(date +%Y%m%d-%H%M%S)}"'
    backup_prefix = shlex.quote(args.backup_prefix)
    script_lines = [
        "set -euo pipefail",
        f"cd {quoted_target_path}",
        f"timestamp={timestamp}",
        f'backup_dir="{backup_prefix}-$timestamp"',
        'if [[ "${backup_dir#/}" == "$backup_dir" ]]; then backup_path="$(pwd)/$backup_dir"; else backup_path="$backup_dir"; fi',
        'mkdir -p "$backup_path"',
        'printf "[INFO] Backup dir: %s\\n" "$backup_path"',
    ]

    for relative_path in AUTH_RUNTIME_PATHS:
        quoted_relative_path = shlex.quote(relative_path)
        script_lines.extend(
            [
                f'if [[ -e {quoted_relative_path} ]]; then',
                f'  mkdir -p "$backup_path/$(dirname {quoted_relative_path})"',
                f'  cp -a {quoted_relative_path} "$backup_path/{relative_path}"',
                f'  printf "[OK] %s\\n" {quoted_relative_path}',
                "else",
                f'  printf "[WARN] absent: %s\\n" {quoted_relative_path}',
                "fi",
            ]
        )

    script_lines.append('printf "BACKUP_DIR=%s\\n" "$backup_path"')
    return run(remote_bash_script(args.target_host, "\n".join(script_lines)))


def command_diff_auth_runtime(args: argparse.Namespace) -> int:
    backup_dir = resolve_remote_backup_dir(args.backup_dir)
    quoted_target_path = shlex.quote(args.target_path)
    quoted_backup_dir = shlex.quote(backup_dir)
    script_lines = [
        "set -euo pipefail",
        f"cd {quoted_target_path}",
        f"backup_dir={quoted_backup_dir}",
        'if [[ "${backup_dir#/}" == "$backup_dir" ]]; then backup_dir="$(pwd)/$backup_dir"; fi',
        '[[ -d "$backup_dir" ]] || { printf "[ERROR] Backup introuvable: %s\\n" "$backup_dir" >&2; exit 1; }',
        'printf "[INFO] Diff against %s\\n" "$backup_dir"',
    ]

    for relative_path in AUTH_RUNTIME_PATHS:
        quoted_relative_path = shlex.quote(relative_path)
        script_lines.extend(
            [
                f'printf "\\n=== %s ===\\n" {quoted_relative_path}',
                f'if [[ ! -e "$backup_dir/{relative_path}" ]]; then',
                '  printf "[STATUS] missing_in_backup\\n"',
                f'elif [[ ! -e {quoted_relative_path} ]]; then',
                '  printf "[STATUS] missing_in_current\\n"',
                f'elif cmp -s "$backup_dir/{relative_path}" {quoted_relative_path}; then',
                '  printf "[STATUS] identical\\n"',
                'else',
                '  printf "[STATUS] changed\\n"',
                f'  diff -u "$backup_dir/{relative_path}" {quoted_relative_path} || true',
                'fi',
            ]
        )

    return run(remote_bash_script(args.target_host, "\n".join(script_lines)))


def command_restore_auth_runtime(args: argparse.Namespace) -> int:
    if not args.yes and not args.dry_run:
        return fail("restore-auth-runtime exige --yes")

    require_file(REPAIR_SCRIPT)

    backup_dir = resolve_remote_backup_dir(args.backup_dir)
    quoted_target_path = shlex.quote(args.target_path)
    quoted_backup_dir = shlex.quote(backup_dir)
    script_lines = [
        "set -euo pipefail",
        f"cd {quoted_target_path}",
        f"backup_dir={quoted_backup_dir}",
        'if [[ "${backup_dir#/}" == "$backup_dir" ]]; then backup_dir="$(pwd)/$backup_dir"; fi',
        '[[ -d "$backup_dir" ]] || { printf "[ERROR] Backup introuvable: %s\\n" "$backup_dir" >&2; exit 1; }',
        'printf "[INFO] Restore from %s\\n" "$backup_dir"',
        f'dry_run={"1" if args.dry_run else "0"}',
    ]

    for relative_path in AUTH_RUNTIME_PATHS:
        quoted_relative_path = shlex.quote(relative_path)
        script_lines.extend(
            [
                f'if [[ -e "$backup_dir/{relative_path}" ]]; then',
                '  if [[ "$dry_run" == "1" ]]; then',
                f'    printf "[DRY-RUN] would restore %s\\n" {quoted_relative_path}',
                '  else',
                f'    mkdir -p "$(dirname {quoted_relative_path})"',
                f'    cp -a "$backup_dir/{relative_path}" {quoted_relative_path}',
                f'    printf "[OK] restored %s\\n" {quoted_relative_path}',
                '  fi',
                "else",
                f'  printf "[WARN] backup missing for %s\\n" {quoted_relative_path}',
                "fi",
            ]
        )

    if args.run_repair and not args.dry_run:
        quoted_php_bin = shlex.quote(args.php_bin)
        quoted_web_user = shlex.quote(args.web_user)
        quoted_target_path_for_repair = shlex.quote(args.target_path)
        script_lines.extend(
            [
                '[ -f "scripts/repair_auth_runtime.sh" ] || { printf "[ERROR] repair_auth_runtime.sh introuvable\\n" >&2; exit 1; }',
                f'PHP_BIN={quoted_php_bin} WEB_USER={quoted_web_user} bash scripts/repair_auth_runtime.sh {quoted_target_path_for_repair}',
            ]
        )

    return run(remote_bash_script(args.target_host, "\n".join(script_lines)))


def command_verify_runtime(args: argparse.Namespace) -> int:
    quoted_target_path = shlex.quote(args.target_path)
    quoted_web_user = shlex.quote(args.web_user)
    quoted_php_bin = shlex.quote(args.php_bin)
    script = f"""set -euo pipefail
cd {quoted_target_path}
oauth_json=$({quoted_php_bin} <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();
echo json_encode([
    'oauth_clients' => Illuminate\\Support\\Facades\\DB::table('oauth_clients')->count(),
    'oauth_personal_access_clients' => Illuminate\\Support\\Facades\\DB::table('oauth_personal_access_clients')->count(),
    'oauth_access_tokens' => Illuminate\\Support\\Facades\\DB::table('oauth_access_tokens')->count(),
], JSON_UNESCAPED_SLASHES), PHP_EOL;
PHP
)
private_key_present=false
public_key_present=false
[[ -f storage/oauth-private.key ]] && private_key_present=true
[[ -f storage/oauth-public.key ]] && public_key_present=true
cache_permission_drift=0
if [[ -d storage/framework/cache/data ]]; then
  cache_permission_drift=$(find storage/framework/cache/data \\( ! -user {quoted_web_user} -o ! -group {quoted_web_user} \\) -print | wc -l)
fi
printf 'OAUTH=%s\\n' "$oauth_json"
printf 'PASSPORT_PRIVATE_KEY=%s\\n' "$private_key_present"
printf 'PASSPORT_PUBLIC_KEY=%s\\n' "$public_key_present"
printf 'CACHE_PERMISSION_DRIFT=%s\\n' "$cache_permission_drift"
"""
    return run(remote_bash_script(args.target_host, script))


def command_provision_food_e2e(args: argparse.Namespace) -> int:
    try:
        fixtures = provision_food_fixtures(args)
    except RuntimeError as exc:
        return fail(str(exc))

    print(json.dumps(fixtures, ensure_ascii=False, indent=2))
    return 0


def command_food_e2e(args: argparse.Namespace) -> int:
    require_file(PLAYWRIGHT_FOOD_SPEC)

    try:
        fixtures = provision_food_fixtures(args)
    except RuntimeError as exc:
        return fail(str(exc))

    credentials = fixtures.get("credentials", {})
    artifacts = fixtures.get("artifacts", {})

    food_env = {
        "BD_BASE_URL": args.base_url,
        "BD_FOOD_CLIENT_EMAIL": str(((credentials.get("customer") or {}).get("email")) or ""),
        "BD_FOOD_RESTAURANT_EMAIL": str(((credentials.get("restaurant") or {}).get("email")) or ""),
        "BD_FOOD_DRIVER_EMAIL": str(((credentials.get("driver") or {}).get("email")) or ""),
        "BD_FOOD_SHARED_PASSWORD": args.food_e2e_password,
        "BD_FOOD_RESTAURANT_ID": str(artifacts.get("restaurant_id") or ""),
        "BD_FOOD_PRODUCT_ID": str(artifacts.get("product_id") or ""),
    }

    missing = [key for key, value in food_env.items() if key != "BD_BASE_URL" and not value]
    if missing:
        return fail(f"Fixtures food E2E incomplètes: {', '.join(missing)}")

    cmd = [
        "npx",
        "playwright",
        "test",
        str(PLAYWRIGHT_FOOD_SPEC),
        "--project",
        args.project,
        "--retries=0",
        "--reporter=line",
    ]
    return run(cmd, food_env)


def command_rollback_auth_runtime(args: argparse.Namespace) -> int:
    if args.dry_run:
        restore_args = argparse.Namespace(
            target_host=args.target_host,
            target_path=args.target_path,
            backup_dir=args.backup_dir,
            php_bin=args.php_bin,
            web_user=args.web_user,
            run_repair=False,
            dry_run=True,
            yes=False,
        )
        return command_restore_auth_runtime(restore_args)

    if not args.yes:
        return fail("rollback-auth-runtime exige --yes")

    restore_args = argparse.Namespace(
        target_host=args.target_host,
        target_path=args.target_path,
        backup_dir=args.backup_dir,
        php_bin=args.php_bin,
        web_user=args.web_user,
        run_repair=True,
        dry_run=False,
        yes=True,
    )
    restore_code = command_restore_auth_runtime(restore_args)
    if restore_code != 0:
        return restore_code

    verify_args = argparse.Namespace(
        target_host=args.target_host,
        target_path=args.target_path,
        php_bin=args.php_bin,
        web_user=args.web_user,
    )
    verify_code = command_verify_runtime(verify_args)
    if verify_code != 0:
        return verify_code

    if args.run_auth_smoke:
        smoke_args = argparse.Namespace(
            base_url=args.base_url,
            user_phone=args.user_phone,
            user_password=args.user_password,
            admin_phone=args.admin_phone,
            admin_password=args.admin_password,
            driver_phone=args.driver_phone,
            driver_password=args.driver_password,
            unauth_only=False,
        )
        return command_smoke(smoke_args)

    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="CLI ops BantuDelice pour deploy, backup/diff/restore et vérifications auth/runtime."
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    preflight_parser = subparsers.add_parser(
        "preflight",
        help="Vérifier la connectivité SSH, l'espace disque et PHP avant de déployer",
    )
    add_remote_target_options(preflight_parser)
    preflight_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    preflight_parser.set_defaults(func=command_preflight)

    deploy_parser = subparsers.add_parser("deploy", help="Déployer sur une cible distante")
    add_remote_target_options(deploy_parser)
    deploy_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    deploy_parser.add_argument("--web-user", default=env("WEB_USER", "www-data"))
    deploy_parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Simuler le rsync sans écrire sur le serveur",
    )
    deploy_parser.add_argument(
        "--run-auth-smoke",
        action="store_true",
        help="Exécuter le smoke test auth/runtime après le déploiement",
    )
    add_smoke_credentials(deploy_parser)
    deploy_parser.set_defaults(func=command_deploy)

    repair_parser = subparsers.add_parser("repair-auth-runtime", help="Réparer Passport et le runtime Laravel")
    repair_parser.add_argument("--app-root", default=env("BD_APP_ROOT", str(ROOT_DIR)))
    repair_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    repair_parser.add_argument("--web-user", default=env("WEB_USER", "www-data"))
    repair_parser.add_argument(
        "--passport-personal-client-name",
        default=env("PASSPORT_PERSONAL_CLIENT_NAME", "BantuDelice Personal Access Client"),
    )
    repair_parser.set_defaults(func=command_repair)

    smoke_parser = subparsers.add_parser("smoke-auth", help="Vérifier les routes protégées et les logins API")
    smoke_parser.add_argument("--unauth-only", action="store_true")
    add_smoke_credentials(smoke_parser)
    smoke_parser.set_defaults(func=command_smoke)

    backup_parser = subparsers.add_parser("backup-auth-runtime", help="Sauvegarder les fichiers runtime auth côté distant")
    add_remote_target_options(backup_parser)
    backup_parser.add_argument("--timestamp", default=env("BACKUP_TIMESTAMP"))
    backup_parser.add_argument("--backup-prefix", default=env("BD_BACKUP_PREFIX", ".codex-backups/auth-runtime"))
    backup_parser.set_defaults(func=command_backup_auth_runtime)

    diff_parser = subparsers.add_parser("diff-auth-runtime", help="Comparer le runtime courant avec un backup distant")
    add_remote_target_options(diff_parser)
    diff_parser.add_argument("--backup-dir", required=True)
    diff_parser.set_defaults(func=command_diff_auth_runtime)

    restore_parser = subparsers.add_parser("restore-auth-runtime", help="Restaurer les fichiers runtime auth depuis un backup distant")
    add_remote_target_options(restore_parser)
    restore_parser.add_argument("--backup-dir", required=True)
    restore_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    restore_parser.add_argument("--web-user", default=env("WEB_USER", "www-data"))
    restore_parser.add_argument("--run-repair", action="store_true", help="Relancer repair_auth_runtime.sh après restauration")
    restore_parser.add_argument("--dry-run", action="store_true", help="Afficher ce qui serait restauré sans rien écrire")
    restore_parser.add_argument("--yes", action="store_true", help="Confirmer explicitement la restauration")
    restore_parser.set_defaults(func=command_restore_auth_runtime)

    verify_parser = subparsers.add_parser("verify-runtime", help="Vérifier Passport, clés et permissions runtime côté distant")
    add_remote_target_options(verify_parser)
    verify_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    verify_parser.add_argument("--web-user", default=env("WEB_USER", "www-data"))
    verify_parser.set_defaults(func=command_verify_runtime)

    provision_food_parser = subparsers.add_parser(
        "provision-food-e2e",
        help="Provisionner les fixtures food E2E cote distant et retourner les identifiants/credentials",
    )
    add_remote_target_options(provision_food_parser)
    provision_food_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    provision_food_parser.add_argument(
        "--food-e2e-password",
        default=env("BD_FOOD_E2E_PASSWORD", "BdE2E!Food2026"),
    )
    provision_food_parser.set_defaults(func=command_provision_food_e2e)

    food_e2e_parser = subparsers.add_parser(
        "food-e2e",
        help="Provisionner les fixtures food E2E puis lancer le scenario Playwright bout en bout",
    )
    add_remote_target_options(food_e2e_parser)
    food_e2e_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    food_e2e_parser.add_argument("--base-url", default=env("BD_BASE_URL", "https://bantudelice.cg"))
    food_e2e_parser.add_argument(
        "--food-e2e-password",
        default=env("BD_FOOD_E2E_PASSWORD", "BdE2E!Food2026"),
    )
    food_e2e_parser.add_argument("--project", default=env("BD_PLAYWRIGHT_PROJECT", "desktop-chromium"))
    food_e2e_parser.set_defaults(func=command_food_e2e)

    rollback_parser = subparsers.add_parser(
        "rollback-auth-runtime",
        help="Restaurer un backup auth/runtime, réparer le runtime, puis vérifier l'état distant",
    )
    add_remote_target_options(rollback_parser)
    rollback_parser.add_argument("--backup-dir", required=True)
    rollback_parser.add_argument("--php-bin", default=env("PHP_BIN", "/usr/bin/php"))
    rollback_parser.add_argument("--web-user", default=env("WEB_USER", "www-data"))
    rollback_parser.add_argument("--dry-run", action="store_true", help="Afficher les fichiers qui seraient restaurés")
    rollback_parser.add_argument(
        "--run-auth-smoke",
        action="store_true",
        help="Lancer le smoke test auth après restauration et réparation",
    )
    rollback_parser.add_argument("--yes", action="store_true", help="Confirmer explicitement le rollback réel")
    add_smoke_credentials(rollback_parser)
    rollback_parser.set_defaults(func=command_rollback_auth_runtime)

    return parser


def main() -> int:
    try:
        parser = build_parser()
        args = parser.parse_args()
        return args.func(args)
    except FileNotFoundError as exc:
        return fail(str(exc))
    except KeyboardInterrupt:
        return fail("Interrompu")


if __name__ == "__main__":
    sys.exit(main())
