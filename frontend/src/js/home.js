async function renderHome(container) {
  container.innerHTML = `
    <div id="storiesContainer"></div>
    <div id="feed"></div>
    <div id="loading" class="loading">Carregando...</div>
  `;

  await loadStoriesBar(document.getElementById('storiesContainer'));

  let currentPage = 1;
  let isLoading = false;
  let hasMorePages = true;

  const feedEl = document.getElementById('feed');
  const loadingEl = document.getElementById('loading');

  function renderPost(post) {
    const isLiked = post.likes && post.likes.length > 0;
    const firstImage = post.images && post.images[0];

    const card = document.createElement('div');
    card.className = 'post-card';
    card.innerHTML = `
      <div class="post-header">
        <img class="post-avatar" src="${post.user.avatar_path ? formatImageUrl(post.user.avatar_path) : 'https://via.placeholder.com/32'}" alt="">
        <a href="#/profile/${post.user.id}" class="post-username">${post.user.username}</a>
      </div>
      <div class="post-image-wrapper">
        <img class="post-image" src="${firstImage ? formatImageUrl(firstImage.image_path) : ''}" alt="">
      </div>
      <div class="post-actions">
        <button class="action-btn like-btn ${isLiked ? 'liked' : ''}" data-post-id="${post.id}" data-liked="${isLiked}">
          ${isLiked ? '♥' : '♡'}
        </button>
        <a href="#/post/${post.id}" class="action-btn">💬</a>
      </div>
      <div class="post-likes">${post.likes_count} curtidas</div>
      ${post.caption ? `<div class="post-caption"><strong>${post.user.username}</strong>${post.caption}</div>` : ''}
      <a href="#/post/${post.id}" class="post-comments-link">Ver os ${post.comments_count} comentários</a>
    `;
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
    if (e.target.classList.contains('like-btn')) {
      const btn = e.target;
      const postId = btn.dataset.postId;
      const isLiked = btn.dataset.liked === 'true';
      const likesCountEl = btn.closest('.post-card').querySelector('.post-likes');

      try {
        let result;
        if (isLiked) {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'DELETE' });
          btn.textContent = '♡'; btn.classList.remove('liked'); btn.dataset.liked = 'false';
        } else {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'POST' });
          btn.textContent = '♥'; btn.classList.add('liked'); btn.dataset.liked = 'true';
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
