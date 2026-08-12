const API_BASE_URL = 'http://localhost:8000/api';

async function apiRequest(endpoint, options = {}) {
  const token = localStorage.getItem('auth_token');

  const headers = {
    'Accept': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw { status: response.status, data };
  }

  return data;
}

function defaultAvatar() {
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23dbdbdb"/><circle cx="50" cy="38" r="18" fill="%23a8a8a8"/><ellipse cx="50" cy="85" rx="30" ry="22" fill="%23a8a8a8"/></svg>'
  );
}

function confirmDialog(message, { title = 'Tem certeza?', confirmText = 'Confirmar', danger = true } = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'settings-overlay';
    overlay.innerHTML = `
      <div class="confirm-dialog">
        <h3>${title}</h3>
        <p>${message}</p>
        <div class="confirm-dialog-actions">
          <button class="confirm-btn cancel" id="confirmCancel">Cancelar</button>
          <button class="confirm-btn ${danger ? 'danger' : 'primary'}" id="confirmOk">${confirmText}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    function close(result) {
      overlay.remove();
      resolve(result);
    }

    overlay.querySelector('#confirmCancel').addEventListener('click', () => close(false));
    overlay.querySelector('#confirmOk').addEventListener('click', () => close(true));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close(false);
    });
  });
}