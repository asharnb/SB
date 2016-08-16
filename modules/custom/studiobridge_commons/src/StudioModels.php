<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioModels.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\image\Entity\ImageStyle;


/**
 * Class StudioModels.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioModels implements StudioModelsInterface {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage service.
   */
  public $nodeStorage;

  /**
   * The user storage service.
   */
  protected $userStorage;

  /**
   * The file storage service.
   */
  public $fileStorage;

  /**
   * The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser = array();

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, AccountProxyInterface $current_user, QueryFactory $query_factory) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->queryFactory = $query_factory;

  }


  /*
   * Helper function, to get all models nodes from the database in the following format.
   *   array(nid,model name, gender, stats, image path);
   */
  public function getModels(){

    $name = '';
    $stats = '';
    $gender = '';
    $image_uri_value = '';
    $models = array();

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('models'), 'IN')
      ->execute();
    if($result){
      $model_node_objects = $this->nodeStorage->loadMultiple($result);

      foreach($model_node_objects as $node){

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
          'nid' => $node->id(),
          'model_name' => $name,
          'model_gender' => $gender,
          'model_stats' => $stats,
          'model_image_path' => $image_uri_value
        );

      }

    }

    return $models;
  }

  /*
 * Helper function, to get all models nodes from the database in the following format.
 *   array(nid,model name, gender, stats, image path);
 */
  public function getModelsBySession($sid){

    $name = '';
    $stats = '';
    $gender = '';
    $image_uri_value = '';
    $model_nids = '';
    $model_node_objects = '';
    $models = array();


    if($sid){
      $model_nids = \Drupal::database()->select('node__field_models', 'c')
        ->fields('c',array('field_models_target_id'))
        ->condition('entity_id', $sid)
        ->condition('bundle', 'sessions')
        ->execute()->fetchAll();
    }


    if($model_nids){
      $nids = array();
      foreach($model_nids as $nid){
        $nids[] = $nid->field_models_target_id;
      }

      if($nids){
        $model_node_objects = $this->nodeStorage->loadMultiple($nids);
      }

      if($model_node_objects){
        foreach($model_node_objects as $node){

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
            'nid' => $node->id(),
            'model_name' => $name,
            'model_gender' => $gender,
            'model_stats' => $stats,
            'model_image_path' => $image_uri_value
          );

        }
      }

    }

    return $models;
  }

}
