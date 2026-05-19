#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request


DEFAULT_BASE_URL = "https://bantudelice.cg"


def env(name: str, default: str = "") -> str:
    return os.environ.get(name, default).strip()


def http_request(url: str, method: str = "GET", data: bytes | None = None, headers: dict[str, str] | None = None) -> tuple[int, str]:
    request = urllib.request.Request(url, data=data, headers=headers or {}, method=method)
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            return response.status, response.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as exc:
        return exc.code, exc.read().decode("utf-8", "replace")


def login(base_url: str, path: str, payload: dict[str, str]) -> tuple[int, dict[str, object], str]:
    status, body = http_request(
        base_url + path,
        method="POST",
        data=urllib.parse.urlencode(payload).encode(),
        headers={
            "Accept": "application/json",
            "Content-Type": "application/x-www-form-urlencoded",
        },
    )

    parsed: dict[str, object]
    try:
        parsed = json.loads(body)
    except json.JSONDecodeError:
        parsed = {"raw_body": body}

    token = ""
    if isinstance(parsed, dict):
        token = str(parsed.get("data") or "")
        if token.startswith("Bearer "):
            token = token[7:]

    return status, parsed, token


def truncate_body(body: str, size: int = 320) -> str:
    return body if len(body) <= size else body[:size]


def main() -> int:
    parser = argparse.ArgumentParser(description="Smoke test auth/runtime BantuDelice")
    parser.add_argument("--base-url", default=env("BD_BASE_URL", DEFAULT_BASE_URL))
    parser.add_argument("--unauth-only", action="store_true")
    args = parser.parse_args()

    base_url = args.base_url.rstrip("/")

    unauth_paths = [
        "/api/driver/deliveries",
        "/api/v1/courier/shipments/assigned",
        "/api/v1/transport/driver/nearby",
        "/api/v1/admin/colis/shipments",
        "/api/admin/metrics/realtime",
    ]

    report: dict[str, object] = {
        "base_url": base_url,
        "unauth_checks": {},
        "auth_checks": {},
    }
    failures: list[str] = []

    for path in unauth_paths:
        status, body = http_request(base_url + path, headers={"Accept": "application/json"})
        ok = status == 401
        if not ok:
            failures.append(f"unauth {path}: expected 401, got {status}")
        report["unauth_checks"][path] = {
            "status": status,
            "ok": ok,
            "body": truncate_body(body),
        }

    if args.unauth_only:
        report["auth_checks"] = {"status": "skipped", "reason": "unauth_only"}
        print(json.dumps(report, ensure_ascii=False, indent=2))
        return 1 if failures else 0

    accounts = {
        "user": {
            "path": "/api/login",
            "payload": {
                "phone": env("BD_USER_PHONE"),
                "password": env("BD_USER_PASSWORD"),
            },
        },
        "admin": {
            "path": "/api/login",
            "payload": {
                "phone": env("BD_ADMIN_PHONE"),
                "password": env("BD_ADMIN_PASSWORD"),
            },
        },
        "driver": {
            "path": "/api/driver_login",
            "payload": {
                "phone": env("BD_DRIVER_PHONE"),
                "password": env("BD_DRIVER_PASSWORD"),
            },
        },
    }

    missing_credentials = [
        label
        for label, config in accounts.items()
        if not config["payload"]["phone"] or not config["payload"]["password"]
    ]

    if missing_credentials:
        report["auth_checks"] = {
            "status": "skipped",
            "reason": "missing_credentials",
            "missing": missing_credentials,
        }
        print(json.dumps(report, ensure_ascii=False, indent=2))
        return 1 if failures else 0

    tokens: dict[str, str] = {}
    login_results: dict[str, object] = {}

    for label, config in accounts.items():
        status, parsed, token = login(base_url, config["path"], config["payload"])
        ok = status == 200 and bool(token)
        if not ok:
            failures.append(f"login {label}: expected 200 with token, got {status}")
        tokens[label] = token
        login_results[label] = {
            "status": status,
            "ok": ok,
            "message": parsed.get("message") if isinstance(parsed, dict) else None,
            "token_prefix": token[:12] if token else "",
            "body": parsed if not ok else None,
        }

    probes = [
        ("driver_deliveries", "/api/driver/deliveries", "driver", {200}),
        ("courier_assigned", "/api/v1/courier/shipments/assigned", "driver", {200}),
        ("transport_driver_nearby", "/api/v1/transport/driver/nearby?lat=-4.2634&lng=15.2429", "driver", {200, 422}),
        ("admin_shipments_with_admin", "/api/v1/admin/colis/shipments", "admin", {200}),
        ("admin_metrics_with_admin", "/api/admin/metrics/realtime", "admin", {200}),
        ("admin_metrics_with_user", "/api/admin/metrics/realtime", "user", {403}),
    ]

    probe_results: dict[str, object] = {}
    for label, path, token_label, expected in probes:
        headers = {"Accept": "application/json"}
        token = tokens.get(token_label, "")
        if token:
            headers["Authorization"] = f"Bearer {token}"
        status, body = http_request(base_url + path, headers=headers)
        ok = status in expected
        if not ok:
            failures.append(f"probe {label}: expected {sorted(expected)}, got {status}")
        probe_results[label] = {
            "status": status,
            "ok": ok,
            "expected": sorted(expected),
            "body": truncate_body(body),
        }

    report["auth_checks"] = {
        "logins": login_results,
        "probes": probe_results,
    }

    print(json.dumps(report, ensure_ascii=False, indent=2))
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
