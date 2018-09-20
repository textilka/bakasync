<?php
namespace middleware;
class auth {
    protected $container;
    
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    public function __invoke($request, $response, $next) {
        
        if ($this->container->auth->logged()) {
            return $next($request, $response);
        } else {
            return $response->withRedirect($this->container->router->pathFor('login'), 301);
        }
    }
}
