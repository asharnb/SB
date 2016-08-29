<?php

/**
* @file
* Contains \Drupal\studio_photodesk_screens\Controller\ViewAllModelsController.
*/

//test
namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityTypeManager;


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

  public $fileStorage;

  protected $entityTypeManager;
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

  //$this->entityTypeManager = $entityTypeManager;

  $this->database = $database;
  $this->nodeStorage = $this->entityTypeManager()->getStorage('node');
  $this->userStorage = $this->entityTypeManager()->getStorage('user');
  $this->fileStorage = $this->entityTypeManager()->getStorage('file');

}


/**
* Content.
*/
public function content()
{
      $query = \Drupal::entityQuery('node');
      $model_nids = $query
        ->condition('type', 'models')
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();
        //->fetchAll();

        // $model_nids = \Drupal::database()->select('node__field_models', 'c')
        //   ->fields('c',array('field_models_target_id'))
        //   ->execute()->fetchAll();





if($model_nids){
  $nids = array();
  foreach($model_nids as $nid){
    $nids[] = $nid;
  }

  if($nids){

    $model_node_objects = $this->nodeStorage->loadMultiple($nids);
  }

  if($model_node_objects){
    foreach($model_node_objects as $node){

      $name = '';
      $gender = '';
      $stats = '';
      $image_uri_value = '';

      $model_name = $node->title->getValue();
      if ($model_name) {
        $name = $model_name[0]['value'];
      }

      $model_gender = $node->field_model_gender->getValue();
      if ($model_gender) {
        $gender = $model_gender[0]['value'];
      }

      $model_stats = $node->field_model_stats->getValue();
      if ($model_stats) {
        $stats = $model_stats[0]['value'];
      }

      $model_image = $node->field_model_image->getValue();
      if ($model_image) {
        $image = $model_image[0]['target_id'];

        if($image){
          $image_object = $this->fileStorage->load($image);
          if($image_object){
            $image_uri_value = ImageStyle::load('thumbnail')->buildUrl($image_object->getFileUri());
          }
        }

      }

      $models[] = array(
        'id' => $node->id(),
        'name' => $name,
        'gender' => $gender,
        'stats' => $stats,
        'image' => $image_uri_value
      );

    }
  }

}

//return array to render
//return array to render
return [
  '#theme' => 'view_all_models',
  '#cache' => ['max-age' => 0],
  '#results' => $models,

];

}

}
