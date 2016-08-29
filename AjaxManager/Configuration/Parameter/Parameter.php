<?php
namespace NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter;

/**
 * 
 */
class Parameter {
    
    /**
     * @var string : the parameter name
     */
    protected $name;

    /**
     * @var bool : the parameter require field, define if the parameter is required or not 
     */
    protected $required;

    /**
     * @var mixed : the request value
     */
    protected $value;

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------

    /**
     * Define if the parameter is required, the default value can be define too
     * 
     * @param boolean $required
     * @param mixed $default
     * @return \NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\Parameter
     * @throws \Exception
     */
    public function required($required = true) {
        if (!is_bool($required)) {
            throw new \Exception("Type error for 'required' parameter for required() method.");
        }
        $this->required = $required;
        return $this;
    }

    /**
     * Return TRUE if the parameter is required
     * 
     * @return bool
     */
    public function isRequired() {
        return $this->required;
    }
    
    /**
     * @param array $request_params
     */
    public function isValid($request_params) {
        
    }
    
    public function displayInfos(){
        
    }
    
    //--------------------------------------------------------------------------
    //  Protected methods
    //--------------------------------------------------------------------------

    /**
     * Check, if parameter is require, if the request parameter is set
     * 
     * @param array $request_params
     * @return boolean
     */
    protected function checkRequire($request_params) {
        return ($this->isRequired() && isset($request_params[$this->name])) || !$this->isRequired();
    }
    
    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------
    function getName() {
        return $this->name;
    }

    function getRequired() {
        return $this->required;
    }

    function getValue() {
        return $this->value;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setRequired($required) {
        $this->required = $required;
    }

    function setValue($value) {
        $this->value = $value;
    }
}
