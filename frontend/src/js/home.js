async function renderHome(container) {
  container.classList.add('home-content');
  container.innerHTML = `
    <div class="home-layout">
      <div class="home-feed">
        <div id="storiesContainer"></div>
        <div id="feed"></div>
        <div id="loading" class="loading">Carregando...</div>
      </div>
      <aside class="home-suggestions">
        <div class="suggestions-card">
          <div class="suggestions-header">
            <span class="suggestions-title">Sugestões para você</span>
          </div>
          <div id="suggestionsList"></div>
        </div>
      </aside>
    </div>
  `;

  await loadStoriesBar(document.getElementById('storiesContainer'));
  await loadRecommendedUsers();

  let currentPage = 1;
  let isLoading = false;
  let hasMorePages = true;

  const feedEl = document.getElementById('feed');
  const loadingEl = document.getElementById('loading');

  function renderPost(post) {
    const isLiked = post.likes && post.likes.length > 0;
    const images = post.images || [];

    const card = document.createElement('div');
    card.className = 'post-card';
    card.innerHTML = `
      <div class="post-header">
        <img class="post-avatar" src="${post.user.avatar_path ? formatImageUrl(post.user.avatar_path) : defaultAvatar()}" alt="">
        <a href="#/profile/${post.user.id}" class="post-username">${post.user.username}</a>
      </div>
      <div class="post-image-wrapper" data-current="0">
        <img class="post-image" src="${images[0] ? formatImageUrl(images[0].image_path) : ''}" alt="">
        ${images.length > 1 ? `
          <button class="carousel-arrow left" data-dir="-1" style="display:none;"><i class="fa-solid fa-chevron-left"></i></button>
          <button class="carousel-arrow right" data-dir="1"><i class="fa-solid fa-chevron-right"></i></button>
          <div class="carousel-dots">
            ${images.map((_, i) => `<span class="carousel-dot ${i === 0 ? 'active' : ''}"></span>`).join('')}
          </div>
        ` : ''}
      </div>
      <div class="post-actions">
        <button class="action-btn like-btn ${isLiked ? 'liked' : ''}" data-post-id="${post.id}" data-liked="${isLiked}">
          <i class="${isLiked ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
        </button>
        <a href="#/post/${post.id}" class="action-btn">💬</a>
      </div>
      <div class="post-likes">${post.likes_count} curtidas</div>
      ${post.caption ? `<div class="post-caption"><strong>${post.user.username}</strong>${post.caption}</div>` : ''}
      <a href="#/post/${post.id}" class="post-comments-link">Ver os ${post.comments_count} comentários</a>
    `;

    if (images.length > 1) {
      const wrapper = card.querySelector('.post-image-wrapper');
      const imgEl = wrapper.querySelector('.post-image');
      const dots = wrapper.querySelectorAll('.carousel-dot');
      const leftArrow = wrapper.querySelector('.carousel-arrow.left');
      const rightArrow = wrapper.querySelector('.carousel-arrow.right');

      function updateCarousel(index) {
        wrapper.dataset.current = index;
        imgEl.src = formatImageUrl(images[index].image_path);
        dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
        leftArrow.style.display = index === 0 ? 'none' : 'flex';
        rightArrow.style.display = index === images.length - 1 ? 'none' : 'flex';
      }

      wrapper.querySelectorAll('.carousel-arrow').forEach((btn) => {
        btn.addEventListener('click', () => {
          const current = Number(wrapper.dataset.current);
          const dir = Number(btn.dataset.dir);
          updateCarousel(current + dir);
        });
      });
    }

    return card;
  }

  async function loadFeed() {
    if (isLoading || !hasMorePages) return;
    isLoading = true;
    loadingEl.style.display = 'block';

    try {
      const data = await apiRequest(`/posts?page=${currentPage}`);
      data.data.forEach((post) => feedEl.appendChild(renderPost(post)));
      hasMorePages = data.next_page_url !== null;
      currentPage++;
    } catch (err) {
      console.error('Erro ao carregar feed', err);
    } finally {
      isLoading = false;
      loadingEl.style.display = hasMorePages ? 'block' : 'none';
    }
  }

  feedEl.addEventListener('click', async (e) => {
    const btn = e.target.closest('.like-btn');
    if (btn) {
      const postId = btn.dataset.postId;
      const isLiked = btn.dataset.liked === 'true';
      const likesCountEl = btn.closest('.post-card').querySelector('.post-likes');

      try {
        let result;
        if (isLiked) {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'DELETE' });
          btn.innerHTML = '<i class="fa-regular fa-heart"></i>'; btn.classList.remove('liked'); btn.dataset.liked = 'false';
        } else {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'POST' });
          btn.innerHTML = '<i class="fa-solid fa-heart"></i>'; btn.classList.add('liked'); btn.dataset.liked = 'true';
        }
        likesCountEl.textContent = `${result.likes_count} curtidas`;
      } catch (err) {
        console.error('Erro ao curtir', err);
      }
    }
  });

  setPageScrollHandler(() => {
    const scrolledToBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 300;
    if (scrolledToBottom) loadFeed();
  });

  loadFeed();
}

async function loadRecommendedUsers() {
  const listEl = document.getElementById('suggestionsList');
  if (!listEl) return;

  let users = [];
  try {
    users = await apiRequest('/users/recommended');
  } catch (err) {
    console.error('Erro ao carregar sugestões', err);
    return;
  }

  if (!users.length) {
    listEl.innerHTML = '<div class="suggestions-empty">Nenhuma sugestão por enquanto.</div>';
    return;
  }

  listEl.innerHTML = users.map((user) => `
    <div class="suggestion-item" data-user-id="${user.id}">
      <a href="#/profile/${user.id}" class="suggestion-link">
        <img class="suggestion-avatar" src="${user.avatar_path ? formatImageUrl(user.avatar_path) : defaultAvatar()}" alt="">
        <div class="suggestion-info">
          <span class="suggestion-username">${user.username}</span>
          <span class="suggestion-name">${user.name}</span>
        </div>
      </a>
      <button class="suggestion-follow" data-user-id="${user.id}">Seguir</button>
    </div>
  `).join('');

  listEl.addEventListener('click', async (e) => {
    const btn = e.target.closest('.suggestion-follow');
    if (!btn) return;

    const userId = btn.dataset.userId;
    try {
      await apiRequest(`/users/${userId}/follow`, { method: 'POST' });
      btn.closest('.suggestion-item').remove();
      if (!listEl.querySelector('.suggestion-item')) {
        listEl.innerHTML = '<div class="suggestions-empty">Nenhuma sugestão por enquanto.</div>';
      }
    } catch (err) {
      console.error('Erro ao seguir usuário', err);
    }
  });
}
