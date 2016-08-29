<?php
namespace NBO\Bundle\AjaxBundle\Service;

use NBO\Bundle\AjaxBundle\AjaxManager\AjaxRequest as AjaxRequest;

/**
 * Create new AjaxRequest
 */
class AjaxRequestService extends AjaxRequest{
    /**
     * 
     * @param AppKernel $kernel
     * @param string $method
     */
    public function __construct($kernel) {
        parent::__construct($kernel);
    }
}
