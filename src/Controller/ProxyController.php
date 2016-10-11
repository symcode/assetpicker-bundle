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

namespace Netresearch\AssetPickerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProxyController
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\DependencyInjection
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */
class ProxyController extends Controller
{
    /**
     * @param Request $request
     */
    public function indexAction(Request $request)
    {
        if ($request->query->has('to')) {
            $proxyTo = $request->query->get('to');
            $request->query->remove('to');
            $proxy = new \Netresearch\AssetPicker\Proxy();
            return $proxy->forward($request)->to($proxyTo);
        } else {
            throw new \Exception('No target provided');
        }
    }
}
