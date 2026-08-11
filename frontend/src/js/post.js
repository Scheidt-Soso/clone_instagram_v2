const currentUser = JSON.parse(localStorage.getItem('current_user') || '{}');

function getPostIdFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function formatImageUrl(path) {
  return `http://localhost:8000/storage/${path}`;
}

function renderComment(comment, postOwnerId) {
  const canDelete = comment.user.id === currentUser.id || postOwnerId === currentUser.id;

  return `
    <div class="comment-item" data-comment-id="${comment.id}">
      <div>
        <strong>${comment.user.username}</strong>${comment.body}
      </div>
      ${canDelete ? `<button class="comment-delete" data-comment-id="${comment.id}">excluir</button>` : ''}
    </div>
  `;
}

async function loadComments(postId, postOwnerId) {
  const comments = await apiRequest(`/posts/${postId}/comments`);

  const list = comments.length
    ? comments.map((c) => renderComment(c, postOwnerId)).join('')
    : '<div class="empty-comments">Nenhum comentário ainda.</div>';

  document.getElementById('commentsList').innerHTML = list;
}

async function loadPost() {
  const postId = getPostIdFromUrl();

  if (!postId) {
    window.location.href = 'home.html';
    return;
  }

  const post = await apiRequest(`/posts/${postId}`);
  const isLiked = post.likes && post.likes.length > 0;
  const firstImage = post.images && post.images[0];

  const container = document.getElementById('postDetail');
  container.innerHTML = `
    <div class="post-card">
      <div class="post-header">
        <img class="post-avatar" src="${post.user.avatar_path ? formatImageUrl(post.user.avatar_path) : 'https://via.placeholder.com/32'}" alt="">
        <a href="profile.html?id=${post.user.id}" class="post-username">${post.user.username}</a>
      </div>
      <div class="post-image-wrapper">
        <img class="post-image" src="${firstImage ? formatImageUrl(firstImage.image_path) : ''}" alt="">
      </div>
      <div class="post-actions">
        <button class="action-btn like-btn ${isLiked ? 'liked' : ''}" data-post-id="${post.id}" data-liked="${isLiked}">
          ${isLiked ? '♥' : '♡'}
        </button>
      </div>
      <div class="post-likes" id="likesCount">${post.likes_count} curtidas</div>
      ${post.caption ? `<div class="post-caption"><strong>${post.user.username}</strong>${post.caption}</div>` : ''}
    </div>
    <div class="comments-section">
      <div id="commentsList"></div>
      <form class="comment-form" id="commentForm">
        <input type="text" class="comment-input" id="commentInput" placeholder="Adicione um comentário..." required>
        <button type="submit" class="comment-submit" id="commentSubmitBtn">Publicar</button>
      </form>
    </div>
  `;

  await loadComments(post.id, post.user.id);

  document.querySelector('.like-btn').addEventListener('click', async (e) => {
    const btn = e.target;
    const isLiked = btn.dataset.liked === 'true';

    try {
      let result;
      if (isLiked) {
        result = await apiRequest(`/posts/${post.id}/like`, { method: 'DELETE' });
        btn.textContent = '♡';
        btn.classList.remove('liked');
        btn.dataset.liked = 'false';
      } else {
        result = await apiRequest(`/posts/${post.id}/like`, { method: 'POST' });
        btn.textContent = '♥';
        btn.classList.add('liked');
        btn.dataset.liked = 'true';
      }
      document.getElementById('likesCount').textContent = `${result.likes_count} curtidas`;
    } catch (err) {
      console.error('Erro ao curtir', err);
    }
  });

  document.getElementById('commentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('commentInput');
    const submitBtn = document.getElementById('commentSubmitBtn');
    const body = input.value.trim();

    if (!body) return;

    submitBtn.disabled = true;

    try {
      await apiRequest(`/posts/${post.id}/comments`, {
        method: 'POST',
        body: JSON.stringify({ body }),
      });
      input.value = '';
      await loadComments(post.id, post.user.id);
    } catch (err) {
      console.error('Erro ao comentar', err);
    } finally {
      submitBtn.disabled = false;
    }
  });

  document.getElementById('commentsList').addEventListener('click', async (e) => {
    if (e.target.classList.contains('comment-delete')) {
      const commentId = e.target.dataset.commentId;

      try {
        await apiRequest(`/posts/${post.id}/comments/${commentId}`, { method: 'DELETE' });
        await loadComments(post.id, post.user.id);
      } catch (err) {
        console.error('Erro ao excluir comentário', err);
      }
    }
  });
}

loadPost();
