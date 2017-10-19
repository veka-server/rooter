<?php

namespace VekaServer\Rooter;


use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Rooter implements MiddlewareInterface {

    /**
     * @var array[][] $routes        The defined routes
     * @var callback  $error         The error handler, invoked as ($method, $path)
     * @var string    $baseNamespace The base namespace
     * @var string    $currentPrefix The current route prefix
     * @var mixed     $services      Application-wide service
     */
    private $routes;
    private $error;
    private $baseNamespace;
    private $currentPrefix;
    private $service;

    /**
     * Initiates the router and sets some default values
     *
     * @param callable $error route vers la page 404
     * @param string   $baseNamespace The base namespace
     */
    public function __construct($error, $baseNamespace = '')
    {
        $this->routes = [];
        $this->baseNamespace = $baseNamespace == '' ? '' : $baseNamespace.'\\';
        $this->currentPrefix = '';

        $this->set404($error);
    }

    /**
     * Sets a service object, which will be passed as first parameter to every call.
     *
     * @param mixed $service The service
     */
    public function setService($service) {
        $this->service = $service;
    }

    /**
     * Gets the currently set service
     *
     * @return mixed|null The service, or null if none is set.
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Adds a route to the specified collection
     *
     * @param string|string[] $method  The method(s) this route will react to
     * @param string $regex
     * @param callable        $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function route($method, $regex, $handler, $forceString = false)
    {
        if ($method == '*') {
            $method = ['GET', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'POST', 'HEAD'];
        }

        foreach ((array)$method as $m) {
            $this->addRoute($m, $regex, $handler, $forceString);
        }

        return $this;
    }

    private function addRoute($method, $regex, $handler, $forceString = false) {
        $this->routes[strtoupper($method)][$this->currentPrefix . $regex] = [$handler, $this->service, 'no-regex' => $forceString];
    }

    /**
     * Prefix a group of routes
     *
     * @param string $prefix The prefix
     * @param callable $routes callable(Router) wherein the mounted routes be added
     * @param mixed|mixed[]|false Custom service(s) for this route group
     * @return Rooter
     */
    public function mount($prefix, callable $routes, $service = false) {
        // Save current prefix and service
        $previousPrefix = $this->currentPrefix;
        $this->currentPrefix = $previousPrefix . $prefix;

        $previousService = null;
        if ($service !== false){
            $previousService = $this->service;
            $this->service = $service;
        }

        // Add the routes
        $routes($this);

        // Restore old prefix and service
        $this->currentPrefix = $previousPrefix;

        if ($service !== false) {
            $this->service = $previousService;
        }

        return $this;
    }

    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function get($regex, $handler, $forceString = false)
    {
        $this->addRoute('GET', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function getAndPost($regex, $handler, $forceString = false)
    {
        $this->addRoute('GET', $regex, $handler, $forceString);
        $this->addRoute('POST', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the POST route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function post($regex, $handler, $forceString = false)
    {
        $this->addRoute('POST', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the PUT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function put($regex, $handler, $forceString = false)
    {
        $this->addRoute('PUT', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the HEAD route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function head($regex, $handler, $forceString = false)
    {
        $this->addRoute('HEAD', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the DELETE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function delete($regex, $handler, $forceString = false)
    {
        $this->addRoute('DELETE', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the OPTIONS route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function options($regex, $handler, $forceString = false)
    {
        $this->addRoute('OPTIONS', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the TRACE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function trace($regex, $handler, $forceString = false)
    {
        $this->addRoute('TRACE', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * Adds a route to the CONNECT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param string $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function connect($regex, $handler, $forceString = false)
    {
        $this->addRoute('CONNECT', $regex, $handler, $forceString);
        return $this;
    }

    /**
     * definie la route vers la page d'erreur 404
     * @param $error
     */
    public function set404($error = null){

        if(!empty($error))
            $this->error = $error;
        else {
            $this->error = array(self::class, "default404");
        }
    }

    /**
     * Page d'erreur 404 par default
     */
    public function default404(){
        header('HTTP/1.1 404 Not Found');
        exit();
    }

    /**
     * Dispatches the router
     *
     * @param string $method The HTTP method, most likely from $_SERVER['REQUEST_METHOD']
     * @param string $path   The request path, most likely from some URL rewrite ?r=
     * @return mixed The router output
     */
    public function dispatch($method, $path)
    {
        // If there are no routes for that method, just error immediately
        if (!isset($this->routes[$method])) {
            $h = $this->error;
            return $h($method, $path);
        } else {
            // Loop over all given routes
            foreach ($this->routes[$method] as $regex => $route) {
                $len = strlen($regex);
                if ($len > 0) {
                    // Get route
                    $callback = $route[0];
                    $service = isset($route[1]) ? $route[1] : [];

                    // Fix missing begin-/
                    if ($regex[0] != '/')
                        $regex = '/' . $regex;

                    // Fix trailing /
                    if ($len > 1 && $regex[$len - 1] == '/')
                        $regex = substr($regex, 0, -1);

                    if($route['no-regex']) {
                        $test = ($regex == $path);
                        $params = array();
                    } else {
                        // Prevent @ collision
                        $regex = str_replace('@', '\\@', $regex);
                        $test = preg_match('@^' . $regex . '$@', $path, $params);
                    }


                    // If the path matches the pattern
                    if ($test) {
                        // Pass the params to the callback, without the full url
                        array_shift($params);

                        return $this->call($callback, array_merge($service, $params));
                    }


                }
            }
        }

        // Nothing found --> error handler
        return $this->call($this->error, [$method, $path]);
    }

    /**
     * Internal function to parse and call custom callables
     *
     * @param mixed $callable string, string[] or callable to call
     * @param array $params   The parameters to send to call_user_func_array
     * @return mixed The results from the call
     */
    private function call($callable, array $params = []) {
        if (is_string($callable)) {
            if (strlen($callable) > 0) {
                if ($callable[0] == '@') {
                    $callable = $this->baseNamespace . substr($callable, 1);
                }
            } else {
                throw new \InvalidArgumentException('Route/error callable as string must not be empty.');
            }
            $callable = str_replace('.', '\\', $callable);
        }
        if (is_array($callable)) {
            if (count($callable) !== 2)
                throw new \InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');
            if (strlen($callable[0]) > 0) {
                if ($callable[0][0] == '@') {
                    $callable[0] = $this->baseNamespace . substr($callable[0], 1);
                }
            } else {
                throw new \InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');
            }
            $callable[0] = str_replace('.', '\\', $callable[0]);
        }

        // Call the callable
        return call_user_func_array($callable, $params);
    }

    public static function extractPage($request_uri, $script_name){

        $url = urldecode($request_uri );

        $pos = strpos($url, '?');
        return '/'. trim(
                substr($pos !== false
                    ?   substr($url, 0, $pos)
                    :   $url,
                    strlen(implode('/', array_slice(explode('/', $script_name), 0, -1)) .'/')),
                '/');
    }

    /**
     * Dispatches the router using data from the $_SERVER global
     *
     * @return mixed Router output
     */
    public function dispatchGlobal()
    {
        return $this->dispatch(
            $_SERVER['REQUEST_METHOD'],
            self::extractPage($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])
        );
    }


    /**
     * Methode appeler lors de l'utilisation par middleware
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $response = $handler->handle($request);

        $stream = $response->getBody();

        $ServerParams = $request->getServerParams();

        ob_start();

        $this->dispatch(
            $ServerParams['REQUEST_METHOD'],
            self::extractPage($ServerParams['REQUEST_URI'], $ServerParams['SCRIPT_NAME'])
        );

        $data_return = ob_get_contents();
        ob_end_clean();

        $stream->write($data_return);
        $response->withBody($stream);

        return $response;
    }

}