<?php

namespace NBO\Bundle\AjaxBundle\AjaxManager;

use Symfony\Component\Security\Core\SecurityContext as SecurityContext;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\RouteParameter as RouteParameter;
use NBO\Bundle\AjaxBundle\AjaxManager\Response\AjaxResponse as AjaxResponse;
use NBO\Bundle\AjaxBundle\AjaxManager\Route\AjaxRoute as AjaxRoute;

/**
 * AjaxRequestManager provide easy methods to manage a AJAX request,
 * Check if is a Xml Http request, if method is good, and other methods
 */
class AjaxRequest {

    /**
     * @var type 
     */
    protected $container;

    /**
     * @var Request : the request object
     */
    protected $request;

    /**
     * @var RequestConfiguration : the request configuration
     */
    protected $config;

    /**
     * @var array
     */
    protected $pre_config;

    /**
     *
     * @var AjaxRoute : the request route 
     */
    protected $route;

    /**
     * @var User 
     */
    protected $user;

    /**
     * Constructor
     * 
     * @param AppKernel $kernel
     * @param string $method
     */
    public function __construct($kernel) {
        $this->container = $kernel->getContainer();

        // store request
        $this->request = $this->container->get('request');
        // create new Ajax route manager
        $this->route = new AjaxRoute($this->container->get('router'), $this->request, null, $this->container->getParameter('nbo_ajax.pre_config'));
        // set error to NULL
        $this->error = null;
        // store the current user, if NULL user is not authenticated
        $this->user = $this->getCurrentUser($this->container->get('security.context'));
        
    }

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------


    /**
     * Define the request configuration method
     * 
     * @param string $method
     */
    public function setMethod($method) {
        $this->route->setMethod($method);
    }

    /**
     * Define the global parameters config
     * All parameters will be configurated as this config
     * The available keys should be the same addParameter config
     * 
     * @param array $config
     */
    public function setGlobalParametersConfig($config = array()) {
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $this->route->getConfig()->addGlobalConfigParam($key, $val);
            }
        }
    }

    /**
     * Define the global files parameters config
     * All files parameters will be configurated as this config
     * The available keys should be the same addFileParameter config
     * 
     * @param array $config
     */
    public function setGlobalFilesParametersConfig($config = array()) {
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $this->route->getConfig()->addGlobalConfigFileParam($key, $val);
            }
        }
    }

    //--------------------------------------------------------------------------

    /**
     * Add a parameter to the request configuration
     * 
     * @param string $name : the parameter name
     * @param array $config [=array ] : the config parameter
     * @return RouteParameter
     * @throws Exception
     */
    public function addParameter($name, $config = array()) {
        return $this->route->addParameter($name, $config);
    }

    /**
     * Add parameters to the request configuration
     * If you want to set up a parameter config, 
     * make an associative array where the key is the parameter name and the value is the parameter configuration
     * Else the value is the parameter name
     * 
     * Example: 
     *      array("param_0","param_1"=>array("require"=>false))
     *      will created two parameters (param_0 and param_1) with the config for param_1
     * 
     * @param array $parameters
     */
    public function addParameters($parameters) {
        $this->route->addParameters($parameters);
    }

    /**
     * Add a file parameter to the file request configuration
     * 
     * @param string $name
     * @param array $config
     * @return NBO\Bundle\AjaxBundle\AjaxManager\Parameter\FileRouteParameter
     * @throws Exception
     */
    public function addFileParameter($name, $config = array()) {
        return $this->route->addFileParameter($name, $config);
    }

    /**
     * Add file parameters to the file request configuration
     * If you want to set up a parameter config, 
     * make an associative array where the key is the parameter name and the value is the parameter configuration
     * Else the value is the parameter name
     * 
     * Example: 
     *      array("param_0","param_1"=>array("maxSize"=>1000))
     *      will created two parameters (param_0 and param_1) with the config for param_1
     * 
     * @param array $parameters
     */
    public function addFileParameters($parameters) {
        $this->route->addFileParameters($parameters);
    }

    //--------------------------------------------------------------------------

    /**
     * Return a Parameter by its name, NULL if not found
     * 
     * @param string $name : the parameter name
     * @return RouteParameter
     */
    public function getParameter($name) {
        return $this->route->findParam($name);
    }

    /**
     * Return the Parameter value by its name, NULL if not found
     * 
     * @param string $name
     * @return mixed
     */
    public function getParameterValue($name) {
        if (is_null($parameter = $this->getParameter($name))) {
            return null;
        }
        return $parameter->getValue();
    }

    /**
     * Return an array of all parameters values, 
     * the result is an associative array with parameters name as keys and parameters value as values
     * 
     * @return array
     */
    public function getData() {
        $result = array();
        foreach ($this->route->getConfig()->getConfig_params() as $parameter) {
            $result[$parameter->getName()] = $parameter->getValue();
        }
        foreach ($this->route->getConfig()->getFiles_Config_params() as $parameter) {
            $result[$parameter->getName()] = $parameter->getValue();
        }
        return $result;
    }

    /**
     * Get the configuration error
     * 
     * @return array|null
     */
    public function getError() {
        return $this->route->getConfig()->getError();
    }

    /**
     * Get the route roles
     * 
     * @return array|string
     */
    public function getRouteRoles() {
        return $this->route->getRoles();
    }

    /**
     * Check if is valid, return TRUE if valid, else FALSE
     * 
     * @return boolean
     */
    public function isValid() {
        return (!is_array($this->route->getConfig()->validRequest($this->request)) && !is_array($this->route->getConfig()->validParameters()) && !is_array($this->validRole()));
    }

    //--------------------------------------------------------------------------
    //  Responses methods
    //--------------------------------------------------------------------------

    /**
     * Create a JSON response
     * 
     * @param array $data
     * @return JsonResponse
     * @throws \Exception
     */
    public function createJsonResponse($data = array()) {
        $ajaxResponse = new AjaxResponse(AjaxResponse::JSON_RESPONSE);
        return $ajaxResponse->getResponse($data);
    }

    /**
     * Create a HTML response
     * 
     * @param string $view : the twig view
     * @return Response
     * @throws \Exception
     */
    public function createHtmlResponse($view) {
        $ajaxResponse = new AjaxResponse(AjaxResponse::HTML_RESPONSE);
        return $ajaxResponse->getResponse($view);
    }

    /**
     * Create JSON error response
     * If $error is null, get the configuration error for response content
     * By default, the configuration error is like {"message"=>"configuration error message"}
     * 
     * @param array $error : the data error
     * @param int $code : the error code
     * @return JsonResponse
     * @throws \Exception
     */
    public function createErrorJsonResponse($error = null, $code = 400) {
        $ajaxResponse = new AjaxResponse(AjaxResponse::JSON_RESPONSE);
        $errorData = is_null($error) ? $this->route->getConfig()->getError() : $error;
        return $ajaxResponse->getErrorResponse($errorData, $code);
    }

    /**
     * Create HTML error response
     * If $error is null, get the configuration error for response content
     * By default, the configuration error is like {"message"=>"configuration error message"}
     * 
     * @param string $view : the twig view
     * @param int $code : the error code
     * @return Response
     * @throws \Exception
     */
    public function createErrorHtmlResponse($view, $code = 400) {
        $ajaxResponse = new AjaxResponse(AjaxResponse::HTML_RESPONSE);
        return $ajaxResponse->getErrorResponse($view, $code);
    }

    //--------------------------------------------------------------------------
    //  Privates methods
    //--------------------------------------------------------------------------

    /**
     * Valid the current user role if one or more roles are defined for the route
     * 
     * @return boolean|array
     */
    private function validRole() {
        // Some role is required
        if (!is_null($this->route->getRoles())) {
            // user is authenticated
            if (!is_null($this->user)) {
                // check roles
                if (is_array(($error = $this->checkUserRoles()))) {
                    return $error;
                }
            } else {  // user is not authenticated
                $this->route->getConfig()->setError(array("message" => "Access denied."));
                return $this->route->getConfig()->getError();
            }
        }
        return true;
    }

    /**
     * Check if the user roles is able
     * If unable, return an error array, else return TRUE
     * 
     * @return boolean|array
     */
    private function checkUserRoles() {
        // get user roles
        $roles = $this->getFullHierarchyRoles();

        if (is_array($this->route->getRoles())) {
            if (empty(array_intersect($this->route->getRoles(), $roles))) {
                $this->route->getConfig()->setError(array("message" => "Access denied."));
                return $this->route->getConfig()->getError();
            }
        } else {
            if (!in_array($this->route->getRoles(), $roles)) {
                $this->route->getConfig()->setError(array("message" => "Access denied."));
                return $this->route->getConfig()->getError();
            }
        }
        return true;
    }

    /**
     * Return an array of all user roles
     * 
     * @return array
     */
    private function getFullHierarchyRoles() {
        // get user roles
        $roles = $this->user->getRoles();
        // get hierarchy roles
        $roles_hierarchy = $this->container->getParameter('security.role_hierarchy.roles');

        $result = array();
        foreach ($roles as $role) {
            array_push($result, $role);
            $result = array_merge($result, $roles_hierarchy[$role]);
        }
        return $result;
    }

    /**
     * Return the current User, NULL if not authenticated
     * 
     * @param SecurityContext $context
     * @return User
     */
    public function getCurrentUser(SecurityContext $context) {
        return $context->getToken()->getUser();
    }

    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------
    function getRequest() {
        return $this->request;
    }

    function getConfig() {
        return $this->route->getConfig();
    }

    function getRoute() {
        return $this->route;
    }

    function getUser() {
        return $this->user;
    }

    function setRequest(Symfony\Component\HttpFoundation\Request $request) {
        $this->request = $request;
    }

    /*
      function setConfig(RequestConfiguration $config) {
      $this->route->getConfig() = $config;
      }
     */

    function setRoute(AjaxRoute $route) {
        $this->route = $route;
    }

    function setUser($user) {
        $this->user = $user;
    }

}
