<?php

namespace App\Core;

class View
{
    public function __construct(private array $config = [])
    {
    }

    public function render(string $view, array $data = []): void
    {
        $viewFile = view_path($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("La vista {$view} no existe");
        }

        extract($data, EXTR_SKIP);
        $appName = $this->config['name'] ?? 'Aplicación MVC';

        include view_path('layout');
    }
}
