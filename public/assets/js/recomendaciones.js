(function () {
	const feed = document.querySelector("[data-feed]");
	if (!feed) return;

	const lista = feed.querySelector("[data-feed-lista]");
	const sentinel = feed.querySelector("[data-feed-sentinel]");
	const cargandoEl = feed.querySelector("[data-feed-cargando]");
	const finEl = feed.querySelector("[data-feed-fin]");
	const estrellaTpl = document.getElementById("feed-plantilla-estrella");
	const estrellaHtml = estrellaTpl ? estrellaTpl.textContent : "";

	const tipo = feed.dataset.tipo || "movie";
	let offset = parseInt(feed.dataset.offset, 10) || 0;
	let hasMore = feed.dataset.hasMore === "1";
	let cargandoAhora = false;
	let controller = null;

	const idsVistos = new Set();
	feed.querySelectorAll("[data-feed-slide]").forEach(function (slide) {
		idsVistos.add(slide.dataset.id);
	});

	// revela titulo/sinopsis/rating con fade-in cuando el slide queda
	// centrado en el viewport (mismo criterio para slides iniciales y
	// los agregados via scroll infinito)
	const infoObserver = new IntersectionObserver(
		function (entradas) {
			entradas.forEach(function (entrada) {
				const info = entrada.target.querySelector("[data-feed-info]");
				if (!info) return;
				info.classList.toggle("feed-info-visible", entrada.isIntersecting);
			});
		},
		{ root: feed, threshold: 0.55 },
	);

	function observarSlide(slide) {
		infoObserver.observe(slide);
	}

	feed.querySelectorAll("[data-feed-slide]").forEach(observarSlide);

	function crearSlide(item) {
		const section = document.createElement("section");
		section.className = "feed-slide";
		section.setAttribute("data-feed-slide", "");
		section.dataset.id = String(item.id);

		const fondo = document.createElement("div");
		fondo.className = "feed-fondo";
		if (item.backdrop) {
			fondo.style.backgroundImage = "url(" + JSON.stringify(item.backdrop) + ")";
		}
		section.appendChild(fondo);

		const veladura = document.createElement("div");
		veladura.className = "feed-veladura";
		section.appendChild(veladura);

		const info = document.createElement("div");
		info.className = "feed-info";
		info.setAttribute("data-feed-info", "");

		const posterMarco = document.createElement("div");
		posterMarco.className = "poster-marco feed-poster";
		const img = document.createElement("img");
		img.className = "poster-img";
		img.src = item.poster;
		img.alt = "";
		img.loading = "lazy";
		img.draggable = false;
		posterMarco.appendChild(img);
		info.appendChild(posterMarco);

		const texto = document.createElement("div");
		texto.className = "feed-texto";

		const link = document.createElement("a");
		link.className = "feed-titulo-link";
		link.href = "/contenido?id=" + encodeURIComponent(item.id) + "&tipo=" + encodeURIComponent(item.tipo);

		const h2 = document.createElement("h2");
		h2.className = "feed-titulo";
		h2.append(item.titulo);
		if (item.anio) {
			const anioSpan = document.createElement("span");
			anioSpan.className = "feed-anio";
			anioSpan.textContent = item.anio;
			h2.appendChild(anioSpan);
		}
		link.appendChild(h2);
		texto.appendChild(link);

		const sinopsis = document.createElement("p");
		sinopsis.className = "feed-sinopsis";
		sinopsis.textContent = item.overview;
		texto.appendChild(sinopsis);

		const rating = document.createElement("div");
		rating.className = "feed-rating";
		if (item.rating_count > 0) {
			if (estrellaHtml) rating.insertAdjacentHTML("beforeend", estrellaHtml);
			const span = document.createElement("span");
			const plural = item.rating_count === 1 ? "" : "es";
			span.textContent = Number(item.rating_avg).toFixed(1) + " · " + item.rating_count + " calificación" + plural;
			rating.appendChild(span);
		} else {
			const span = document.createElement("span");
			span.textContent = "Sin calificaciones";
			rating.appendChild(span);
		}
		texto.appendChild(rating);

		info.appendChild(texto);
		section.appendChild(info);

		return section;
	}

	function cargarMas() {
		if (cargandoAhora || !hasMore || !lista) return;
		cargandoAhora = true;
		if (cargandoEl) cargandoEl.hidden = false;

		// una sola peticion en vuelo: cancela la anterior si todavia
		// no respondio (evita condiciones de carrera al scrollear rapido)
		if (controller) controller.abort();
		controller = new AbortController();

		const url = "/recomendaciones/mas?tipo=" + encodeURIComponent(tipo) + "&offset=" + offset;

		fetch(url, { signal: controller.signal })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				const items = Array.isArray(data.items) ? data.items : [];

				items.forEach(function (item) {
					const id = String(item.id);
					if (idsVistos.has(id)) return;
					idsVistos.add(id);
					const slide = crearSlide(item);
					lista.appendChild(slide);
					observarSlide(slide);
				});

				offset += items.length;
				hasMore = Boolean(data.hasMore) && items.length > 0;

				if (cargandoEl) cargandoEl.hidden = true;
				if (!hasMore && finEl) finEl.hidden = false;
			})
			.catch(function (err) {
				if (err && err.name === "AbortError") return;
				if (cargandoEl) cargandoEl.hidden = true;
			})
			.finally(function () {
				cargandoAhora = false;
			});
	}

	if (!hasMore && finEl) finEl.hidden = false;

	if (sentinel) {
		const scrollObserver = new IntersectionObserver(
			function (entradas) {
				entradas.forEach(function (entrada) {
					if (entrada.isIntersecting) cargarMas();
				});
			},
			// rootMargin dispara la carga un poco antes de llegar al final,
			// para que el usuario no note el corte
			{ root: feed, rootMargin: "600px 0px", threshold: 0 },
		);
		scrollObserver.observe(sentinel);
	}
})();
