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
      return new RedirectResponse(base_path() . 'view-sessions2');
    }
    elseif(!in_array($bundle = $product->bundle(),array('products','unmapped_products'))){
      drupal_set_message('Invalid product id '.$nid, 'warning');
      return new RedirectResponse(base_path() . 'view-products2');
    }

    $values = $product->toArray();
    //extract($values);
    $field_images = $values['field_images'];

    $images =[];
    $images2 =[];

    foreach($field_images as $img){
      $fid = $img['target_id'];
      // Load the file entity by its fid.
      //$file = File::load($fid);
      $file = $this->fileStorage->load($fid);

      $file_name = $file->filename->getValue();
      $qc_state = $file->field_qc_state->getValue();
      $file_name = $file_name[0]['value'];

      $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
      $image_uri_large = ImageStyle::load('large_image_preview')->buildUrl($file->getFileUri());
      $image_uri_full = file_create_url($file->getFileUri());
      $images[$fid] = array('fid'=>$fid,'uri'=>$image_uri_value,'name'=>$file_name,'fullimage'=>$image_uri_full, 'largeimage'=>$image_uri_large, 'qc'=>$qc_state);

      $images2[$fid] = $file;
    }

    $a =1;

    return [
      '#theme' => 'view_product',
      '#cache' => ['max-age' => 0],
      '#product' => $values,
      '#images' => $images,
      '#images2' => $images2,
      '#product_type' => $bundle,
      '#attached' => array(
        'library' => array(
          'studiobridge/run-fancybox'
        ),
      ),
    ];
  }

}
