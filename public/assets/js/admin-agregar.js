(function () {
    'use strict';

    function elems(id) {
        return document.getElementById(id);
    }

    function setVal(id, val) {
        var el = elems(id);
        if (el) el.value = val;
    }

    var posterField = window.AdminPosterField.crear({
        imgId: 'poster-preview',
        placeholderId: 'poster-placeholder',
        fileInputId: 'poster',
        hintId: 'poster-hint',
        hintArchivo: 'Se usará el archivo que subiste.',
    });
    var backdropField = window.AdminPosterField.crear({
        imgId: 'backdrop-preview',
        placeholderId: 'backdrop-placeholder',
    });

    function mostrarBtnQuitar(visible) {
        var btn = elems('btn-quitar-poster');
        if (btn) btn.hidden = !visible;
    }

    function mostrarPoster(path) {
        var hint = elems('poster-hint');
        posterField.mostrarImagen('https://image.tmdb.org/t/p/w300' + path);
        mostrarBtnQuitar(true);
        if (hint) hint.textContent = 'Sube un archivo para reemplazar el poster de TMDB.';
    }

    function ocultarPoster() {
        var hint = elems('poster-hint');
        var file = elems('poster');
        posterField.mostrarPlaceholder();
        mostrarBtnQuitar(false);
        if (hint) hint.textContent = 'Requerido. Sube un archivo o selecciona un resultado de TMDB.';
        if (file) file.value = '';
        setVal('field-tmdb-poster', '');
    }

    function mostrarBackdrop(path) {
        var campo = elems('backdrop-campo');
        backdropField.mostrarImagen('https://image.tmdb.org/t/p/w780' + path);
        if (campo) campo.hidden = false;
    }

    function sincronizarGeneros(jsonStr) {
        var ids = [];
        try { ids = JSON.parse(jsonStr || '[]'); } catch (_) {}
        document.querySelectorAll('.chip-input').forEach(function (cb) {
            var label = cb.closest('[data-tmdb-id]');
            var tid = label ? parseInt(label.dataset.tmdbId, 10) : 0;
            cb.checked = tid > 0 && ids.indexOf(tid) !== -1;
        });
    }

    function usarResultado(card) {
        var d = card.dataset;

        setVal('titulo', d.titulo || '');
        setVal('tipo', d.tipo || 'movie');
        setVal('anio', d.anio || '');
        setVal('descripcion', d.descripcion || '');
        setVal('field-tmdb-id', d.tmdbId || '');
        setVal('field-tmdb-poster', d.poster || '');
        setVal('field-tmdb-backdrop', d.backdrop || '');

        if (d.poster) mostrarPoster(d.poster);
        if (d.backdrop) mostrarBackdrop(d.backdrop);
        sincronizarGeneros(d.genreIds);

        var aviso = elems('existe-aviso');
        if (aviso) aviso.hidden = d.existe !== '1';

        document.querySelectorAll('.admin-tmdb-card').forEach(function (c) {
            c.classList.remove('admin-tmdb-card--activo');
        });
        card.classList.add('admin-tmdb-card--activo');

        if (window.innerWidth < 760) {
            var panel = elems('admin-agregar-form');
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    var lista = document.querySelector('.admin-tmdb-lista');
    if (lista) {
        lista.addEventListener('click', function (e) {
            var card = e.target.closest('.admin-tmdb-card');
            if (card) usarResultado(card);
        });
    }

    var btnQuitar = elems('btn-quitar-poster');
    if (btnQuitar) {
        btnQuitar.addEventListener('click', ocultarPoster);
    }

    var fileInput = elems('poster');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                mostrarBtnQuitar(true);
                setVal('field-tmdb-poster', '');
            }
        });
    }
}());
