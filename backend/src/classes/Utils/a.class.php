<?php

class a implements Stringable {
    protected $controllerName;
    protected $actionName;
    protected $params;

    protected function __construct($controllerName, $actionName, $params) {
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
        $this->params = $params;
    }

    public static function href($controllerName, $actionName = 'default', array $params = array()){
        return new self($controllerName, $actionName, $params);
    }

    public function set($param, $value) {
        $this->params[$param] = $value;
    }

    public function __toString() {
        $url = PATH_WEB . $this->controllerName;
        if ($this->actionName != null && $this->actionName != 'default') {
            $url .= '/' . $this->actionName;
        }
        $paramString = http_build_query($this->params);
        if ($paramString) {
            $url .= '?' . $paramString;
        }
        return $url;
    }

    public function toString() {
        return (string)$this;
    }

} 