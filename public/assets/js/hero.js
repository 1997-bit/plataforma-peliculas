/**
 * hero.js
 * autoplay simple del hero carousel + dots clickeables.
 * sin libs, reusa el patron de data-attributes del proyecto.
 */
(function () {
  const hero = document.querySelector('[data-hero]');
  if (!hero) return;

  const slides = Array.from(hero.querySelectorAll('[data-hero-slide]'));
  const dots = Array.from(hero.querySelectorAll('[data-hero-dot]'));
  if (slides.length <= 1) return;

  let index = 0;
  let timer = null;

  function show(i) {
    slides[index].classList.remove('home-hero-slide--activo');
    dots[index]?.classList.remove('home-hero-dot--activo');
    index = (i + slides.length) % slides.length;
    slides[index].classList.add('home-hero-slide--activo');
    dots[index]?.classList.add('home-hero-dot--activo');
  }

  function next() {
    show(index + 1);
  }

  function start() {
    timer = setInterval(next, 6000);
  }

  function stop() {
    clearInterval(timer);
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      stop();
      show(i);
      start();
    });
  });

  hero.addEventListener('mouseenter', stop);
  hero.addEventListener('mouseleave', start);

  start();
})();
