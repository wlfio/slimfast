<?php

namespace WlfIO\SlimFast;

use DI\Container;
use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;

class SlimFast
{
    const APP_START = "APP_START";
    const APP_ROOT = "APP_ROOT";
    const APP_NAME = "APP_NAME";
    const SLIMFAST_ROOT = "SLIMFAST_ROOT";
    const DEBUG_ENABLED = "DEBUG_ENABLED";

    private static $instance;

    /** @var App */
    private $app;
    private $container = null;

    public static function Instance(): SlimFast
    {
        if (empty(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->setup();
    }

    protected function setup()
    {
        self::Defines();

        $this->setupContainer();

        $this->createSlim();

        $this->setupMiddleware();

        $this->populateRoutes();
    }

    protected function setupContainer()
    {
        $builder = new ContainerBuilder();

        $builder->addDefinitions(
            $this->getContainerDefinitions()
        );

        $this->container = $builder->build();
    }

    protected function getContainerDefinitions(){
        $definitions = [];

        $twigPath = APP_ROOT . "/srv/Views";
        if(file_exists($twigPath)){
            $definitions[Twig::class] = function(Container $c) use ($twigPath) {
                $view = new Twig(
                    [$twigPath],
                    [
                        "cache" => false,
                        "debug" => true,
                    ]
                );

                $view->offsetSet("app_name", $this->getAppName());
                $view->offsetSet("year", date("Y"));

                return $view;
            };
        }
        return $definitions;
    }

    protected function populateRoutes($path = null)
    {
        $path = $path ?? APP_ROOT . "/src/routes";
        Router::Instance($path)->populateRoutes($this->app);
    }

    protected function getContainerAliases()
    {
        return [];
    }

    private function populateContainerAliases(ContainerBuilder $builder)
    {
        foreach ($this->getContainerAliases() as $alias => $class) {
            if ($alias !== $class) {
                $builder->addDefinitions()[$alias] = function (Container $c) use ($class) {
                    return $c->get($class);
                };
            }
        }
    }

    protected function setupMiddleware()
    {

    }

    private function createSlim()
    {
        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
    }

    final public function getContainer()
    {
        return $this->container;
    }

    public static function Defines(array $params = [])
    {
        defined(self::APP_START) or define(self::APP_START, $params[self::APP_START] ?? microtime(true));
        defined(self::SLIMFAST_ROOT) or define(self::SLIMFAST_ROOT,
            realpath($params[self::SLIMFAST_ROOT] ?? __DIR__ . "/../"));
        defined(self::APP_ROOT) or define(self::APP_ROOT,
            realpath($params[self::APP_ROOT] ?? __DIR__ . "/../../../../"));
        defined(self::APP_NAME) or define(self::APP_NAME, $params[self::APP_NAME] ?? "SlimFastApp");
        defined(self::DEBUG_ENABLED) or define(self::DEBUG_ENABLED, ($params[self::DEBUG_ENABLED] ?? false) === true);
    }

    public function run()
    {
        $this->app->run();
    }
}
