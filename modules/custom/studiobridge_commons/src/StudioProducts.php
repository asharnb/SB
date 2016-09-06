<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioProducts.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use \Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\State\StateInterface;

CONST PRODUCT_LOOKUP_SERVER_URL = 'http://alpha.cms2.dreamcms.me/service/product-data?_format=json&product_identifier=';

/**
 * Class StudioProducts.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioProducts implements StudioProductsInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage service.
   */
  protected $nodeStorage;

  /**
   * The user storage service.
   */
  protected $userStorage;

  /**
   * The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser = array();

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The user storage service.
   */
  protected $fileStorage;

  protected $StudioQc;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, AccountProxyInterface $current_user, QueryFactory $query_factory, StateInterface $state) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->fileStorage = $entityTypeManager->getStorage('file');

    $this->database = $database;
    $this->currentUser = $current_user;
    $this->queryFactory = $query_factory;
    $this->state = $state;

    $this->StudioQc = \Drupal::service('studio.qc');

  }

  /*
   * Helper function, to get a product by its identifier.
   *
   * @param identifier
   *   Name of the identifier.
   */
  public function getProductByIdentifier($identifier) {

    $query = $this->queryFactory->get('node');
    $query->condition('type', array('products', 'unmapped_products'), 'IN');

    // Or condition for product fields
    $orCondition = $query->orConditionGroup();
    $orCondition->condition('field_color_variant', $identifier);
    $orCondition->condition('title', $identifier);
    $orCondition->condition('field_barcode', $identifier);
    $orCondition->condition('field_size_variant', $identifier);

    $query->condition($orCondition);
    $query->sort('created', 'DESC');
    $result = $query->execute();

    return $result;
  }

  /*
   * Helper function, to get a product by its author.
   *
   * @param uid
   *   User entity uid.
   */
  public function getProductByUid($uid) {
    $result = $this->queryFactory->get('node')
      ->condition('type', array('products', 'unmapped_products'), 'IN')
      ->sort('created', 'DESC')
      ->condition('field_state', 'open')
      ->condition('uid', $uid)
      ->range(0, 1)
      ->execute();
    return $result;
  }

  /*
   * Helper function, update product state value.
   *
   * @param identifier
   *   Name of the identifier.
   * @param state
   *   Values like open, close..
   */
  public function updateProductState($identifier, $state) {
    // Get the node by its identifier.
    $node_id = $this->getProductByIdentifier($identifier);

    if (count($node_id)) {
      $node_id = reset($node_id);
      // Load the node object.
      $product_node = $this->nodeStorage->load($node_id);
      if ($product_node) {
        $state = array(
          'value' => $state
        );
        // Set the field state values.
        $product_node->field_state->setValue($state);
        // Save the node.
        $product_node->save();
      }
    }
  }

  /*
   * Helper function to create unmapped products.
   * @param image
   *   Image values array.
   * @param session_id
   *   Session node id.
   * @param identifier
   *   Name of the identifier.
   * @param fid
   *   File entity fid.
   */
  public function createUnmappedProduct($image = array(), $session_id, $identifier = 'UnMapped', $fid, $import_state='photodesk') {
    // The owner of session will be become owner of unmapped product.
    // Load session entity
    $session = $this->nodeStorage->load($session_id);
    // Get owner of session, ie., photographer.
    $session_uid = $session->getOwnerId();

    // build image property.
    $values = array(
      'nid' => NULL,
      'type' => 'unmapped_products',
      'title' => $identifier,
      'uid' => $session_uid,
      'status' => TRUE,
      'field_images' => $image,
      'field_product_import_state' => array('value' => $import_state)
    );
    // Create new node entity.
    $node = $this->nodeStorage->create($values);
    // Save unmapped node entity.
    $node->save();

    // Update last scanned product nid of current user state value.
    $this->state->set('last_scan_product_nid' . $session_uid . '_' . $session_id, $node->id());

    // Log transferred image.
    if ($fid) {
      //StudioImages::AddFileTransfer($fid, $node->id(), $session_id);
      $StudioImgs = \Drupal::service('studio.imgs');
      $StudioImgs->AddFileTransfer($fid, $node->id(), $session_id);
    }

    // Update image sequence number.
    if ($image) {
      //$file = File::load($image['target_id']);
      $file = $this->fileStorage->load($image['target_id']);
      $filemime = $file->filemime->getValue();
      if ($filemime) {
        $filemime = $filemime[0]['value'];
        $filemime = explode('/', $filemime);
        $filemime = $filemime[1];

        $file->filename->setValue($identifier . '_1' . $filemime);
        $file->save();
      }

    }

    // Update product to current session, ie,, session sent by chrome app.
    $this->addProductToSession($session_id, $node);

    return $node;
  }

  /*
   * Helper function to create Mapped products.
   *
   * @param product
   *   Product node object.
   * @param identifier
   *   Name of the identifier.
   */
  public function createMappedProduct($product, $identifier, $import_state = 'warehouse') {
    // Get current logged in user.
    $uid = $this->currentUser->id();

    if (is_object($product)) {

      $field_size_variant = array();
      $field_size_name = array();
      $field_barcode = array();

      if ($product->barcode) {
        foreach ($product->barcode as $barcode) {
          //$field_size_variant[] = ['value' => $barcode];
          $tmp1 = array('value' => $barcode);
          array_push($field_barcode, $tmp1);
        }
      }

      if ($product->size_name) {
        foreach ($product->size_name as $sn) {
          //$field_size_variant[] = ['value' => $barcode];
          $tmp2 = array('value' => $sn);
          array_push($field_size_name, $tmp2);
        }
      }

      if ($product->size_variant) {
        foreach ($product->size_variant as $sv) {
          //$field_size_variant[] = ['value' => $barcode];
          $tmp3 = array('value' => $sv);
          array_push($field_size_variant, $tmp3);
        }
      }

      $values = array(
        'nid' => NULL,
        'type' => 'products',
        'title' => $identifier,
        'uid' => $uid,
        'status' => TRUE,
        'field_base_product_id' => array('value' => $product->base_product_id),
        'field_department' => array('value' => $product->department),
        'field_style_family' => array('value' => $product->style_no),
        'field_concept_name' => array('value' => $product->concept),
        'field_gender' => array('value' => $product->gender),
        'field_description' => array('value' => $product->description),
        'field_color_variant' => array('value' => str_replace('/','-', $product->color_variant)), // todo: may be multiple
        'field_color_name' => array('value' => $product->color_name), //  todo: may be multiple
        'field_size_name' => $field_size_name, // todo: may be multiple
        'field_size_variant' => $field_size_variant, // todo: may be multiple
        'field_barcode' => $field_barcode, // todo: may be multiple
        'field_product_import_state' => array('value' => $import_state), // todo: may be multiple
      );
      // Create node object with above values.
      $node = $this->nodeStorage->create($values);
      // Finally save the node object.
      $node->save();
      // todo : add exceptions
      return $node;
    }
  }

  /*
   * Helper function to get images in a product.
   *
   * @param nid
   *   Node nid value.
   */
  public function getProductImages($nid, $containerOlny = false, $nid_is_object = false) {

    $image_uri = array();

    // Load the node.
    if(!$nid_is_object){
      $product = $this->nodeStorage->load($nid);
    }else{
      $product = $nid;
    }

    if ($product) {
      // Get available images from the product.
      $images = $product->field_images->getValue();
      if ($images) {
        foreach ($images as $img) {
          $fid = $img['target_id'];

          // Load the file object.
          //$file = File::load($fid);
          $file = $this->fileStorage->load($fid);
          // Validated file if it is deleted then error will occur.
          if ($file) {
            // Get the file name.
            $file_name = $file->filename->getValue();
            $file_name = $file_name[0]['value'];

            if($containerOlny){
              $field_container = $file->field_container->getValue();
              if($field_container){
                if($field_container[0]['target_id']){
                  // Get if image has been tagged
                  $image_tag = $file->field_tag->getValue();
                  $image_tag = $image_tag[0]['value'];
                  // Get the image of style - Live shoot preview.
                  $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
                  $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
                }
              }

            }else{

              // Get if image has been tagged
              $image_tag = $file->field_tag->getValue();
              $image_tag = $image_tag[0]['value'];

              $image_container_ref = $file->field_reference->getValue();
              if(isset($image_container_ref) && !empty($image_container_ref[0]['value'])){
                $image_container_ref = $image_container_ref[0]['value'];
                if(!$image_container_ref){
                  // Get the image of style - Live shoot preview.
                  $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
                  $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
                }

              }else{
                // Get the image of style - Live shoot preview.
                $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
                $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
              }
            }

          }

        }
        return $image_uri;
      }
    }
    return FALSE;
  }

  /*
   * Helper function, to get a product from external resource.
   *
   * @param input
   *   Probably sku_id or color_variant or something else.
   */
  public function getProductExternal($input) {

    // todo : multiple search, means if product not found with sku_id then look for color variant.
    // todo : for now external resource is public, but it might be changed to auth.

    $config = \Drupal::config('studiobridge_global_settings.studiosettings');
    $url = "http://beta.contentcentral.co/service/product-data?_format=json&product_identifier=$input";
    $url_without_input = "http://beta.contentcentral.co/service/product-data?_format=json&product_identifier=";
    $user_name = 'demouser';
    $pass = 'demouser';
    if($config){
      $url = $config->get('url');
      if($config->get('url')){
        $url = $config->get('url') .''. $input;
        $url_without_input = $config->get('url');
      }
      if($config->get('user_name')){
        $user_name = $config->get('user_name');
      }
      if($config->get('password')){
        $pass = $config->get('password');
      }
    }


//    try {
//      $response = \Drupal::httpClient()
//        ->get($url, [
//          'auth' => [$user_name, $pass],
//          //'body' => $serialized_entity,
//          'headers' => [
//            'Content-Type' => 'application/json'
//          ],
//        ]);
//      $result = (string) $response->getBody();
//    } catch (\Exception $e) {
//      $result = json_encode(array('msg' => $e->getMessage()));
//    }
    $result = $this->lookProductServer($url, $user_name, $pass);

    //$result = $this->checkBarecodesInDB($result, $input, $url_without_input, $user_name, $pass);


    return $result;
  }

  /*
   * Helper function, to add product to session.
   *
   * @param session_id
   *   Session node nid.
   * @param node
   *   Node object.
   */
  public function addProductToSession($session_id, $node) {
    // Load session node object.
    $session_node = $this->nodeStorage->load($session_id);
    // Get products from session node.
    $session_products = $session_node->field_product->getValue();
    // Get product nid.
    $product_nid = $node->id();

    // Check for this product already exist in the current session.
    // todo : other logs and property settings may come here
    $product_exist = FALSE;
    if (count($session_products)) {
      foreach ($session_products as $each) {
        if ($each['target_id'] == $product_nid) {
          $product_exist = TRUE;
          break;
        }
      }
    }
    if (!$product_exist) {
      // Prepare product array.
      $product = array(
        array(
          'target_id' => $product_nid
        )
      );

      // merge the current product to existing products.
      $products = array_merge($product, $session_products);

      // add the product to field.
      $session_node->field_product->setValue($products);
      // save the node.
      $session_node->save();
    }
  }

  /*
* Helper function, to get a product by its identifier.
*
* @param identifier
*   Name of the identifier.
*/
  public function getProductInformation($identifier) {

    $node_id = $this->getProductByIdentifier($identifier);

    if (count($node_id)) {
      $node_id = reset($node_id);
      // Load the node object.
      $product = $this->nodeStorage->load($node_id);
      $images = $product->field_images->getValue();

      $bundle = $product->bundle();
      $concept = '';
      $style_no = '';
      $color_variant = '';
      $gender = '';
      $color = '';
      $description = '';


      if ($bundle == 'unmapped_products') {
        $concept = 'Unmapped';

        $output_array = array(
          "concept" => $concept,
          "styleno" => '',
          "colorvariant" => '',
          "gender" => '',
          "color" => '',
          "description" => '',
          "image_count" => count($images)
        );

      }
      else {

        $product_concept = $product->field_concept_name->getValue();
        if ($product_concept) {
          $concept = $product_concept[0]['value'];
        }

        $product_style_no = $product->field_style_family->getValue();
        if ($product_style_no) {
          $style_no = $product_style_no[0]['value'];
        }
        $product_color_variant = $product->field_color_variant->getValue();
        if ($product_color_variant) {
          $color_variant = $product_color_variant[0]['value'];
        }
        $product_gender = $product->field_gender->getValue();
        if ($product_gender) {
          $gender = $product_gender[0]['value'];
        }
        $product_color = $product->field_color_name->getValue();
        if ($product_color) {
          $color = $product_color[0]['value'];
        }

        $product_description = $product->field_description->getValue();
        if ($product_description) {
          $description = $product_description[0]['value'];
        }

        $output_array = array(
          "concept" => $concept,
          "styleno" => $style_no,
          "colorvariant" => $color_variant,
          "gender" => $gender,
          "color" => $color,
          "description" => $description,
          "image_count" => count($images)
        );

      }
      $output = $output_array;
      return $output;
    }
    else {
      return FALSE;
    }

  }

  /*
  * Helper function, to insert log into {studio_file_transfers} table.
  *
  * @param fid
  *   File object fid.
  * @param pid
  *   Product node nid.
  * @param sid
  *   Session node nid.
  */
  public function DeleteProductLog($product) {

    $key = 'close_operation_delete_' . $product->id();
    $check_sid = $this->state->get($key, FALSE);
    if (!$check_sid) {
      $StudioSessions = \Drupal::service('studio.sessions');
      $session_id = $StudioSessions->openSessionRecent();
    }
    else {
      $session_id = $check_sid;
    }

    if(!$session_id){
      return;
    }

    $session = array(array('target_id' => $session_id));
    $color_variant = NULL;
    $concept = NULL;
    $title = NULL;
    $style_family = NULL;
    $uid = $this->currentUser->id();

    $bundle = $product->bundle();

    if ($bundle == 'products') {

      // Get color variant.
      $product_color_variant = $product->field_color_variant->getValue();
      if ($product_color_variant) {
        $color_variant = $product_color_variant[0]['value'];
      }
      $title = $product->title->getValue();
      if (!$color_variant) {
        if ($title) {
          $color_variant = $title[0]['value'];
        }
      }
      if ($title) {
        $title = $title[0]['value'];
      }

      // Get concept name.
      $product_concept = $product->field_concept_name->getValue();
      if ($product_concept) {
        $concept = $product_concept[0]['value'];
      }

      // Get concept name.
      $style_family = $product->field_style_family->getValue();
      if ($style_family) {
        $style_family = $style_family[0]['value'];
      }

    }
    elseif ($bundle == 'unmapped_products') {

      // Set concept as unmapped.
      $concept = 'Unmapped';

      $field_identifier = $product->field_identifier->getValue();
      $title = $product->title->getValue();
      if ($field_identifier) {
        $color_variant = $field_identifier[0]['value'];
      }
      elseif ($title) {
        $color_variant = $title[0]['value'];
      }

      if ($title) {
        $title = $title[0]['value'];
      }

    }

    $values = array(
      'nid' => NULL,
      'type' => 'dropped_products',
      'title' => $title,
      'uid' => $uid,
      'status' => TRUE,
      'field_style_family' => array('value' => $style_family),
      'field_concept_name' => array('value' => $concept),
      'field_color_variant' => array('value' => $color_variant), // todo: may be multiple
      'field_session' => $session,
    );
    // Create node object with above values.
    $node = \Drupal::entityManager()->getStorage('node')->create($values);
    // Finally save the node object.
    $node->save();

  }

  /*
  * Helper function, to insert log into {studio_product_shoot_period} table.
  *
  * @param sid
  *   Session node nid.
  * @param pid
  *   Product node nid.
  */
  public function AddStartTimeToProduct($sid, $pid) {
    // On same request avoid saving multiple records.
    $result = $this->database->select('studio_product_shoot_period', 'spsp')
      ->fields('spsp', array('id'))
      ->condition('spsp.pid', $pid)
      ->condition('spsp.sid', $sid)
      ->condition('spsp.start', REQUEST_TIME);
    $already_set = $result->execute()->fetchAll();
    if (count($already_set) == 0) {
      $this->database->insert('studio_product_shoot_period')
        ->fields(array(
          'sid' => $sid,
          'pid' => $pid,
          'start' => REQUEST_TIME,
        ))
        ->execute();
    }
  }

  /*
* Helper function, to insert log into {studio_product_shoot_period} table.
*
* @param sid
*   Session node nid.
* @param pid
*   Product node nid.
*/
  public function AddEndTimeToProduct($sid, $pid, $identifier = NULL) {

    if (!$pid && $identifier) {
      $pid = $this->getProductByIdentifier($identifier);
      $pid = reset($pid);
    }

    if (!$pid) {
      return FALSE;
    }


    $result = $this->database->select('studio_product_shoot_period', 'spsp')
      ->fields('spsp', array('id'))
      ->condition('spsp.pid', $pid)
      ->condition('spsp.sid', $sid)
      ->orderBy('spsp.id', 'desc')
      ->range(0, 1);
    $last_log_id = $result->execute()->fetchField();


    // todo conditions to check multiple periods

    if ($last_log_id) {
      $this->database->update('studio_product_shoot_period') // Table name no longer needs {}
        ->fields(array(
          'end' => REQUEST_TIME,
        ))
        ->condition('sid', $sid)
        ->condition('pid', $pid)
        ->condition('id', $last_log_id)
        ->execute();
    }
    else {
      $this->database->update('studio_product_shoot_period') // Table name no longer needs {}
        ->fields(array(
          'end' => REQUEST_TIME,
        ))
        ->condition('sid', $sid)
        ->condition('pid', $pid)
        ->execute();
    }

  }

  /*
   * @param pid
   *   Product node nid.
   */
  public function CalculateProductPeriod($pid, $sid) {
    $secs = 0;
    $result = $this->database->select('studio_product_shoot_period', 'spsp')
      ->fields('spsp', array('start', 'end'))
      ->condition('spsp.pid', $pid)
      ->condition('spsp.sid', $sid)
      ->range(0, 1000);
    $product_period = $result->execute()->fetchAll();
    if ($product_period) {
      foreach ($product_period as $period) {
        // check still product closed or not. ie., end time 0 or timestamp
        if ($period->end) {
          $diff = $period->end - $period->start;
          $secs += $diff;
        }
      }
    }
    return $secs;
  }

  public function createUnmappedProductFromContainer($identifier = 'UnMapped', $import_state = 'warehouse') {
    $image = array();
    // The owner of session will be become owner of unmapped product.
    // Load session entity
    $uid = $this->currentUser->id();
    // build image property.
    $values = array(
      'nid' => NULL,
      'type' => 'unmapped_products',
      'title' => $identifier,
      'uid' => $uid,
      'status' => TRUE,
      'field_images' => $image,
      'field_product_import_state' => array('value' => $import_state)
    );
    // Create new node entity.
    //$node = \Drupal::entityManager()->getStorage('node')->create($values);
    $node = $this->nodeStorage->create($values);
    // Save unmapped node entity.
    $node->save();

    return $node;
  }


  /*
* Helper function, to get a product by product object.
*
* @param identifier
*   Name of the identifier.
*/
  public function getProductInfoByObject($product) {


    if ($product) {
      $images = $product->field_images->getValue();
      $bundle = $product->bundle();
      $concept = '';
      $style_no = '';
      $color_variant = '';
      $gender = '';
      $color = '';
      $description = '';
      $identifier = '';

      $title = $product->title->getValue();
      if ($title) {
        $identifier = $title[0]['value'];
      }

      if ($bundle == 'unmapped_products') {
        $concept = 'Unmapped';

        $output_array = array(
          "concept" => $concept,
          "styleno" => '',
          "colorvariant" => '',
          "gender" => '',
          "color" => '',
          "description" => '',
          "image_count" => count($images),
          "identifier" => $identifier,
          "pid" => $product->id()
        );

      }
      else {

        $product_concept = $product->field_concept_name->getValue();
        if ($product_concept) {
          $concept = $product_concept[0]['value'];
        }

        $product_style_no = $product->field_style_family->getValue();
        if ($product_style_no) {
          $style_no = $product_style_no[0]['value'];
        }
        $product_color_variant = $product->field_color_variant->getValue();
        if ($product_color_variant) {
          $color_variant = $product_color_variant[0]['value'];
        }
        $product_gender = $product->field_gender->getValue();
        if ($product_gender) {
          $gender = $product_gender[0]['value'];
        }
        $product_color = $product->field_color_name->getValue();
        if ($product_color) {
          $color = $product_color[0]['value'];
        }

        $product_description = $product->field_description->getValue();
        if ($product_description) {
          $description = $product_description[0]['value'];
        }

        $output_array = array(
          "concept" => $concept,
          "styleno" => $style_no,
          "colorvariant" => $color_variant,
          "gender" => $gender,
          "color" => $color,
          "description" => $description,
          "image_count" => count($images),
          "identifier" => $identifier,
          "pid" => $product->id()
        );

      }
      $output = $output_array;
      return $output;
    }
    else {
      return FALSE;
    }

  }

  /*
   * Helper function, to check product duplicates in session/container.
   */
  public function checkProductDuplicate($product_nid, $bundles = array()) {
    //$product_nid = $product->id();
    $query = $this->queryFactory->get('node');
    if ($bundles) {
      $query->condition('type', $bundles, 'IN');
    }

    $query->condition('field_product', $product_nid);
    $result = $query->execute();

    return $result;
  }

  /*
   * Helper function. to delete a image from product.
   */
  public function deleteImageFromProduct($product, $fid){

    if(is_object($product)){

    }else{
      return array('status' => 'Invalid product found');
    }

  }

  /*
   *
   */
  public function insertProductImportRecord($identifier, $pid, $sid, $cid){
    $this->database->insert('studio_products_import_track')
      ->fields(array(
        'identifier' => $identifier,
        'pid' => $pid,
        'sid' => $sid,
        'cid' => $cid,
        'time' => REQUEST_TIME,
      ))
      ->execute();
  }

  public function getLastScannedProduct($sid, $cid){
    $result = $this->database->select('studio_products_import_track', 'spit')
      ->fields('spit', array('identifier','pid','time'))
      ->condition('spit.sid', $sid)
      ->condition('spit.cid', $cid)
      ->orderBy('spit.id', 'desc')
      ->range(0, 1);
    $return = $result->execute()->fetchAssoc();
    return $return;
  }

  /*
   * Helper function to handle product lookup call.
   */
  public function lookProductServer($url, $user_name, $pass){
    try {
      $response = \Drupal::httpClient()
        ->get($url, [
          'auth' => [$user_name, $pass],
          //'body' => $serialized_entity,
          'headers' => [
            'Content-Type' => 'application/json'
          ],
        ]);
      $result = (string) $response->getBody();
    } catch (\Exception $e) {
      $result = json_encode(array('msg' => $e->getMessage()));
    }

    return $result;
  }

  /*
   * Helper function to check for missing products on product server.
   */
  public function checkBarecodesInDB($result, $identifier, $url_without_input, $user_name, $pass){

    $product = json_decode($result);
    if(count((array)$product) <= 1){

    //  $table_exists = $this->database->schema()->tableExists('studio_barcodes');

     // if($table_exists){
        $rows_query = $this->database->select('studio_barcodes', 'sb')
          ->fields('sb', array('Barcode','Color'));

        $orCondition = $rows_query->orConditionGroup();
        $orCondition->condition('sb.Barcode', $identifier);
        $orCondition->condition('sb.Color', $identifier);

        $rows_query->condition($orCondition);


        $rows = $rows_query->execute()->fetchAssoc();

        if(!empty($rows['Color'])){

          $key = array_search($identifier, $rows);

          if($key == 'Color'){
            $url = $url_without_input.''.$rows['Barcode'];
          }else{
            $url = $url_without_input.''.$rows['Color'];
          }

          $result = $this->lookProductServer($url, $user_name, $pass);
        }

      //}

    }

    return $result;
  }

  /*
   *
   */
  public function getProductsData($pids, $dataset= array('images','id', 'title', 'field_color_variant', 'field_concept_name','image_count', 'view_link'), $group_by='sessions'){

    if($pids){
      $products = $this->nodeStorage->loadMultiple($pids);

      $total_images = 0;
      $data = array();


      foreach ($products as $current_product) {
        // Get product type; mapped or unmapped
        $bundle = $current_product->bundle();
        $data_row = array();

        $session_ids = $this->checkProductDuplicate($current_product->id(), array('sessions'));


        // Map unmapped & mapped products
        if ($bundle == 'products') {
          $cp = $current_product->toArray();
          $pid = $current_product->id();

          if(in_array('id',$dataset)){
            $data_row['id'] = $pid;
          }

          $total_images = count($cp['field_images']);
          if(in_array('image_count',$dataset)){
            $data_row['image_count'] = $total_images;
            $data_row['totalimages'] = $total_images;
          }

          // Get Concept
          //$concept = $current_product->field_concept_name->getValue();

          if(in_array('field_concept_name',$dataset)){
            $product_concept = $current_product->field_concept_name->getValue();
            if ($product_concept) {
              $concept = $product_concept[0]['value'];
              $theme_path = base_path().''.drupal_get_path('theme', 'studiobridge');
              //$theme_path = base_path().
              $img_file_name = str_replace(' ','', $concept);
              $concept_img = $theme_path.'/images/brands/brand_logo_'.strtolower($img_file_name).'.png';
              $concept_img = '<img height="20px" src="'.$concept_img.'">';
              $data_row['concept'] = $concept;
              $data_row['concept_img_url'] = $concept_img;
            }
          }

          if(in_array('field_color_variant', $dataset)){
            $product_color_variant = $current_product->field_color_variant->getValue();
            if ($product_color_variant) {
              $color_variant = $product_color_variant[0]['value'];
              $data_row['field_color_variant'] = $color_variant;
              // temporary todo : update in js files & remove next line.
              $data_row['colorvariant'] = $color_variant;
            }

          }

          if(in_array('field_state', $dataset)){
            $product_state = $current_product->field_state->getValue();

            if ($product_state) {
              $state = $product_state[0]['value'];
              $data_row['field_state'] = $state;

            }
          }


          if(in_array('title',$dataset)){
            $product_title = $current_product->title->getValue();
            $title = '';
            if ($product_title) {
              $title = $product_title[0]['value'];
              $data_row['title'] = $title;
            }
          }

          if(in_array('view_link',$dataset)){
            $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';
            $data_row['view'] = $view_link;
          }

          if(in_array('field_style_family', $dataset)){
            $product_field_style_family = $current_product->field_style_family->getValue();
            if ($product_field_style_family) {
              $data_row['field_style_family'] = $product_field_style_family[0]['value'];
            }
          }

          //
          if(in_array('field_base_product_id', $dataset)){
            $field_base_product_id = $current_product->field_base_product_id->getValue();
            if ($field_base_product_id) {
              $data_row['field_base_product_id'] = $field_base_product_id[0]['value'];
            }
          }

          //
          if(in_array('field_gender', $dataset)){
            $field_gender = $current_product->field_gender->getValue();
            if ($field_gender) {
              $data_row['field_gender'] = $field_gender[0]['value'];
            }
          }
          //field_color_name
          if(in_array('field_color_name', $dataset)){
            $field_color_name = $current_product->field_color_name->getValue();
            if ($field_color_name) {
              $data_row['field_color_name'] = $field_color_name[0]['value'];
            }
          }

          //field_department
          if(in_array('field_department', $dataset)){
            $field_department = $current_product->field_department->getValue();
            if ($field_department) {
              $data_row['field_department'] = $field_department[0]['value'];
            }
          }

          //field_product_import_state
          if(in_array('field_product_import_state', $dataset)){
            $field_product_import_state = $current_product->field_product_import_state->getValue();
            if ($field_product_import_state) {
              $data_row['field_product_import_state'] = $field_product_import_state[0]['value'];
            }
          }


          if(in_array('images', $dataset)){
            $data_row['images'] = $this->getProductImages($current_product, false, true);
          }


          if(in_array('qc', $dataset)){
            $qc_records = $this->StudioQc->getQcRecordsByProduct($pid);
            $qc_array = array();

            if($qc_records){
              foreach($qc_records as $record){
                $qc_array[] = (array) $record;
              }

            }
            $data_row['qc'] = $qc_array;
          }

        }
        elseif ($bundle == 'unmapped_products') {
          $cpu = $current_product->toArray();
          $total_images = count($cpu['field_images']);
          $pid = $current_product->id();

          $total_images = count($cpu['field_images']);
          if(in_array('image_count',$dataset)){
            $data_row['image_count'] = $total_images;
            $data_row['totalimages'] = $total_images;
          }


          if(in_array('id',$dataset)){
            $data_row['id'] = $pid;
          }

          $concept = 'Unmapped';

          if(in_array('field_state', $dataset)){
            $product_state = $current_product->field_state->getValue();

            if ($product_state) {
              $state = $product_state[0]['value'];
              $data_row['field_state'] = $state;

            }
          }

          if(in_array('title',$dataset)){
            $product_title = $current_product->title->getValue();
            $title = '';
            if ($product_title) {
              $title = $product_title[0]['value'];
              $data_row['title'] = $title;
            }
          }

          if(in_array('view_link',$dataset)){
            $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';
            $data_row['view'] = $view_link;
          }

          if(in_array('images', $dataset)){
            $data_row['images'] = $this->getProductImages($current_product, false, true);
          }

          if(in_array('qc', $dataset)){
            $qc_records = $this->StudioQc->getQcRecordsByProduct($pid);
            $qc_array = array();

            if($qc_records){
              foreach($qc_records as $record){
                $qc_array[] = (array) $record;
              }

            }
            $data_row['qc'] = $qc_array;
          }


        }


        $session_details = $this->getSessionDetailsOfProduct($session_ids);
        if(in_array('sessions', $dataset)){
          $data_row['sessions'] = $session_details;
        }

        switch ($group_by) {
          case  'sessions':

            //$session_ids = $this->checkProductDuplicate($pid, array('sessions'));

            //$data[] = $session_ids;
            foreach($session_ids as $sid){
              $data[$sid][] = $data_row;
            }



            break;
          case  'photographer':

            foreach($session_details as $each){
              $photographer = $each['photographer'];
              $data[$photographer][] = $data_row;
            }

            break;

          case  'date':

            $created = $current_product->created->getValue();
            $created = $created[0]['value'];
            $created = date('Y-m-d', $created);

            $data[$created][] = $data_row;

            break;
          case  'qc_state':

            $qc_state = $this->StudioQc->getQcRecordsByProduct($current_product->id(), $fields = array('qc_state'));
            if($qc_state){
              if($recent_qc_record = reset($qc_state)){
                $qc_state = $recent_qc_record->qc_state;
                $data[$qc_state][] = $data_row;
              }else{
                $data['No QC record'][] = $data_row;
              }
            }else{
              $data['No QC record'][] = $data_row;
            }

            break;
          case  'state':

            if(count($session_ids) <= 1){
              $data['new'][] = $data_row;
            }else{
              $data['reshoot'][] = $data_row;
            }

            break;
          default:
            //
            foreach($session_ids as $sid){
              $data[$sid][] = $data_row;
            }

        }


      }


      return $data;


    }


  }

  /*
   *
   */
  public function getSessionDetailsOfProduct($session_ids){

    $sessions_data = array();

    $sessions = $this->nodeStorage->loadMultiple($session_ids);

    foreach($sessions as $session){
      $photographer = '';
      $vm = '';
      $stylish = '';
      $sid = $session->id();
      if(!empty($session->field_photographer->get(0)->target_id)){
        $photographer = $this->userStorage->load($session->field_photographer->get(0)->target_id)->label();
      }
      if(!empty($session->field_vm->get(0)->target_id)){
        $vm = $this->userStorage->load($session->field_vm->get(0)->target_id)->label();
      }
      if(!empty($session->field_stylish->get(0)->target_id)){
        $stylish = $this->userStorage->load($session->field_stylish->get(0)->target_id)->label();
      }

      $sessions_data[$sid] = array(
        'photographer' => $photographer,
        'vm' => $vm,
        'stylish' => $stylish
      );
    }

    return $sessions_data;

  }

  public function getPhotographerDetailsOfProduct($nid){

  }

  public function getImportedDateOfProduct($nid){

  }

  public function getQcDetailsOfProduct($nid){

  }


  /*
   * Helper function to get images in a product.
   *
   * @param nid
   *   Node nid value.
   */
  public function getProductImagesShotInParticularSession($nid, $sid, $style = 'live_shoot_preview', $containerOlny = false, $nid_is_object = false) {

    $image_uri = array();

    // Load the node.
    if(!$nid_is_object){
      $product = $this->nodeStorage->load($nid);
    }else{
      $product = $nid;
    }

    $pid = $product->id();

    if ($product) {
      // Get available images from the product.
      //$images = $product->field_images->getValue();

      $StudioImgs = \Drupal::service('studio.imgs');
      $images = $StudioImgs->getProductImagesOfSessionFromFileLogs($sid, $pid);

      if ($images) {
        foreach ($images as $img) {
          $fid = $img->fid;

          // Load the file object.
          $file = File::load($fid);
          // Validated file if it is deleted then error will occur.
          if ($file) {
            // Get the file name.
            $file_name = $file->filename->getValue();
            $file_name = $file_name[0]['value'];

            if($containerOlny){
              $field_container = $file->field_container->getValue();
              if($field_container){
                if($field_container[0]['target_id']){
                  // Get if image has been tagged
                  $image_tag = $file->field_tag->getValue();
                  $image_tag = $image_tag[0]['value'];
                  // Get the image of style - Live shoot preview.
                  $image_uri_value = ImageStyle::load($style)->buildUrl($file->getFileUri());
                  $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
                }
              }

            }else{

              // Get if image has been tagged
              $image_tag = $file->field_tag->getValue();
              $image_tag = $image_tag[0]['value'];

              $image_container_ref = $file->field_reference->getValue();
              if(isset($image_container_ref) && !empty($image_container_ref[0]['value'])){
                $image_container_ref = $image_container_ref[0]['value'];
                if(!$image_container_ref){
                  // Get the image of style - Live shoot preview.
                  $image_uri_value = ImageStyle::load($style)->buildUrl($file->getFileUri());
                  $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
                }

              }else{
                // Get the image of style - Live shoot preview.
                $image_uri_value = ImageStyle::load($style)->buildUrl($file->getFileUri());
                $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);
              }
            }

          }

        }
        return $image_uri;
      }
    }
    return FALSE;
  }



}
