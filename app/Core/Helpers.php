<?php
function sanitizeInput($data): string
{
    return htmlspecialchars(strip_tags(trim((string) $data)));
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function base_path(string $path = ''): string
{
    $trimmed = ltrim($path, '/');
    $prefix = BASE_PATH === '' ? '' : BASE_PATH;
    return $prefix . '/' . $trimmed;
}
?>
