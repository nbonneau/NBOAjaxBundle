<?php

namespace NBO\Bundle\AjaxBundle\AjaxManager\Route;

use Symfony\Component\Routing\Route as Route;
use Symfony\Bundle\FrameworkBundle\Routing\Router as Router;
use Symfony\Component\HttpFoundation\Request as Request;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\RouteConfiguration as RouteConfiguration;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\RouteParameter as RouteParameter;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\FileRouteParameter as FileRouteParameter;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\ParameterType as ParameterType;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of AjaxRoute
 */
class AjaxRoute {

    /**
     * var string 
     */
    protected $name;

    /**
     * @var array
     */
    protected $roles;

    /**
     * @var boolean
     */
    protected $autoValid;

    /**
     * @var Request 
     */
    protected $request;

    /**
     * @var Route 
     */
    protected $route;

    /**
     * @var RouteConfiguration 
     */
    protected $config;

    /**
     * Constructor
     * 
     * @param Router $router
     * @param Request $request
     */
    public function __construct(Router $router, Request $request = null, $route_name = null, $pre_config = null) {
        $this->request = $request;
        $this->autoValid = true;
        // get route name
        $this->name = is_null($route_name) ? $request->get('_route') : $route_name;
        // get route
        $this->route = $this->findRoute($router);
        // check if route exist and is Ajax route, identify by the "expose: true" option
        if (!is_null($this->route)) {
            // reset autoValid if define
            $this->autoValid = !is_null($this->route->getOption("auto_valid")) ? $this->route->getOption("auto_valid") : true;
            // get routes roles
            $this->roles = $this->findRouteRoles();
            $requirements = $this->route->getRequirements();
            // create route configuration
            $this->config = new RouteConfiguration($request, isset($requirements['_method']) ? $requirements['_method'] : "GET");

            $this->getParametersFromRouting();

            $this->getFileParametersFromRouting();

            // set method as requirements if defined
            if (isset($requirements['_method'])) {
                $this->setMethod($requirements['_method']);
            }
            // set pre-config is define
            if(!is_null($this->getPreConfigOption())){
                $this->setPreConfig($pre_config);
            }
        } else {
            throw new \Exception("The route " . $this->name . " not found or is not an Ajax route. "
            . "You can use the command \"php app/console ajax:show:routes\" to show all available Ajax routes.");
        }
    }

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------

    public function setPreConfig($pre_config) {
        $preConfigOption = $this->getPreConfigOption();

        if (!key_exists($preConfigOption, $pre_config)) {
            throw new \Exception("There is no pre-configuration with name \"" . $preConfigOption . "\" for NBOAjaxBundle.");
        }
        $preConfig = $pre_config[$preConfigOption];
        
        $this->addParameters($preConfig['parameters']);
        $this->addFileParameters($preConfig['files']);
    }
    
    /**
     * @return string
     */
    public function getPreConfigOption() {
        return !is_null($this->route->getOption("pre_config")) ? $this->route->getOption("pre_config") : null;
    }

    /**
     * Define the request configuration method
     * 
     * @param string $method
     */
    public function setMethod($method) {
        $this->config->setMethod($method);
        if (!is_null($this->request)) {
            $this->config->setRequest_params($this->config->extractRouteParameters($this->request));
            $this->config->setFiles_request_params($this->config->extractFilesRouteParameters($this->request));
        }
    }

    /**
     * Add a parameter to the request configuration
     * 
     * @param string $name : the parameter name
     * @param array $config [=array ] : the config parameter
     * @return RouteParameter
     * @throws Exception
     */
    public function addParameter($name, $config = array()) {
        // if the parameter name is available
        if (is_null($this->findParam($name))) {
            // create new RouteParameter
            $parameter = new RouteParameter($name);

            if (is_array($config)) {
                // merge global config and parameter config
                $this->mergeConfig($config);

                // set type property if defined
                $parameter->setType(isset($config['type']) ? $config['type'] : ParameterType::STRING_TYPE);
                // set datetime format if defined
                $parameter->dateTimeFormat(isset($config['datetimeFormat']) ? $config['datetimeFormat'] : null);
                // set require property if defined
                $parameter->required(isset($config['require']) ? $config['require'] : true);
                // set empty property if defined
                $parameter->canBeEmpty(isset($config['empty']) ? $config['empty'] : false);
                // set default value(s) if defined
                if(isset($config['defaultValue'])){
                    $default = $config['defaultValue'];
                    if(is_array($default)){
                        if($parameter->getType() == 3){
                            $parameter->defaultValue($default);
                        } else {
                            if(empty($default)){
                                $parameter->defaultValue(ParameterType::getDefault($parameter->getType()));
                            }else{
                                $parameter->defaultValue($default[0]);
                            }
                        }
                    }else{
                        $parameter->defaultValue($default);
                    }
                }else{
                    $parameter->defaultValue(ParameterType::getDefault($parameter->getType()));
                }
                //$parameter->defaultValue((isset($config['defaultValue']) && !empty($config['defaultValue'])) ? $config['defaultValue'] : ParameterType::getDefault($parameter->getType()));
                // set disable value(s) if defined
                $parameter->disabledValue(isset($config['disabledValue']) && !empty($config['disabledValue']) ? $config['disabledValue'] : null);
                // set restrict value(s) if defined
                $parameter->restrictedBy(isset($config['restrictedValue']) && !empty($config['restrictedValue']) ? $config['restrictedValue'] : null);
                // set min value if defined
                $parameter->min(isset($config['min']) ? $config['min'] : null);
                // set max value if defined
                $parameter->max(isset($config['max']) ? $config['max'] : null);
                // set regex if defined
                $parameter->regex(isset($config['regex']) ? $config['regex'] : null);
            }
            // store parameter into configuration parameters array
            $this->config->addConfigParam($parameter);
            
            // return the created parameter
            return $parameter;
        }
        throw new \Exception("A parameter with the name \"" . $name . "\" already exists.");
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
        if (is_array($parameters)) {
            foreach ($parameters as $key => $val) {
                if (is_array($val)) {  // $key = parameter_name & $val = parameter_config
                    $this->addParameter($key, $val);
                } else {              // $val = parameter_name
                    $this->addParameter($val);
                }
            }
        }
    }

    /**
     * Add a file parameter to the file request configuration
     * 
     * @param string $name
     * @param array $config
     * @return FileRouteParameter
     * @throws Exception
     */
    public function addFileParameter($name, $config = array()) {
        // if the parameter name is available
        if (is_null($this->findParam($name))) {

            // create enw file request parameter
            $parameter = new FileRouteParameter($name);
            if (is_array($config)) {
                // merge global config and parameter config
                $this->mergeConfig($config, true);

                $parameter->required(isset($config["require"]) ? $config["require"] : true);
                $parameter->mimeType(isset($config["mimeType"]) ? $config["mimeType"] : null);
                $parameter->maxSize(isset($config["maxSize"]) ? $config["maxSize"] : null);
            }
            // store file parameter into file config request array
            $this->config->addFileConfigParam($parameter);
            // return the created parameter
            return $parameter;
        }
        throw new \Exception("A parameter with the name \"" . $name . "\" already exists.");
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
        if (is_array($parameters)) {
            foreach ($parameters as $key => $val) {
                if (is_array($val)) {  // $key = parameter_name & $val = parameter_config
                    $this->addFileParameter($key, $val);
                } else {              // $val = parameter_name
                    $this->addFileParameter($val);
                }
            }
        }
    }

    /**
     * Search a parameter by its name
     * Return the corresponding Parameter if found, else NULL
     * 
     * @param string $name
     * @return RouteParameter or NULL
     */
    public function findParam($name) {
        $find = false;
        $cmp = 0;
        $array_merge = array_merge($this->config->getConfig_params(), $this->config->getFiles_config_params());
        while (!$find && $cmp < sizeof($array_merge)) {
            if ($array_merge[$cmp]->getName() == $name) {
                $find = true;
            }
            $cmp++;
        }
        return $find ? $array_merge[$cmp - 1] : null;
    }

    /**
     * Get the config parameters
     * 
     * @param boolean $file
     * @return array
     */
    public function getParameters($file = false) {
        return $file ? $this->config->getFiles_config_params() : $this->config->getConfig_params();
    }

    /**
     * Display route informations for console command
     * 
     * @param OutputInterface $output
     * @param boolean $full_info : define if display all parameters properties
     */
    public function displayInfos(OutputInterface $output, $full_info) {
        $bcm = $this->findBundleControllerAndMethod();

        $table = new Table($output);

        // by default, this is based on the default style
        $style = new TableStyle();
        // customize the style
        $style->setHorizontalBorderChar('<fg=magenta> </>')->setVerticalBorderChar('<fg=magenta>   </>')->setCrossingChar(' ');
        // use the style for this table
        $table->setStyle($style);

        $table->addRow(array("<fg=green>Name</>", $this->getName()));
        $table->addRow(array("<fg=green>Path</>", $this->getRoute()->getPath()));
        $table->addRow(array("<fg=green>Bundle</>", substr($bcm["bundle"], 0, -6)));
        $table->addRow(array("<fg=green>Controller</>", substr($bcm["controller"], 0, -10)));
        $table->addRow(array("<fg=green>Function</>", substr($bcm["method"], 0, -6)));
        $table->addRow(array("<fg=green>Method</>", $this->getConfig()->getMethod()));
        $table->addRow(array("<fg=green>Roles</>", is_array($this->getRoles()) ? implode(', ', $this->getRoles()) : $this->getRoles()));

        // display parameters row
        if (!is_null($row = $this->displayParametersRow(false, $full_info))) {
            $table->addRow($row);
        }

        // display files row
        if (!is_null($row = $this->displayParametersRow(true, $full_info))) {
            $table->addRow($row);
        }

        // render table
        $table->render();
    }

    //--------------------------------------------------------------------------
    //  Privates methods
    //--------------------------------------------------------------------------
    /**
     * Dsiplay parameters row
     * 
     * @param boolean $fileParameter : define if display files parameter or the others
     * @param boolean $full_info : define if display all parameters properties
     * @return array
     */
    private function displayParametersRow($fileParameter, $full_info = false) {
        $parameters_row = null;
        if ($fileParameter) {
            if (sizeof($this->config->getFiles_config_params()) > 0) {
                $parameters_row = array("<fg=green>Files</>", "");
                foreach ($this->getParameters(true) as $fileParameter) {
                    $parameters_row[1] .= $fileParameter->getStrInfos($full_info);
                }
            }
        } else {
            if (sizeof($this->config->getConfig_params()) > 0) {
                $parameters_row = array("<fg=green>Parameters</>", "");
                foreach ($this->getParameters() as $parameter) {
                    $parameters_row[1] .= $parameter->getStrInfos($full_info);
                }
            }
        }
        return $parameters_row;
    }

    /**
     * Find the bundle name, the controller name and the method name for the route
     * 
     * @return array
     */
    protected function findBundleControllerAndMethod() {
        $default = $this->getRoute()->getDefaults();
        if (isset($default['_controller']) && strpos($default['_controller'], "\\")) {
            $bundleControllerMethod = explode("::", $default['_controller']);
            $bundleControler = explode("\\", $bundleControllerMethod[0]);

            $result = array();
            $result["bundle"] = $bundleControler[sizeof($bundleControler) - 3];
            $result["controller"] = $bundleControler[sizeof($bundleControler) - 1];
            $result["method"] = $bundleControllerMethod[1];

            return $result;
        }
        return array("bundle" => null, "controller" => null, "method" => null);
    }

    /**
     * Add parameters from routing file config
     */
    private function getParametersFromRouting() {
        $opts = $this->getRoute()->getOptions();
        if (isset($opts['parameters'])) {
            $parameters = $opts['parameters'];
            $this->addParameters($parameters);
        }
    }

    /**
     * Add file parameters from routing file config
     */
    private function getFileParametersFromRouting() {
        $opts = $this->getRoute()->getOptions();
        if (isset($opts['files'])) {
            $parameters = $opts['files'];
            $this->addFileParameters($parameters);
        }
    }

    /**
     * Merge global config and $config parameter, if $fileConfig is set to TRUE, merge the global file config
     * 
     * @param array $config
     * @param boolean $fileConfig
     */
    private function mergeConfig(&$config, $fileConfig = false) {
        $globalConfig = ($fileConfig) ? $this->config->getGlobalFilesParametersConfig() : $this->config->getGlobalParametersConfig();
        foreach ($globalConfig as $key => $val) {
            if (!isset($config[$key])) {
                $config[$key] = $val;
            }
        }
    }

    /**
     * Find a Route by it name
     * 
     * @param Router $router
     * @return Symfony\Component\Routing\Route|NULL
     */
    protected function findRoute(Router $router) {
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            if ($name == $this->name) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Find the roles for the route
     * 
     * @return array|string|null
     */
    protected function findRouteRoles() {
        // if route exists
        if (!is_null($this->route)) {
            // return roles option value(s) or NULL if not defined
            return isset($this->route->getOptions()['roles']) ? $this->route->getOptions()['roles'] : null;
        }
        // route doesn't exist, return NULL
        return null;
    }

    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------
    function getName() {
        return $this->name;
    }

    function getRoles() {
        return $this->roles;
    }

    function getAutoValid() {
        return $this->autoValid;
    }

    function getRequest() {
        return $this->request;
    }

    function getRoute() {
        return $this->route;
    }

    function getConfig() {
        return $this->config;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setRoles($roles) {
        $this->roles = $roles;
    }

    function setAutoValid($autoValid) {
        $this->autoValid = $autoValid;
    }

    function setRequest(Request $request) {
        $this->request = $request;
    }

    function setRoute(Route $route) {
        $this->route = $route;
    }

    function setConfig(RouteConfiguration $config) {
        $this->config = $config;
    }

}
