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

  const usersStoriesList = Object.values(storiesByUser);

  const bar = document.createElement('div');
  bar.className = 'stories-bar';

  bar.innerHTML = `
    <div class="story-item">
      <div class="story-avatar-ring own" id="ownStoryRing">
        <img class="story-avatar" src="${currentUser.avatar_path ? formatImageUrl(currentUser.avatar_path) : defaultAvatar()}" alt="">
        <span class="story-add-badge" id="ownStoryAddBtn"><i class="fa-solid fa-plus"></i></span>
      </div>
      <span class="story-username">Seu story</span>
    </div>
  `;

  usersStoriesList.forEach((userStories, userIndex) => {
    const user = userStories[0].user;
    const item = document.createElement('div');
    item.className = 'story-item';
    item.innerHTML = `
      <div class="story-avatar-ring">
        <img class="story-avatar" src="${user.avatar_path ? formatImageUrl(user.avatar_path) : defaultAvatar()}" alt="">
      </div>
      <span class="story-username">${user.username}</span>
    `;
    item.addEventListener('click', () => openStoryViewer(usersStoriesList, userIndex));
    bar.appendChild(item);
  });

  container.appendChild(bar);

  document.getElementById('ownStoryRing').addEventListener('click', async () => {
    try {
      const myStories = await apiRequest('/stories/mine');
      if (myStories.length > 0) {
        openStoryViewer([myStories], 0);
      } else {
        alert('Você ainda não tem stories. Clique no "+" para adicionar.');
      }
    } catch (err) {
      console.error('Erro ao carregar suas stories', err);
    }
  });

  document.getElementById('ownStoryAddBtn').addEventListener('click', (e) => {
    e.stopPropagation();

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

function openStoryViewer(usersStoriesList, startUserIndex) {
  let userIndex = startUserIndex;
  let storyIndex = 0;

  const overlay = document.createElement('div');
  overlay.className = 'story-viewer-overlay';

  function currentStories() {
    return usersStoriesList[userIndex];
  }

  function render() {
    const stories = currentStories();
    const story = stories[storyIndex];

    overlay.innerHTML = `
      <button class="story-viewer-close" id="closeStoryViewer">&times;</button>
      <div class="story-viewer-username">${story.user.username}</div>
      <img class="story-viewer-image" src="${formatImageUrl(story.media_path)}" alt="">
      <div class="story-viewer-zone left" id="storyPrevZone"></div>
      <div class="story-viewer-zone right" id="storyNextZone"></div>
    `;

    overlay.querySelector('#closeStoryViewer').addEventListener('click', () => overlay.remove());
    overlay.querySelector('#storyNextZone').addEventListener('click', goNext);
    overlay.querySelector('#storyPrevZone').addEventListener('click', goPrev);
  }

  function goNext() {
    const stories = currentStories();

    if (storyIndex < stories.length - 1) {
      storyIndex++;
      render();
      return;
    }

    if (userIndex < usersStoriesList.length - 1) {
      userIndex++;
      storyIndex = 0;
      render();
      return;
    }

    overlay.remove();
  }

  function goPrev() {
    if (storyIndex > 0) {
      storyIndex--;
      render();
      return;
    }

    if (userIndex > 0) {
      userIndex--;
      storyIndex = currentStories().length - 1;
      render();
      return;
    }
  }

  render();
  document.body.appendChild(overlay);
}