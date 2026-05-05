<?php

declare(strict_types=1);

require_once __DIR__ . '/backend/app.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (backend_handle_request($requestPath, $method)) {
	return;
}

frontend_render_entrypoint("fe-pixi");
