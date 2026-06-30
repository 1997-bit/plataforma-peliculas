# Diseño, guia rapida

Para que el equipo sepa que estamos haciendo con el diseño sin preguntar.

## Idea principal

Usamos variables css (tokens).

Archivos clave:
[`public/assets/css/base.css`](../public/assets/css/base.css) los tokens base

## Colores

Usamos variables con nombre.

```css
background: var(--bg);
color: var(--texto);
```

Rojo siempre es error. Amarillo siempre es pendiente. Verde siempre
es exito.

## Tipografia con clamp

```css
--text-base: clamp(1rem, 2.5vw, 1.125rem);
```

Minimo 1rem, maximo 1.125rem, crece solo segun el ancho de pantalla.
Sin media queries.

## Espaciado

Multiplos de 4px.

```css
--esp-2: 0.5rem;
--esp-4: 1rem;
--esp-6: 1.5rem;
```

## Propiedades usadas

### scrollbar gutter

```css
html {
	scrollbar-gutter: stable;
}
```

Reserva el espacio de la barra de scroll siempre. Sin esto el
contenido salta cuando una pagina pasa de corta a larga.

### view transitions

```css
@view-transition {
	navigation: auto;
}
```

Fade automatico al navegar entre paginas, sin js. Solo funciona si
ambas paginas (salida y entrada) tienen esta regla.

### user invalid

```css
input:user-invalid {
	border-color: var(--ds-error);
}
```

Pone el campo rojo solo despues de intentar enviar el formulario,
no mientras el usuario todavia esta escribiendo.

### mask image

```css
mask-image: linear-gradient(
	to right,
	transparent 0%,
	black 15%,
	black 85%,
	transparent 100%
);
```

Difumina los bordes de una imagen.

### oklch

```css
oklch(70% 0.15 231)
```

Luminosidad, saturacion, tono. Permite generar variantes de un
color cambiando un solo numero.
