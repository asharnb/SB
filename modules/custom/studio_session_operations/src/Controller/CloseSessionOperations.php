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
   * Initial runner for controller.
   *
   * @param $sid
   *  session nid
   * @param $confirm
   *  1 for confirm, 0 for not.
   * @return object
   *    Redirect
   */
  public function run($sid, $confirm) {

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

    if(!in_array($confirm,array(0,1))){
      drupal_set_message('Invalid confirm integer, only 1 or 0 allowed', 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }

    $status = $this->session_node->field_status->getValue();
    $status = isset($status[0]['value']) ? $status[0]['value'] : '';

    if($status == 'closed'){
      drupal_set_message('Session already closed, this operation can\'t be performed.', 'warning');
      return new RedirectResponse(base_path() . 'view-session/'.$sid);
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

  /*
   *  Build required batch operations.
   */
  public function buildOperations(){
    // Dropping products
    $this->getDraftProducts();

    // Mapping of UnMapped Products
    $this->MapUnmappedProductsOperations();

    // Images physical naming & folder structure
    $this->ImageNameOperations($this->sid);

    // Automated emails  // Create shootlist
    $this->operations[] = array(array(get_class($this), 'AutomaticEmails'), array($this->sid, $this->session_node));


    $this->operations[] = array(array(get_class($this), 'closeSession'), array($this->session_node));

    // Update end time to last scanned product.
    // todo: if last scanned product marked as draft ??
    $last_scan_product = \Drupal::state()->get('last_scan_product_' . $this->session_node->getOwnerId() . '_' . $this->sid, false);
    $this->operations[] = array(array(get_class($this), 'ProductEndTime'), array($this->sid,$last_scan_product));

  }

  public function closeSession($session){
    $session->field_status->setValue(array('value' => 'closed'));
    $session->save();
  }

  public function ProductEndTime($sid, $identifier){
    Products::AddEndTimeToProduct($sid,false,$identifier);
    $StudioSessions = \Drupal::service('studio.sessions');
    $StudioSessions->AddEndTimeToSession($sid, 0, false);
  }

  /*
   * Function to delete individual draft products.
   * @param product
   *  Product node object
   *
   */
  public function DeleteProducts($product, $sid, &$context) {
    $key = 'close_operation_delete_'.$product->id();
    \Drupal::state()->set($key,$sid);

    //    // todo : move it to service.
    $title = $product->title->getValue();
    \Drupal::logger('Studio')->notice('Product deleted - '. $title[0]['value']);

    $product->delete();
    $context['results']['drop'][] = $product->id();
    \Drupal::state()->delete($key);
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

  public function AutomaticEmails($sid, $session){
    Queues::RunMappingQueues($sid);


    $title = $session->title->getValue();
    if ($title) {
      $title = $title[0]['value'];
    }

    $mailManager = \Drupal::service('plugin.manager.mail');

    $module = 'studio_session_operations';
    $key = 'shootlist';
    //$to = \Drupal::currentUser()->getEmail();
    $to = 'ashar.babar@landmarkgroup.com';
    global $base_insecure_url;
    $link = $base_insecure_url."/shootlist/$sid/download.csv";

    $params['message'] = 'Download the shootlist csv file here ' . $link;
    $params['node_title'] = $title;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if ($result['result'] !== true) {
      drupal_set_message(t('There was a problem sending automated shootlist email, please download shootlists manually.'), 'error');
    }
    else {
      drupal_set_message(t('Shootlist email has been sent.'));
    }

  }

  // RunQueues
  public function RunQueues($sid){
    Queues::RunMappingQueues($sid);
  }


  public function getDraftProducts(){

    $product_nids = $this->session_node->field_product->getValue();
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
          $this->operations[] = array(array(get_class($this), 'DeleteProducts'), array($product,$this->sid));
        }

        $bundle = $product->bundle();
        if($bundle == 'unmapped_products'){
          $this->unmapped_products[] = $product;
        }

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

  public function ImageNameOperations($sid){
    foreach($this->products as $product){
      $this->operations[] = array(array(get_class($this), 'PhysicalImageName'), array($product, $sid));
    }
  }

  /*
   *
   */
  public function PhysicalImageName($product, $sid){


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
      }else{
        $color_variant = $field_base_product_id;
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

    // push to last in row.
    $tag_img = \Drupal::state()->get('Image_tag' . '_' . $sid,false);

    // make sure both values are set.
    if ($field_base_product_id && $images) {
      $i = 1;
      foreach ($images as $img) {
        // load file entity.
        $file = File::load($img['target_id']);

        $session_id = $sid;

        if ($file && $session_id) {

          $tag = $file->field_tag->getValue();
          $tagged = $tag[0]['value'];

          //$file_name = $file->filename->getValue();
          if($tagged){
            StudioImages::ImgUpdate($file, $sid,$field_base_product_id,$i,$concept, $color_variant, true);
            continue;
          }else{
            StudioImages::ImgUpdate($file, $session_id,$field_base_product_id,$i,$concept, $color_variant,false);
            $i++;
          }

        }
      }

//      // update tag image to last.
      $a =1;
//      if($tag_img){
//        StudioImages::ImgUpdate(File::load($tag_img), $sid,$field_base_product_id,$i,$concept, $color_variant, true);
//      }

    }


  }



}
