<?php
/**
 * See class comment
 *
 * PHP Version 5
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\Listener
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */

namespace Netresearch\AssetPickerBundle\Listener;

use Netresearch\AssetPicker;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


class KernelListener
{

    protected $router;

    protected $kernel;

    function __construct($router, $kernel)
    {
        $this->router = $router;
        $this->kernel = $kernel;
    }

    /**
     * Listen on terminate to clear cache if akeneo config was changed!
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if($event->isMasterRequest()){
            $router = $this->router;
            $request = $event->getRequest();
            $route = $router->match($request->getPathInfo());
            if($route['_route'] == 'oro_config_configuration_system_post'){
                $response = $event->getResponse();
                $response->sendHeaders();
                $response->sendContent();

                $application = new Application($this->kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput(array(
                    'command' => 'cache:clear',
                    '--no-warmup' => '',
                    '--env' => 'prod'
                ));
                $output = new NullOutput();
                $application->run($input, $output);


                $input = new ArrayInput(array(
                    'command' => 'cache:clear',
                    '--no-warmup' => '',
                    '--env' => 'dev'
                ));
                $output = new NullOutput();
                $application->run($input, $output);

                $input = new ArrayInput(array(
                    'command' => 'cache:warmup',
                    '--no-warmup' => '',
                    '--env' => 'prod'
                ));
                $output = new NullOutput();
                $application->run($input, $output);

                $input = new ArrayInput(array(
                    'command' => 'cache:warmup',
                    '--no-warmup' => '',
                    '--env' => 'dev'
                ));
                $output = new NullOutput();
                $application->run($input, $output);

                // we need to die because after the cache clear we will get some class not found
                // errors because the current php process has still references to deleted cache classes
                die();
            }
        }

    }



}
