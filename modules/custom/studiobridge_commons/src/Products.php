<?php

namespace Drupal\studiobridge_commons;

use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\studiobridge_commons\Sessions;
use Drupal\image\Entity\ImageStyle;

Class Products {

  /*
   * Helper function, to get a product by its identifier.
   *
   * @param identifier
   *   Name of the identifier.
   */
  public static function getProductByIdentifier($identifier) {

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('products', 'unmapped_products'), 'IN')
      ->sort('created', 'DESC')
      ->condition('title', $identifier)
      ->range(0, 1)
      ->execute();

    return $result;
  }

  /*
   * Helper function, to get a product by its author.
   *
   * @param uid
   *   User entity uid.
   */
  public static function getProductByUid($uid) {
    return \Drupal::entityQuery('node')
      ->condition('type', array('products', 'unmapped_products'), 'IN')
      ->sort('created', 'DESC')
      ->condition('field_state', 'open')
      ->condition('uid', $uid)
      ->range(0, 1)
      ->execute();

  }

  /*
   * Helper function, update product state value.
   *
   * @param identifier
   *   Name of the identifier.
   * @param state
   *   Values like open, close..
   */
  public static function updateProductState($identifier, $state) {
    // Get the node by its identifier.
    $node_id = self::getProductByIdentifier($identifier);
    if (count($node_id)) {
      $node_id = reset($node_id);
      // Load the node object.
      $product_node = Node::load($node_id);
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
  public static function createUnmappedProduct($image = array(), $session_id, $identifier = 'UnMapped', $fid) {
    // The owner of session will be become owner of unmapped product.
    // Load session entity
    $session = Node::load($session_id);
    // Get owner of session, ie., photographer.
    $session_uid = $session->getOwnerId();

    // build image property.
    $values = array(
      'nid' => NULL,
      'type' => 'unmapped_products',
      'title' => $identifier,
      'uid' => $session_uid,
      'status' => TRUE,
      'field_images' => $image
    );
    // Create new node entity.
    $node = \Drupal::entityManager()->getStorage('node')->create($values);
    // Save unmapped node entity.
    $node->save();

    // Update last scanned product nid of current user state value.
    \Drupal::state()->set('last_scan_product_nid' . $session_uid . '_' . $session_id, $node->id());

    // Log transferred image.
    if ($fid) {
      StudioImages::AddFileTransfer($fid, $node->id(), $session_id);
    }

    // Update image sequence number.
    if ($image) {
      $file = File::load($image['target_id']);
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
    self::addProductToSession($session_id, $node);

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
  public static function createMappedProduct($product, $identifier) {
    // Get current logged in user.
    $user = \Drupal::currentUser();
    // Get uid of logged in user.
    $uid = $user->id();
    if (is_object($product)) {
      $values = array(
        'nid' => NULL,
        'type' => 'products',
        'title' => $identifier,
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
      // Create node object with above values.
      $node = \Drupal::entityManager()->getStorage('node')->create($values);
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
  public static function getProductImages($nid) {

    $image_uri = array();

    // Load the node.
    $product = Node::load($nid);

    if ($product) {
      // Get available images from the product.
      $images = $product->field_images->getValue();
      if ($images) {
        foreach ($images as $img) {
          $fid = $img['target_id'];

          // Load the file object.
          $file = File::load($fid);
          // Validated file if it is deleted then error will occur.
          if($file){
            // Get the file name.
            $file_name = $file->filename->getValue();
            $file_name = $file_name[0]['value'];
            // Get if image has been tagged
            $image_tag = $file->field_tag->getValue();
            $image_tag = $image_tag[0]['value'];
            // Get the image of style - Live shoot preview.
            $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
            $image_uri[$fid] = array('uri' => $image_uri_value, 'name' => $file_name, 'tag' => $image_tag);

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
  public static function getProductExternal($input) {

    // todo : multiple search, means if product not found with sku_id then look for color variant.
    // todo : for now external resource is public, but it might be changed to auth.
    $response = \Drupal::httpClient()
      ->get("http://staging.dreamcms.me/service/product-data?sku_id=$input"
      //['auth' => ['username', 'password'],]
      );

    return (string) $response->getBody();
  }

  /*
   * Helper function, to add product to session.
   *
   * @param session_id
   *   Session node nid.
   * @param node
   *   Node object.
   */
  public static function addProductToSession($session_id, $node) {
    // Load session node object.
    $session_node = Node::load($session_id);
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
  public static function getProductInformation($identifier) {

    $node_id = self::getProductByIdentifier($identifier);

    if (count($node_id)) {
      $node_id = reset($node_id);
      // Load the node object.
      $product = Node::load($node_id);
      $images = $product->field_images->getValue();


    $bundle = $product->bundle();
    $concept = '';
      $style_no = '';
      $color_variant = '';
      $gender = '';
      $color = '';
      $description = '';


    if($bundle == 'unmapped_products'){
      $concept = 'Unmapped';

      $output_array = array("concept" => $concept,
          "styleno" => '',
          "colorvariant" => '',
          "gender" => '',
          "color" => '',
          "description" => '',
          "image_count" => count($images)
      );

    }else{

      $product_concept = $product->field_concept_name->getValue();
      if($product_concept){
        $concept = $product_concept[0]['value'];
      }

      $product_style_no = $product->field_style_family->getValue();
      if($product_style_no){
        $style_no = $product_style_no[0]['value'];
      }
      $product_color_variant = $product->field_color_variant->getValue();
      if($product_color_variant){
        $color_variant = $product_color_variant[0]['value'];
      }
      $product_gender = $product->field_gender->getValue();
      if($product_gender){
        $gender = $product_gender[0]['value'];
      }
      $product_color = $product->field_color_name->getValue();
      if($product_color){
        $color = $product_color[0]['value'];
      }

      $product_description = $product->field_description->getValue();
      if($product_description){
        $description = $product_description[0]['value'];
      }

      $output_array = array("concept" => $concept,
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
  }else{
      return false;
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
  public static function DeleteProductLog($identifier, $sid, $uid) {
    db_insert('studio_dropped_products')
      ->fields(array(
        'product' => $identifier,
        'sid' => $sid,
        'uid' => $uid,
        'dropped' => REQUEST_TIME,
      ))
      ->execute();
    \Drupal::logger('Studio')->notice('Product deleted - '. $identifier);

  }

  /*
  * Helper function, to insert log into {studio_product_shoot_period} table.
  *
  * @param sid
  *   Session node nid.
  * @param pid
  *   Product node nid.
  */
  public static function AddStartTimeToProduct($sid, $pid) {
    db_insert('studio_product_shoot_period')
      ->fields(array(
        'sid' => $sid,
        'pid' => $pid,
        'start' => REQUEST_TIME,
      ))
      ->execute();
  }

  /*
* Helper function, to insert log into {studio_product_shoot_period} table.
*
* @param sid
*   Session node nid.
* @param pid
*   Product node nid.
*/
  public static function AddEndTimeToProduct($sid, $pid, $identifier=null) {

    if(!$pid){
      $pid = Products::getProductByIdentifier($identifier);
      $pid = reset($pid);
    }


    // todo conditions to check multiple periods

    db_update('studio_product_shoot_period') // Table name no longer needs {}
      ->fields(array(
        'end' => REQUEST_TIME,
      ))
      ->condition('sid',$sid)
      ->condition('pid', $pid)
      ->execute();

  }

  /*
   * @param pid
   *   Product node nid.
   */
  public static function CalculateProductPeriod($pid){
    return 10;
  }

}