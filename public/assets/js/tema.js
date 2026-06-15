const btn = document.getElementById('btn-tema');

btn.addEventListener('click', () => {
  const actual =
    document.documentElement.dataset.tema || 'light';

  const nuevo =
    actual === 'light' ? 'dark' : 'light';

  document.documentElement.dataset.tema = nuevo;

  document.cookie =
    `tema=${nuevo}; path=/; max-age=31536000; SameSite=Strict`;
});
