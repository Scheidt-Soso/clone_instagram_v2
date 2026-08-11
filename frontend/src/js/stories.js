function formatImageUrl(path) {
  return `http://localhost:8000/storage/${path}`;
}

async function loadStoriesBar(container) {
  const currentUser = JSON.parse(localStorage.getItem('current_user') || '{}');
  let storiesByUser = {};

  try {
    storiesByUser = await apiRequest('/stories');
  } catch (err) {
    console.error('Erro ao carregar stories', err);
  }

  const bar = document.createElement('div');
  bar.className = 'stories-bar';

  bar.innerHTML = `
    <div class="story-item" id="ownStoryItem">
      <div class="story-avatar-ring own story-add-icon">
        <img class="story-avatar" src="${currentUser.avatar_path ? formatImageUrl(currentUser.avatar_path) : 'https://via.placeholder.com/60'}" alt="">
        <span class="story-add-badge">+</span>
      </div>
      <span class="story-username">Seu story</span>
    </div>
  `;

  Object.values(storiesByUser).forEach((userStories) => {
    const user = userStories[0].user;
    const item = document.createElement('div');
    item.className = 'story-item';
    item.innerHTML = `
      <div class="story-avatar-ring">
        <img class="story-avatar" src="${user.avatar_path ? formatImageUrl(user.avatar_path) : 'https://via.placeholder.com/60'}" alt="">
      </div>
      <span class="story-username">${user.username}</span>
    `;
    item.addEventListener('click', () => openStoryViewer(userStories));
    bar.appendChild(item);
  });

  container.appendChild(bar);

  document.getElementById('ownStoryItem').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('media', file);

      try {
        await apiRequest('/stories', { method: 'POST', body: formData });
        window.location.reload();
      } catch (err) {
        console.error('Erro ao postar story', err);
      }
    };
    input.click();
  });
}

function openStoryViewer(stories) {
  let index = 0;
  const overlay = document.createElement('div');
  overlay.className = 'story-viewer-overlay';

  function render() {
    overlay.innerHTML = `
      <button class="story-viewer-close" id="closeStoryViewer">&times;</button>
      <img class="story-viewer-image" src="${formatImageUrl(stories[index].media_path)}" alt="">
    `;
    document.getElementById('closeStoryViewer').addEventListener('click', () => overlay.remove());
  }

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      index = (index + 1) % stories.length;
      render();
    }
  });

  render();
  document.body.appendChild(overlay);
}
