<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioContainer.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use \Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\State\StateInterface;

/**
 * Class StudioContainer.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioContainer implements StudioContainerInterface {

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

  }

  /*
   * Helper function, to get container node id by its title/container Id.
   */
  public function getContainerNodeIdByContainerId($container_id){
    $result = $this->queryFactory->get('node')
      ->condition('type', array('container'))
      ->sort('created', 'DESC')
      ->condition('title', $container_id)
      ->range(0, 1)
      ->execute();

    return reset($result);
  }

  /*
* Helper function, to add reshot product to session.
*
* @param session_id
*   Session node nid.
* @param node
*   Product Node object.
*/
  public function addProductToContainer($container_id, $node) {
    // Load session node object.
    $container_node = $this->nodeStorage->load($container_id);
    // Get products from session node.
    $container_products = $container_node->field_product->getValue();
    // Get product nid.
    $product_nid = $node->id();

    // Check for this product already exist in the current session.
    // todo : other logs and property settings may come here
    $product_exist = FALSE;
    if (count($container_products)) {
      foreach ($container_products as $each) {
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
      $products = array_merge($product, $container_products);

      // add the product to field.
      $container_node->field_product->setValue($products);
      // save the node.
      $container_node->save();
    }
  }

  /*
* Helper function, to add reshot product to session.
*
* @param session_id
*   Session node nid.
* @param node
*   Product Node object.
*/
  public function addDropProductToContainer($container_id, $node) {
    // Load session node object.
    $container_node = $this->nodeStorage->load($container_id);
    // Get products & dropped products from session node.
    $container_dropped_products = $container_node->field_dropped_products->getValue();
    $container_products = $container_node->field_product->getValue();
    $container_products_duplicate = $container_products;

    // Get product nid.
    $product_nid = $node->id();

    // Check for this product already exist in the current session.
    // todo : other logs and property settings may come here
    $product_exist = FALSE;
    if (count($container_dropped_products)) {
      foreach ($container_dropped_products as $each) {
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

      // Remove dropped product from products field
      if($container_products){
        foreach($container_products as $k=>$pid){
          if($pid['target_id'] == $product_nid){
            unset($container_products_duplicate[$k]);
          }
        }
      }


      // merge the current product to existing products.
      $products = array_merge($product, $container_dropped_products);

      // add the product to field.
      $container_node->field_dropped_products->setValue($products);

      // remove dropped product from product.
      $container_node->field_product->setValue($container_products_duplicate);
      // save the node.
      $container_node->save();
      return array('message' => 'Product dropped successfully.','status' => true);
    }else{
      return array('message' => 'Product already dropped.','status' => false);
    }
  }

  /*
   * Helper method to update container status.
   *
   * @param container
   *  container node object.
   * @param status
   *  Container state value.
   *
   */
  public function updateContainerStatus($container, $status = 'checkout'){
    if($container){
      $state = array(
        'value' => $status
      );
      // Set the field state values.
      $container->field_container_state->setValue($state);
      // Save the node.
      $container->save();
    }
  }

}
