<?php

/**
 * @file
 * Contains \Drupal\studio_session_operations\Controller\MapUnMappedProducts.
 */

namespace Drupal\studio_session_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\studiobridge_commons\Products;
use Drupal\studiobridge_commons\Queues;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\node\Entity\Node;
use Drupal\studiobridge_commons\StudioImages;
use \Drupal\file\Entity\File;


/**
 * Class MapUnMappedProducts.
 *
 * @package Drupal\studio_session_operations\Controller
 */
class MapUnMappedProducts extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $nodeStorage;

  protected $userStorage;

  protected $batch = array();

  protected $operations = array();

  protected $session_node;

  protected $products;

  protected $sid;

  protected $pids = array();

  protected $draft_products = array();

  protected $draft_product_nids = array();

  protected $unmapped_products = array();

  protected $fileStorage;

  /*
 * {@inheritdoc}
 */
  public static function create(ContainerInterface $container) {
    //$entity_manager = $container->get('entity.manager');
    return new static(
      $container->get('database')
    //$entity_manager->getStorage('node')
    );
  }

  /*
   * constructor function.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    //$this->formBuilder = $form_builder;
    //$this->userStorage = $this->entityManager()->getStorage('user');
    $this->nodeStorage = $this->entityTypeManager()->getStorage('node');
    $this->fileStorage = $this->entityTypeManager()->getStorage('file');
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Run.
   *
   * @return string
   *   Return Hello string.
   */
  public function run($sid) {
    $this->sid = $sid;

    $this->session_node = $this->nodeStorage->load($sid);

    // on invalid product, redirect user to somewhere & notify him.
    if (!$this->session_node) {
      drupal_set_message('Invalid Session id '.$sid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }
    elseif(!in_array($bundle = $this->session_node->bundle(),array('sessions'))){
      drupal_set_message('Invalid Session id '.$sid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }


    // Build required operations for batch process.
    $this->buildOperations();

    $batch = array(
      'title' => t('Batch Process'),
      'operations' => $this->operations,
      'finished' => array(get_class($this), 'finishBatch'),
    );

    // Set the batch.
    batch_set($batch);
//    drupal_set_message("confirm - $confirm");
    return batch_process('view-session/' . $sid);
  }

  /*
 *  Build required batch operations.
 */
  public function buildOperations(){
    // Dropping products
    $this->getProducts();

    // Mapping of UnMapped Products
    $this->MapUnmappedProductsOperations();

    // Automated emails  // Create shootlist
    $this->operations[] = array(array(get_class($this), 'RunQueues'), array($this->sid, $this->session_node));
  }


  public function RunQueues($sid, $session){
    Queues::RunMappingQueues($sid);
  }

  /*
 *
 */
  public function MapUnmappedProductsOperations() {
    $identifier = false;
    if($this->unmapped_products){
      foreach($this->unmapped_products as $product){

        $title = $product->title->getValue();
        if ($title) {
          $identifier = $title[0]['value'];
        }

        if($identifier){
          $StudioProducts = \Drupal::service('studio.products');
          //$server_product = Products::getProductExternal($identifier);
          $server_product = $StudioProducts->getProductExternal($identifier);
          $server_product = json_decode($server_product);
          if (!isset($server_product->msg)){

            if (is_object($server_product)) {
              $this->operations[] = array(array(get_class($this), 'NodeCovert'), array($product,$server_product));
              Queues::CreateQueueProductMapping($this->sid, $server_product, $product->id());
            }

          }
        }
      }
    }
  }

  public function getProducts(){

    $product_nids = $this->session_node->field_product->getValue();
    foreach($product_nids as $target){
      $this->pids[] = $target['target_id'];
    }

    $this->products = $this->nodeStorage->loadMultiple($this->pids);

    foreach($this->products as $product){
      $bundle = $product->bundle();
      if($bundle == 'unmapped_products'){
        $this->unmapped_products[] = $product;
      }
    }

  }


  /*
   *
   */
  public function NodeCovert($unmappedProduct,$server_product, &$context){
    if (is_object($server_product)) {
      $unmappedProduct->type->setValue('products');
      $unmappedProduct->save();

      $context['results']['mapped'][] = $unmappedProduct->id();
    }
  }

  /*
 * Function will run after batch finished.
 */
  public function finishBatch($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message1 = \Drupal::translation()->formatPlural(
        count($results['mapped']),
        'One product mapped.', '@count products mapped.'
      );

      $message2 = \Drupal::translation()->formatPlural(
        count($results['drop']),
        'One product deleted.', '@count products deleted.'
      );
    }
    else {
      $message1 = t('Finished with an error.');
      $message2 = false;
    }

    drupal_set_message($message1);
    if($message2){
      drupal_set_message($message2);
    }
  }


}
