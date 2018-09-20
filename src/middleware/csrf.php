<?php

namespace middleware;

class csrf {
    use \traits\sendResponse;
    protected $container;
    
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    public function __invoke($request, $response, $next) {
        $args = $request->getAttribute('routeInfo')[2];
        $path = $request->getAttribute('routeInfo')['request'][1];
        
        $csrf_status = @$args['csrf_status'];
        if ($csrf_status === false) {
            $this->container->logger->addInfo("CSRF failed for " . $path);
            $this->redirectWithMessage($response, "dashboard", "error", ["Communication error!", "Please try again"]);
            return $response;
        } else {
            return $next($request, $response);
        }
        
    }
    protected function escapeName($name) {
        $name = preg_replace('/[^\x20-\x7E]/','', $name);
        return isset($name) ? $name : null;
    }
}