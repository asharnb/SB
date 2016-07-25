<?php

/**
 * @file
 * Contains \Drupal\warehouse_checkin\Controller\ContainerView.
 */

namespace Drupal\warehouse_checkin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\studiobridge_commons\Products;
use Drupal\studiobridge_commons\Sessions;

/**
 * Class ContainerView.
 *
 * @package Drupal\warehouse_checkin\Controller
 */
class ContainerView extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $nodeStorage;

  protected $userStorage;

  protected $products;

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
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function view($cid) {

    // on invalid container, redirect user to somewhere & notify him.
    if (!$cid) {
      drupal_set_message('Invalid Container id found', 'warning');
      return new RedirectResponse(base_path() . 'containers');
    }

    $container = $this->nodeStorage->load($cid);
    if (!$cid) {
      drupal_set_message('Invalid Container found', 'warning');
      return new RedirectResponse(base_path() . 'containers');
    }
    $container_values = $container->toArray();

    $products_nid = $container->field_product->getValue();
    $pids = array();
    if($products_nid){
      foreach($products_nid as $id){
        $pids[] = $id['target_id'];
      }
    }
    $products_objs =  $this->nodeStorage->loadMultiple($pids);

    $grouped_concepts = [];
    $grouped_concepts_count = [];
    $unmapped_products = array();

    $products = array();
    $all_products = array();
    $output_product = array();

    if($products_objs){
      foreach($products_objs as $product){
        $concept = '';
        $style_no = '';
        $color_variant = '';
        $state = '';
        $title = '';
        $description = '';

        $total_images = 0;
        // Get product type; mapped or unmapped
        $bundle = $product->bundle();
        $drop = $product->field_draft->getValue();
        $images = $product->field_images->getValue();
        // Map unmapped & mapped products
        if ($bundle == 'products') {
          $cp = $product->toArray();
//          $products[] = $all_products[] = $cp;

          //$total_images = count($cp['field_images']);
          //$this->products[$product->id()] = array('image_count' => $total_images);

          // Get Concept
          $concept = $product->field_concept_name->getValue();
          if($drop['0']['value'] == 1){
            //if the product is dropped add it to dropped products list
            $mapped_dropped_products[] = array('nid' => $product->id());

          }
          if ($concept) {
            $concept = $concept[0]['value'];


            if (array_key_exists($concept, $grouped_concepts)) {

              $count = count($grouped_concepts[$concept]) + 1;
              $grouped_concepts[$concept][] = array('nid' => $product->id());
              $grouped_concepts_count[$concept] = array('concept' => $concept, 'product_count' => $count);

            } else {
              $count = 1;
              if (isset($grouped_concepts[$concept])) {
                $count = count($grouped_concepts[$concept]);
              }
              $grouped_concepts[$concept][] = array('nid' => $product->id());
              $grouped_concepts_count[$concept] = array('concept' => $concept, 'product_count' => $count);
            }

          }



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

          $product_state = $product->field_state->getValue();
          if($product_state){
            $state = $product_state[0]['value'];
          }

          $product_title = $product->title->getValue();
          if($product_title){
            $title = $product_title[0]['value'];
          }

          $output_product[$product->id()] = array("concept" => $concept,
            "styleno" => $style_no,
            "colorvariant" => $color_variant,
            "image_count" => count($images),
            "state" => $state,
            "identifier" => $title,
            "nid" => $product->id()
          );



        } elseif ($bundle == 'unmapped_products') {

          $concept = 'Unmapped';

          $product_state = $product->field_state->getValue();
          if($product_state){
            $state = $product_state[0]['value'];
          }

          $product_title = $product->title->getValue();
          if($product_title){
            $title = $product_title[0]['value'];
          }

          $output_product[$product->id()] = array("concept" => $concept,
            "styleno" => 'N/A',
            "colorvariant" => 'N/A',
            "image_count" => count($images),
            "state" => $state,
            "identifier" => $title,
            "nid" => $product->id()
          );

          $cpu = $product->toArray();
          $unmapped_products[] = $cpu;
          $all_products[]  = $cpu;
          if($drop['0']['value'] == 1){
            //if the product is dropped add it to dropped products list
            $unmapped_dropped_products[] = array('nid' => $product->id());

          }
        }
      }
    }

    $a = 1;
    return [
        '#theme' => 'view_container',
        '#cache' => ['max-age' => 0],
        '#container' => $container_values,
        '#grouped_concepts' => $grouped_concepts_count,
        '#unmapped_products' => $unmapped_products,
        '#products' => $output_product
    ];
  }

  public function processProducts($product){

  }

}
