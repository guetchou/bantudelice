const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const DEFAULT_ROUTE_FILE = '/tmp/bd_routes_global_audit.json';
const DEFAULT_REMOTE_ROUTE_CMD = "ssh vps-ovh 'cd /opt/bantudelice && php artisan route:list --json'";
const DEFAULT_RSYNC_EXCLUDES = [
  '.env',
  'vendor',
  'node_modules',
  'storage',
  'bootstrap/cache',
  'public/storage',
];

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: PROJECT_ROOT,
    encoding: 'utf8',
    env: { ...process.env, ...(options.env || {}) },
  });

  return {
    command: [command, ...args].join(' '),
    status: result.status ?? 1,
    stdout: result.stdout || '',
    stderr: result.stderr || '',
  };
}

function ensureRouteFile() {
  const routeFile = process.env.BD_ROUTE_LIST_JSON || DEFAULT_ROUTE_FILE;
  if (fs.existsSync(routeFile)) {
    return routeFile;
  }

  const phpCheck = run('bash', ['-lc', 'command -v php >/dev/null 2>&1']);
  const routeCommand = phpCheck.status === 0
    ? `php artisan route:list --json > ${routeFile}`
    : `${process.env.BD_ROUTE_LIST_CMD || DEFAULT_REMOTE_ROUTE_CMD} > ${routeFile}`;

  const fetchResult = run('bash', ['-lc', routeCommand]);
  if (fetchResult.status !== 0) {
    throw new Error(`Unable to build route list.\n${fetchResult.stderr || fetchResult.stdout}`);
  }

  return routeFile;
}

function loadJsonIfPossible(text) {
  try {
    return JSON.parse((text || '').trim());
  } catch (error) {
    return null;
  }
}

function collectBackups(root) {
  const backupFiles = [];

  function walk(target) {
    const entries = fs.readdirSync(target, { withFileTypes: true });
    entries.forEach((entry) => {
      const fullPath = path.join(target, entry.name);
      if (entry.isDirectory()) {
        walk(fullPath);
        return;
      }

      if (/\.(bak(?:-.+)?)$|\.old$|\.orig$/.test(entry.name)) {
        backupFiles.push(path.relative(root, fullPath));
      }
    });
  }

  walk(root);
  return backupFiles.sort();
}

function summarizeCoverage(root) {
  const coverageTargets = [
    {
      key: 'cms',
      label: 'CMS create/update/delete',
      patterns: [/cms/i, /destroy|delete|remove/i],
    },
    {
      key: 'payouts',
      label: 'Payouts',
      patterns: [/payout|restaurant_pay|driver_pay/i],
    },
    {
      key: 'incidents',
      label: 'Incidents and redelivery',
      patterns: [/incident|redelivery/i],
    },
    {
      key: 'multiRole',
      label: 'Multi-role admin/restaurant/delivery',
      patterns: [/admin/i, /restaurant/i, /delivery|driver/i],
    },
    {
      key: 'mobile',
      label: 'Mobile/responsive',
      patterns: [/viewport|devices|iphone|android|pixel|ismobile/i],
    },
  ];

  const files = [];
  const testsRoot = path.join(root, 'tests');

  function walk(target) {
    const entries = fs.readdirSync(target, { withFileTypes: true });
    entries.forEach((entry) => {
      const fullPath = path.join(target, entry.name);
      if (entry.isDirectory()) {
        walk(fullPath);
        return;
      }

      if (/\.(php|js)$/.test(entry.name)) {
        files.push(fullPath);
      }
    });
  }

  walk(testsRoot);

  return coverageTargets.map((target) => {
    const matches = [];
    files.forEach((file) => {
      const content = fs.readFileSync(file, 'utf8');
      const allPatternsMatch = target.patterns.every((pattern) => pattern.test(content));
      if (allPatternsMatch) {
        matches.push(path.relative(root, file));
      }
    });

    return {
      key: target.key,
      label: target.label,
      fileCount: matches.length,
      files: matches.slice(0, 10),
    };
  });
}

function summarizeLayoutDrift(root) {
  const layoutPath = path.join(root, 'resources/views/layouts/app.blade.php');
  const legacyPath = path.join(root, 'resources/views/app.blade.php');

  if (!fs.existsSync(layoutPath) || !fs.existsSync(legacyPath)) {
    return { present: false };
  }

  const layoutContent = fs.readFileSync(layoutPath, 'utf8');
  const legacyContent = fs.readFileSync(legacyPath, 'utf8');
  const trimmedLegacy = legacyContent.trim();
  const shimTargets = new Set([
    "@extends('layouts.app')",
    '@extends("layouts.app")',
  ]);

  return {
    present: true,
    identical: layoutContent === legacyContent,
    isShim: shimTargets.has(trimmedLegacy),
    layoutUsedByViews: true,
  };
}

function summarizeRsyncDrift() {
  const target = process.env.BD_RSYNC_TARGET;
  if (!target) {
    return { enabled: false };
  }

  const excludeArgs = DEFAULT_RSYNC_EXCLUDES.flatMap((value) => ['--exclude', value]);
  const result = run('rsync', [
    '-azn',
    '--delete',
    '--itemize-changes',
    ...excludeArgs,
    `${PROJECT_ROOT}/`,
    target,
  ]);

  const lines = result.stdout
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  return {
    enabled: true,
    status: result.status,
    changeCount: lines.length,
    sample: lines.slice(0, 40),
    stderr: result.stderr.trim(),
  };
}

function main() {
  const routeFile = ensureRouteFile();
  const auditEnv = { BD_ROUTE_LIST_JSON: routeFile };
  const auditScripts = [
    { key: 'views', file: path.join(PROJECT_ROOT, 'scripts/audit_view_routes.js') },
    { key: 'permissions', file: path.join(PROJECT_ROOT, 'scripts/audit_role_access.js') },
    { key: 'modules', file: path.join(PROJECT_ROOT, 'scripts/audit_module_guards.js') },
    { key: 'forms', file: path.join(PROJECT_ROOT, 'scripts/audit_form_actions.js') },
  ];

  const audits = auditScripts.map((audit) => {
    const result = run('bash', ['-lc', `node ${audit.file}`], { env: auditEnv });
    return {
      key: audit.key,
      status: result.status,
      report: loadJsonIfPossible(result.stdout),
      stdout: result.stdout.trim(),
      stderr: result.stderr.trim(),
    };
  });

  const report = {
    generatedAt: new Date().toISOString(),
    routeFile,
    audits,
    backups: collectBackups(PROJECT_ROOT),
    layoutDrift: summarizeLayoutDrift(PROJECT_ROOT),
    testCoverageSignals: summarizeCoverage(PROJECT_ROOT),
    rsyncDrift: summarizeRsyncDrift(),
  };

  console.log(JSON.stringify(report, null, 2));

  const hasAuditFailure = audits.some((audit) => audit.status !== 0);
  if (hasAuditFailure) {
    process.exit(1);
  }
}

main();
