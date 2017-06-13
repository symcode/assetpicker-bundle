<?php

namespace Netresearch\AssetPickerBundle\Compiler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

/**
 *
 * this Compiler pass will read our saved Settings from the Database and manipulate
 * the Container Parameters to use the Database Values if it was activated
 * we need this changed parameters for the JS to enable the auto login in the symcodecloud adapter
 * and additional we need this parameters for the symcodecloud callback controller for the auto assignment of assets
 *
 * Class OverrideParameterCompilerPass
 * @package Netresearch\AssetPickerBundle\Compiler
 */
class OverrideParameterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $additionalSettings = OverrideServiceCompilerPass::getAdditinalSettings();

        /**
         * @var $configManager ConfigManager
         */
        $configManager = $container->get('oro_config.global');
        $config = $container->getParameter('assetpicker');
        // check if akeneo settings for symcode cloud adapter are active
        $active = $configManager->get('symcode_cloud_akeneo.active');
        if($active) {

            $cloudStorageConfig = null;
            foreach ($config['storages'] as $storageKey => $storageConfig) {
                if ($storageConfig['adapter'] == 'symcodecloud') {
                    $cloudStorageConfig = &$config['storages'][$storageKey];
                    break;
                }
            }

            if (!$cloudStorageConfig) {
                // if no cloud configured and it is only one storage configured
                // then its the default setting and we will overwrite it!
                if (count($config['storages']) <= 1) {
                    $cloudStorageConfig = &$config['storages'][0];
                } else {
                    $newConfig = array();
                    $config['storages'][] = $newConfig;
                    $cloudStorageConfig = &$newConfig;
                }
            }

            // now reconfigure the target storage entry (by reference)
            $cloudStorageConfig['adapter'] = 'symcodecloud';
            $cloudStorageConfig['proxy'] = false;

            foreach ($additionalSettings as $additionalSetting) {
                $value = $configManager->get(
                    $additionalSetting['section'].ConfigManager::SECTION_MODEL_SEPARATOR.$additionalSetting['name']
                );
                $cloudStorageConfig[$additionalSetting['parameter_key']] = $value;
            }

        }

        $container->setParameter('assetpicker', $config);

    }
}