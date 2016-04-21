<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewProductController.
 */

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;



/**
 * Class ViewProductController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class ViewProductController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $nodeStorage;

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
    $this->nodeStorage = $this->entityManager()->getStorage('node');
  }

  /**
   * Content.
   *
   * @param $product
   *  product nid
   *
   * @return string
   *   Return Hello string.
   */
  public function content($product) {
    // build the variables here.

    //$this->database->query('');
    $product = $this->nodeStorage->load($product);
    //$title = $product->title->getValue();
    $values = $product->toArray();
    $values['nid'] = 123456;
    //extract($values);

    return [
      '#theme' => 'view_product',
      '#cache' => ['max-age' => 0],
      '#product' => $values
     ,
    ];
  }

}
