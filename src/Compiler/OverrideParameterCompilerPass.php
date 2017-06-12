<?php

namespace Netresearch\AssetPickerBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class OverrideParameterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        /**
         * @var $configManager ConfigManager
         */
        $configManager = $container->get('oro_config.global');
        $config = $container->getParameter('assetpicker');

        foreach ($config['storages'] as $storageKey => $storageConfig) {
            if ($storageConfig['adapter'] == 'symcodecloud') {
                $host = $configManager->get('symcode_cloud_akeneo.host');
                $username = $configManager->get('symcode_cloud_akeneo.username');
                $password = $configManager->get('symcode_cloud_akeneo.password');
                if (!empty($host)) {
                    $config['storages'][$storageKey]['url'] = $host;
                    $config['storages'][$storageKey]['cliUsername'] = $username;
                    $config['storages'][$storageKey]['cliPassword'] = $password;
                }
            }
        }

        $container->setParameter('assetpicker', $config);

    }
}