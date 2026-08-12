async function renderPost(container, params) {
  const currentUser = JSON.parse(localStorage.getItem('current_user') || '{}');
  const postId = params.id;

  function renderComment(comment, postOwnerId) {
    const canDelete = comment.user.id === currentUser.id || postOwnerId === currentUser.id;
    return `
      <div class="comment-item" data-comment-id="${comment.id}">
        <div class="comment-body"><strong>${comment.user.username}</strong>${comment.body}</div>
        ${canDelete ? `<button class="comment-delete" data-comment-id="${comment.id}" title="Excluir comentário" aria-label="Excluir comentário"><i class="fa-solid fa-trash-can"></i></button>` : ''}
      </div>
    `;
  }

  async function loadComments(postOwnerId) {
    const comments = await apiRequest(`/posts/${postId}/comments`);
    document.getElementById('commentsList').innerHTML = comments.length
      ? comments.map((c) => renderComment(c, postOwnerId)).join('')
      : '<div class="empty-comments">Nenhum comentário ainda.</div>';
  }

  const post = await apiRequest(`/posts/${postId}`);
  const isLiked = post.likes && post.likes.length > 0;
  const images = post.images || [];

  container.innerHTML = `
    <div class="post-card">
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
        <button class="action-btn like-btn ${isLiked ? 'liked' : ''}" data-liked="${isLiked}">
          <i class="${isLiked ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
        </button>
      </div>
      <div class="post-likes" id="likesCount">${post.likes_count} curtidas</div>
      ${post.caption ? `<div class="post-caption"><strong>${post.user.username}</strong>${post.caption}</div>` : ''}
    </div>
    <div class="comments-section">
      <div id="commentsList"></div>
      <form class="comment-form" id="commentForm">
        <div class="comment-form-box">
          <input type="text" class="comment-input" id="commentInput" placeholder="Adicione um comentário..." autocomplete="off">
          <button type="submit" class="comment-submit" id="commentSubmitBtn" disabled title="Publicar" aria-label="Publicar">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
      </form>
    </div>
  `;

  await loadComments(post.user.id);

  if (images.length > 1) {
    const wrapper = container.querySelector('.post-image-wrapper');
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

  document.querySelector('.like-btn').addEventListener('click', async (e) => {
    const btn = e.target.closest('.like-btn');
    if (!btn) return;
    const isLiked = btn.dataset.liked === 'true';
    try {
      let result;
        if (isLiked) {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'DELETE' });
          btn.innerHTML = '<i class="fa-regular fa-heart"></i>'; btn.classList.remove('liked'); btn.dataset.liked = 'false';
        } else {
          result = await apiRequest(`/posts/${postId}/like`, { method: 'POST' });
          btn.innerHTML = '<i class="fa-solid fa-heart"></i>'; btn.classList.add('liked'); btn.dataset.liked = 'true';
        }
      document.getElementById('likesCount').textContent = `${result.likes_count} curtidas`;
    } catch (err) {
      console.error('Erro ao curtir', err);
    }
  });

  const commentInput = document.getElementById('commentInput');
  const commentSubmitBtn = document.getElementById('commentSubmitBtn');

  commentInput.addEventListener('input', () => {
    commentSubmitBtn.disabled = !commentInput.value.trim();
  });

  document.getElementById('commentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = commentInput.value.trim();
    if (!body || commentSubmitBtn.disabled) return;
    commentSubmitBtn.disabled = true;
    try {
      await apiRequest(`/posts/${postId}/comments`, { method: 'POST', body: JSON.stringify({ body }) });
      commentInput.value = '';
      commentSubmitBtn.disabled = true;
      await loadComments(post.user.id);
    } catch (err) {
      console.error('Erro ao comentar', err);
    } finally {
      commentSubmitBtn.disabled = !commentInput.value.trim();
    }
  });

  document.getElementById('commentsList').addEventListener('click', async (e) => {
    const btn = e.target.closest('.comment-delete');
    if (!btn) return;
    const commentId = btn.dataset.commentId;
    try {
      await apiRequest(`/posts/${postId}/comments/${commentId}`, { method: 'DELETE' });
      await loadComments(post.user.id);
    } catch (err) {
      console.error('Erro ao excluir comentário', err);
    }
  });
}
