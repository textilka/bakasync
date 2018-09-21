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

        if ($request->getAttribute('csrf_status') === false) {
            $this->redirectWithMessage($response, "dashboard", "error", ["message" => "Chyba komunikace, zkuste to prosím znovu"]);
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