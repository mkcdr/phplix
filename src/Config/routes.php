<?php

/**
 * App Routes
 */

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Middlewares\{
    AuthMiddleware,
    CsrfProtectMiddleware
};

/**
 * Configure App Routes
 * The Router support the GET, POST, PUT, PATCH and DELETE Http methods
 * 
 * @param Router $r
 * @return void
 */
return function (Router $r) {

    /**
     * Example:
     *  1- Every route pattern start with "/".
     *  2- Callback functions or array with controller name and action can be used.
     *  3- Naming the route can be done as an option to be used with the url() function to reverse URLs.
     */
    $r->get('/', [HomeController::class, 'index'], ['name' => 'home']);

    $r->get('/welcome', function() { return render_template('welcome.phtml'); });

    /**
     * Example:
     * Using named parameters.
     */
    $r->get('/article/(?P<slug>[^/]+)', function($params) {
        return ucwords(preg_replace('#[\-]+#', ' ', strip_tags($params['slug'])));
        
    });

    /**
     * Grouping routes with the same prefix
     */
    $r->group('/api', function($r) {

        /**
         * Example:
         *  1- Using anonymous capture groups as parameters.
         *  2- Returning arrays or objects will display a json response.
         */
        $r->get('/hello/(\w+)', function($params, $name) {
            return ['message' => sprintf("Hello, %s!", ucfirst($name))];
        });

        /**
         * Example:
         * Accessing parameters according to their order in the pattern.
         */
        $r->get('/sum/(\d+)/(\d+)', function($params, $num1, $num2) {
            return $num1 + $num2;
        });

    });

    /**
     * Exampe:
     * Using middlewares for a group
     */
    $r->group('/user', function($r) {

        $r->get('/logout', [UserController::class, 'logout'], ['name' => 'user.logout']);
        $r->map(['GET', 'POST'], '/login', [UserController::class, 'login'], ['name' => 'user.login']);

        $r->group('/settings', function($r) {
    
            $r->map(
                ['GET', 'POST'], 
                '/profile', 
                [UserController::class, 'profile'],
                ['name' => 'user.settings.profile']
            );
    
        }, ['middlewares' => AuthMiddleware::class]);

    }, ['middlewares' => CsrfProtectMiddleware::class]);

};
