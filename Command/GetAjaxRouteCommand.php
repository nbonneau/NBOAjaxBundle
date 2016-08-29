<?php

namespace NBO\Bundle\AjaxBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use NBO\Bundle\AjaxBundle\AjaxManager\Route\AjaxRoute as AjaxRoute;

/**
 * Description of GetAjaxRoutesCommand
 */
class GetAjaxRouteCommand extends ContainerAwareCommand {

    /**
     * Configuration de la commande
     */
    protected function configure() {
        // Name and description for app/console command
        $this->setName('ajax:show:route')
                ->setDescription('Display detail for a specific Ajax route.')
                ->addArgument('route_name', InputArgument::REQUIRED, 'The route name')
                ->addOption('fullinfo', 'I', InputOption::VALUE_NONE, 'Specify if you want to show all parameters properties. default is false.');
    }

    /**
     * Execute the script
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // get the route name
        $route_name = $input->getArgument("route_name");
        
        $full_info = $input->getOption("fullinfo");
        
        // find route by name
        $route = $this->findAjaxRoute($route_name);
        
        // if route not found
        if(is_null($route)){
            $output->writeln("");
            $output->writeln("      The route \"".$route_name."\" doesn't exist or is not an Ajax route.");
            exit(0);
        }
        
        // display route informations
        $route->displayInfos($output, $full_info);
    }

    /**
     * Return the corresponding Route or null if not found
     * 
     * @param string $route_name
     * @return Route|null
     */
    protected function findAjaxRoute($route_name) {
        $ajaxRoute = new AjaxRoute($this->getContainer()->get('router'), null, $route_name, $this->getContainer()->getParameter('nbo_ajax.pre_config'));
        return !is_null($ajaxRoute->getRoute()) ? $ajaxRoute : null;
    }
}
