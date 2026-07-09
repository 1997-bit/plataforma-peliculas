(function () {
    'use strict';

    function byId(id) {
        return document.getElementById(id);
    }

    function crearCampoImagen(cfg) {
        var img = byId(cfg.imgId);
        var placeholder = byId(cfg.placeholderId);
        var fileInput = cfg.fileInputId ? byId(cfg.fileInputId) : null;
        var hint = cfg.hintId ? byId(cfg.hintId) : null;

        function mostrarImagen(src) {
            if (img) {
                delete img.dataset.broken;
                img.hidden = false;
                img.src = src;
            }
            if (placeholder) placeholder.hidden = true;
        }

        function mostrarPlaceholder() {
            if (img) {
                img.hidden = true;
                img.removeAttribute('src');
            }
            if (placeholder) placeholder.hidden = false;
        }

        if (img) {
            img.addEventListener('error', function () {
                if (img.getAttribute('src')) mostrarPlaceholder();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) {
                    mostrarImagen(URL.createObjectURL(fileInput.files[0]));
                    if (hint && cfg.hintArchivo) hint.textContent = cfg.hintArchivo;
                }
            });
        }

        return { mostrarImagen: mostrarImagen, mostrarPlaceholder: mostrarPlaceholder };
    }

    window.AdminPosterField = { crear: crearCampoImagen };
}());
