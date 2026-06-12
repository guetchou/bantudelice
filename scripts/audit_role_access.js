const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const ROUTE_FILE = process.env.BD_ROUTE_LIST_JSON || '/tmp/bd_routes_20260327.json';
const TARGETS = [
  path.join(PROJECT_ROOT, 'resources/views/frontend'),
  path.join(PROJECT_ROOT, 'resources/views/admin'),
];

const ROUTE_REGEX = /route\(\s*['"]([^'"]+)['"]/g;
const ALLOWED_FRONTEND_ROLE_ROUTES = new Set([
  'admin.dashboard',
  'restaurant.dashboard',
  'delivery.dashboard',
]);

function walk(targetPath) {
  return fs.readdirSync(targetPath, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(targetPath, entry.name);
    if (entry.isDirectory()) {
      return walk(fullPath);
    }

    return entry.name.endsWith('.blade.php') && !entry.name.includes('.bak-') ? [fullPath] : [];
  });
}

function relative(file) {
  return path.relative(PROJECT_ROOT, file);
}

function stripComments(content) {
  return content.replace(/{{--[\s\S]*?--}}/g, '').replace(/<!--[\s\S]*?-->/g, '');
}

function routeRole(route) {
  const middleware = (route.middleware || []).join(' ').toLowerCase();
  if (middleware.includes('adminmiddleware')) {
    return 'admin';
  }
  if (middleware.includes('restaurantmiddleware') || middleware.includes(' restaurant ')) {
    return 'restaurant';
  }
  if (middleware.includes(' delivery ')) {
    return 'delivery';
  }
  return 'shared';
}

function main() {
  const routes = JSON.parse(fs.readFileSync(ROUTE_FILE, 'utf8'));
  const routeMap = new Map(routes.filter((route) => route.name).map((route) => [route.name, route]));
  const files = TARGETS.flatMap(walk);
  const issues = [];

  files.forEach((file) => {
    const rel = relative(file);
    const scope = rel.startsWith('resources/views/frontend/') ? 'frontend' : 'admin';
    const content = stripComments(fs.readFileSync(file, 'utf8'));
    const routeNames = [...new Set([...content.matchAll(ROUTE_REGEX)].map((match) => match[1]))];

    routeNames.forEach((routeName) => {
      const route = routeMap.get(routeName);
      if (!route) {
        return;
      }

      const role = routeRole(route);
      if (scope === 'frontend' && ['admin', 'restaurant', 'delivery'].includes(role)) {
        if (ALLOWED_FRONTEND_ROLE_ROUTES.has(routeName)) {
          return;
        }
        issues.push({ file: rel, route: routeName, routeRole: role });
      }
      if (scope === 'admin' && ['restaurant', 'delivery'].includes(role)) {
        issues.push({ file: rel, route: routeName, routeRole: role });
      }
    });
  });

  const report = {
    filesScanned: files.length,
    issueCount: issues.length,
    issues,
  };

  console.log(JSON.stringify(report, null, 2));

  if (issues.length) {
    process.exit(1);
  }
}

main();
