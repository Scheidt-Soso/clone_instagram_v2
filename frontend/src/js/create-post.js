let selectedFiles = [];

function renderPreviews(container) {
  container.innerHTML = selectedFiles.map((file, index) => `
    <div class="preview-item">
      <img src="${URL.createObjectURL(file)}" alt="">
      <button type="button" class="preview-remove" data-index="${index}">&times;</button>
    </div>
  `).join('');

  container.querySelectorAll('.preview-remove').forEach((btn) => {
    btn.addEventListener('click', () => {
      const index = Number(btn.dataset.index);
      selectedFiles.splice(index, 1);
      renderPreviews(container);
    });
  });
}

async function renderCreatePost(container) {
  selectedFiles = [];

  container.innerHTML = `
    <div class="create-post-container">
      <h2>Nova publicação</h2>
      <div class="preview-grid" id="previewGrid"></div>

      <label for="postImagesInput" class="btn-secondary">
        <i class="fa-regular fa-image"></i> Escolher fotos (até 10)
      </label>
      <input type="file" id="postImagesInput" accept="image/*" multiple hidden>

      <textarea id="postCaption" placeholder="Escreva uma legenda..." rows="3"></textarea>

      <button type="button" class="btn-primary" id="publishBtn" disabled>Publicar</button>
      <div class="error-message" id="createPostError"></div>
    </div>
  `;

  const previewGrid = document.getElementById('previewGrid');
  const input = document.getElementById('postImagesInput');
  const publishBtn = document.getElementById('publishBtn');
  const errorEl = document.getElementById('createPostError');

  input.addEventListener('change', () => {
    const newFiles = Array.from(input.files);
    selectedFiles = [...selectedFiles, ...newFiles].slice(0, 10);
    renderPreviews(previewGrid);
    publishBtn.disabled = selectedFiles.length === 0;
    input.value = '';
  });

  publishBtn.addEventListener('click', async () => {
    if (selectedFiles.length === 0) return;

    errorEl.textContent = '';
    publishBtn.disabled = true;
    publishBtn.textContent = 'Publicando...';

    const formData = new FormData();
    const caption = document.getElementById('postCaption').value;
    if (caption) formData.append('caption', caption);
    selectedFiles.forEach((file) => formData.append('images[]', file));

    try {
      const post = await apiRequest('/posts', { method: 'POST', body: formData });
      window.location.hash = `#/post/${post.id}`;
    } catch (err) {
      errorEl.textContent = err.data?.errors
        ? Object.values(err.data.errors)[0][0]
        : (err.data?.message || 'Erro ao criar post.');
      publishBtn.disabled = false;
      publishBtn.textContent = 'Publicar';
    }
  });
}