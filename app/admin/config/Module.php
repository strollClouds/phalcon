<?php

namespace Admin\Config;

use Admin\Plugins\SecurityPlugin;
use Phalcon\Config;
use Phalcon\DiInterface;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Loader;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;

class Module implements ModuleDefinitionInterface
{
    /**
     * Registers an autoloader related to the module
     *
     * @param DiInterface $di
     */
    public function registerAutoloaders(DiInterface $di = null)
    {
        $loader = new Loader();

        $loader->registerNamespaces([
            'Admin\\Controllers' => __DIR__ . '/controllers/',
            'Admin\\Entity' => __DIR__ . '/entity/',
            'Admin\\Models' => __DIR__ . '/models/',
            'Admin\\Plugins' => __DIR__ . '/plugins/',
        ]);

        $loader->register();
    }

    /**
     * Registers services related to the module
     *
     * @param DiInterface $di
     */
    public function registerServices(DiInterface $di)
    {
        /**
         * Try to load local configuration
         */

        $config = $di->get('config')->getConfig();

        $override = new \Phalcon\Config\Adapter\Php(__DIR__ . '/config.php');

        if ($config instanceof Config) {
            $config->merge($override);
        } else {
            $config = $override;
        }
        /**
         * Setting up the view component
         */
        $di->set('view', function () {
            $config = $this->get('config')->getConfig();
            $view = new View();
            $view->setViewsDir(realpath($config->get('application')->viewsDir));

            $view->registerEngines([
                '.volt' => 'voltShared',
                '.phtml' => PhpEngine::class,
            ]);

            return $view;
        });

        /**
         * Database connection is created based in the parameters defined in the configuration file
         */
        $di->set('db', function () {
            $config = $this->get('config')->getConfig();

            $dbConfig = $config->database->toArray();

            $dbAdapter = '\\Phalcon\\Db\\Adapter\\Pdo\\' . $dbConfig['adapter'];
            unset($config['adapter']);

            return new $dbAdapter($dbConfig);
        });

        $di->setShared('dispatcher', function () {

            // 创建一个事件管理器
            $eventsManager = new EventsManager();

            // 监听分发器中使用安全插件产生的事件
            $eventsManager->attach(
                "dispatch:beforeExecuteRoute",
                new SecurityPlugin()
            );

            // 处理异常和使用 NotFoundPlugin 未找到异常
            /*$eventsManager->attach(
            "dispatch:beforeException",
            new NotFoundPlugin()
            );*/
            $dispatcher = new \Phalcon\Mvc\Dispatcher();
            // 分配事件管理器到分发器
            //$dispatcher->setEventsManager($eventsManager);
            return $dispatcher;
        });
    }
}
