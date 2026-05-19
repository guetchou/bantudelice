const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const VIEW_TARGETS = [
  path.join(PROJECT_ROOT, 'resources/views/frontend'),
  path.join(PROJECT_ROOT, 'resources/views/admin'),
  path.join(PROJECT_ROOT, 'resources/views/layouts/app.blade.php'),
  path.join(PROJECT_ROOT, 'resources/views/app.blade.php'),
];

const DEFAULT_ROUTE_LIST_CMD = "ssh vps-ovh 'cd /opt/bantudelice && php artisan route:list --json'";
const ROUTE_REGEX = /route\(\s*['"]([^'"]+)['"]/g;
const HASH_ALLOW_PATTERNS = [
  'data-widget="pushmenu"',
  'data-toggle="modal"',
  'data-toggle="dropdown"',
  'nav-dropdown-toggle',
  'id="backToTop"',
  'dropdown-toggle',
];

function walk(targetPath) {
  const stats = fs.statSync(targetPath);
  if (stats.isFile()) {
    return [targetPath];
  }

  return fs.readdirSync(targetPath, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(targetPath, entry.name);
    if (entry.isDirectory()) {
      return walk(fullPath);
    }

    return entry.name.endsWith('.blade.php') ? [fullPath] : [];
  });
}

function relative(file) {
  return path.relative(PROJECT_ROOT, file);
}

function extractRouteNames(content) {
  const normalizedContent = content
    .replace(/{{--[\s\S]*?--}}/g, '')
    .replace(/<!--[\s\S]*?-->/g, '');
  const matches = new Set();
  for (const match of normalizedContent.matchAll(ROUTE_REGEX)) {
    matches.add(match[1]);
  }

  return [...matches].sort();
}

function loadRoutes() {
  if (process.env.BD_ROUTE_LIST_JSON) {
    return JSON.parse(fs.readFileSync(process.env.BD_ROUTE_LIST_JSON, 'utf8'));
  }

  const command = process.env.BD_ROUTE_LIST_CMD || DEFAULT_ROUTE_LIST_CMD;
  const output = execSync(command, {
    cwd: PROJECT_ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'inherit'],
  });

  return JSON.parse(output);
}

function auditHashes(file, content) {
  const issues = [];
  const lines = content.split('\n');

  lines.forEach((line, index) => {
    if (line.includes('<!--') || line.includes('--}}')) {
      return;
    }

    if (!line.includes('href="#"')) {
      return;
    }

    const allowed = HASH_ALLOW_PATTERNS.some((pattern) => line.includes(pattern));
    if (!allowed) {
      issues.push({
        file: relative(file),
        line: index + 1,
        content: line.trim(),
      });
    }
  });

  return issues;
}

function main() {
  const routes = loadRoutes();
  const namedRoutes = new Set(routes.filter((route) => route.name).map((route) => route.name));
  const files = VIEW_TARGETS.flatMap(walk);

  const missingRoutes = [];
  const unresolvedHashes = [];
  const summary = {
    filesScanned: files.length,
    routesReferenced: 0,
    frontendFiles: 0,
    adminFiles: 0,
  };

  files.forEach((file) => {
    const content = fs.readFileSync(file, 'utf8');
    const routeNames = extractRouteNames(content);
    const hashIssues = auditHashes(file, content);

    if (relative(file).startsWith('resources/views/frontend/')) {
      summary.frontendFiles += 1;
    } else if (relative(file).startsWith('resources/views/admin/')) {
      summary.adminFiles += 1;
    }

    summary.routesReferenced += routeNames.length;

    routeNames.forEach((routeName) => {
      if (!namedRoutes.has(routeName)) {
        missingRoutes.push({
          file: relative(file),
          route: routeName,
        });
      }
    });

    unresolvedHashes.push(...hashIssues);
  });

  const report = {
    summary,
    missingRoutes,
    unresolvedHashes,
  };

  console.log(JSON.stringify(report, null, 2));

  if (missingRoutes.length || unresolvedHashes.length) {
    process.exit(1);
  }
}

main();
