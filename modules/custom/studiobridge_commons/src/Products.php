<?php

namespace Drupal\studiobridge_commons;

use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\studiobridge_commons\Sessions;
use Drupal\image\Entity\ImageStyle;

Class Products{

  /*
   * Helper function, to get a product by its identifier.
   */
  public static function getProductByIdentifier($identifier){
    $result = \Drupal::entityQuery('node')
      ->condition('type', array('products','unmapped_products'),'IN')
      ->sort('created', 'DESC')
      ->condition('title', $identifier) // todo : title will be changed as per response
      ->range(0, 1)
      ->execute();

    return $result;
  }

  /*
   * Helper function, to get a product by its author.
   */
  public static function getProductByUid($uid){
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
   * @param state
   *
   * todo : remove - Replacement for studiobridge_store_images_update_product_as_open().
   */
  public static function updateProductState($identifier,$state) {

    $node_id = self::getProductByIdentifier($identifier);
    if (count($node_id)) {
      $node_id = reset($node_id);
      $product_node = Node::load($node_id);
      $state = array(
        'value' => $state
      );

      $product_node->field_state->setValue($state);
      $product_node->save();

    }
  }

  /*
   * Helper function to create unmapped products.
   *
   * todo : Remove- studiobridge_store_images_create_unmapped_product().
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

    \Drupal::state()->set('last_scan_product_nid' . $session_uid . '_' . $session_id, $node->id());
    //\Drupal::state()->set('last_scan_product_nid'.$uid.'_'.$session_id,$new_or_old_product_nid);

    // Log transferred image.
    if ($fid) {
      StudioImages::AddFileTransfer($fid,$node->id(),$session_id);
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
    //studiobridge_store_images_add_product_to_session($session_id, $node);
    self::addProductToSession($session_id, $node);
  }

  /*
  * Helper function to create unmapped products.
  */
  public static function createMappedProduct($product, $identifier) {
    $user = \Drupal::currentUser();
    $uid = $user->id();
    if (is_object($product)) {
      $values = array(
        'nid' => NULL,
        'type' => 'products',
        'title' => $identifier,
        'uid' => $uid,
        'status' => TRUE,
        'field_base_product_id' => array('value'=>$product->base_product_id),
        'field_style_family' => array('value'=>$product->style_no),
        'field_concept_name' => array('value'=> $product->concept),
        'field_gender' => array('value'=> $product->gender),
        'field_description' => array('value'=> $product->description),
        'field_color_variant' => array('value'=> $product->color_variant), // todo: may be multiple
        'field_color_name' => array('value'=> $product->color_name),  //  todo: may be multiple
        'field_size_name' => array('value'=> $product->size_name),  // todo: may be multiple
        'field_size_variant' => array('value'=> $product->size_variant),  // todo: may be multiple
      );
      $node = \Drupal::entityManager()->getStorage('node')->create($values);
      $node->save();
      // todo : add exceptions
      return $node;
    }
  }

  /*
   * Helper function to get images in a product.
   */
  public static function getProductImages($nid){

    $sid = Sessions::openSessionRecent();
    $image_uri = array();

    $product = \Drupal\node\Entity\Node::load($nid);
    if($product){
      $images = $product->field_images->getValue();
      if($images){
        foreach($images as $img){
          $fid = $img['target_id'];
          $file = File::load($fid);
          $file_name = $file->filename->getValue();
          $file_name = $file_name[0]['value'];
          $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
          $image_uri[$fid] = array('uri'=>$image_uri_value,'name'=>$file_name);
        }
        return $image_uri;
      }
    }
    return false;
  }

  /*
   * @todo : product should lookup
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
   * Helper function to add product to session.
   *
   * todo : remove - replace studiobridge_store_images_add_product_to_session().
   */
  //public static function studiobridge_store_images_add_product_to_session($session_id, $node) {
  public static function addProductToSession($session_id, $node) {
    // Load session node object
    $session_node = Node::load($session_id);
    // Get products
    $session_products = $session_node->field_product->getValue();
    // Get product id
    $product_nid = $node->id();

    // Check for this product already exist in the current session
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


}