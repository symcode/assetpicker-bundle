<?php
/**
 * See class comment
 *
 * PHP Version 5
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\Twig
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */

namespace Netresearch\AssetPickerBundle\Twig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AssetPickerExtension
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\Twig
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */
class AssetPickerExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * AssetPickerExtension constructor.
     *
     * @param ContainerInterface $container
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(ContainerInterface $container, UrlGeneratorInterface $generator)
    {
        $this->container = $container;
        $this->generator = $generator;
    }

    /**
     * Get the functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'assetpicker_config',
                function () {
                    $config = $this->container->getParameter('assetpicker');
                    if (!isset($config['proxy']['url'])) {
                        try {
                            $config['proxy']['url'] = $this->generator->generate('assetpicker_proxy') . '?to={{url}}';
                        } catch (RouteNotFoundException $e) {
                        }
                    }
                    return $config ? json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '{}';
                },
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'assetpicker_url',
                function (array $options = []) {
                    return $this->container->get('templating.helper.assets')->getUrl('bundles/assetpicker/js/picker.js');
                }
            )
        ];
    }
}
