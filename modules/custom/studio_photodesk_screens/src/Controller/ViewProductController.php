<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewProductController.
 */

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

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
    $this->nodeStorage = $this->entityManager()->getStorage('node');
    $this->fileStorage = $this->entityManager()->getStorage('file');
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
  public function content($nid) {
    // build the variables here.

    $product = $this->nodeStorage->load($nid);

    // on invalid product, redirect user to somewhere & notify him.
    if (!$product) {
      drupal_set_message('Invalid product id '.$nid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions');
    }
    elseif(!in_array($bundle = $product->bundle(),array('products','unmapped_products'))){
      drupal_set_message('Invalid product id '.$nid, 'warning');
      return new RedirectResponse(base_path() . 'products');
    }

    $values = $product->toArray();
    //extract($values);
    $field_images = $values['field_images'];

    $images =[];

    foreach($field_images as $img){
      $fid = $img['target_id'];
      // Load the file entity by its fid.
      //$file = File::load($fid);
      $file = $this->fileStorage->load($fid);

      $file_name = $file->filename->getValue();
      $file_name = $file_name[0]['value'];
      $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
      $images[$fid] = array('uri'=>$image_uri_value,'name'=>$file_name);
    }

    $a =1;

    return [
      '#theme' => 'view_product',
      '#cache' => ['max-age' => 0],
      '#product' => $values,
      '#images' => $images,
      '#product_type' => $bundle,
    ];
  }

}
