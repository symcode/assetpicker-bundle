<?php

namespace Netresearch\AssetPickerBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class OverrideServiceCompilerPass implements CompilerPassInterface
{

    /**
     * @return array
     */
    public static function getAdditinalSettings(){
        $additionalSettings = [];
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'active',
            'parameter_key' => 'active'
        );
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'host',
            'parameter_key' => 'url'
        );
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'username',
            'parameter_key' => 'cliUsername'
        );
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'password',
            'parameter_key' => 'cliPassword'
        );
        return $additionalSettings;
    }

    public function process(ContainerBuilder $container)
    {

        $additionalSettings = self::getAdditinalSettings();

        // at first we need to manipulare the rest controller parameter
        // to accept our new configurations, its the method parameter 2
        // see: src/Oro/Bundle/ConfigBundle/Resources/config/controllers.yml
        // an normale class overwrite will also do the job but with this compiler pass
        // we allow also other extensions to manipulate the data
        $restConfigControllerDefinition = $container->getDefinition('oro_config.controller.configuration');
        $args = $restConfigControllerDefinition->getArguments(1);
        $args[1] = array_merge($args[1], $additionalSettings);
        $restConfigControllerDefinition->setArguments($args);

        // next we need to extend the ConfigManager Settings
        // normale all bundle configs with bundle prefix of oro_ or pim_ will be collected
        // but this bundle has an differend naming convention, therefore we need to force our settings into the
        // config manager see:
        // src/Oro/Bundle/ConfigBundle/DependencyInjection/Compiler/ConfigPass.php
        $taggedServices = $container->findTaggedServiceIds('oro_config.manager');
        foreach ($taggedServices as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $args = $definition->getArguments();
            foreach ($additionalSettings as $additionalSetting) {
                if (!isset($args[1][$additionalSetting['section']])) {
                    $args[1][$additionalSetting['section']] = [];
                }
                $args[1][$additionalSetting['section']][$additionalSetting['name']] = array(
                    'value' => '',
                    'scope' => 'app',
                );
            }
            $definition->setArguments($args);
        }

    }
}