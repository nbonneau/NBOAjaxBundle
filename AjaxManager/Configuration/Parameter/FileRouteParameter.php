<?php

namespace NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter;

use NBO\Bundle\AjaxBundle\AjaxManager\Configuration\Parameter\Parameter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 
 */
class FileRouteParameter extends Parameter {

    /**
     * @var mixed
     */
    protected $mimeType;

    /**
     * @var int 
     */
    protected $maxSize;

    /**
     * Constructor
     * 
     * @param string $name
     * @param boolean $required
     */
    public function __construct($name, $required = true) {
        $this->name = $name;
        $this->required = $required;

        $this->mimeType = null;
        $this->maxSize = null;
    }

    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------
    /**
     * Define the file mimeType
     * 
     * @param mixed $mimeType
     * @return FileRouteParameter
     */
    public function mimeType($mimeType) {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * Define the max file size
     * 
     * @param int $maxSize
     * @return FileRouteParameter
     */
    public function maxSize($maxSize) {
        if (is_numeric($maxSize)) {
            $maxSize = (int) $maxSize;
            if ($maxSize > 0 && $maxSize < UploadedFile::getMaxFilesize()) {
                $this->maxSize = $maxSize;
            }
        }
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
            // Symfony\Component\HttpFoundation\File\UploadedFile
            $uploadedFile = $this->getFileRequestParamValue($request_params);
            // if is not null, the file parameter is found
            if (!is_null($uploadedFile)) {
                // check all properties
                if (!is_null($error = $this->checkProperties($uploadedFile))) {
                    return $error;
                }
                $this->value = $uploadedFile;
                return true;
            }
            $this->value = null;
            return true;
        }
        return array("message" => "The file parameter \"" . $this->name . "\" is required and is not defined in file request parameters.");
    }

    /**
     * Get a string informations like property_name = property_value \n
     * 
     * @return string
     */
    public function getStrInfos($full_infos = false) {
        $properties = get_object_vars($this);
        unset($properties['name']);
        $result = "<fg=red>" . $this->name . "</>";
        if ($full_infos) {
            $result .= ":\n";
            $cmp = 0;
            $str = "                 ";
            foreach ($properties as $name => $value) {
                if (is_null($value)) {
                    $value = "null";
                } elseif (is_bool($value)) {
                    $value = $value == true ? "true" : "false";
                }

                $substr = substr($str, 0, -strlen($name));

                $result .= "    <fg=green>" . $name . "</>" . $substr . (is_array($value) ? implode(', ', $value) : $value);
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
     * Check all parameter properties
     * 
     * @param UploadedFile $uploadedFile
     * @return array|null
     */
    private function checkProperties(UploadedFile $uploadedFile) {
        if (!is_null($error = $this->checkMimeType($uploadedFile))) {
            return $error;
        }
        if (!is_null($error = $this->checkMaxSize($uploadedFile))) {
            return $error;
        }
        return null;
    }

    /**
     * Check if the file mimeType is valid
     * 
     * @param UploadedFile $uploadedFile
     * @return array|null
     */
    private function checkMimeType(UploadedFile $uploadedFile) {
        if (!is_null($this->mimeType)) {
            if (is_array($this->mimeType)) {
                if (!in_array($uploadedFile->getClientMimeType(), $this->mimeType)) {
                    return array("message" => "The mimeType \"" . $uploadedFile->getClientMimeType() . "\" for the file parameter \"" . $this->name . "\" is not valid, must be \"" . implode(',', $this->mimeType) . "\"");
                }
            } else {
                if ($uploadedFile->getClientMimeType() != $this->mimeType) {
                    return array("message" => "The mimeType \"" . $uploadedFile->getClientMimeType() . "\" for the file parameter \"" . $this->name . "\" is not valid, must be \"" . $this->mimeType . "\"");
                }
            }
        }
        return null;
    }

    /**
     * Check if the file size is valid
     * 
     * @param UploadedFile $uploadedFile
     * @return array|null
     */
    private function checkMaxSize(UploadedFile $uploadedFile) {
        if (!is_null($this->maxSize) && $uploadedFile->getClientSize() > $this->maxSize) {
            return array("message" => "The file parameter \"" . $this->name . "\" is too big, must be smaller than " . $this->maxSize . ".");
        }
        return null;
    }

    /**
     * Return the request parameter value, or the default value if is not set
     * 
     * @param array $request_params
     * @return Symfony\Component\HttpFoundation\File\UploadedFile|null
     */
    private function getFileRequestParamValue($request_params) {
        if (!$this->isRequired() && !isset($request_params[$this->name])) {
            return null;
        }
        return $request_params[$this->name];
    }

    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------

    function getMimeType() {
        return $this->mimeType;
    }

    function getMaxSize() {
        return $this->maxSize;
    }

    function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
    }

    function setMaxSize($maxSize) {
        $this->maxSize = $maxSize;
    }

}
