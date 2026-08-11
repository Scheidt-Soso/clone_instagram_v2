(function () {
  const token = localStorage.getItem('auth_token');

  if (!token) {
    window.location.href = 'index.html';
  }
})();