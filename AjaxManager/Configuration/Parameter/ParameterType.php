<?php
namespace NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter;

/**
 * 
 */
class ParameterType {

    const INT_TYPE = 0;     // index for integer type
    const STRING_TYPE = 1;  // index for string type
    const BOOL_TYPE = 2;    // index for boolean type
    const ARRAY_TYPE = 3;   // index for array type
    const FLOAT_TYPE = 4;   // index for array type
    const DATETIME_TYPE = 5;   // index for array type

    /**
     * Return TRUE if $type is INT_TYPE, STRING_TYPE, BOOL_TYPE, ARRAY_TYPE or FLOAT_TYPE, else FALSE
     * 
     * @param int $type
     * @return boolean
     */
    public static function isValid($type) {
        return (is_numeric($type) && in_array($type, array(ParameterType::INT_TYPE, ParameterType::STRING_TYPE, ParameterType::BOOL_TYPE, ParameterType::ARRAY_TYPE, ParameterType::FLOAT_TYPE, ParameterType::DATETIME_TYPE)));
    }

    /**
     * Check if $var type is equal to corresponding index type $type
     * 
     * @param mixed $var
     * @param int $type
     * @throws \Exception
     * @return boolean
     */
    public static function checkType($var, $type, $dateTimeFormat = 'Y-m-d H:i:s') {
        switch ($type) {
            case ParameterType::INT_TYPE:
                return ParameterType::isInteger($var);
            case ParameterType::STRING_TYPE:
                return ParameterType::isString($var);
            case ParameterType::BOOL_TYPE:
                return ParameterType::isBool($var);
            case ParameterType::ARRAY_TYPE:
                return ParameterType::isArray($var);
            case ParameterType::FLOAT_TYPE:
                return ParameterType::isFloat($var);
            case ParameterType::DATETIME_TYPE:
                return ParameterType::isDateTime($var, $dateTimeFormat);
            default:
                throw new \Exception("Oups, type with index " . $type . " not found.");
        }
    }

    /**
     * Get the type name
     * 
     * @return string
     */
    public static function getTypeName($type) {
        switch ($type) {
            case ParameterType::INT_TYPE:
                return "integer";
            case ParameterType::STRING_TYPE:
                return "string";
            case ParameterType::BOOL_TYPE:
                return "boolean";
            case ParameterType::ARRAY_TYPE:
                return "array";
            case ParameterType::FLOAT_TYPE:
                return "float";
            case ParameterType::DATETIME_TYPE:
                return "datetime";
            default:
                return "undefined";
        }
    }

    /**
     * Cast the value to the parameter type.
     * For example, if is a integer and value is equal to "22" then return 22.
     * Warning: this method must be called after ParameterType::checkType() 
     * becasue the cast is forced, so for a string "test" and type integer the method will returned 0 or "test" is not a valid type for integer
     * 
     * @param int $type
     * @param mixed $value
     * @return mixed
     * @throws \Exception
     */
    public static function castValue($type, $value, $dateTimeFormat = 'Y-m-d H:i:s') {
        switch ($type) {
            case ParameterType::INT_TYPE:
                return (int) $value;
            case ParameterType::STRING_TYPE:
                return $value;
            case ParameterType::BOOL_TYPE:
                return ParameterType::getBoolVal($value, true);
            case ParameterType::ARRAY_TYPE:
                return is_array($value) ? $value : explode(",", $value);
            case ParameterType::FLOAT_TYPE:
                return (float) $value;
            case ParameterType::DATETIME_TYPE:
                return is_a($value, "\DateTime") ? $value : \DateTime::createFromFormat($dateTimeFormat, $value);
            default:
                throw new \Exception("Oups, type with index " . $type . " not found.");
        }
    }

    /**
     * Return the default value switch $type
     * 
     * @param int $type
     * @return boolean|string|int|array
     * @throws \Exception
     */
    public static function getDefault($type) {
        switch ($type) {
            case ParameterType::INT_TYPE:
                return 0;
            case ParameterType::STRING_TYPE:
                return "";
            case ParameterType::BOOL_TYPE:
                return false;
            case ParameterType::ARRAY_TYPE:
                return array();
            case ParameterType::FLOAT_TYPE:
                return 0.0;
            case ParameterType::DATETIME_TYPE:
                return new \DateTime();
            default:
                throw new \Exception("Oups, type with index " . $type . " not found.");
        }
    }

    /**
     * Return TRUE if $var is an integer, if $var is an array then check all values inside it
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function isInteger($var) {
        if (is_array($var)) {
            $valid = true;
            $cmp = 0;
            while ($valid && $cmp < sizeof($var)) {
                if (!is_numeric($var[$cmp])) {
                    $valid = false;
                }
                $cmp++;
            }
            return $valid;
        }
        return is_numeric($var);
    }

    /**
     * Return TRUE if $var is a string, if $var is an array then check all values inside it
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function isString($var) {
        if (is_array($var)) {
            $valid = true;
            $cmp = 0;
            while ($valid && $cmp < sizeof($var)) {
                if (!is_string($var[$cmp])) {
                    $valid = false;
                }
                $cmp++;
            }
            return $valid;
        }
        return is_string($var);
    }

    /**
     * Return TRUE if $var is a boolean, if $var is an array then check all values inside it
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function isBool($var) {
        if (is_array($var)) {
            $valid = true;
            $cmp = 0;
            while ($valid && $cmp < sizeof($var)) {
                $value = is_bool($var[$cmp]) ? $var[$cmp] : strtolower($var[$cmp]);
                if ((!in_array($value, array('false', 'no', 'n', '0', 'off', false, 0, null), true) && !in_array($value, array('true', 'yes', 'y', '1', 'on', true, 1), true))) {
                    $valid = false;
                }
                $cmp++;
            }
            return $valid;
        }
        $value = is_bool($var) ? $var : strtolower($var);
        return (in_array($value, array('false', 'no', 'n', '0', 'off', false, 0, null), true) || in_array($value, array('true', 'yes', 'y', '1', 'on', true, 1), true));
    }

    /**
     * Return TRUE if $var is an array, if $var is an array then check all values inside it
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function isArray($var) {
        $value = is_string($var) ? explode(",", $var) : $var;
        // if is an array of array
        if (ParameterType::is_array_r($value)) {
            $valid = true;
            $cmp = 0;
            while ($valid && $cmp < sizeof($value)) {
                if (!is_array($value[$cmp])) {
                    $valid = false;
                }
                $cmp++;
            }
            return $valid;
        }
        return is_array($value);
    }
    
    /**
     * Return TRUE if $var is a DateTime string in the good format
     * 
     * @param mixed $var
     * @param string $dateTimeFormat
     * @return boolean
     */
    public static function isDateTime($var, $dateTimeFormat = 'Y-m-d H:i:s'){
        return is_a($var, "\DateTime") || \DateTime::createFromFormat($dateTimeFormat, $var) != false;
    }

    /**
     * Return TRUE if $var is a float, if $var is an array then check all values inside it
     * 
     * @param type $var
     * @return boolean
     */
    public static function isFloat($var) {
        if (is_array($var)) {
            $valid = true;
            $cmp = 0;
            while ($valid && $cmp < sizeof($var)) {
                if (ParameterType::test_float($var[$cmp]) || is_float($var[$cmp])) {
                    $valid = false;
                }
                $cmp++;
            }
            return $valid;
        }
        return ParameterType::test_float($var) || is_float($var);
    }
    
    /**
     * Check if $var is a float
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function test_float($var) {
        if (!is_scalar($var)) {
            return false;
        }
        return (gettype($var) === "float") ? true : (preg_match("/^\\d+\\.\\d+$/", $var) === 1);
    }

    /**
     * Check if $var is an array and recursively
     * 
     * @param mixed $var
     * @return boolean
     */
    public static function is_array_r($var) {
        if (is_array($var)) {
            foreach ($var as $val) {
                if (is_array($val)) {
                    return $this->is_array_r($val);
                }
            }
        }
        return false;
    }

    /** Checks a variable to see if it should be considered a boolean true or false.
     *     Also takes into account some text-based representations of true of false,
     *     such as 'false','N','yes','on','off', etc.
     * 
     * @param mixed $in The variable to check
     * @param bool $strict If set to false, consider everything that is not false to
     *                     be true.
     * @return bool The boolean equivalent or null
     */
    public static function getBoolVal($in, $strict = false) {
        $out = null;
        // if not strict, we only have to check if something is false
        if (in_array(strtolower($in), array('false', 'no', 'n', '0', 'off', false, 0, null), true)) {
            $out = false;
        } else if ($strict) {
            // if strict, check the equivalent true values
            if (in_array(strtolower($in), array('true', 'yes', 'y', '1', 'on', true, 1), true)) {
                $out = true;
            }
        } else {
            // not strict? let the regular php bool check figure it out (will largely default to true)
            $out = ($in ? true : false);
        }
        return $out;
    }

}
