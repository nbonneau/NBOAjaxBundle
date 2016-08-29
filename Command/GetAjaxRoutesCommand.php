<?php

namespace NBO\Bundle\AjaxBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;

/**
 * Description of GetAjaxRoutesCommand
 */
class GetAjaxRoutesCommand extends ContainerAwareCommand {

    /**
     * Configuration de la commande
     */
    protected function configure() {
        // Name and description for app/console command
        $this->setName('ajax:show:routes')
                ->setDescription('Display all Ajax routes.')
                ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'Specify the routes bundle')
                ->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, 'Specify the routes controller');
    }

    /**
     * Execute the script
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // find routes
        $ajax_routes = $this->getAjaxRoutes($input->getOption("bundle"), $input->getOption("controller"));
        // display routes
        $this->displayAjaxRoutes($ajax_routes, $output);
    }

    /**
     * Get Ajax routes. An Ajax route is identify by the option "expose" set to TRUE
     * 
     * @param string $bundle
     * @param string $controller
     * @return array
     */
    protected function getAjaxRoutes($bundle, $controller) {
        $router = $this->getContainer()->get('router');
        $ajax_routes = array();
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            $bcm = $this->findBundleControllerAndMethod($route);
            if (is_null($bundle) || $bcm["bundle"] == $bundle) {
                if (is_null($controller) || $bcm["controller"] == $controller) {
                    $route_opt = $route->getOptions();
                    if (isset($route_opt["expose"]) && $route_opt["expose"] == true) {
                        $ajax_routes[$name] = $route;
                    }
                }
            }
        }
        return $ajax_routes;
    }

    /**
     * Display Ajax routes
     * 
     * @param array $ajax_routes
     * @param OutputInterface $output
     */
    protected function displayAjaxRoutes($ajax_routes, OutputInterface $output) {
        $table = new Table($output);
        $table->setHeaders(array("Route", "Path", "Bundle", "Controller", "Method", 'Options'));

        foreach ($ajax_routes as $name => $route) {
            $str_opts = $this->optionsToString($route);
            $bcm = $this->findBundleControllerAndMethod($route);

            $table->addRow(array($name, $route->getPath(), $bcm["bundle"], $bcm["controller"], $bcm["method"], $str_opts));
        }
        $table->render();
    }
    /**
     * Format the route options to a string with return line
     * 
     * @param Route $route
     * @return string
     */
    protected function optionsToString($route){
        $str_opts = "";
        $cmp_opt = 0;
        foreach ($route->getOptions() as $key => $val) {
            if ($key != "compiler_class") {
                $value = is_array($val) ? implode(",", $val) : $val;
                $str_opts .= $key . " = " . $value;
                $str_opts .= ($cmp_opt + 1 != sizeof($route->getOptions())) ? "\n" : "";
            }
            $cmp_opt++;
        }
        return $str_opts;
    }

    /**
     * Find the bundle name, the controller name and the method name for the route
     * 
     * @param Route $route
     * @return array
     */
    protected function findBundleControllerAndMethod($route) {
        if (isset($route->getDefaults()['_controller']) && strpos($route->getDefaults()['_controller'], "\\")) {
            $bundleControllerMethod = explode("::", $route->getDefaults()['_controller']);
            $bundleControler = explode("\\", $bundleControllerMethod[0]);

            $result = array();
            $result["bundle"] = $bundleControler[sizeof($bundleControler) - 3];
            $result["controller"] = $bundleControler[sizeof($bundleControler) - 1];
            $result["method"] = $bundleControllerMethod[1];

            return $result;
        }
        return array("bundle" => null, "controller" => null, "method" => null);
    }

}
