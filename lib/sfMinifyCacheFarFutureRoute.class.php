<?php

class sfMinifyCacheFarFutureRoute extends sfRoute {
    public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array()) {
        parent::__construct($pattern, $defaults, $requirements, $options);
    }

    public function matchesUrl($url, $context = array()) {
        $parameters = array_merge($this->getDefaultParameters(), $this->defaults);
        
        $pathStartPositon = strpos($url, $this->staticPrefix);
        if ($pathStartPositon === false) {
            return false;
        } 
        
        $starUrl = substr($url, $pathStartPositon + strlen($this->staticPrefix) + 1);
        
        $parser = new sfMinifyCacheFarFutureURLParser();
        $parser->parse($parameters, $starUrl);
        
        return $parameters;
    }

}