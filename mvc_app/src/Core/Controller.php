<?php

namespace App\Core;

class Controller
{
    protected array $container;

    public function __construct(array $container)
    {
        $this->container = $container;
    }

    protected function view(string $view, array $data = []): void
    {
        $viewInstance = new View($this->container['config'] ?? []);
        $viewInstance->render($view, $data + ['installerMessage' => $this->container['installerMessage'] ?? null]);
    }
}
