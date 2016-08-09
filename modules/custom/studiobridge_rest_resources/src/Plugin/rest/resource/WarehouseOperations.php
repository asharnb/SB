<?php

namespace Drupal\studiobridge_rest_resources\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Plugin\views\field\File;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "warehouse_operations",
 *   label = @Translation("[Studio|Warehouse] warehouse operations"),
 *   serialization_class = "Drupal\node\Entity\Node",
 *   uri_paths = {
 *     "canonical" = "/warehouse/operation/{random}",
 *     "https://www.drupal.org/link-relations/create" = "/warehouse/operation/{op_type}/post"
 *   }
 * )
 */
class WarehouseOperations extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  protected $studioProducts;

  protected $studioSessions;

  protected $state;

  protected $nodeStorage;

  protected $fileStorage;

  protected $studioContainer;

  protected $newProduct = false;


  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user, $studioProducts, $studioSessions, $state, $entity_manager, $studioContainer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->studioProducts = $studioProducts;
    $this->studioSessions = $studioSessions;
    $this->state = $state;
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->fileStorage = $entity_manager->getStorage('file');
    $this->studioContainer = $studioContainer;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('ccms_rest'),
      $container->get('current_user'),
      $container->get('studio.products'),
      $container->get('studio.sessions'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('studio.container')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $op_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($op_type, $data) {

    $node_type = $op_type;

    if ($node_type == 'import') {
      if ($data->body->value['product'] && $data->body->value['container'] && $data->body->value['container_nid']) {
        $container = $data->body->value['container'];
        $product_identifier = $data->body->value['product'];
        $container_nid = $data->body->value['container_nid'];
        $on_pageload = $data->body->value['onload'];
        $duplicate = false;
        $already_scanned = false;

        // Set product identifier to container state
        $this->state->set('warehouse_container_last_scan_product_' . $container, $product_identifier);
        //$b = $this->studioProducts->getLastScannedProduct(0, $container_nid);


        // Check server for product.
        $result = $this->studioProducts->getProductByIdentifier($product_identifier);
        $product = $this->productCheck($result, $product_identifier);
        $product_values = $product->toArray();
        $product_container_images = $this->studioProducts->getProductImages($product->id(),true);

        // Assign this product to container.
        $this->studioContainer->addProductToContainer($container_nid, $product);

        // Product data response.
        $product_return_data = $this->studioProducts->getProductInfoByObject($product);

        // Check for duplicate/Reshoot
        if($product){
          $pid = $product->id();
          // Add imported product identifier & product node it to schema table.
          if(!$on_pageload){
            $this->studioProducts->insertProductImportRecord($product_identifier, $pid, 0, $container_nid);
          }

          $duplicates = $this->studioProducts->checkProductDuplicate($product->id(), array('container'));
          if(count($duplicates) > 1){
            $duplicate = true;

            if(in_array($container_nid, $duplicates)){
              $already_scanned = true;
            }

          }elseif(count($duplicates) == 1){
            if(reset($duplicates) == $container_nid){
              if(!$this->newProduct){
                //$already_scanned = true;
              }
            }
          }
        }

        // Return the response. Product info, container info, import status, etc.,
        return new ResourceResponse(array('product'=>$product_return_data,'duplicate' => $duplicate,'already_scanned' => $already_scanned,'images' => $product_container_images));
      }
    }

    if ($node_type == 'drop-product') {
      if ($data->body->value['product'] && $data->body->value['container'] && $data->body->value['container_nid']) {

        $return = $this->dropProductInContainer($data);
        return new ResourceResponse($return);
      }
    }

    if ($node_type == 'drop-image'){
      $this->dropImageInProductOfContainer($data);
    }

    return new ResourceResponse(array(rand(1, 22222222), array($node_type)));
  }


  /*
   *
   */
  public function productCheck($result, $identifier) {
    $return = array();
    if (!$result) {
      // Get product from server
      $product = $this->studioProducts->getProductExternal($identifier);
      $product = json_decode($product);
      // validate product
      if (isset($product->msg)) {
        // product not found on the server so save it as unmapped product.
        $return = $this->studioProducts->createUnmappedProductFromContainer($identifier);
        $this->newProduct = true;
      }
      else {
        // Create them if not exist in drupal server.
        $return = $this->studioProducts->createMappedProduct($product, $identifier, 'warehouse');
        $this->newProduct = true;
      }
    }
    else {
      // todo : code for product re shoot
      $product_nid = reset($result);
      if($product_nid){
        $return = $this->nodeStorage->load($product_nid);
      }
    }

    return $return;

  }

  /*
   * Helper function, to drop product from container.
   */
  public function dropProductInContainer($data){
    $container = $data->body->value['container'];
    $product_identifier = $data->body->value['product'];
    $container_nid = $data->body->value['container_nid'];
    // Return the response. Product info, container info, import status, etc.,

    $result = $this->studioProducts->getProductByIdentifier($product_identifier);
    $node = $this->nodeStorage->load(reset($result));

    if($node){
      return $this->studioContainer->addDropProductToContainer($container_nid, $node);
    }

    return array('message' => 'Failed to drop product. It not exists in the system.','status' => false);
  }

  /*
 * Helper function, to drop product from container.
 */
  public function dropImageInProductOfContainer($data){
    $container = $data->body->value['container'];
    $product_identifier = $data->body->value['product'];
    $container_nid = $data->body->value['container_nid'];
    $fid = $data->body->value['fid'];
    // Return the response. Product info, container info, import status, etc.,

    $result = $this->studioProducts->getProductByIdentifier($product_identifier);
    $node = $this->nodeStorage->load(reset($result));

    if($node){
      //$return =  $this->studioProducts->deleteImageFromProduct($node, $fid);

      // Delete image physically.
      $file = $this->fileStorage->load($fid);
      $file->delete();


      return array('status' => 'File deleted successfully!');
    }

    return array('message' => 'Failed to drop product. It not exists in the system.','status' => false);
  }


  public function processProduct($result, $product_identifier, $container_nid){
    $duplicate = false;
    $already_scanned = false;
    $product = $this->productCheck($result, $product_identifier);
    $product_values = $product->toArray();
    $product_container_images = $this->studioProducts->getProductImages($product->id(),true);

    // Assign this product to container.
    $this->studioContainer->addProductToContainer($container_nid, $product);

    // Product data response.
    $product_return_data = $this->studioProducts->getProductInfoByObject($product);

    // Check for duplicate/Reshoot
    if($product){
      $duplicates = $this->studioProducts->checkProductDuplicate($product->id(), array('container','sessions'));
      if(count($duplicates) > 1){
        $duplicate = true;
      }elseif(count($duplicates) == 1){
        if(reset($duplicates) == $container_nid){
          if(!$this->newProduct){
            $already_scanned = true;
          }
        }
      }
    }

    return array('product'=>$product_return_data,'duplicate' => $duplicate,'already_scanned' => $already_scanned,'images' => $product_container_images);
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $op_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get($random) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    if(!empty($_GET['identifier']) && !empty($_GET['cid'])){

      $result = $this->studioProducts->getProductByIdentifier($_GET['identifier']);
      if($result){
        $duplicates = $this->studioProducts->checkProductDuplicate(reset($result), array('container','sessions'));

        if(count($duplicates) > 1){

          if(in_array($_GET['cid'], $duplicates)){
            return new ResourceResponse(array('reshoot'=> true, 'same_container' => true));
          }else{
            return new ResourceResponse(array('reshoot'=> true, 'same_container' => false));
          }

        }elseif(count($duplicates) == 1){
          if(reset($duplicates) == $_GET['cid']){
            return new ResourceResponse(array('reshoot'=> false, 'same_container' => true));
          }else{
            return new ResourceResponse(array('reshoot'=> true, 'same_container' => false));
          }
        }

        return new ResourceResponse(array('reshoot'=> false, 'same_container' => false));
      }else{
        return new ResourceResponse(array('reshoot'=> false, 'same_container' => false));
      }

    }

    return new ResourceResponse($_GET);
  }
}

