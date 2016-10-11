<?php
/**
 * See class comment
 *
 * PHP Version 5
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\DependencyInjection
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */

namespace Netresearch\AssetPickerBundle\DependencyInjection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


/**
 * Class AssetPickerExtension
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\DependencyInjection
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */
class AssetPickerExtension extends Extension
{
    /**
     * Load the configurations
     *
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('assetpicker', call_user_func_array('array_replace_recursive', $config));
    }
}
