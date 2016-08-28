<?php

/**
* @file
* Contains \Drupal\studio_photodesk_screens\Controller\ViewAllModelsController.
*/

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\image\Entity\ImageStyle;


/**
* Class ViewSessionController.
*
* @package Drupal\studio_photodesk_screens\Controller
*/
class ViewAllModelsController extends ControllerBase
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
      $query = \Drupal::entityQuery('node');
      $result = $query
        ->condition('type', 'models')
        ->sort('created', 'DESC')
        ->range(0, 500)
        ->execute();


  //load all the nodes from the result
  $models = $this->nodeStorage->loadMultiple($result);

  //if results are not empty load each node and get info
  if (!empty($models)) {
    foreach ($models as $model) {
      $model_data[] = array( 'id' => $model->id(),
      'name' => $model->title->getValue(),
       'gender' => $model->field_model_gender->getValue(),
       'stats' => $model->field_model_stats->getValue(),
       'image' => ImageStyle::load('thumbnail')->buildUrl($model->field_model_image->entity->getFileUri()),
    );

  // }

  }
}



//return array to render
//return array to render
return [
  '#theme' => 'view_all_models',
  '#cache' => ['max-age' => 0],
  '#results' => $model_data,

];

}

}
