<?php
namespace NBO\Bundle\AjaxBundle\AjaxManager\Response;

use Symfony\Component\HttpFoundation\JsonResponse as JsonResponse;
use Symfony\Component\HttpFoundation\Response as Response;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AjaxResponse
 */
class AjaxResponse {
    
    const JSON_RESPONSE = "JSON";   // JSON response type
    const HTML_RESPONSE = "HTML";   // HTML response type
    /**
     * @var string : the response type
     */
    protected $type;
    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse or Symfony\Component\HttpFoundation\Response
     */
    protected $response;
    /**
     * Constructor
     * 
     * @param string $type
     * @throws \Exception
     */
    public function __construct($type = AjaxResponse::JSON_RESPONSE) {
        // if $type is not JSON or HTML
        if(!$this->checkResponseType($type)){
            throw new \Exception("Bad response type, available response types are ".implode(',', array(AjaxResponse::JSON_RESPONSE, AjaxResponse::HTML_RESPONSE)));
        }
        $this->type = $type;
        switch($type){
            case AjaxResponse::JSON_RESPONSE:
                $this->response = new JsonResponse();
                break;
            case AjaxResponse::HTML_RESPONSE:
                $this->response = new Response();
                break;
            default:
                $this->response = new JsonResponse();
        }
    }
    
    //--------------------------------------------------------------------------
    //  Publics methods
    //--------------------------------------------------------------------------
    
    /**
     * Get a response, JSON or HTML
     * 
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function getResponse($data){
        if($this->type == AjaxResponse::JSON_RESPONSE){
            if(!$this->is_array_r($data)){
               throw new \Exception("Data must be an array, or an array of array, for a JSON response.");
            }
            $this->response->setContent(json_encode($data));
        }else{
            $this->response->setContent($data);
        }
        return $this->response;
    }
    
    /**
     * Get an error response, JSON or HTML
     * 
     * @param array $errorData
     * @param int $code
     * @return mixed
     * @throws \Exception
     */
    public function getErrorResponse($errorData, $code = 400){
        $response = $this->getResponse($errorData);
        $response->setStatusCode($code);
        return $response;
    }
    
    //--------------------------------------------------------------------------
    //  Privates methods
    //--------------------------------------------------------------------------
    
    /**
     * Check if type is available, return TRUE if $type is equal to AjaxResponse::JSON_RESPONSE or AjaxResponse::HTML_RESPONSE, else FALSE
     * 
     * @param string $type
     * @return bool
     */
    private function checkResponseType($type){
        return in_array($type, array(AjaxResponse::JSON_RESPONSE, AjaxResponse::HTML_RESPONSE));
    }
    /**
     * Check if $var is an array and recursively
     * 
     * @param mixed $var
     * @return boolean
     */
    private function is_array_r($var){
        if(is_array($var)){
            foreach($var as $val){
                if(is_array($val)){
                    return $this->is_array_r($val);
                }
            }
            return true;
        }
        return false;
    }
    
    //--------------------------------------------------------------------------
    //  Getters & Setters
    //--------------------------------------------------------------------------
}
