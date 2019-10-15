<?php

namespace WlfIO\SlimFast;

use Slim\App as SlimApp;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Router
{
    private static $instances = [];

    private $routes = [];

    public static function Instance(string $path): Router
    {
        if (empty(self::$instances)) {
            $class = get_called_class();
            self::$instances[$path] = new $class($path);
        }
        return self::$instances[$path];
    }

    public function __construct(string $path)
    {
        $this->gatherRoutes($path);
    }

    private function gatherRoutes(string $path)
    {
        $path = realpath($path);
        if (!is_string($path)) {
            return;
        }
        if (file_exists($path)) {
            foreach (new \DirectoryIterator($path) as $file) {
                if ($file->isDot()) {
                    continue;
                }
                if ($file->isFile() && $file->getExtension() == "php") {
                    $this->loadRouteFile($file->getRealPath());
                } else {
                    if ($file->isDir()) {
                        $this->gatherRoutes($file->getRealPath());
                    }
                }
            }
        }
    }

    private function loadRouteFile(string $filePath)
    {
        require $filePath . "";
    }

    public function addRoute($method, string $pattern, $callback, $data = null)
    {
        $this->routes[] = [
            "slim" => [
                $method,
                $pattern,
                $callback
            ],
            "data" => $data
        ];
        return $this;
    }

    public function populateRoutes(SlimApp $app)
    {
        foreach ($this->routes as $route) {
            $app->map(...$route["slim"]);
        }
    }
}