async function renderEditProfile(container) {
  const currentUser = JSON.parse(localStorage.getItem('current_user') || '{}');

  container.innerHTML = `
    <div class="edit-container">
      <div class="edit-avatar-section">
        <img class="profile-avatar" id="avatarPreview" src="" alt="">
        <label for="avatarInput" class="change-photo-label">Alterar foto</label>
        <input type="file" id="avatarInput" accept="image/*" hidden>
      </div>
      <form id="editForm">
        <div class="form-group"><label>Nome</label><input type="text" id="name" required></div>
        <div class="form-group"><label>Nome de usuário</label><input type="text" id="username" required></div>
        <div class="form-group"><label>Bio</label><textarea id="bio" rows="3"></textarea></div>
        <button type="submit" class="btn-primary" id="submitBtn">Salvar</button>
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
      </form>
    </div>
  `;

  const avatarPreview = document.getElementById('avatarPreview');
  const avatarInput = document.getElementById('avatarInput');
  const editForm = document.getElementById('editForm');
  const errorMessage = document.getElementById('errorMessage');
  const successMessage = document.getElementById('successMessage');
  const submitBtn = document.getElementById('submitBtn');

  const profile = await apiRequest(`/users/${currentUser.id}`);
  avatarPreview.src = profile.avatar_path ? formatImageUrl(profile.avatar_path) : 'https://via.placeholder.com/90';
  document.getElementById('name').value = profile.name;
  document.getElementById('username').value = profile.username;
  document.getElementById('bio').value = profile.bio || '';

  avatarInput.addEventListener('change', async () => {
    const file = avatarInput.files[0];
    if (!file) return;
    avatarPreview.src = URL.createObjectURL(file);

    const formData = new FormData();
    formData.append('avatar', file);

    try {
      const updatedUser = await apiRequest(`/users/${currentUser.id}/avatar`, { method: 'POST', body: formData });
      currentUser.avatar_path = updatedUser.avatar_path;
      localStorage.setItem('current_user', JSON.stringify(currentUser));
      successMessage.textContent = 'Foto atualizada!';
    } catch (err) {
      errorMessage.textContent = 'Erro ao atualizar a foto.';
    }
  });

  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorMessage.textContent = '';
    successMessage.textContent = '';
    submitBtn.disabled = true;

    const name = document.getElementById('name').value;
    const username = document.getElementById('username').value;
    const bio = document.getElementById('bio').value;

    try {
      const updatedUser = await apiRequest(`/users/${currentUser.id}`, {
        method: 'PUT',
        body: JSON.stringify({ name, username, bio }),
      });
      currentUser.name = updatedUser.name;
      currentUser.username = updatedUser.username;
      currentUser.bio = updatedUser.bio;
      localStorage.setItem('current_user', JSON.stringify(currentUser));
      successMessage.textContent = 'Perfil atualizado com sucesso!';
    } catch (err) {
      errorMessage.textContent = err.data?.errors
        ? Object.values(err.data.errors)[0][0]
        : (err.data?.message || 'Erro ao atualizar perfil.');
    } finally {
      submitBtn.disabled = false;
    }
  });
}
