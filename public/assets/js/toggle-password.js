/**
 * toggle-password.js
 * Muestra u oculta el valor de los inputs type="password" al hacer click
 * en el boton-ojo (.auth-toggle-ojo) que los acompaña.
 */
(function () {
  document.querySelectorAll('.auth-toggle-ojo').forEach((boton) => {
    const input = boton.closest('.auth-campo-con-icono')?.querySelector('input');
    const ojoAbierto = boton.querySelector('.icono-ojo-abierto');
    const ojoCerrado = boton.querySelector('.icono-ojo-cerrado');
    if (!input) return;

    boton.addEventListener('click', () => {
      const oculto = input.type === 'password';
      input.type = oculto ? 'text' : 'password';
      ojoAbierto.style.display = oculto ? 'none' : '';
      ojoCerrado.style.display = oculto ? '' : 'none';
      boton.setAttribute('aria-pressed', oculto ? 'true' : 'false');
    });
  });
})();
