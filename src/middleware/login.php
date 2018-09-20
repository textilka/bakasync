<?php
namespace middleware;
class login {
    protected $container;
    
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    public function __invoke($request, $response, $next) {
        
        if ($this->container->auth->logged()) {
            return $response->withRedirect($this->container->router->pathFor('dashboard'), 301);
        } else {
            return $next($request, $response);
        }
    }
}