<?php

/**
 * @file
 * Contains \Drupal\studio_session_operations\Controller\CloseSessionOperations.
 *
 * // Dropping products
 *
 * // Mapping of UnMapped Products
 *
 * // Create shootlist
 *
 * // Images physical naming & folder structure
 *
 * // Automated emails
 *
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
 * Class CloseSessionOperations.
 *
 * @package Drupal\studio_session_operations\Controller
 */
class CloseSessionOperations extends ControllerBase {

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

  protected $session;

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

  public function __construct(Connection $database) {
    $this->database = $database;
    //$this->formBuilder = $form_builder;
    //$this->userStorage = $this->entityManager()->getStorage('user');
    $this->nodeStorage = $this->entityTypeManager()->getStorage('node');
    $this->fileStorage = $this->entityTypeManager()->getStorage('file');
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Hello.
   *
   * @param $sid
   * @return object
   *    Redirect
   */
  public function run($sid, $confirm) {

    $this->sid = $sid;

    $this->session = $this->nodeStorage->load($sid);

    // on invalid product, redirect user to somewhere & notify him.
    if (!$this->session) {
      drupal_set_message('Invalid Session id '.$sid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }
    elseif(!in_array($bundle = $this->session->bundle(),array('sessions'))){
      drupal_set_message('Invalid Session id '.$sid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }

    if(!in_array($confirm,array(0,1))){
      drupal_set_message('Invalid confirm integer, only 1 or 0 allowed', 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }

    $this->buildOperations();


    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $operations = array();
    for ($i = 1; $i <= $total; $i++) {
      $operations[] = array(array(get_class($this), '__callback_1'), array($i, $sleep));
    }

    $batch = array(
      'title' => t('Batch Process'),
      'operations' => $this->operations,
      'finished' => array(get_class($this), 'finishBatch'),
    );

    batch_set($batch);
    drupal_set_message("confirm - $confirm");
    return batch_process('view-session/' . $sid);
  }

  public function __callback_1($id, $sleep, &$context) {

    //die($sleep);
    // No-op, but ensure the batch take a couple iterations.
    // Batch needs time to run for the test, so sleep a bit.
    usleep($sleep);
    // Track execution, and store some result for post-processing in the
    // 'finished' callback.
    //batch_test_stack("op 1 id $id");
    $context['results'][] = $id;
  }

  public function finishBatch($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One operation processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }

    drupal_set_message($message);
    //$_SESSION['disc_migrate_batch_results'] = $results;
  }

  /*
   *
   */
  public function buildOperations(){
    // Dropping products
    $this->getDraftProducts();

    // Mapping of UnMapped Products
    $this->MapUnmappedProductsOperations();


    // Create shootlist

    // Images physical naming & folder structure
    $this->ImageNameOperations();

    // Automated emails

    //


    $this->operations[] = array(array(get_class($this), 'AutomaticEmails'), array($this->sid));
  }

  /*
   * Function to delete individual draft products.
   * @param product
   *  Product node object
   *
   */
  public function DeleteProducts($product, &$context) {
    //$product->delete();
    $a = 1;
    $context['results'][] = $product->id();
  }

  /*
   *
   */
  public function MapUnmappedProductsOperations() {

    if($this->unmapped_products){
      foreach($this->unmapped_products as $product){
        $this->operations[] = array(array(get_class($this), 'NodeCovert'), array($product));

        $title = $product->title->getValue();
        if ($title) {
          $identifier = $title[0]['value'];
        }

        Queues::CreateQueueProductMapping($this->sid, $identifier, $product->id());
      }
    }
  }

  public function AutomaticEmails($sid){
    Queues::RunMappingQueues($sid);
  }

  public function getDraftProducts(){

    $product_nids = $this->session->field_product->getValue();
    foreach($product_nids as $target){
       $this->pids[] = $target['target_id'];
    }

    $this->products = $this->nodeStorage->loadMultiple($this->pids);

    foreach($this->products as $product){
      $draft = $product->field_draft->getValue();
      if(isset($draft[0]['value'])){
        if($draft[0]['value'] == 1){
          $this->draft_products[] = $product;
          $this->draft_product_nids[] = $product->id();
          $this->operations[] = array(array(get_class($this), 'DeleteProducts'), array($product));
        }

        $bundle = $product->bundle();
        if($bundle == 'unmapped_products'){
          $this->unmapped_products[] = $product;
        }

      }
    }

    $a =1;

  }

  /*
   *
   */
  public function NodeCovert($unmappedProduct){
    $identifier = false;

    $title = $unmappedProduct->title->getValue();
    if ($title) {
      $identifier = $title[0]['value'];
    }
    $uid = $unmappedProduct->uid->getValue();
    $uid = $uid[0]['target_id'];
    $nid = $unmappedProduct->id();


    if($identifier){

      $product = Products::getProductExternal($identifier);
      $product = json_decode($product);
      if (!isset($product->msg)){
        // Get current logged in user.
        $user = \Drupal::currentUser();
        // Get uid of logged in user.
        $uid = $user->id();
        if (is_object($product)) {
          $values = array(
            'nid' => $nid,
            'type' => 'products',
            'title' => 'sdfs',
            'uid' => $uid,
            'status' => TRUE,
            'field_base_product_id' => array('value' => $product->base_product_id),
            'field_style_family' => array('value' => $product->style_no),
            'field_concept_name' => array('value' => $product->concept),
            'field_gender' => array('value' => $product->gender),
            'field_description' => array('value' => $product->description),
            'field_color_variant' => array('value' => $product->color_variant), // todo: may be multiple
            'field_color_name' => array('value' => $product->color_name), //  todo: may be multiple
            'field_size_name' => array('value' => $product->size_name), // todo: may be multiple
            'field_size_variant' => array('value' => $product->size_variant), // todo: may be multiple
          );

          $unmappedProduct->type->setValue('products');

          // todo MAP FIELDS

          //$unmappedProduct->field_base_product_id->setValue(array('value' => $product->base_product_id));
          //$id = $unmappedProduct->id();
          $unmappedProduct->save();


          //\Drupal\Core\Cache\Cache::invalidateTags(array('node:'.$id));
          //\Drupal::cache()->delete('node:'.$id);
//
//          drupal_flush_all_caches();
//          $xx = Node::load($id);
//          $a = 1;

          // Create node object with above values.
          //$node = \Drupal::entityManager()->getStorage('node')->save($values);
          //$unmappedProduct->
          // Finally save the node object.
          //$node->save();
        }
      }
    }

  }

  public function ImageNameOperations(){
    foreach($this->products as $product){
      $this->operations[] = array(array(get_class($this), 'PhysicalImageName'), array($product));
    }
  }

  /*
   *
   */
  public function PhysicalImageName($product){


    $concept = 'InValidConcept';
    $color_variant = 'InValidColorVariant';
    $product_bundle = $product->bundle();

    // Get base product id from mapped product.
    // Get identifier from unmapped product.
    if ($product_bundle == 'products') {
      $field_base_product_id = $product->field_base_product_id->getValue();
      if ($field_base_product_id) {
        $field_base_product_id = $field_base_product_id[0]['value'];
      }

      $product_concept = $product->field_concept_name->getValue();
      if($product_concept){
        $concept = $product_concept[0]['value'];
      }

      $product_color_variant = $product->field_color_variant->getValue();
      if($product_color_variant){
        $color_variant = $product_color_variant[0]['value'];
      }
    }
    elseif ($product_bundle == 'unmapped_products') {
      $field_identifier = $product->field_identifier->getValue();
      $title = $product->title->getValue();
      if ($field_identifier) {
        $field_base_product_id = $field_identifier[0]['value'];
      }elseif ($title) {
        $field_base_product_id = $title[0]['value'];
      }

      $concept = 'Unmapped';
      $color_variant = $field_base_product_id;
    }

    // Get images field from product.
    $images = $product->field_images->getValue();

    // make sure both values are set.
    if ($field_base_product_id && $images) {
      $i = 1;
      foreach ($images as $img) {
        // load file entity.
        $file = File::load($img['target_id']);
        $session_id = $file->field_session->getValue();
        if($session_id){
          $session_id = $session_id[0]['target_id'];
        }
        //\Drupal::logger('123wer')->notice('<pre>'.print_r($session_id,true).'</pre>');

        $filemime = $file->filemime->getValue();
        if ($filemime && $session_id) {
          $filemime = $filemime[0]['value'];
          $filemime = explode('/', $filemime);
          $filemime = $filemime[1];
          if ($filemime == 'octet-stream') {
            $filemime = 'jpg';
          }
          // todo : filemime will be wrong
          // change file name as per sequence number and base product_id value.
          $filename = $field_base_product_id . '_' . $i . ".$filemime";
          //$file_uri = $file->uri->getValue();
          //$x = 'public://'.'xyz/_krishna_'.time();

          //$dir = 'Sessionsx/'.date('H-i-s');

          $dir = $session_id.'/'.$concept.'/'.$color_variant;

          if(StudioImages::ImagePhysicalName($dir,$filename,$file)){
            $folder = "public://$dir";
            $uri = $folder.'/'.$filename;
            $file->uri->setValue($uri); //public://fileKVxEHe
          }
          $file->filename->setValue($filename);
          $file->save();
          //
          $folder = "public://$dir";
          $uri = $folder.'/'.$filename;
          StudioImages::UpdateFileLog($file->id(),$uri);

          $i++;

          //\Drupal::logger('GGG')->notice('<pre>'.print_r($file,true).'</pre>');

          //file_prepare_directory()
        }
      }
    }


  }

}
