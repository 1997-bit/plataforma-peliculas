(function () {
    const seccion = document.querySelector('.detalle-calificacion');
    if (!seccion) return;

    const contentId = seccion.dataset.contentId;
    const csrf = seccion.dataset.csrf;
    const estrellas = seccion.querySelectorAll('.detalle-estrellas:not(.detalle-estrellas-promedio) .detalle-estrella');
    const mensaje = seccion.querySelector('.detalle-calificacion-mensaje');
    let valorGuardado = parseInt(seccion.dataset.misEstrellas, 10) || 0;

    function pintarHasta(valor) {
        estrellas.forEach(function (b, idx) {
            b.classList.toggle('detalle-estrella-activa', idx < valor);
        });
    }

    estrellas.forEach(function (boton) {
        boton.addEventListener('mouseenter', function () {
            pintarHasta(parseInt(boton.dataset.valor, 10));
        });

        boton.addEventListener('mouseleave', function () {
            pintarHasta(valorGuardado);
        });

        boton.addEventListener('click', function () {
            const valor = parseInt(boton.dataset.valor, 10);

            pintarHasta(valor);
            estrellas.forEach(function (b, idx) {
                b.setAttribute('aria-checked', idx + 1 === valor ? 'true' : 'false');
            });

            fetch('/contenido/calificar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    content_id: contentId,
                    estrellas: String(valor),
                    _csrf: csrf,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    mensaje.textContent = data.ok ? 'Calificación guardada.' : 'No se pudo guardar.';
                    if (data.ok) {
                        valorGuardado = valor;
                    }
                })
                .catch(function () {
                    mensaje.textContent = 'Error de conexión.';
                });
        });
    });
})();
