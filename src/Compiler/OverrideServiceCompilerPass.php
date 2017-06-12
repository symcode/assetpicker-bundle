<?php

namespace Netresearch\AssetPickerBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $additionalSettings = [];
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'host',
        );
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'username',
        );
        $additionalSettings[] = array(
            'section' => 'symcode_cloud_akeneo',
            'name' => 'password',
        );

        $restConfigControllerDefinition = $container->getDefinition('oro_config.controller.configuration');
        $args = $restConfigControllerDefinition->getArguments(1);
        $args[1] = array_merge($args[1], $additionalSettings);
        $restConfigControllerDefinition->setArguments($args);

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