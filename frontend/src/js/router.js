const routes = [];

function registerRoute(pattern, handler) {
  routes.push({ pattern, handler });
}

function matchRoute(hash) {
  const path = hash.replace(/^#/, '') || '/home';
  const parts = path.split('/').filter(Boolean);

  for (const route of routes) {
    const routeParts = route.pattern.split('/').filter(Boolean);
    if (routeParts.length !== parts.length) continue;

    const params = {};
    let matched = true;

    for (let i = 0; i < routeParts.length; i++) {
      if (routeParts[i].startsWith(':')) {
        params[routeParts[i].slice(1)] = parts[i];
      } else if (routeParts[i] !== parts[i]) {
        matched = false;
        break;
      }
    }

    if (matched) return { handler: route.handler, params };
  }

  return null;
}

function updateActiveNav(hash) {
  const routeName = hash.replace(/^#\//, '').split('/')[0] || 'home';
  document.querySelectorAll('.sidebar-item').forEach((item) => {
    item.classList.toggle('active', item.dataset.route === routeName);
  });
}

function setPageScrollHandler(handler) {
  if (window.__activeScrollHandler) {
    window.removeEventListener('scroll', window.__activeScrollHandler);
  }
  window.__activeScrollHandler = handler;
  if (handler) window.addEventListener('scroll', handler);
}

async function handleRouteChange() {
  const hash = window.location.hash || '#/home';
  const match = matchRoute(hash);
  const contentEl = document.getElementById('app-content');

  setPageScrollHandler(null);
  updateActiveNav(hash);

  if (!match) {
    contentEl.innerHTML = '<div class="empty-grid">Página não encontrada.</div>';
    return;
  }

  contentEl.innerHTML = '';
  contentEl.className = '';
  try {
    await match.handler(contentEl, match.params || {});
  } catch (err) {
    console.error('Erro ao renderizar rota', err);
    contentEl.innerHTML = '<div class="empty-grid">Erro ao carregar.</div>';
  }
}

window.addEventListener('hashchange', handleRouteChange);
window.addEventListener('DOMContentLoaded', handleRouteChange);
