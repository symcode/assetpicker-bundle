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

namespace Netresearch\AssetPickerBundle\Controller\SymcodeCloud;

use Netresearch\AssetPickerBundle\Compiler\OverrideServiceCompilerPass;
use Netresearch\Bundle\DamConnectBundle\Model\DamImageEntity;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symcode\Cloud\Client\Structure\UsageResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CallbackController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     * @Security("has_role('')")
     */
    public function indexAction(Request $request)
    {
        $action = $request->request->get('action', '');
        $active = $this->getConfig('active');
        $callbackActive = $this->getConfig('callback_active');

        $responseData = array();

        if($active && $callbackActive){

            $files = $request->request->get('files', array());
            if(isset($files['hash'])){
                $files = array($files);
            }

            switch ($action) {
                case 'update':
                case 'create':
                    $this->assign($files);
                    break;
                case 'delete':
                    $this->remove($files);
                    break;
                case 'checkUsage':
                    $responseData = $this->findUsage($files);
                    break;
                default:
                    break;
            }
        }

        return new JsonResponse($responseData);
    }

    /**
     * @param $files
     * @return array
     */
    protected function findUsage($files){
        $result = array(
            'data' => array(),
            'success' => true
        );
        /**
         * @var $giler UsageResponse
         */
        foreach($files as $file){
            $productsCursor = $this->locateProductsByHash($file);
            $usages = array();
            foreach($productsCursor as $product){
                $usages[] = array(
                    'url' => $this->router->generate('pim_enrich_associations', array('id' => $product->getId())),
                    'message' => sprintf('File is used in Akeneo in Product %s', $productsCursor->getValue('sku'))
                );
            }
            $result[] = array(
                'hash' => $file['hash'],
                'usages' => $usages
            );
        }
        return $result;
    }

    /**
     * @param $file
     * @return array
     */
    protected function locateProductsByPattern($file){

        $patternData = $this->calculateDataBaseOnPatternAndFile($file);

        $count = 0;
        $productsCursor = [];

        if(!empty($patternData)){
            $sku = $patternData['sku'];
            $count = $patternData['count'];

            $pqb = $this->getProductQueryBuilder();
            // search for the sku based on the filename and pattern
            $pqb->addFilter('sku', '=', $sku);
            $productsCursor = $pqb->execute();
        }

        return array(
            'products' => $productsCursor,
            'count' => $count
        );
    }

    /**
     * @param $file
     * @return mixed
     */
    protected function locateProductsByHash($file){
        $pqb = $this->getProductQueryBuilder();
        $pqb->addFilter('values.text', 'LIKE', '{"id":"'.$file['hash'].'",%');
        $productsCursor = $pqb->execute();
        return $productsCursor;
    }

    /**
     * @param $file
     * @return array
     */
    protected function calculateDataBaseOnPatternAndFile($file){
        $pattern = $this->getConfig('callback_pattern');
        if(empty($pattern)){
            // default pattern are every string with a file extension
            // the filename will be used as sku
            $pattern = '/^(.*)\.[a-z]+/';
        }

        $matches = array();
        if(count($matches) > 1){
            preg_match($pattern, $file['name'], $matches);

            return array(
                'sku' => $matches[1],
                'count' => 0
            );
        }

        return array();
    }

    /**
     * @param $files
     */
    protected function assign($files){
        $pm = $this->getProductManager();
        foreach($files as $file){
            $locateData = $this->locateProductsByPattern($file);
            $count = (int)$locateData['count'];
            foreach($locateData['products'] as $product){
                $attributes = $this->getAssetpickerAttributes($product);
                if(isset($attributes[$count])){
                    $attribute = $attributes[$count];
                    //$value = $product->getValue($attribute->getCode())->setData();
                    $assetEntry = $this->findOrCreateAssetEntry($file);
                    $product->getValue($attribute->getCode())->setData($assetEntry);
                    $pm->save($product);
                }
            }
        }
    }

    /**
     * @param $files
     */
    protected function delete($files){
        $remover = $this->getContainer()->get('pim_catalog.remover.product');
        foreach($files as $file){
            $assetData = $this->findAssetEntryOfCloudFile($file);
            if(!empty($assetData)){
                $productsCursor = $this->locateProductsByHash($file);
                foreach($productsCursor as $product){
                    $remover->remove($product);
                }
            }
        }
    }

    protected function getPatternProducts($file){
        $pqb = $this->getProductQueryBuilder();
        $patternData = $this->calculateDataBaseOnPatternAndFile($file);
        // search for the sku based on the filename and pattern
        $pqb->addFilter('sku', '=', $patternData['sku']);
        $productsCursor = $pqb->execute();
        return $productsCursor;
    }

    /**
     * @param $file
     * @return DamImageEntity|null
     */
    protected function findOrCreateAssetEntry($file){
        $adapterConfig = $this->getAdapterConfig();
        if(!empty($adapterConfig)){
            $assetData = $this->findAssetEntryOfCloudFile($file);
            if(empty($assetData)){
                $identifier = $adapterConfig['__key'].'/'.$file['hash'];
                $data = array(
                    'id' => $file['hash'],
                    'storage' => $adapterConfig['__key'],
                    'query' => [],
                    'name' => $file['name'],
                    'type' => 'file',
                    'mediaType' => array('name' => 'image'),
                    'links' => array(
                        'open' => $file['media_thumb_url']+'?width=800px',
                        'download' => $file['media_file_url']
                    ),
                    'created' => $file['created_at'],
                    'modified' => $file['changed_at'],
                    'data' => $file
                );
                $assetSaver = $this->getAssetSaver();
                $assetData      = new DamImageEntity();
                $assetData->setIdentifier($identifier);
                $assetData->setAssetData(json_encode($data));
                $assetSaver->save($assetData);
            }
            return $assetData;
        }
        return null;
    }

    /**
     * @return mixed
     */
    protected function getProductQueryBuilder(){
        // product query builder factory
        $pqbFactory = $this->getContainer()->get('pim_catalog.query.product_query_builder_factory');
        // returns a new instance of product query builder
        $pqb = $pqbFactory->create(['default_locale' => 'en_US', 'default_scope' => 'ecommerce']);
        return $pqb;
    }

    /**
     * @param $file
     * @return array
     */
    protected function findAssetEntryOfCloudFile($file) {
        $adapterConfig = $this->getAdapterConfig();
        $assetData = array();
        if(!empty($adapterConfig)) {
            $identifier = $adapterConfig['__key'].'/'.$file['hash'];
            $assetData = $this->getAssetRepo()->createQueryBuilder()->addFilter('identifier', '=', $identifier);
        }
        return $assetData;
    }

    /**
     * @return array
     */
    protected function getAdapterConfig(){
        $pickerConfig = $this->getParameter('assetpicker');
        $config = array();
        foreach($pickerConfig['storages'] as $key => $storage){
            if($storage['adapater'] == 'symcodecloud'){
                $storage['__key'] = $key;
                $config = $storage;
                break;
            }
        }
        return $config;
    }

    /**
     * @return array
     */
    protected function getAssetpickerAttributes($product){
        // attribute manager
        $am = $this->container->get('pim_catalog.manager.attribute');
        $attributes = $am->findByCode('asset_test');
        // @todo add attribute set of product to filter
        return $attributes;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager(){
        return $this->get('oro_config.global');
    }

    /**
     * @return mixed
     */
    protected function getProductManager(){
        // product manager
        return $this->container->get('pim_catalog.manager.product');
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getConfig($key){
        return $this->getConfigManager()->get(OverrideServiceCompilerPass::SYMCODE_CLOUD_AKENEO_CONFIG_SECTION.ConfigManager::SECTION_MODEL_SEPARATOR.$key);
    }

    /**
     * @return NrDoctrine\MongoDBODM\Repository\DamImageRepository|NrDoctrine\ORM\Repository\DamImageRepository
     */
    protected function getAssetRepo(){
        /** @var NrDoctrine\MongoDBODM\Repository\DamImageRepository | NrDoctrine\ORM\Repository\DamImageRepository $assetRepository  */
        $assetRepository = $this->get('nr_dc.repository.damimage');
        return $assetRepository;
    }

    /**
     * @return NrDoctrine\MongoDBODM\Repository\DamImageSaver|NrDoctrine\ORM\Repository\DamImageSaver
     */
    protected function getAssetSaver(){
        $assetSaver = $this->get('nr_dc.saver.damimage');
        return $assetSaver;
    }
}
