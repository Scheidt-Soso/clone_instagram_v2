function renderSettingsPanel(currentUser) {
  const overlay = document.createElement('div');
  overlay.className = 'settings-overlay';
  overlay.id = 'settingsOverlay';
  overlay.innerHTML = `
    <div class="settings-panel">
      <button class="settings-close" id="settingsClose">&times;</button>
      <h3>Configurações</h3>
      <a href="#/edit-profile" class="settings-item">
        <i class="fa-solid fa-pen"></i> Editar perfil
      </a>
      <button class="settings-item" id="logoutOption">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair
      </button>
      <button class="settings-item danger" id="deleteAccountOption">
        <i class="fa-solid fa-trash"></i> Excluir conta
      </button>
    </div>
  `;
  document.body.appendChild(overlay);
  document.getElementById('settingsClose').addEventListener('click', () => overlay.remove());
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.remove();
  });
  document.getElementById('logoutOption').addEventListener('click', () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('current_user');
    window.location.href = 'index.html';
  });
  document.getElementById('deleteAccountOption').addEventListener('click', async () => {
    const confirmed = await confirmDialog(
      'Essa ação não pode ser desfeita. Você perderá acesso a todos os seus dados.',
      { title: 'Excluir sua conta?', confirmText: 'Excluir conta' }
    );
    if (!confirmed) return;

    try {
      await apiRequest(`/users/${currentUser.id}`, { method: 'DELETE' });
      localStorage.removeItem('auth_token');
      localStorage.removeItem('current_user');
      window.location.href = 'index.html';
    } catch (err) {
      alert('Erro ao excluir conta.');
    }
  });
}

function renderPostMenu(postId, isArchived, onAction) {
  const overlay = document.createElement('div');
  overlay.className = 'settings-overlay';
  overlay.innerHTML = `
    <div class="settings-panel">
      <button class="settings-close" id="postMenuClose">&times;</button>
      <h3>Opções da publicação</h3>
      <a href="#/edit-post/${postId}" class="settings-item">
        <i class="fa-solid fa-pen"></i> Editar
      </a>
      <button class="settings-item" id="postMenuArchive">
        <i class="fa-solid fa-box-archive"></i> ${isArchived ? 'Desarquivar' : 'Arquivar'}
      </button>
      <button class="settings-item danger" id="postMenuDelete">
        <i class="fa-solid fa-trash"></i> Excluir
      </button>
    </div>
  `;
  document.body.appendChild(overlay);

  overlay.querySelector('#postMenuClose').addEventListener('click', () => overlay.remove());
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.remove();
  });

  overlay.querySelector('#postMenuArchive').addEventListener('click', async () => {
    try {
      const endpoint = isArchived ? `/posts/${postId}/unarchive` : `/posts/${postId}/archive`;
      await apiRequest(endpoint, { method: 'POST' });
      overlay.remove();
      onAction();
    } catch (err) {
      alert('Erro ao arquivar/desarquivar.');
    }
  });

  overlay.querySelector('#postMenuDelete').addEventListener('click', async () => {
    const confirmed = await confirmDialog(
      'Essa ação não pode ser desfeita.',
      { title: 'Excluir publicação?', confirmText: 'Excluir' }
    );
    if (!confirmed) return;

    try {
      await apiRequest(`/posts/${postId}`, { method: 'DELETE' });
      overlay.remove();
      onAction();
    } catch (err) {
      alert('Erro ao excluir publicação.');
    }
  });
}

async function renderProfile(container, params) {
  const currentUser = JSON.parse(localStorage.getItem('current_user') || '{}');
  const profileId = params.id || currentUser.id;
  const isOwnProfile = Number(profileId) === Number(currentUser.id);
  const profile = await apiRequest(`/users/${profileId}`);

  let activeTab = 'posts';

  function renderActionButton() {
    if (isOwnProfile) {
      return `
        <a href="#/edit-profile" class="profile-btn">Editar perfil</a>
        <button class="profile-btn icon-only" id="settingsBtn" title="Configurações"><i class="fa-solid fa-gear"></i></button>
      `;
    }
    if (profile.is_following) {
      return `<button class="profile-btn following" id="followBtn" data-following="true">Seguindo</button>`;
    }
    return `<button class="profile-btn primary" id="followBtn" data-following="false">Seguir</button>`;
  }

  function renderGrid(posts, isArchivedTab) {
    if (!posts.length) {
      return `<div class="empty-grid">${isArchivedTab ? 'Nenhum post arquivado.' : 'Nenhum post ainda.'}</div>`;
    }
    return posts.map((post) => {
      const firstImage = post.images && post.images[0];
      const imageUrl = firstImage ? formatImageUrl(firstImage.image_path) : '';
      const isCarousel = post.images && post.images.length > 1;
      return `
        <div class="profile-grid-item">
          <a href="#/post/${post.id}">
            <img src="${imageUrl}" alt="">
            ${isCarousel ? '<i class="fa-solid fa-clone carousel-badge"></i>' : ''}
          </a>
          ${isOwnProfile ? `<button class="grid-item-menu" data-post-id="${post.id}" data-archived="${isArchivedTab}"><i class="fa-solid fa-ellipsis"></i></button>` : ''}
        </div>
      `;
    }).join('');
  }

  async function renderTabs() {
    const gridEl = container.querySelector('.profile-grid');

    if (activeTab === 'posts') {
      gridEl.innerHTML = renderGrid(profile.posts || [], false);
    } else {
      const archivedPosts = await apiRequest('/posts/archived');
      gridEl.innerHTML = renderGrid(archivedPosts, true);
    }

    gridEl.querySelectorAll('.grid-item-menu').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const postId = btn.dataset.postId;
        const isArchived = btn.dataset.archived === 'true';
        renderPostMenu(postId, isArchived, () => renderProfile(container, params));
      });
    });
  }

  container.innerHTML = `
    <div class="profile-header">
      <img class="profile-avatar" src="${profile.avatar_path ? formatImageUrl(profile.avatar_path) : defaultAvatar()}" alt="">
      <div class="profile-info">
        <div class="profile-top-row">
          <span class="profile-username">${profile.username}</span>
          ${renderActionButton()}
        </div>
        <div class="profile-stats">
          <span><strong>${profile.posts_count}</strong> posts</span>
          <span><strong>${profile.followers_count}</strong> seguidores</span>
          <span><strong>${profile.following_count}</strong> seguindo</span>
        </div>
        <div class="profile-name">${profile.name}</div>
        <div class="profile-bio">${profile.bio || ''}</div>
      </div>
    </div>
    ${isOwnProfile ? `
      <div class="profile-tabs">
        <button class="profile-tab active" id="tabPosts"><i class="fa-solid fa-table-cells"></i> POSTS</button>
        <button class="profile-tab" id="tabArchived"><i class="fa-solid fa-box-archive"></i> ARQUIVADOS</button>
      </div>
    ` : ''}
    <div class="profile-grid"></div>
  `;

  await renderTabs();

  const followBtn = document.getElementById('followBtn');
  if (followBtn) {
    followBtn.addEventListener('click', async () => {
      const isFollowing = followBtn.dataset.following === 'true';
      try {
        if (isFollowing) {
          await apiRequest(`/users/${profileId}/follow`, { method: 'DELETE' });
        } else {
          await apiRequest(`/users/${profileId}/follow`, { method: 'POST' });
        }
        await renderProfile(container, params);
      } catch (err) {
        console.error('Erro ao seguir/deixar de seguir', err);
      }
    });
  }

  const settingsBtn = document.getElementById('settingsBtn');
  if (settingsBtn) {
    settingsBtn.addEventListener('click', () => renderSettingsPanel(currentUser));
  }

  const tabPosts = document.getElementById('tabPosts');
  const tabArchived = document.getElementById('tabArchived');
  if (tabPosts && tabArchived) {
    tabPosts.addEventListener('click', async () => {
      activeTab = 'posts';
      tabPosts.classList.add('active');
      tabArchived.classList.remove('active');
      await renderTabs();
    });
    tabArchived.addEventListener('click', async () => {
      activeTab = 'archived';
      tabArchived.classList.add('active');
      tabPosts.classList.remove('active');
      await renderTabs();
    });
  }
}