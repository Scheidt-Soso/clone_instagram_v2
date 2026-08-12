const searchInput = document.getElementById('searchInput');
const searchResultsEl = document.getElementById('searchResults');
let searchDebounceTimer = null;
let searchController = null;

function showSearchResults() {
  searchResultsEl.classList.remove('hidden');
}

function hideSearchResults() {
  searchResultsEl.classList.add('hidden');
}

function renderSearchResults(users) {
  if (users.length === 0) {
    searchResultsEl.innerHTML = '<div class="search-no-results">Nenhum usuário encontrado.</div>';
    showSearchResults();
    return;
  }

  searchResultsEl.innerHTML = users.map((user) => `
    <a href="#/profile/${user.id}" class="search-result-item">
      <img class="search-result-avatar" src="${user.avatar_path ? formatImageUrl(user.avatar_path) : defaultAvatar()}" alt="">
      <div class="search-result-info">
        <span class="search-result-username">${user.username}</span>
        <span class="search-result-name">${user.name}</span>
      </div>
    </a>
  `).join('');

  showSearchResults();
}

async function performSearch(term) {
  if (searchController) searchController.abort();

  const controller = new AbortController();
  searchController = controller;

  try {
    const data = await apiRequest(`/users?search=${encodeURIComponent(term)}`, { signal: controller.signal });
    if (searchController !== controller) return;
    renderSearchResults((data.data || data).slice(0, 10));
  } catch (err) {
    if (err.name !== 'AbortError') {
      console.error('Erro ao buscar usuários', err);
      hideSearchResults();
    }
  }
}

searchInput.addEventListener('input', () => {
  const term = searchInput.value.trim();

  clearTimeout(searchDebounceTimer);

  if (!term) {
    if (searchController) searchController.abort();
    hideSearchResults();
    return;
  }

  searchDebounceTimer = setTimeout(() => performSearch(term), 300);
});

searchInput.addEventListener('focus', () => {
  const term = searchInput.value.trim();
  if (term) showSearchResults();
});

searchResultsEl.addEventListener('click', (e) => {
  if (e.target.closest('.search-result-item')) {
    searchInput.value = '';
    hideSearchResults();
  }
});

document.addEventListener('click', (e) => {
  if (!e.target.closest('.sidebar-search')) {
    hideSearchResults();
  }
});
