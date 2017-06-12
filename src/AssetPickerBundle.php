<?php
/**
 * See class comment
 *
 * PHP Version 5
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */

namespace Netresearch\AssetPickerBundle;
use Netresearch\AssetPickerBundle\Compiler\OverrideParameterCompilerPass;
use Netresearch\AssetPickerBundle\Compiler\OverrideServiceCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Class AssetPickerBundle
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */
class AssetPickerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // we need to manipulate the oro settings
        // we cannot save without this compiler because no default config are defined and the ConfigManager will
        // skip our attributes
        $container->addCompilerPass(new OverrideServiceCompilerPass());

        // parameter must be manipulated later
        // we need the configured config manager
        $container->addCompilerPass(
            new OverrideParameterCompilerPass(),
            PassConfig::TYPE_AFTER_REMOVING
        );
    }
}

?>
