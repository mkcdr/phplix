<?php

namespace App\Core;

use Exception;
use App\Core\Router;
use App\Core\Database\Database;
use App\Core\Error\ErrorHandler;
use App\Core\Http\Exception\{
    HttpRouteMethodNotAllowedException,
    HttpRouteNotFoundException,
};

class Kernel
{
    /**
     * @var self
     */
    public static $App;

    /**
     * @var array Settings
     */
    private $settings;

    /**
     * @var ErrorHandler
     */
    private $err_h;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array $matchResults The result of match operation
     */
    private $matchResults=[];

    /**
     * @param array $settings Application settings
     */
    public function __construct(array $settings=[]) 
    {
        // Set the current app instance 
        self::$App = $this;
        $this->settings = $settings;
        $this->router = new Router();
        $this->err_h = new ErrorHandler($this);

        // initialize application
        $this->init();
    }

    /**
     * Initialize Application
     * 
     * @return void
     */
    public function init() : void
    {
        // Set the templates directory
        View::setPath($this->settings['application']['templatesDirectory']);

        // Connect to database if database settings are provided
        if (!empty($this->settings['database']['driver']))
        {
            Database::getInstance($this->settings['database']);
        }

        // Set custom error handlers
        $this->setCustomErrorHandlers();

        // Register app routes
        $this->registerRoutes();
    }

    /**
     * Return the debug mode status
     * 
     * @return bool
     */
    public function isDebug() : bool
    {
        return $this->settings['application']['environment'] === 'development';
    }

    /**
     * Return router
     * 
     * @return Router
     */
    public function getRouter() : Router
    {
        return $this->router;
    }

    /**
     * Set App Router
     * 
     * @param Router $router
     * @return void
     */
    public function setRouter(Router $router) : void
    {
        $this->router = $router;
    }

    /**
     * Register app routes
     * 
     * @return void
     */
    private function registerRoutes() : void
    {
        $registerRoutes = require_once $this->settings['application']['routeConfig'];
        $registerRoutes($this->router);
    }

    /**
     * Get match results
     * 
     * @return array
     */
    public function getMatchResults() : array
    {
        return $this->matchResults;
    }

    /**
     * Get error handler object
     * 
     * @return ErrorHandler
     */
    public function getErrorHandler() : ErrorHandler
    {
        return $this->err_h;
    }

    /**
     * Get error handler object
     * 
     * @param ErrorHandler $err_h
     */
    public function setErrorHandler(ErrorHandler $err_h) : void
    {
        $this->err_h = $err_h;
    }

    /**
     * Set custom error handlers from settings
     * 
     * @return void
     */
    private function setCustomErrorHandlers() : void
    {
        if (empty($this->settings['application']['errorHandlers']))
            return;
            
        foreach ($this->settings['application']['errorHandlers'] as $code => $handler)
        {
            $this->err_h->setCustomHandler($code, $handler);
        }
    }

    /**
     * Run App
     * 
     * @return void
     */
    public function run() : void
    {
        $this->matchResults = $this->router->match();

        switch ($this->matchResults['status']) {
            case Router::ROUTE_FOUND:

                $handler = $this->matchResults['handler'];
                $params  = $this->matchResults['params'];
                $options = $this->matchResults['options'];

                if (!empty($options['middlewares'])) {
                    foreach ($options['middlewares'] as $middleware) {
                        if (class_exists($middleware)) {
                            $middleware = new $middleware;
                        }
                        $middleware();
                    }
                }

                $handleParams = $params;
                foreach ($handleParams as $key => $value) {
                    if (!is_int($key)) {
                        unset($handleParams[$key]);
                    }
                }
                array_unshift($handleParams, $params);

                if (!is_array($handler) && is_callable($handler)) 
                {
                    $response = call_user_func_array($handler, $handleParams);
                }
                elseif (is_array($handler)) 
                {
                    [$class, $method] = $handler;
                    
                    if (!class_exists($class)) 
                    {
                        throw new Exception(sprintf("'%s' class not found", $class));
                    }

                    $class = new $class;

                    if (!method_exists($class, $method)) 
                    {
                        throw new Exception(sprintf("'%s' method not found in %s class.", $method, get_class($class)));
                    } 
                    
                    $response = call_user_func_array([$class, $method], $handleParams);
                }
                else
                {
                    throw new Exception('Route handler does not exist');
                }
                break;
            case Router::ROUTE_METHOD_NOT_ALLOWED:
                throw new HttpRouteMethodNotAllowedException();
                break;
            case Router::ROUTE_NOT_FOUND:
                throw new HttpRouteNotFoundException();
                break;
        }

        if (is_object($response) || is_array($response)) { 
            header('Content-Type: application/json; charset=UTF-8');
            $response = json_encode($response);
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD')
        {
            return;
        }

        echo $response;
    }

}