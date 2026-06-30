	<form method="POST" action="/logout">
		<input type="hidden" name="_csrf" value="<?= \App\Core\Session::generarCsrf() ?>">
		<button type="submit">Cerrar sesión</button>
	</form>
