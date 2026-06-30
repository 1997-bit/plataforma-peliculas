(function () {
	const feed = document.querySelector("[data-feed]");
	if (!feed) return;

	const slides = feed.querySelectorAll("[data-feed-slide]");
	if (slides.length === 0) return;

	const observer = new IntersectionObserver(
		function (entradas) {
			entradas.forEach(function (entrada) {
				const info = entrada.target.querySelector("[data-feed-info]");
				if (!info) return;
				info.classList.toggle(
					"feed-info-visible",
					entrada.isIntersecting,
				);
			});
		},
		{ root: feed, threshold: 0.55 },
	);

	slides.forEach(function (slide) {
		observer.observe(slide);
	});
})();
