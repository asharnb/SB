<?php

/**
* @file
* Contains \Drupal\studio_photodesk_screens\Controller\ViewSessionController.
*/

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
* Class ViewSessionController.
*
* @package Drupal\studio_photodesk_screens\Controller
*/
class ViewAllProductsController extends ControllerBase
{

  /**
  * The database service.
  *
  * @var \Drupal\Core\Database\Connection
  */
  protected $database;

  protected $nodeStorage;

  protected $userStorage;
  /*
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container)
  {
    //$entity_manager = $container->get('entity.manager');
    return new static(
    $container->get('database')

    //$entity_manager->getStorage('node')
  );
}

public function __construct(Connection $database)
{
  $this->database = $database;
  //$this->formBuilder = $form_builder;
  //$this->userStorage = $this->entityManager()->getStorage('user');
  $this->nodeStorage = $this->entityTypeManager()->getStorage('node');
  $this->userStorage = $this->entityTypeManager()->getStorage('user');

}


/**
* Content.
*/
  public function content() {
    $product_data = array();

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('products', 'unmapped_products'), 'IN')
      ->sort('created', 'DESC')
      ->range(0, 1000000)
      ->execute();

    //load all the nodes from the result
    if ($result) {
      $products = $this->nodeStorage->loadMultiple($result);

      //if results are not empty load each node and get info
      if ($products) {
        foreach ($products as $product) {
          $product_data[] = $product->toArray();
        }
      }
    }


//return array to render
    return [
      '#theme' => 'view_all_products',
      '#cache' => ['max-age' => 0],
      '#results' => $product_data,
      '#attached' => array(
        'library' => array(
          'studio_photodesk_screens/studiobridge-sessions'
        ),
      ),
    ];

  }



}
