(function () {
  const botones = document.querySelectorAll('[data-tema-valor]');
  if (botones.length === 0) return;

  function temaActual() {
    return document.documentElement.dataset.tema || 'light';
  }

  function pintarActivo() {
    const actual = temaActual();
    botones.forEach(function (boton) {
      const esActivo = boton.dataset.temaValor === actual;
      boton.classList.toggle('perfil-tema-activo', esActivo);
      boton.setAttribute('aria-pressed', esActivo ? 'true' : 'false');
    });
  }

  function aplicarTema(nuevo) {
    document.documentElement.dataset.tema = nuevo;
    document.cookie = `tema=${nuevo}; path=/; max-age=31536000; SameSite=Strict`;
    pintarActivo();
  }

  botones.forEach(function (boton) {
    boton.addEventListener('click', function () {
      aplicarTema(boton.dataset.temaValor);
    });
  });

  pintarActivo();
})();
