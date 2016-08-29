<?php

namespace NBO\Bundle\AjaxBundle\AjaxManager\Configuration;

use Symfony\Component\HttpFoundation\Request as Request;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\RouteParameter;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\FileRouteParameter;

/**
 * This class allow to create a request configuration,
 * define method request and other parameters
 */
class RouteConfiguration {

    const POST_METHOD = "POST"; // constant for POST method value
    const GET_METHOD = "GET";   // constant for GET method value

    /**
     * @var string : the request configuration method, must be GET or POST
     */

    protected $method;

    /**
     * @var array : the request parameters
     */
    protected $request_params;

    /**
     * @var array : the files request parameters
     */
    protected $files_request_params;

    /**
     * @var array : the configuration parameters
     */
    protected $config_params;

    /**
     * @var array : the files configuration parameters
     */
    protected $files_config_params;

    /**
     * @var array : the error if exists 
     */
    protected $error;
    
    protected $globalParametersConfig;
    
    protected $globalFilesParametersConfig;

    /**
     * Constructor
     * 
     * @param string $method : the request configuration method, must be POST (RouteConfiguration::POST_METHOD) or GET (RouteConfiguration::GET_METHOD)
     * @throws Exception
     */
    public function __construct(Request $request = null, $method = RouteConfiguration::GET_METHOD) {
        // if $method is not GET or POST method
        if (!in_array($method, array(RouteConfiguration::POST_METHOD, RouteConfiguration::GET_METHOD))) {
            throw new \Exception("Bad value for method request configuration. Must be " . implode(",", array(RouteConfiguration::POST_METHOD, RouteConfiguration::GET_METHOD)));
        }
        $this->method = $method;
        
        if(!is_null($request)){
            $this->request_params = $this->extractRouteParameters($request);
           $this->files_request_params = $this->extractFilesRouteParameters($request);
        }
        
        $this->config_params = array();
        $this->files_config_params = array();
        
        $this->error = null;

        $this->globalParametersConfig = array();
        $this->globalFilesParametersConfig = array();
    }

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------
    /**
     * Return TRUE if the request is a Xml Http request and the request method is equals to the configuration method, else return an error array
     * 
     * @param Request $request
     * @return mixed
     */
    public function validRequest(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            $this->error = array("message" => "The request is not a Xml Http request.");
            return $this->error;
        }
        if ($request->getMethod() != $this->method) {
            $this->error = array("message" => "Bad request method, must be '" . $this->method . "'.");
            return $this->error;
        }
        return true;
    }

    /**
     * Check all configuration parameters, if one is not valid return an error array, else TRUE
     * 
     * @return mixed
     */
    public function validParameters() {
        if (!empty($this->config_params) && is_array($error = $this->validRequestParams())) {
            return $error;
        }
        if (!empty($this->files_config_params) && is_array($error = $this->validFileRequestParams())) {
            return $error;
        }
        return true;
    }

    /**
     * Add parameter to the config request array
     * 
     * @param RouteParameter $parameter
     */
    public function addConfigParam(RouteParameter $parameter) {
        $this->config_params[] = $parameter;
    }

    /**
     * Add parameter to the files config request array
     * 
     * @param FileRouteParameter $parameter
     */
    public function addFileConfigParam(FileRouteParameter $parameter) {
        $this->files_config_params[] = $parameter;
    }

    /**
     * Add a global parameter config property
     * 
     * @param mixed $key
     * @param mixed $val
     */
    public function addGlobalConfigParam($key, $val) {
        $this->globalParametersConfig[$key] = $val;
    }

    /**
     * Add a global file parameter config property
     * 
     * @param mixed $key
     * @param mixed $val
     */
    public function addGlobalConfigFileParam($key, $val) {
        $this->globalFilesParametersConfig[$key] = $val;
    }

    /**
     * Extract the request parameters switch the configuration method
     * 
     * @param Request $request
     * @return array
     */
    public function extractRouteParameters(Request $request) {
        // if is POST method
        if ($this->method == RouteConfiguration::POST_METHOD) {
            return $request->request->all();
        }
        // else
        return $request->query->all();
    }

    /**
     * Extract files parameters
     * 
     * @param Request $request
     * @return array
     */
    public function extractFilesRouteParameters(Request $request) {
        return $request->files->all();
    }
    //--------------------------------------------------------------------------
    //  Privates methods
    //--------------------------------------------------------------------------

    /**
     * Check the request parameters, not the files parameters
     * Return an Array if not valid, else TRUE
     * 
     * @return boolean
     */
    private function validRequestParams() {
        $error = false;
        $cmp = 0;
        while (!$error && $cmp < sizeof($this->config_params)) {
            if (is_array($this->error = $this->config_params[$cmp]->isValid($this->request_params))) {
                $error = true;
            }
            $cmp++;
        }
        if ($error) {
            return $this->error;
        }
        return true;
    }

    /**
     * Check the files request parameters, not the request parameters
     * Return an Array if not valid, else TRUE
     * 
     * @return boolean
     */
    private function validFileRequestParams() {
        $error = false;
        $cmp = 0;
        while (!$error && $cmp < sizeof($this->files_config_params)) {
            if (is_array($this->error = $this->files_config_params[$cmp]->isValid($this->files_request_params))) {
                $error = true;
            }
            $cmp++;
        }
        if ($error) {
            return $this->error;
        }
        return true;
    }

    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------
    function getMethod() {
        return $this->method;
    }

    function getRequest_params() {
        return $this->request_params;
    }

    function getFiles_request_params() {
        return $this->files_request_params;
    }

    function getConfig_params() {
        return $this->config_params;
    }

    function getFiles_config_params() {
        return $this->files_config_params;
    }

    function getError() {
        return $this->error;
    }

    function getGlobalParametersConfig() {
        return $this->globalParametersConfig;
    }

    function getGlobalFilesParametersConfig() {
        return $this->globalFilesParametersConfig;
    }

    function setMethod($method) {
        if (!in_array($method, array(RouteConfiguration::POST_METHOD, RouteConfiguration::GET_METHOD))) {
            throw new \Exception("Bad value for method request configuration. Must be " . implode(",", array(RouteConfiguration::POST_METHOD, RouteConfiguration::GET_METHOD)));
        }
        $this->method = $method;
    }

    function setRequest_params($request_params) {
        $this->request_params = $request_params;
    }

    function setFiles_request_params($files_request_params) {
        $this->files_request_params = $files_request_params;
    }

    function setConfig_params($config_params) {
        $this->config_params = $config_params;
    }

    function setFiles_config_params($files_config_params) {
        $this->files_config_params = $files_config_params;
    }

    function setError($error) {
        $this->error = $error;
    }

    function setGlobalParametersConfig($globalParametersConfig) {
        $this->globalParametersConfig = $globalParametersConfig;
    }

    function setGlobalFilesParametersConfig($globalFilesParametersConfig) {
        $this->globalFilesParametersConfig = $globalFilesParametersConfig;
    }

}
