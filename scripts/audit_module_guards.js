const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const ROUTE_FILE = process.env.BD_ROUTE_LIST_JSON || '/tmp/bd_routes_20260327.json';
const TARGETS = [
  path.join(PROJECT_ROOT, 'resources/views/frontend'),
];

const ROUTE_REGEX = /route\(\s*['"]([^'"]+)['"]/g;
const GENERIC_FRONTEND_FILE = /resources\/views\/frontend\/(layouts\/|index|sitemap|about|contact|faq|help|legal_notices|privacy_policy|cookies|terms|offers)/;
const GUARD_TOKENS = {
  food: ['$foodEnabled', "Module::isEnabled('food')", 'module:food'],
  colis: ['$colisEnabled', "Module::isEnabled('colis')", 'module:colis'],
  transport: ['$transportEnabled', "Module::isEnabled('transport')", 'module:transport'],
};

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

function routeModule(route) {
  const middleware = (route.middleware || []).join(' ');
  if (/EnsureModuleEnabled:food|module:food/i.test(middleware)) {
    return 'food';
  }
  if (/EnsureModuleEnabled:colis|module:colis/i.test(middleware)) {
    return 'colis';
  }
  if (/EnsureModuleEnabled:transport|module:transport/i.test(middleware)) {
    return 'transport';
  }
  return null;
}

function main() {
  const routes = JSON.parse(fs.readFileSync(ROUTE_FILE, 'utf8'));
  const routeMap = new Map(routes.filter((route) => route.name).map((route) => [route.name, route]));
  const files = TARGETS.flatMap(walk).filter((file) => GENERIC_FRONTEND_FILE.test(relative(file)));
  const issues = [];

  files.forEach((file) => {
    const rel = relative(file);
    const content = stripComments(fs.readFileSync(file, 'utf8'));
    const routeNames = [...new Set([...content.matchAll(ROUTE_REGEX)].map((match) => match[1]))];
    const modulesUsed = [...new Set(routeNames.map((routeName) => routeModule(routeMap.get(routeName) || {})).filter(Boolean))];

    modulesUsed.forEach((moduleName) => {
      const hasGuard = GUARD_TOKENS[moduleName].some((token) => content.includes(token));
      if (!hasGuard) {
        issues.push({ file: rel, module: moduleName });
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
