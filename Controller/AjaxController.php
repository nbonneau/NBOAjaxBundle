<?php
namespace NBO\Bundle\AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use NBO\Bundle\AjaxBundle\AjaxManager\AjaxRequest;

/**
 * 
 */
class AjaxController extends Controller{
    
    /**
     * @var AjaxRequestService
     */
    protected $ajaxRequest;
    
    //--------------------------------------------------------------------------
    // Publics methods
    //--------------------------------------------------------------------------
    
    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     * @api
     */
    public function setContainer(ContainerInterface $container = null){
        parent::setContainer($container);
        
        // create new Ajax request
        $this->ajaxRequest = $this->get('ajax_request');
        
        // valid request if autoValid is set to TRUE
        if($this->ajaxRequest->getRoute()->getAutoValid() && !$this->isValid()){
            throw new \Exception($this->getAjaxError()['message']);
        }
    }
    
    /**
     * Check if the request is valid
     * 
     * @return boolean
     */
    public function isValid(){
        return $this->ajaxRequest->isValid();
    }
    
    /**
     * Return the Ajax error or NULL if no error
     * 
     * @return array|null
     */
    public function getAjaxError(){
        return $this->ajaxRequest->getError();
    }
    
    /**
     * Return an associative array of all parameters value.
     * The keys are the parameters name and the values are the parameters value
     * If no parameters was define, return an empty array
     * 
     * @return array
     */
    public function getParameters(){
        return $this->ajaxRequest->getData();
    }
    
    /**
     * Return the parameter value if the parameter exists.
     * Return NULL if the parameter doesn't exist.
     * 
     * @param string $parameter_name
     * @return mixed|null
     */
    public function getParameter($parameter_name){
        return $this->ajaxRequest->getParameterValue($parameter_name);
    }
    
    //--------------------------------------------------------------------------
    // Response methods
    //--------------------------------------------------------------------------
    
    /**
     * Create a JSON response
     * 
     * @param array $data
     * @return JsonResponse
     * @throws \Exception
     */
    public function createJsonResponse($data = array()) {
        return $this->ajaxRequest->createJsonResponse($data);
    }

    /**
     * Create a HTML response
     * 
     * @param string $view : the twig view
     * @return Response
     * @throws \Exception
     */
    public function createHtmlResponse($view) {
        return $this->ajaxRequest->createHtmlResponse($view);
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
        return $this->ajaxRequest->createErrorJsonResponse($error, $code);
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
        return $this->ajaxRequest->createErrorHtmlResponse($view, $code);
    }
}
