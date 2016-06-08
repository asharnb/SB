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

    $session_state = $session->field_status->getValue();
    $session_state = $session_state[0]['value'];

    $products = [];
    $unmapped_products = [];
    $dropped_products = [];
    $grouped_concepts = [];
    $grouped_concepts_count = [];
    $product_period = [];
    $total_images = 0;

    $concept = '';
    $style_no = '';
    $color_variant = '';
    $state = '';
    $title = '';
    $drops = array();
    $mapped_dropped_products = array();
    $unmapped_dropped_products = array();


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

        if($drop['0']['value'] == 1 && $session_state !== 'closed'){

          $product_concept = $current_product->field_concept_name->getValue();
          if($product_concept){
            $concept = $product_concept[0]['value'];
          }

          $product_style_no = $current_product->field_style_family->getValue();
          if($product_style_no){
            $style_no = $product_style_no[0]['value'];
          }
          $product_color_variant = $current_product->field_color_variant->getValue();
          if($product_color_variant){
            $color_variant = $product_color_variant[0]['value'];
          }

          $product_state = $current_product->field_state->getValue();
          if($product_state){
            $state = $product_state[0]['value'];
          }

          $product_title = $current_product->title->getValue();
          if($product_title){
            $title = $product_title[0]['value'];
          }

          $drops[$current_product->id()] = array("concept" => $concept,
            "styleno" => $style_no,
            "colorvariant" => $color_variant,
            "image_count" => count($cp['field_images']),
            "state" => $state,
            "identifier" => $title,
            "nid" => $current_product->id()
          );



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
        if($drop['0']['value'] == 1 && $session_state !== 'closed'){

          $concept = 'Unmapped';

          $product_state = $current_product->field_state->getValue();
          if($product_state){
            $state = $product_state[0]['value'];
          }

          $product_title = $current_product->title->getValue();
          if($product_title){
            $title = $product_title[0]['value'];
          }

          $drops[$current_product->id()] = array("concept" => $concept,
            "styleno" => 'N/A',
            "colorvariant" => 'N/A',
            "image_count" => count($cpu['field_images']),
            "state" => $state,
            "identifier" => $title,
            "nid" => $current_product->id()
          );


        }
        $total_images += count($cpu['field_images']);
      }



    }

    unset($concept);

    if($session_state == 'closed'){
     // $db = \Drupal::database();
      $p_drafts = $this->database->select('node__field_session', 's')
        ->fields('s',array('entity_id'))
        ->condition('bundle', 'dropped_products')
        ->condition('field_session_target_id',$nid)
        ->execute()->fetchAll();
      if($p_drafts){
        $deleted_pids = array();
        foreach($p_drafts as $id){
          $deleted_pids[] = $id->entity_id;
        }
        $deleted_products = $this->nodeStorage->loadMultiple($deleted_pids);

        foreach($deleted_products as $d_product){
          $product_concept = $d_product->field_concept_name->getValue();
          if($product_concept){
            $concept = $product_concept[0]['value'];
          }

          $product_style_no = $d_product->field_style_family->getValue();
          if($product_style_no){
            $style_no = $product_style_no[0]['value'];
          }
          $product_color_variant = $d_product->field_color_variant->getValue();
          if($product_color_variant){
            $color_variant = $product_color_variant[0]['value'];
          }


          $product_title = $d_product->title->getValue();
          if($product_title){
            $title = $product_title[0]['value'];
          }
          if(!$concept){
            $concept = $title;
          }

          $drops[$d_product->id()] = array("concept" => $concept,
            "styleno" => $style_no,
            "colorvariant" => $color_variant,
            "image_count" => 0,
            "state" => 'N/A',
            "identifier" => $title,
            "nid" => $d_product->id()
          );
        }

      }
    }

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
      '#drops' => $drops,
      '#mapped_dropped_products' => $mapped_dropped_products,
      '#unmapped_dropped_products' => $unmapped_dropped_products,
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
