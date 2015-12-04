<?php
namespace Tagged\Rest\Api;
use \Tagged\Rest\api;

/**
 * RawWrapper wraps an API. This allows it to be called with a request and coerces $params into
 * the format which $controller expects
 */
class RawWrapper {
    private $controller;

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function fetch($params) {
        return $this->controller->invoke('fetch',$params);
    }

    public function update($params) {
        return $this->controller->invoke('update',$params);
    }

    public function delete($params) {
        return $this->controller->invoke('delete',$params);
    }

    public function find($params) {
        return $this->controller->invoke('find',$params);
    }

    public function index($params) {
        return $this->controller->invoke('index',$params);
    }

    public function create($params) {
        return $this->controller->invoke('create',$params);
    }

    public function bulkUpdate($params) {
        return $this->controller->invoke('bulkUpdate',$params);
    }

    public function deleteAll($params) {
        return $this->controller->invoke('deleteAll',$params);
    }


    public function __call($method, $args) {
        if (!method_exists($this->controller,$method)) {
            $class = get_class($this->controller);
            throw new \BadMethodCallException("Method '$class::$method' does not exist");
        }

        if (empty($args)) {
            $args = [];
        } else {
            $params = $args[0];
        }

        if ($this->controller->respondsTo($method)) {
            return $this->controller->invoke($method,$params);
        }

        return json_decode(json_encode(call_user_func_array(
            array($this->controller,$method),
            $args
        )));
    }
}
