<?php

namespace NBO\Bundle\AjaxBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AjaxControllerListener {

    //private $tokens;

    public function __construct() {
        //$this->tokens = $tokens;
    }

    public function onKernelController(FilterControllerEvent $event) {
        // get current controller
        $controller = $event->getController();
        // get current processing request
        $request = $event->getRequest();
        
        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }
        // if is Ajax Controller
        if (is_a($controller[0], "NBO\Bundle\AjaxBundle\Controller\AjaxController")) {
            // get all ajax parameters
            $ajaxParameters = $controller[0]->getParameters();
            // add each parameters to the request attributs
            foreach ($ajaxParameters as $name => $val) {
                $request->attributes->set($name, $val);
            }
        }
    }

}
