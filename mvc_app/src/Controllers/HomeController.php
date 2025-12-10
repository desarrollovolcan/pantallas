<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ScreenModel;
use App\Models\UserModel;

class HomeController extends Controller
{
    public function index(): void
    {
        $users = new UserModel($this->container['db']);
        $screens = new ScreenModel($this->container['db']);

        $this->view('home', [
            'title' => 'Dashboard inicial',
            'userCount' => $users->count(),
            'screenCount' => $screens->count(),
            'recentUsers' => $users->all(),
            'recentScreens' => $screens->all(),
        ]);
    }

    public function status(): void
    {
        $response = [
            'app' => $this->container['config']['name'] ?? 'MVC',
            'installed' => $this->container['installerMessage'] === null,
            'database' => 'sqlite',
            'php_version' => PHP_VERSION,
        ];

        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
