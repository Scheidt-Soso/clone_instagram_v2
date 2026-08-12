async function renderEditPost(container, params) {
  const postId = params.id;
  const post = await apiRequest(`/posts/${postId}`);
  const images = post.images || [];

  container.innerHTML = `
    <div class="create-post-container">
      <h2>Editar publicação</h2>

      <div class="preview-grid" id="editImagesGrid"></div>

      <textarea id="editCaption" rows="3" placeholder="Escreva uma legenda...">${post.caption || ''}</textarea>

      <button type="button" class="btn-primary" id="saveEditBtn">Salvar alterações</button>
      <div class="error-message" id="editPostError"></div>
    </div>
  `;

  function renderImagesGrid() {
    const grid = document.getElementById('editImagesGrid');
    grid.innerHTML = images.map((img) => `
      <div class="preview-item">
        <img src="${formatImageUrl(img.image_path)}" alt="">
        ${images.length > 1 ? `<button type="button" class="preview-remove" data-image-id="${img.id}">&times;</button>` : ''}
      </div>
    `).join('');

    grid.querySelectorAll('.preview-remove').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const imageId = btn.dataset.imageId;
        try {
          await apiRequest(`/posts/${postId}/images/${imageId}`, { method: 'DELETE' });
          const index = images.findIndex((img) => String(img.id) === imageId);
          if (index !== -1) images.splice(index, 1);
          renderImagesGrid();
        } catch (err) {
          document.getElementById('editPostError').textContent = err.data?.message || 'Erro ao remover imagem.';
        }
      });
    });
  }

  renderImagesGrid();

  document.getElementById('saveEditBtn').addEventListener('click', async () => {
    const errorEl = document.getElementById('editPostError');
    const saveBtn = document.getElementById('saveEditBtn');
    errorEl.textContent = '';
    saveBtn.disabled = true;
    saveBtn.textContent = 'Salvando...';

    const caption = document.getElementById('editCaption').value;

    try {
      await apiRequest(`/posts/${postId}`, {
        method: 'PUT',
        body: JSON.stringify({ caption }),
      });
      window.location.hash = `#/post/${postId}`;
    } catch (err) {
      errorEl.textContent = err.data?.errors
        ? Object.values(err.data.errors)[0][0]
        : (err.data?.message || 'Erro ao salvar.');
      saveBtn.disabled = false;
      saveBtn.textContent = 'Salvar alterações';
    }
  });
}