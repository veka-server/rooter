<?php

namespace VekaServer\Rooter;


use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Rooter implements MiddlewareInterface {

    /**
     * @var array[][] $routes        The defined routes
     * @var callback  $error         The error handler, invoked as ($method, $path)
     * @var string    $baseNamespace The base namespace
     * @var string    $currentPrefix The current route prefix
     */
    private $routes;
    private $error;
    private $baseNamespace;
    private $currentPrefix;
    public RequestHandlerInterface|null $handler = null;
    public ServerRequestInterface|null $request = null;
    protected const DEFAULT_404 = "default404";

    /**
     * Initiates the router and sets some default values
     *
     * @param callable $error route vers la page 404
     * @param string   $baseNamespace The base namespace
     */
    public function __construct($error = null, $baseNamespace = '')
    {
        $this->routes = [];
        $this->baseNamespace = $baseNamespace == '' ? '' : $baseNamespace.'\\';
        $this->currentPrefix = '';

        $this->set404($error);
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
    public function route($method, $regex, $handler, $options = [])
    {
        if ($method == '*') {
            $method = ['GET', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'POST', 'HEAD'];
        }

        foreach ((array)$method as $m) {
            $this->addRoute($m, $regex, $handler, $options = []);
        }

        return $this;
    }

    private function addRoute($method, $regex, $handler, $options = []) {
        $this->routes[strtoupper($method)][$this->currentPrefix . $regex] = [$handler, 'no-regex' => $options['no-regex'] ?? false, 'options' => $options];
    }

    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function get($regex, $handler, $options = [])
    {
        $this->addRoute('GET', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the GET route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function getAndPost($regex, $handler, $options = [])
    {
        $this->addRoute('GET', $regex, $handler, $options);
        $this->addRoute('POST', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the POST route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function post($regex, $handler, $options = [])
    {
        $this->addRoute('POST', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the PUT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function put($regex, $handler, $options = [])
    {
        $this->addRoute('PUT', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the PATCH route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function patch($regex, $handler, $options = [])
    {
        $this->addRoute('PATCH', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the HEAD route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function head($regex, $handler, $options = [])
    {
        $this->addRoute('HEAD', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the DELETE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function delete($regex, $handler, $options = [])
    {
        $this->addRoute('DELETE', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the OPTIONS route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function options($regex, $handler, $options = [])
    {
        $this->addRoute('OPTIONS', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the TRACE route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function trace($regex, $handler, $options = [])
    {
        $this->addRoute('TRACE', $regex, $handler, $options);
        return $this;
    }

    /**
     * Adds a route to the CONNECT route collection
     *
     * @param string $regex    The path, allowing regex
     * @param callable $handler The handler
     * @param boolean $forceString desactive l'analyse regex. default : false
     * @return Rooter
     */
    public function connect($regex, $handler, $options = [])
    {
        $this->addRoute('CONNECT', $regex, $handler, $options);
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
            $this->error = array(self::class, self::DEFAULT_404);
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
        $params = [];
        $route = $this->getRouteByURI($method, $path, $params);

        // Nothing found --> error handler
        if(empty($route)){
            return $this->call($this->error, [$method, $path], []);
        }

        // Pass the params to the callback, without the full url
        array_shift($params);
        $callback = $route[0];
        $options = $route['options'];

        return $this->call($callback, $params, $options);
    }

    public function getRouteByURI($method, $path, &$params = [])
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $regex => $route) {
            if ($this->checkRoute($regex, $route, $path,$params)) {
                return $route;
            }
        }

        return null;
    }

    public function checkRoute($regex, $route, $path, &$params)
    {
        $len = strlen($regex);
        if ($len <= 0) {
            return false;
        }

        $params = [];

        // Fix missing begin-/
        if ($regex[0] != '/') {
            $regex = '/' . $regex;
        }

        // Fix trailing /
        if ($len > 1 && $regex[$len - 1] == '/') {
            $regex = substr($regex, 0, -1);
        }

        if ($route['no-regex']) {
            $test = ($regex == $path);
        } else {
            // Prevent @ collision
            $regex = str_replace('@', '\\@', $regex);
            $test = preg_match('@^' . $regex . '$@', $path, $params);
        }

        return ($test);
    }

    /**
     * Internal function to parse and call custom callables
     *
     * @param mixed $callable string, string[] or callable to call
     * @param array $params   The parameters to send to call_user_func_array
     * @return mixed The results from the call
     */
    protected function call($callable, array $params = [], $options = []) {
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

        ob_start();

        // Call the callable
        $data = call_user_func_array($callable, $params);

        $dataFromEcho = ob_get_contents();
        ob_end_clean();

        $data = $data ?? ($dataFromEcho ?? '');

        // si pas en mode middleware
        if(is_null($this->handler)){
            return $data;
        }

        // si nous avons directement une response
        if($data instanceof ResponseInterface){
            return $data;
        }

        // sinon on genere une response
        $response = $this->handler->handle($this->request);
        $stream = $response->getBody();
        $stream->write($data);
        $response->withBody($stream);

        // si pas de content-type on le force en text/html
        if(empty($response->getHeaderLine('Content-Type'))){
            $response = $response->withHeader('Content-Type', 'text/html');
        }

        return $response;
    }

    public static function extractPage($request_uri, $script_name){

        $url = urldecode($request_uri );

        $pos = strpos($url, '?');
        return '/'. trim(
                $pos !== false
                    ?   substr($url, 0, $pos)
                    :   $url,
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->handler = $handler;
        $this->request = $request;
        $ServerParams = $request->getServerParams();

        $response = $this->dispatch(
            $ServerParams['REQUEST_METHOD'],
            self::extractPage($ServerParams['REQUEST_URI'], $ServerParams['SCRIPT_NAME']),
            $request
        );

        return $response;
    }

}
