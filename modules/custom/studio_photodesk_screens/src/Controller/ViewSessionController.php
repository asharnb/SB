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
use Drupal\studiobridge_commons\Products;
use Drupal\studiobridge_commons\Sessions;
/**
 * Class ViewSessionController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class ViewSessionController extends ControllerBase
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
   *
   * @param nid
   *   session nid.
   * @return array
   *   Return session data.
   */
  public function content($nid)
  {
    // build the variables here.

    //get session time
    $seconds = Sessions::CalculateSessionPeriod($nid);

    $H = floor($seconds / 3600);
    $i = ($seconds / 60) % 60;
    $s = $seconds % 60;

    $session_time = sprintf("%02d:%02d:%02d", $H, $i, $s);

    // Load session node object

    $session = $this->nodeStorage->load($nid);


    // on invalid session, redirect user to somewhere & notify him.
    if (!$session) {
      drupal_set_message('Invalid session id ' . $nid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions2');
    }

    // Convert node object to readable array format for twig file.
    $values = $session->toArray();
    $stylist = '';
    $vm = '';

    // Get user details of session; Photographer, stylist, VM, created by(Photographer only).
    // Created by (Photographer)
    $uid = $values['uid'][0]['target_id'];
    $created_by = $photographer = $this->userStorage->load($uid)->label();

    // stylist
    if (!empty($values['field_stylish'][0]['target_id'])) {
      $stylist = $this->userStorage->load($values['field_stylish'][0]['target_id'])->label();
    }
    // vm
    if (!empty($values['field_vm'][0]['target_id'])) {
      $vm = $this->userStorage->load($values['field_vm'][0]['target_id'])->label();
    }

    $session_users = array('photographer' => $photographer, 'stylist' => $stylist, 'vm' => $vm);

    $products_ids = $session->field_product->getValue();
    $products = [];
    $unmapped_products = [];
    $dropped_products = [];
    $grouped_concepts = [];
    $grouped_concepts_count = [];
    $product_period = [];
    $total_images = 0;

    // Build unmapped & mapped products
    foreach ($products_ids as $product) {

      //calculate time taken for product

      $productseconds = Products::CalculateProductPeriod($product['target_id'],$nid);

      $H = floor($productseconds / 3600);
      $i = ($productseconds / 60) % 60;
      $s = $productseconds % 60;

      $product_time = sprintf("%02d:%02d:%02d", $H, $i, $s);

      $product_period[] = array('nid' => $product['target_id'], 'time' => $product_time);



      $current_product = $this->nodeStorage->load($product['target_id']);
      //$products[] = $current_product;

      if(!$current_product) continue;

      // Get product type; mapped or unmapped
      $bundle = $current_product->bundle();
      $drop = $current_product->field_draft->getValue();
      // Map unmapped & mapped products
      if ($bundle == 'products') {
        $cp = $current_product->toArray();
        $products[] = $cp;

        $total_images += count($cp['field_images']);

        // Get Concept
        $concept = $current_product->field_concept_name->getValue();
        if($drop['0']['value'] == 1){
          //if the product is dropped add it to dropped products list
          $mapped_dropped_products[] = array('nid' => $current_product->id());

        }
        if ($concept) {
          $concept = $concept[0]['value'];


          if (array_key_exists($concept, $grouped_concepts)) {

            $count = count($grouped_concepts[$concept]) + 1;
            $grouped_concepts[$concept][] = array('nid' => $current_product->id());
            $grouped_concepts_count[$concept] = array('concept' => $concept, 'product_count' => $count);

          } else {
            $count = 1;
            if (isset($grouped_concepts[$concept])) {
              $count = count($grouped_concepts[$concept]);
            }
            $grouped_concepts[$concept][] = array('nid' => $current_product->id());
            $grouped_concepts_count[$concept] = array('concept' => $concept, 'product_count' => $count);
          }

        }

      } elseif ($bundle == 'unmapped_products') {
        $cpu = $current_product->toArray();
        $unmapped_products[] = $cpu;
        if($drop['0']['value'] == 1){
          //if the product is dropped add it to dropped products list
          $unmapped_dropped_products[] = array('nid' => $current_product->id());

        }
        $total_images += count($cpu['field_images']);
      }



    }

    unset($concept);


    return [
      '#theme' => 'view_session',
      '#cache' => ['max-age' => 0],
      '#session' => $values,
      '#grouped_concepts' => $grouped_concepts_count,
      '#unmapped_products' => $unmapped_products,
      '#mapped_products' => $products,
      '#session_users' => $session_users,
      '#total_images' => $total_images,
      '#period' => $product_period,
      '#session_time' => $session_time,
      '#period_chart' => self::product_analysis($product_period),
      // '#mapped_dropped_products' => $mapped_dropped_products,
      // '#unmapped_dropped_products' => $unmapped_dropped_products,
      '#attached' => array(
        'library' => array(
          'studio_photodesk_screens/studiobridge-sessions',
        )
      ),

    ];
  }

  function product_analysis($productperiod){

    //this function will collect time taken from the session and return data to create a chart

    //data collection

    $period_product = $productperiod;

  	foreach ($period_product as $key => $period) {
  		$data[] = array(
  			'label'       => $key,
  			'value'       => $period['time']
  		);
  	}

    return ($data);
  }

}
