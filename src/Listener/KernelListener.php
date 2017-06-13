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
use Netresearch\AssetPickerBundle\Compiler\OverrideServiceCompilerPass;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symcode\Cloud\Client\SecurityClient;
use Symcode\Cloud\Client\SettingClient;
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

    /**
     * @var ConfigManager
     */
    protected $configManager;

    function __construct($router, $kernel, ConfigManager $configManager)
    {
        $this->router = $router;
        $this->kernel = $kernel;
        $this->configManager = $configManager;
    }

    /**
     * Listen on terminate to clear cache if akeneo config was changed!
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $router = $this->router;
            $request = $event->getRequest();
            $route = $router->match($request->getPathInfo());
            if ($route['_route'] == 'oro_config_configuration_system_post') {

                // send the response, wee need to do this manually
                // because wie will call die() at the end
                // and dont use ->send() methode because this will end the php process
                $response = $event->getResponse();
                $response->sendHeaders();
                $response->sendContent();

                try {
                    // check if cloud was activated and the callback was also activated
                    // then save the callback url into the symcodecloud system
                    $active = (int)$this->getCloudConfig($request, 'active');
                    $callbackActive = (int)$this->getCloudConfig($request, 'callback_active');

                    if($active && $callbackActive){
                        $host = $this->getCloudConfig($request, 'host');
                        $username = $this->getCloudConfig($request, 'username');
                        $password = $this->getCloudConfig($request, 'password');
                        $cloudSecurityClient = new SecurityClient($host, '');
                        $apikey = $cloudSecurityClient->login($username, $password);
                        if(!empty($apikey)){
                            $cloudSecurityClient = new SettingClient($host, $apikey);
                            $cloudSecurityClient->save(array(), $this->router->generate('assetpicker_symcodecloud_callback'));
                        }
                    }

                    $this->clearCache();
                } catch (\Exception $exception) {

                }
                // we need to die because after the cache clear we will get sometimes class not found errors
                // because the current php process has still references to deleted cache classes
                die();
            }
        }

    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getCloudConfig($request, $key){
        // we need to use the current request value
        // the config manager has still the old value! ( bad class design ;) )
        $value = $request->get('symcode_cloud_akeneo'.ConfigManager::SECTION_VIEW_SEPARATOR.$key)['value'];
        //$value = $this->configManager->get(OverrideServiceCompilerPass::SYMCODE_CLOUD_AKENEO_CONFIG_SECTION.ConfigManager::SECTION_MODEL_SEPARATOR.$key);
        return $value;
    }

    protected function clearCache(){
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(
            array(
                'command' => 'cache:clear',
                '--no-warmup' => '',
                '--env' => 'prod',
            )
        );
        $output = new NullOutput();
        $application->run($input, $output);


        $input = new ArrayInput(
            array(
                'command' => 'cache:clear',
                '--no-warmup' => '',
                '--env' => 'dev',
            )
        );
        $output = new NullOutput();
        $application->run($input, $output);

        $input = new ArrayInput(
            array(
                'command' => 'cache:warmup',
                '--no-warmup' => '',
                '--env' => 'prod',
            )
        );
        $output = new NullOutput();
        $application->run($input, $output);

        $input = new ArrayInput(
            array(
                'command' => 'cache:warmup',
                '--no-warmup' => '',
                '--env' => 'dev',
            )
        );
        $output = new NullOutput();
        $application->run($input, $output);
    }


}
