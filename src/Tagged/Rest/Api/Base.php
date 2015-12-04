<?php
namespace Tagged\Rest\Api;

use Klein\Exceptions\DispatchHaltedException;

class Base {
    private $inputSchemas = array();
    private $outputSchemas = array();

    // Defines a custom mapping between HTTP methods
    // and the controller actions
    protected $resourceMapping = array(
    );

    protected $collectionMapping = array(
    );

    public function fetch(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::fetch is undefined",400);
    }

    public function update(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::update is undefined",400);
    }

    public function delete(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::delete is undefined",400);
    }

    public function find(\stdClass $params) {
        if ($this->respondsTo('index')) {
            throw new DispatchHaltedException(null, DispatchHaltedException::SKIP_THIS);
        } else {
            $class = get_class($this);
            throw new \Exception("Method $class::find is undefined",400);
        }
    }

    public function create(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::create is undefined",400);
    }

    public function bulkUpdate(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::bulkUpdate is undefined",400);
    }

    public function deleteAll(\stdClass $params) {
        $class = get_class($this);
        throw new \Exception("Method $class::deleteAll is undefined",400);
    }

    public function index($params) {
        if ($this->respondsTo('find')) {
            throw new DispatchHaltedException(null, DispatchHaltedException::SKIP_THIS);
        } else {
            $class = get_class($this);
            throw new \Exception("Method $class::index is undefined",400);
        }
    }

    /*
     * Get a web enabled version of the controller.
     * Right now it's not actually wrapped.
     */
    public static function api() {
        return new static();
    }

    /*
     * Get a version of the controller for raw access,
     * like from code. Used when you want to hit the
     * api locally without an http request.
     */
    public static function raw(...$params) {
        return new RawWrapper(new static(...$params));
    }

    /**
     * Register a schema for a method.
     * @param string $method name of a handler method
     * @param array $schema a php assoc array representing a json schema to pass as the constructor argument for a Tagged\Rest\Schema\Validator class
     */
    protected function _registerInputSchema($method, $schema) {
        if (!isset($schema['type']) || $schema['type'] !== 'object') {
            $type = isset($schema['type']) ? json_encode($schema['type']) : "null";
            trigger_error("Invalid input schema for an HTTP request: " . $type, E_USER_NOTICE);
            throw new InvalidArgumentException("Invalid input schema for an HTTP request: " . $type);
        }
        $this->inputSchemas[$method] = new \Tagged\Rest\Schema\Validator($schema);
    }

    protected function _registerOutputSchema($method, $schema) {
        $this->ouputSchemas[$method] = new \Tagged\Rest\Schema\Validator($schema);
    }

    protected function _routeableMethods() {
        return array_keys($this->inputSchemas);
    }

    /*
     * Hook a method as a custom handler for an http action.
     * Multiple callbacks can be specified for the same action,
     * so 3 methods could respond to GET requests
     */
    protected function _customResourceHandler($action, $method) {
        $this->resourceMapping[$method] = $action;
    }

    protected function _customCollectionHandler($action, $method) {
        $this->collectionMapping[$method] = $action;
    }

    // alias for _customCollectionHandler
    protected function _customHandler($action, $method) {
        $this->_customCollectionHandler($action, $method);
    }

    protected function _validateInputFor($method, $args) {
        return $this->inputSchemaFor($method)->validate($args);
    }

    /*
     * Returns whether a method by that name exists, and
     * responds to http requests. Methods that would not
     * respond to an http request return false even if
     * they exist on the object.
     */
    public function respondsTo($method) {
        return in_array($method, $this->_routeableMethods());
    }

    public function inputSchemaFor($method) {
        if (!isset($this->inputSchemas[$method])) {
            return new \Tagged\Rest\Schema\Validator(array());
        }
        return $this->inputSchemas[$method];
    }

    public function outputSchemaFor($method) {
        if (!isset($this->outputSchemas[$method])) {
            return array();
        }
        return $this->outputSchemas[$method];
    }

    /*
     * This is the function that actually invokes the method.
     * Call this if you want to filter the input using the
     * schema registered for the method
     */
    public function invoke($action, array $params) {
        $params = $this->_validateInputFor($action, $params);
        $params = json_decode(json_encode($params));
        return json_decode(json_encode($this->$action($params)));
    }

    public function invokeWithRequest($action, \Klein\Request $request, \Klein\Response $response) {
        $params = $request->params();

        try{
            $result = $this->invoke($action,$params);
            $result = $this->_formatResponse($action,$result, $request->format);
        } catch(\Exception $e) {
            $result = $this->_formatResponse($action, array(
                "error"=>array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage(),
                    "trace"=>$e->getTrace()
                )
            ), $request->format);
        }

        // Using this instead of $response->json json expects an array, and some subclasses may have _formatResponse do something other than json_encode.
        $response->header('Content-Type', $this->_getContentType($request->format));
        $response->body($result);
    }

    /**
     * Gets the Content-Type to send to the requester.
     * Override this in the subclass to have different Content-Types for an http request.
     *
     * @param $format string format requested by client (unused, use this the subclass)
     * @return string value of Content-Type to send.
     */
    protected function _getContentType($format) {
        return 'application/json; charset=UTF-8';
    }

    /*
     * Override in the subclass to have different formats
     * for an http request. This only works for http requests
     * and not for internal code calls
     */
    protected function _formatResponse($action,$result,$format) {
        return json_encode($result);
    }

    // Stub. Will add documentation later
    public function getDocumentation() {
        return new ApiDocumentor($this);
    }

    public function __call($action, $args) {
        if (method_exists($this, $action)) {
            return $this->invoke($action, $args);
        }

        throw new \Exception("Method $action not declared for ".get_class($this));
    }
}
