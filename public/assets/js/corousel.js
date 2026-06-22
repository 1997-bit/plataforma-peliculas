/**
 * carousel.js
 * loop infinito clonando items, drag y swipe nativo con scroll snap.
 * sin flechas, sin autoplay. funciona con cualquier numero de .card
 * dentro de [data-carousel].
 */
(function () {
  function setupCarousel(row) {
    const originals = Array.from(row.children);
    if (originals.length === 0) return;

    // Clona el set completo antes y después para loop continuo.
    const cloneSet = () =>
      originals.map((node) => {
        const clone = node.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.removeAttribute('id');
        return clone;
      });

    const before = cloneSet();
    const after = cloneSet();

    before.forEach((node) => row.appendChild(node)); // paso temporal, se reordena abajo
    row.innerHTML = '';
    before.forEach((node) => row.appendChild(node));
    originals.forEach((node) => row.appendChild(node));
    after.forEach((node) => row.appendChild(node));

    const setWidth = () => {
      const first = originals[0];
      const gap = parseFloat(getComputedStyle(row).gap || '0');
      return originals.reduce((sum, _, i) => {
        const card = row.children[originals.length + i]; // offset del set real
        return sum + card.getBoundingClientRect().width + gap;
      }, 0);
    };

    let segmentWidth = 0;
    const recalc = () => {
      segmentWidth = setWidth();
      // Arranca en el inicio del set real (después del primer clon-set).
      row.scrollLeft = segmentWidth;
    };

    // Espera a que las imágenes definan el layout real.
    requestAnimationFrame(() => requestAnimationFrame(recalc));
    window.addEventListener('resize', recalc);

    let ticking = false;
    row.addEventListener('scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        if (segmentWidth > 0) {
          if (row.scrollLeft <= 0) {
            row.scrollLeft += segmentWidth;
          } else if (row.scrollLeft >= segmentWidth * 2) {
            row.scrollLeft -= segmentWidth;
          }
        }
        ticking = false;
      });
    });

    // Drag con mouse (desktop). Touch ya funciona nativo via overflow-x.
    let isDown = false;
    let startX = 0;
    let startScroll = 0;

    row.addEventListener('mousedown', (e) => {
      isDown = true;
      startX = e.pageX;
      startScroll = row.scrollLeft;
      row.style.scrollSnapType = 'none';
    });

    window.addEventListener('mouseup', () => {
      if (!isDown) return;
      isDown = false;
      row.style.scrollSnapType = '';
    });

    window.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      row.scrollLeft = startScroll - (e.pageX - startX);
    });
  }

  document.querySelectorAll('[data-carousel]').forEach(setupCarousel);
})();
