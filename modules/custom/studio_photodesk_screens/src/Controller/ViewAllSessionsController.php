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
class ViewAllSessionsController extends ControllerBase
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
public function content()
{

  // Get current logged in user.
  $user = \Drupal::currentUser();
  // Get uid of user.
  $uid = $user->id();

  //get all nodes of session type
  $result = \Drupal::entityQuery('node')
  ->condition('type', 'sessions')
  ->condition('uid', $uid)
  ->sort('created', 'DESC')
  ->range(0, 10000)
  ->execute();

  //load all the nodes from the result
  $sessions = $this->nodeStorage->loadMultiple($result);


  //if results are not empty load each node and get info
  if (!empty($sessions)) {
    foreach ($sessions as $session) {

      //get concepts
      $products = $session->field_product->getValue();

      $pid_array = '';
      foreach($products as $p){
        $pid_array[] = $p['target_id'];
      }

      if ($pid_array !== '') {
        $in_q = '('.implode(',', $pid_array).')';
        $concepts = db_query("select distinct field_concept_name_value as concept from node__field_concept_name
        where bundle='products' AND entity_id IN $in_q")->fetchAll();
        $mapped = db_query("select count(nid) as mappedcount from node where type='products' AND nid IN $in_q")->fetchAll();
        $unmapped = db_query("select count(nid) as unmappedcount from node where type='unmapped_products' AND nid IN $in_q")->fetchAll();
    
      }

      if($in_q !== ''){
}

      //set values into array
      $session_data[] = array( 'id' => $session->id(),
      'name' => $session->title->getValue(),
      'stage' => $session->field_status->getValue(),
      'shootdate' => $session->created->getValue(),
      'type' => $session->field_shoot_type->getValue(),
      'concepts' => $concepts,
      'photographer' => $this->userStorage->load($session->field_photographer->get(0)->target_id)->label(),
      'vm' => $this->userStorage->load($session->field_vm->get(0)->target_id)->label(),
      'stylist' => $this->userStorage->load($session->field_stylish->get(0)->target_id)->label(),
      'productcount' => $products,
      'mapped' => $mapped,
      'unmapped' => $unmapped
    );
  }
}



//return array to render
return [
  '#theme' => 'view_all_sessions',
  '#cache' => ['max-age' => 0],
  '#results' => $session_data,
  '#attached' => array(
    'library' => array(
      'studio_photodesk_screens/studiobridge-sessions'
    ),
  ),
];

}

}
