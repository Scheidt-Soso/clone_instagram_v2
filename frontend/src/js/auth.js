const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const errorMessage = document.getElementById('errorMessage');
const submitBtn = document.getElementById('submitBtn');

function showError(message) {
  errorMessage.textContent = message;
}

function setLoading(isLoading) {
  submitBtn.disabled = isLoading;
  submitBtn.textContent = isLoading ? 'Aguarde...' : (loginForm ? 'Entrar' : 'Cadastrar');
}

if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    setLoading(true);

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
      const data = await apiRequest('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });

      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('current_user', JSON.stringify(data.user));
      window.location.href = 'home.html';
    } catch (err) {
      showError(err.data?.message || 'Erro ao fazer login.');
    } finally {
      setLoading(false);
    }
  });
}

if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    setLoading(true);

    const name = document.getElementById('name').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const password_confirmation = document.getElementById('passwordConfirmation').value;

    try {
      const data = await apiRequest('/register', {
        method: 'POST',
        body: JSON.stringify({ name, username, email, password, password_confirmation }),
      });

      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('current_user', JSON.stringify(data.user));
      window.location.href = 'home.html';
    } catch (err) {
      if (err.data?.errors) {
        const firstError = Object.values(err.data.errors)[0][0];
        showError(firstError);
      } else {
        showError(err.data?.message || 'Erro ao cadastrar.');
      }
    } finally {
      setLoading(false);
    }
  });
}