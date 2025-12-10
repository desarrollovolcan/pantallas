<?php
function view_path(string $view): string
{
    return __DIR__ . '/../Views/' . $view . '.php';
}

function base_path(string $path = ''): string
{
    return rtrim(__DIR__ . '/../../' . ltrim($path, '/'), '/');
}
