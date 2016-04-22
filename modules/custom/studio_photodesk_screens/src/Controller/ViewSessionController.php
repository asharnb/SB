<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewSessionController.
 */

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
/**
 * Class ViewSessionController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class ViewSessionController extends ControllerBase {

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
   * @param nid
   *   session nid.
   * @return array
   *   Return session data.
   */
  public function content($nid) {
    // build the variables here.

    // Load session node object
    $session = $this->nodeStorage->load($nid);
    // Convert node object to readable array format for twig file.
    $values = $session->toArray();

    // Get product nid values.
    $products_id = $session->field_product->getValue();
    $products = [];
    $unmapped_products = [];
    $grouped_concepts = [];
    $grouped_concepts_count = [];

//    foreach($products_id as $product){
//      $products[] = $product['target_id'];
//    }
    //$products = $this->nodeStorage->loadMultiple($products);


    $concept_count = 0;
    // Build unmapped & mapped products
    foreach($products_id as $product){

      $current_product = $this->nodeStorage->load($product['target_id']);
      $products[] = $current_product;

      // Get product type; mapped or unmapped
      $bundle = $current_product->bundle();
      // Map unmapped & mapped products
      if($bundle == 'products'){
        $products[] = $current_product->toArray();

        // Get Concept
        $concept = $current_product->field_concept_name->getValue();
        if($concept){
          $concept = $concept[0]['value'];

          if(array_key_exists($concept,$grouped_concepts)){
            $c = count($grouped_concepts[$concept]) + 1;
            $grouped_concepts[$concept][] = array('nid'=>$current_product->id());
            $grouped_concepts_count[$concept] =  array('concept'=> $concept, 'product_count'=> $c);

          }else{
            $c = count($grouped_concepts[$concept]);
            $grouped_concepts[$concept][] = array('nid'=>$current_product->id());
            $grouped_concepts_count[$concept] =  array('concept'=> $concept, 'product_count'=> $c);
          }

        }


      }
      elseif($bundle == 'unmapped_products'){
        $unmapped_products[] = $current_product->toArray();
      }


    }

    $a =1;

    return [
      '#theme' => 'view_session',
      '#cache' => ['max-age' => 0],
      '#session' => $values,
      '#grouped_concepts' => $grouped_concepts_count,
      '#unmapped_products' => $unmapped_products,
      '#mapped_products' => $products,
    ];
  }

}
