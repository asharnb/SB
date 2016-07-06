<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewSessionController.
 */

namespace Drupal\warehouse_checkin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class ViewSessionController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class warehousecheckinController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $nodeStorage;

  protected $userStorage;

  protected $studioContainer;

  protected $studioProducts;


  /*
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    //$entity_manager = $container->get('entity.manager');
    return new static(
      $container->get('database'),
      $container->get('studio.container'),
      $container->get('studio.products')
      //$entity_manager->getStorage('node')
    );
  }

  public function __construct(Connection $database, $studioContainer, $studioProducts) {
    $this->database = $database;
    //$this->formBuilder = $form_builder;
    //$this->userStorage = $this->entityManager()->getStorage('user');
    $this->nodeStorage = $this->entityManager()->getStorage('node');
    $this->fileStorage = $this->entityManager()->getStorage('file');
    $this->studioContainer = $studioContainer;
    $this->studioProducts = $studioProducts;
  }

  //handle all checkin at the warehouse level
  public function content($cid, $state) {
    // Get container id. (not container node id, container title will be container id in our case.)
    //$container_id = $cid;
    $container_nid = $cid;

    $product_return_data = array();

    // on invalid container, redirect user to somewhere & notify him.
    if (!$container_nid) {
      drupal_set_message('Invalid Container found', 'warning');
      return new RedirectResponse(base_path() . 'containers');
    }

    $container = $this->nodeStorage->load($container_nid);
    $container_values = $container->toArray();
    if($container_values['title']){
      $container_id = $container_values['title'][0]['value'];
    }else{
      drupal_set_message('Invalid Container found', 'warning');
      return new RedirectResponse(base_path() . 'containers');
    }

    if($state == 'checkin'){

      // validate if container already checked out.
      if($container_values){
        $container_state = $container_values['field_container_state'][0]['value'];
        if($container_state == 'checkout'){
          drupal_set_message('Container cannot be checkin again', 'warning');
          return new RedirectResponse(base_path() . 'container/view/'. $container_nid);
        }
      }

      $last_scanned_product_container = $this->state()->get('warehouse_container_last_scan_product_' . $container_id,'');

      if($last_scanned_product_container){
        $result = $this->studioProducts->getProductByIdentifier($last_scanned_product_container);
        if($result){
          $product = $this->nodeStorage->load(reset($result));
          // Product data response.
          $product_return_data = $this->studioProducts->getProductInfoByObject($product);
        }
      }

      // Update container state to checkin
      $this->studioContainer->updateContainerStatus($container, 'checkIn');

      return [
        '#theme' => 'checkin',
        '#cache' => ['max-age' => 0],
        '#container' => $container_values,
        '#product_identifier' => $last_scanned_product_container,
        '#product_block' => $product_return_data,
        '#tmp' => $container_id,
        '#attached' => array(
          'library' => array(
            'warehouse_checkin/checkin-form',
          )
        ),
      ];
    }
    elseif($state == 'checkout'){
      // if any checkout operations will come here.

      // Update container state to checkin
      $this->studioContainer->updateContainerStatus($container, 'checkout');

      drupal_set_message('Container check out successful.', 'success');
      return new RedirectResponse(base_path() . 'container/view/'. $container_nid);
    }

  }
}
