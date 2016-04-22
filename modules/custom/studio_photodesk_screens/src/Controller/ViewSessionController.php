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
class ViewSessionController extends ControllerBase {

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
    $this->userStorage = $this->entityManager()->getStorage('user');
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

    // on invalid session, redirect user to somewhere & notify him.
    if (!$session) {
      drupal_set_message('Invalid session id '.$nid, 'warning');
      return new RedirectResponse(base_path() . 'view-sessions');
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
    if(!empty($values['field_stylish'][0]['target_id'])){
      $stylist = $this->userStorage->load($values['field_stylish'][0]['target_id'])->label();
    }
    // vm
    if(!empty($values['field_vm'][0]['target_id'])){
      $vm = $this->userStorage->load($values['field_vm'][0]['target_id'])->label();
    }

    $session_users = array('photographer'=>$photographer, 'stylist'=>$stylist, 'vm' => $vm);

    $products_ids = $session->field_product->getValue();
    $products = [];
    $unmapped_products = [];
    $grouped_concepts = [];
    $grouped_concepts_count = [];
    $total_images = 0;

    // Build unmapped & mapped products
    foreach($products_ids as $product){

      $current_product = $this->nodeStorage->load($product['target_id']);
      $products[] = $current_product;

      // Get product type; mapped or unmapped
      $bundle = $current_product->bundle();


      // Map unmapped & mapped products
      if($bundle == 'products'){
        $cp = $current_product->toArray();
        $products[] = $cp;

        $total_images += count($cp['field_images']);

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
        $cpu = $current_product->toArray();
        $unmapped_products[] = $cpu;

        $total_images += count($cpu['field_images']);
      }

    }

    unset($concept);
    $a =1;

    return [
      '#theme' => 'view_session',
      '#cache' => ['max-age' => 0],
      '#session' => $values,
      '#grouped_concepts' => $grouped_concepts_count,
      '#unmapped_products' => $unmapped_products,
      '#mapped_products' => $products,
      '#session_users' => $session_users,
      '#total_images' => $total_images,
    ];
  }

}
