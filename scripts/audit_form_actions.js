const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const ROUTE_FILE = process.env.BD_ROUTE_LIST_JSON || '/tmp/bd_routes_20260327.json';
const TARGETS = [
  path.join(PROJECT_ROOT, 'resources/views/frontend'),
  path.join(PROJECT_ROOT, 'resources/views/admin'),
  path.join(PROJECT_ROOT, 'resources/views/layouts'),
  path.join(PROJECT_ROOT, 'resources/views/app.blade.php'),
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

    return entry.name.endsWith('.blade.php') && !entry.name.includes('.bak-') ? [fullPath] : [];
  });
}

function relative(file) {
  return path.relative(PROJECT_ROOT, file);
}

function stripComments(content) {
  return content.replace(/{{--[\s\S]*?--}}/g, '').replace(/<!--[\s\S]*?-->/g, '');
}

function normalizeMethods(method) {
  return new Set((method || 'GET').split('|').map((part) => part.toUpperCase()));
}

function loadRoutes() {
  return JSON.parse(fs.readFileSync(ROUTE_FILE, 'utf8'));
}

function extractRouteName(expression) {
  const match = expression.match(/route\(\s*['"]([^'"]+)['"]/);
  return match ? match[1] : null;
}

function extractStaticPath(expression) {
  const trimmed = expression.trim();
  const quoted = trimmed.match(/^['"]([^'"]+)['"]$/);
  if (quoted) {
    return quoted[1];
  }

  const template = trimmed.match(/^`([^${`]+)`$/);
  if (template) {
    return template[1];
  }

  return null;
}

function findRouteByPath(routes, value, method) {
  const cleanPath = value.replace(/^https?:\/\/[^/]+/i, '').replace(/^\/+/, '').replace(/\?.*$/, '');
  return routes.find((route) => {
    if ((route.uri || '') !== cleanPath) {
      return false;
    }

    return normalizeMethods(route.method).has(method);
  });
}

function extractForms(content) {
  return [...content.matchAll(/<form\b[\s\S]*?<\/form>/gi)].map((match) => match[0]);
}

function extractFetches(content) {
  return [...content.matchAll(/fetch\(\s*([^,]+?)\s*,\s*\{([\s\S]*?)\}\s*\)/g)].map((match) => ({
    target: match[1].trim(),
    options: match[2],
  }));
}

function auditForms(routes, file, content) {
  const issues = [];
  const forms = extractForms(content);

  forms.forEach((form) => {
    const actionMatch = form.match(/\baction\s*=\s*"([^"]*)"/i);
    if (!actionMatch) {
      return;
    }

    const methodMatch = form.match(/\bmethod\s*=\s*"([^"]+)"/i);
    const spoofMatch = form.match(/@method\(\s*['"]([^'"]+)['"]\s*\)/i);
    const method = (spoofMatch?.[1] || methodMatch?.[1] || 'GET').toUpperCase();
    const action = actionMatch[1].trim();

    if (action === '') {
      issues.push({ file: relative(file), type: 'empty_form_action', method });
      return;
    }

    const routeName = extractRouteName(action);
    if (routeName) {
      const route = routes.find((item) => item.name === routeName);
      if (!route) {
        issues.push({ file: relative(file), type: 'missing_form_route', route: routeName, method });
        return;
      }

      if (!normalizeMethods(route.method).has(method)) {
        issues.push({
          file: relative(file),
          type: 'form_method_mismatch',
          route: routeName,
          method,
          routeMethod: route.method,
        });
      }
      return;
    }

    const staticPath = extractStaticPath(action);
    if (staticPath && staticPath.startsWith('/')) {
      const route = findRouteByPath(routes, staticPath, method);
      if (!route) {
        issues.push({ file: relative(file), type: 'unmatched_form_path', path: staticPath, method });
      }
    }
  });

  return issues;
}

function auditFetches(routes, file, content) {
  const issues = [];
  const fetches = extractFetches(content);

  fetches.forEach((entry) => {
    const methodMatch = entry.options.match(/\bmethod\s*:\s*['"]([^'"]+)['"]/i);
    const method = (methodMatch?.[1] || 'GET').toUpperCase();
    const routeName = extractRouteName(entry.target);

    if (routeName) {
      const route = routes.find((item) => item.name === routeName);
      if (!route) {
        issues.push({ file: relative(file), type: 'missing_fetch_route', route: routeName, method });
        return;
      }

      if (!normalizeMethods(route.method).has(method)) {
        issues.push({
          file: relative(file),
          type: 'fetch_method_mismatch',
          route: routeName,
          method,
          routeMethod: route.method,
        });
      }
      return;
    }

    const staticPath = extractStaticPath(entry.target);
    if (!staticPath || !staticPath.startsWith('/')) {
      return;
    }

    const route = findRouteByPath(routes, staticPath, method);
    if (!route) {
      issues.push({ file: relative(file), type: 'unmatched_fetch_path', path: staticPath, method });
      return;
    }

    const usesApiAuth = (route.middleware || []).some((middleware) => /Authenticate:api/i.test(middleware));
    const isAdminView = relative(file).startsWith('resources/views/admin/');
    if (usesApiAuth && isAdminView) {
      issues.push({
        file: relative(file),
        type: 'admin_view_calls_api_auth_route',
        path: staticPath,
        method,
      });
    }
  });

  return issues;
}

function main() {
  const routes = loadRoutes();
  const files = TARGETS.flatMap(walk);
  const issues = [];

  files.forEach((file) => {
    const content = stripComments(fs.readFileSync(file, 'utf8'));
    issues.push(...auditForms(routes, file, content));
    issues.push(...auditFetches(routes, file, content));
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
