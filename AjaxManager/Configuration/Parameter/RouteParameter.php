<?php

namespace NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter;

use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\ParameterType;
use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\Parameter;

/**
 * Description of RouteParameter
 */
class RouteParameter extends Parameter {

    /**
     * @var int : the parameter type
     */
    protected $type;

    /**
     * @var bool : define, if parameter type is STRING or ARRAY, if the value can be empty or not 
     */
    protected $empty;

    /**
     * @var int
     */
    protected $max;

    /**
     * @var int
     */
    protected $min;

    /**
     * @var string
     */
    protected $regex;

    /**
     * @var string 
     */
    protected $datetimeFormat;

    /**
     * @var mixed : the default value(s), can be an array of default value
     */
    protected $defaultVal;

    /**
     * @var mixed : the disable value(s), can be an array of disable value
     */
    protected $disableVal;

    /**
     * @var mixed : the restricted value(s), can be an array of restricted value
     */
    protected $restrictedVal;

    /**
     * Constructor
     * 
     * @param string $name : the parameter name
     * @param int $type [=ParameterType::STRING_TYPE] : the parameter type
     * @param bool $required [=TRUE ] : the require value for the parameter
     * @throws Exception
     */
    public function __construct($name, $type = ParameterType::STRING_TYPE, $required = true) {
        $this->name = $name;
        if (!ParameterType::isValid($type)) {
            throw new \Exception("Bad value for parameter type.");
        }
        $this->type = $type;
        $this->required = $required;
        $this->empty = false;

        $this->defaultVal = $this->defineDefault();
        $this->disableVal = null;
        $this->restrictedVal = null;

        $this->max = null;
        $this->min = null;

        $this->regex = null;

        $this->datetimeFormat = "Y-m-d H:i:s";
    }

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------

    /**
     * Define the parameter type
     * 
     * @param int $type
     * @throws \Exception
     */
    public function setType($type) {
        if (!ParameterType::isValid($type)) {
            throw new \Exception("Bad value for parameter type 2.");
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Define the default value
     * 
     * @param mixed $default
     * @return RouteParameter
     * @throws \Exception
     */
    public function defaultValue($default) {
        if (!is_null($default) && !ParameterType::checkType($default, $this->type, $this->datetimeFormat)) {
            throw new \Exception("The type of the default value should be the same as the parameter.");
        }
        $this->defaultVal = $default;
        return $this;
    }

    /**
     * Define the disabled value(s)
     * 
     * @param mixed $disabledValue
     * @return RouteParameter
     * @throws \Exception
     */
    public function disabledValue($disabledValue) {
        if (!is_null($disabledValue) && !ParameterType::checkType($disabledValue, $this->type, $this->datetimeFormat)) {
            throw new \Exception("The type of the disable value(s) should be the same as the parameter.");
        }
        $this->disableVal = $disabledValue;
        return $this;
    }

    /**
     * Define restricted value(s)
     * 
     * @param mixed $restrictedValue
     * @return RouteParameter
     * @throws \Exception
     */
    public function restrictedBy($restrictedValue) {
        if (!is_null($restrictedValue) && !ParameterType::checkType($restrictedValue, $this->type, $this->datetimeFormat)) {
            throw new \Exception("The type of the restrict value(s) should be the same as the parameter.");
        }
        $this->restrictedVal = $restrictedValue;
        $this->defaultVal = $this->defineDefault();
        return $this;
    }

    /**
     * Define the maximum value for the parameter, is use only for INT type
     * 
     * @param int $max
     * @return RouteParameter
     */
    public function max($max) {
        if ($this->type == ParameterType::INT_TYPE && is_numeric($max)) {
            $this->max = (int) $max;
        }
        return $this;
    }

    /**
     * Define the minimum value for the parameter, is use only for INT type
     * 
     * @param int $min
     * @return RouteParameter
     */
    public function min($min) {
        if ($this->type == ParameterType::INT_TYPE && is_numeric($min)) {
            $this->min = (int) $min;
        }
        return $this;
    }

    /**
     * Define the regular expression, is use only for STRING type
     * 
     * @param string $regex
     * @return RouteParameter
     */
    public function regex($regex) {
        if (is_string($regex)) {
            $this->regex = $regex;
        }
        return $this;
    }

    /**
     * Define the datetime format, is use only for DATETIME type
     * 
     * @param string $format
     * @return RouteParameter
     */
    public function dateTimeFormat($format) {
        if (is_string($format)) {
            $this->datetimeFormat = $format;
        }
        return $this;
    }

    /**
     * Define if the parameter can be empty, this property is use only for STRING type
     * 
     * @param type $empty
     * @return RouteParameter
     */
    public function canBeEmpty($empty) {
        $this->empty = $empty;
        return $this;
    }

    /**
     * Check if the $request_params is valid with the parameter settings
     * Return TRUE if is valid, else an error array 
     * 
     * @param array $request_params
     * @return mixed
     */
    public function isValid($request_params) {
        // if is required and request parameter is defined
        if ($this->checkRequire($request_params)) {
            // require is valid
            // get request value, if is not set define as the default value
            $request_value = $this->getRequestParamValue($request_params);

            // if can not be empty and the request value is empty or equal to an empty string
            if (!is_null($error = $this->checkEmptyValue($request_value))) {
                return $error;
            }
            // if request value has the good type, or can be empty and the request value is empty or an empty string
            if (ParameterType::checkType($request_value, $this->type, $this->datetimeFormat) || ($this->empty && $request_value == "")) {
                // cast the value to the good type
                $request_value = ParameterType::castValue($this->type, $request_value, $this->datetimeFormat);

                // check all parameter properties
                if (!is_null($error = $this->checkProperties($request_value))) {
                    return $error;
                }

                $this->value = $request_value;
                return true;
            }
            $error = array("message" => "The parameter type for parameter \"" . $this->name . "\" with value \"" . $request_value . "\" is not valid, must be a \"" . ParameterType::getTypeName($this->type) . "\"");
            $error['message'] .= ($this->type == ParameterType::DATETIME_TYPE) ? " with format \"" . $this->datetimeFormat . "\"." : ".";
            return $error;
        }
        return array("message" => "The parameter \"" . $this->name . "\" is required and is not defined in request parameters.");
    }

    /**
     * Get a string informations like property_name = property_value \n
     * 
     * @return string
     */
    public function getStrInfos($full_infos = false) {
        $properties = get_object_vars($this);
        unset($properties['name']);
        unset($properties['type']);
        $result = "<fg=red>".$this->name . "</> (".ParameterType::getTypeName($this->type).")";
        if($full_infos){
            $result .= ":\n";
            $cmp = 0;
            $str = "                 ";
            foreach ($properties as $name => $val) {
                if(is_null($val)){
                    $val = "null";
                }elseif(is_bool($val)){
                    $val = $val == true ? "true" : "false";
                }elseif(is_a($val, "\DateTime")){
                    $val = $val->format($this->datetimeFormat);
                }

                $substr = substr($str, 0, -strlen($name));

                $result .= "    <fg=green>". $name ."</>" . $substr . (is_array($val) ? implode(', ', $val) : $val);
                $result .= ($cmp + 1 != sizeof($properties)) ? "\n" : "";
                $cmp++;
            }
        }
        return $result . "\n";
    }

    //--------------------------------------------------------------------------
    //  Privates methods
    //--------------------------------------------------------------------------

    /**
     * Check, if the parameter can not be empty, if value is empty
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkEmptyValue($request_value) {
        if (/* ($this->type == ParameterType::STRING_TYPE || $this->type == ParameterType::ARRAY_TYPE) && */!$this->empty && $request_value == "") {
            return array("message" => "The value for parameter \"" . $this->name . "\" can not be empty.");
        }
        return null;
    }

    /**
     * Check all the properties
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkProperties($request_value) {
        // if string doesn't correspond to the regular expression
        if (!is_null($error = $this->checkRegex($request_value))) {
            return $error;
        }

        // if disabled value(s) is defined
        if (!is_null($error = $this->checkDisableValue($request_value))) {
            return $error;
        }

        // if restricted value(s) is defined
        if (!is_null($error = $this->checkRestrictValue($request_value))) {
            return $error;
        }

        // check min value
        if (!is_null($error = $this->checkMinValue($request_value))) {
            return $error;
        }

        // check max value
        if (!is_null($error = $this->checkMaxValue($request_value))) {
            return $error;
        }
        return null;
    }

    /**
     * Check if value match to the regular expression
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkRegex($request_value) {
        if ($this->type == ParameterType::STRING_TYPE && !is_null($this->regex) && preg_match($this->regex, $request_value) == 0) {
            return array("message" => "The value \"" . $request_value . "\" of the parameter \"" . $this->name . "\" does not match to the regular expression.");
        }
        return null;
    }

    /**
     * Check if value is not in the disable value(s)
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkDisableValue($request_value) {
        if (!is_null($this->disableVal) && !empty($this->disableVal)) {
            if (is_array($this->disableVal) && in_array($request_value, $this->disableVal)) {
                return array("message" => "The request parameter value \"" . $request_value . "\" is disabled for parameter \"" . $this->name . "\". Disabled values are " . implode(",", $this->disableVal) . ".");
            }
            if (!is_array($this->disableVal) && $request_value == $this->disableVal) {
                return array("message" => "The request parameter value \"" . $request_value . "\" is disabled for parameter \"" . $this->name . "\". Disabled value is \"" . $this->disableVal . "\".");
            }
        }
        return null;
    }

    /**
     * Check if value is in the restrict value(s)
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkRestrictValue($request_value) {
        if (!is_null($this->restrictedVal) && !empty($this->restrictedVal)) {
            if (is_array($this->restrictedVal) && !in_array($request_value, $this->restrictedVal)) {
                return array("message" => "The request parameter value \"" . $request_value . "\" is not in the restricted values for \"".$this->name."\" parameter. Restricted values are " . implode(",", $this->restrictedVal));
            }
            if (!is_array($this->restrictedVal) && $request_value != $this->restrictedVal) {
                return array("message" => "The request parameter value \"" . $request_value . "\" is not in the restricted value for \"".$this->name."\" parameter. Restricted value is \"" . $this->restrictedVal . "\".");
            }
        }
        return null;
    }

    /**
     * Check if the value is greater than min value
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkMinValue($request_value) {
        if ($this->type == ParameterType::INT_TYPE && !is_null($this->min) && $request_value < $this->min) {
            return array("message" => "The value for parameter \"" . $this->name . "\" with value " . $this->value . " must be greater than " . $this->min . ".");
        }
        return null;
    }

    /**
     * Check if the value is smaller than max value
     * 
     * @param mixed $request_value
     * @return array|null
     */
    private function checkMaxValue($request_value) {
        if ($this->type == ParameterType::INT_TYPE && !is_null($this->max) && $request_value > $this->max) {
            return array("message" => "The value for parameter \"" . $this->name . "\" with value " . $this->value . " must be smaller than " . $this->max . ".");
        }
        return null;
    }

    /**
     * Return a default value switch type( int = 0, string = "", bool = false, array = array())
     * If restricted values are defined, take the first value as default value
     * 
     * @return mixed
     */
    private function defineDefault() {
        if(!is_null($this->defaultVal)){
            return (is_array($this->defaultVal) && isset($this->defaultVal[0])) ? $this->defaultVal[0] : $this->defaultVal;
        }
        if(!is_null($this->restrictedVal)){
            return (is_array($this->restrictedVal) && isset($this->restrictedVal[0])) ? $this->restrictedVal[0] : $this->restrictedVal;
        }
        return ParameterType::getDefault($this->type);
    }

    /**
     * Return the request parameter value, or the default value if is not set
     * 
     * @param array $request_params
     * @return mixed
     */
    private function getRequestParamValue($request_params) {
        if (!$this->isRequired() && !isset($request_params[$this->name])) {
            return $this->defaultVal;
        }
        if ($this->empty && isset($request_params[$this->name]) && $request_params[$this->name] == "") {
            return $this->defaultVal;
        }
        $value = trim($request_params[$this->name]);
        return $value == "null" ? null : $value;
    }

    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------

    function getType() {
        return $this->type;
    }

    function getMax() {
        return $this->max;
    }

    function getMin() {
        return $this->min;
    }

    function getRegex() {
        return $this->regex;
    }

    function getDefaultVal() {
        return $this->defaultVal;
    }

    function getDisableVal() {
        return $this->disableVal;
    }

    function getRestrictedVal() {
        return $this->restrictedVal;
    }

    function setMax($max) {
        $this->max = $max;
    }

    function setMin($min) {
        $this->min = $min;
    }

    function setRegex($regex) {
        $this->regex = $regex;
    }

    function setDefaultVal($defaultVal) {
        $this->defaultVal = $defaultVal;
    }

    function setDisableVal($disableVal) {
        $this->disableVal = $disableVal;
    }

    function setRestrictedVal($restrictedVal) {
        $this->restrictedVal = $restrictedVal;
    }

}
