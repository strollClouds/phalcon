<?phpnamespace Kernel;use Phalcon\Config\Adapter\Php as ConfigPhp;use Phalcon\Di\FactoryDefault;use Phalcon\Mvc\Url as UrlResolver;final class Service{    private static $container;    /**     * 获取容器     * @return FactoryDefault     */    public static function getContainer()    {        if(!self::$container){            self::$container = new FactoryDefault();            self::$container->setShared('config', 'Kernel\\Service\\ConfigService');            $serviceConfig = new ConfigPhp(BASE_PATH . '/common/config/service.php');            $config = self::getService('config')->getConfig()->modules;            foreach ($config as $key => $value){                $path = APP_PATH . '/' . $key . '/config/service.php';                if(file_exists($path)){                    $serviceConfig->merge(new ConfigPhp($path));                }            }            foreach ($serviceConfig as $key => $value) {                self::$container->setShared($key, $value->toArray());            }            /**             * Configure the Volt service for rendering .volt templates             */            self::$container->setShared('voltShared', function ($view) {                return self::getService('fileCache')->volt($view);            });            /**             * The URL component is used to generate all kinds of URLs in the application             */            self::$container->setShared('url', function () {                $url = new UrlResolver();                $url->setBaseUri(self::getService('config')->getConfig()->application->baseUri);                return $url;            });        }        return self::$container;    }    /**     * 设置容器对象     * @param $key     * @param $obj     */    public static function setService($key, $obj)    {        self::getContainer()->setShared($key, $obj);    }    public static function getService($key)    {        return  self::getContainer()->get($key);    }}