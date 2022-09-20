<?php

namespace App\Core;

use Exception;
use InvalidArgumentException;

class Router
{
    public const ROUTE_FOUND = 0;
    public const ROUTE_NOT_FOUND = 1;
    public const ROUTE_METHOD_NOT_ALLOWED = 2;
    public const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var bool determine if the last match found route
     */
    private $resolved = false;

    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var array
     */
    private $namedRoutes = [];

    /**
     * @var array routes that have been checked since last match
     */
    private $resolvedRoutes = [];

    /**
     * @var string group URLs with prefix
     */
    private $groupPrefix = '';

    /**
     * @var array assign options to group of routes
     */
    private $groupOptions = [];

    /**
     * Check if the last match operation found a matching route
     * 
     * @return bool
     */
    public function resolved() : bool
    {
        return $this->resolved;
    }

    /**
     * Get routes
     * 
     * @return array
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Get checked routes since last match operation
     * 
     * @return array
     */
    public function getResolvedRoutes() : array
    {
        return $this->resolvedRoutes;
    }

    /**
     * Add a new route
     * 
     * @param array     $method     Array of Allowed methods
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler, could be anything
     * @param string    $options    Additional route options (optional)
     * @return self
     */
    public function map($method, $uri, $handler, $options=[]) : self
    {
        $method = (array) $method;
        $method = array_map('strtoupper', $method);

        foreach ($method as $m)
        {
            if (!in_array($m, self::ALLOWED_METHODS))
            {
                throw new InvalidArgumentException(
                    sprintf('Invalid methods provided, (%s) are the only methods allowed', implode(', ', self::ALLOWED_METHODS))
                );
            }
        }
        
        $uri = $this->groupPrefix . $uri;

        foreach ($this->groupOptions as $groupOptions) {
            foreach ($groupOptions as $optionKey => $optionValue) {
                if ($optionKey == 'middlewares') {
                    $options['middlewares'] = isset($options['middlewares']) ? (array) $options['middlewares'] : [];
                    $optionValue = (array) $optionValue;
                    foreach ($optionValue as $middleware) {
                        array_unshift($options['middlewares'], $middleware);
                    }
                }
            }
        }

        $route = [$method, $uri, $handler, $options];
        $this->routes[] = $route;

        $name = isset($options['name']) ? $options['name'] : null;

        if ($name && !isset($this->namedRoutes[$name])) 
        { 
            $this->namedRoutes[$name] = $uri; 
        }
        
        return $this;
    }

    /**
     * Shortcut to add a route with GET method
     * 
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function get($uri, $handler, $name=null) : self
    {
        return $this->map('GET', $uri, $handler, $name);
    }

    /**
     * Shortcut to add a route with POST method
     * 
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function post($uri, $handler, $name=null) : self
    {
        return $this->map('POST', $uri, $handler, $name);
    }

    /**
     * Shortcut to add a route with PUT method
     * 
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function put($uri, $handler, $name=null) : self
    {
        return $this->map('PUT', $uri, $handler, $name);
    }

    /**
     * Shortcut to add a route with PATCH method
     * 
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function patch($uri, $handler, $name=null) : self
    {
        return $this->map('PATCH', $uri, $handler, $name);
    }

    /**
     * Shortcut to add a route with DELETE method
     * 
     * @param string    $uri        Route string to be matched
     * @param mixed     $handler    Route handler
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function delete($uri, $handler, $name=null) : self
    {
        return $this->map('DELETE', $uri, $handler, $name);
    }

    /**
     * Shortcut to add a route with POST method
     * 
     * @param string    $uri        Route string to be matched
     * @param callback  $callback   Route callback
     * @param string    $name       Route name (optional)
     * @return self
     */
    public function any($uri, $callback, $name=null) : self
    {
        return $this->map(self::ALLOWED_METHODS, $uri, $callback, $name);
    }
    
    /**
     * Create a routes group
     * 
     * @param string    $prefix     Prefix to prepend to route
     * @param callback  $callback   The grouping callback
     * @param array     $options    Group options
     * @return self
     */
    public function group(string $prefix, callable $callback, array $options=[]) : self
    {
        if (!is_callable($callback))
        {
            throw new InvalidArgumentException('Invalid callback handler');
        }

        $previousGroupPrefix = $this->groupPrefix;
        $this->groupPrefix .= $prefix;
        $this->groupOptions[] = $options;
        $callback($this);
        $this->groupPrefix = $previousGroupPrefix;
        array_pop($this->groupOptions);

        return $this;
    }

    /**
     * Match the requested URI and method against the array of routes
     * and return an array with the results
     * 
     * @param string $uri       Requested URI
     * @param string $method    Requested method
     * @return array
     */
    public function match($uri = null, $method = null) : array
    {
        $this->resolved = false;
        $this->resolvedRoutes = [];

        $uri    = $this->getUriPath($uri);
        $method = strtoupper($method ? $method : $_SERVER['REQUEST_METHOD']);
        $method = $method === 'HEAD' ? 'GET' : $method;
        $params = [];

        foreach ($this->routes as $route)
        {
            $this->resolvedRoutes[] = $route;
            [$r_method, $r_uri, $r_handler, $r_options] = $route;
            $r_uri = trim($r_uri, '/') ?: '/';
            
            if (($staticPrefixIndex = strpos($r_uri, '(')) !== false)
            {
                if (strncmp($uri, $r_uri, $staticPrefixIndex) === 0 && preg_match("#^$r_uri$#", $uri, $matches))
                {
                    $this->resolved = true;
                    array_shift($matches);
                    $params = $matches;
                }
            }
            elseif (strcmp($r_uri, $uri) === 0)
            {
                $this->resolved = true;
            }

            if ($this->resolved)
            {
                if (!in_array($method, $r_method))
                {
                    return [
                        'status'    => self::ROUTE_METHOD_NOT_ALLOWED, 
                        'allowed'   =>  $r_method
                    ];   
                }
                
                return [
                    'status'    => self::ROUTE_FOUND, 
                    'handler'   => $r_handler, 
                    'params'    => $params,
                    'options'   => $r_options
                ];
            }
        }
        
        return ['status' => self::ROUTE_NOT_FOUND];
    }

    /**
     * Reverse named route
     * 
     * @param string $name
     * @return string
     */
    public function url(string $name, array $params=[]) : string
    {
        if (!isset($this->namedRoutes[$name]))
        {
            throw new Exception("Route '{$name}' couldn't be found");
        }

        $patterns = [];
        $replacements = [];

        foreach ($params as $key => $value) 
        {
            $patterns[] = "#\(.+$key.+\)#";
            $replacements[] = $value;             
        }

        $url = preg_replace($patterns, $replacements, $this->namedRoutes[$name]);
        $url = preg_replace('#\(.+\)#', '', $url);
        
        return $url;
    }

    /**
     * Get the URI path from a given URI string
     * If no URI was given, then automaticaly detect path
     * 
     * @param string $uri Requested URI
     * @return string
     */
    private function getUriPath($uri) : string
    {
        $path = !empty($uri) ? $this->removeBaseUri(rawurldecode($uri)) : $this->detectUriPath();

        return trim($path, '/') ?: '/';
    }

    /**
     * Detect URI path
     * 
     * @return string
     */
    private function detectUriPath() : string
    {
        if (!empty($_SERVER['PATH_INFO'])) 
        {
            $path = $_SERVER['PATH_INFO'];
        } 
        else 
        {
            if (isset($_SERVER['REQUEST_URI'])) 
            {
                $path = $_SERVER['REQUEST_URI'];

                if($parsed_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) 
                {
                    $path = $parsed_path;
                }

                $path = rawurldecode($path);
            }
            elseif (isset($_SERVER['PHP_SELF'])) 
            {
                $path = $_SERVER['PHP_SELF'];
            } 
            else 
            {
                $path = '';
            }

            $path = $this->removeBaseUri($path);
        }
        
        return $path;
    }

    /**
     * Sanitize URI Path
     * 
     * @param string $path
     * @return string
     */
    private function removeBaseUri($path) : string
    {
        $pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);

        $base_url = $pathinfo['dirname'];
        $front_file =  '/' . $pathinfo['basename'];

        if (strpos($path, $base_url) === 0) 
        {
            $path = substr($path, strlen($base_url));
        }

        if (strpos($path, $front_file) === 0) 
        {
            $path = substr($path, strlen($front_file));
        }

        return $path;
    }
}