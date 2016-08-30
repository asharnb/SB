<?php

/**
 * @file
 * Contains \Drupal\studiobridge_rest_resources\Plugin\rest\resource\studiobridge_rest_resources.
 */

namespace Drupal\studiobridge_rest_resources\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Connection;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "qc_operations",
 *   label = @Translation("[Studio] Qc operations"),
 *   serialization_class = "Drupal\node\Entity\Node",
 *   uri_paths = {
 *     "canonical" = "/qc/operation/{type}",
 *     "https://www.drupal.org/link-relations/create" = "/qc/operation/{type}/post"
 *   }
 * )
 */
class QcOperations extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $studioQc;

  protected $nodeStorage;

  protected $fileStorage;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   *
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    Connection $database, $entity_manager, $studioQc) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->database = $database;

    $this->studioQc =  $studioQc;

    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->fileStorage = $entity_manager->getStorage('file');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('studio.qc')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @type
   *  - reject_all
   *  - approve_all
   *  - notes
   *  - reject_img
   *  - approve_img
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($type, $data) {
    $pid = $data->body->value['pid'];
    $sid = $data->body->value['sid'];
    $fids = $data->body->value['images'];
    $state = $data->body->value['state'];


    switch($type){

      case  'reject_all':
        if($sid){
          $this->rejectAll($sid, $pid, $fids);
        }
        break;

      case  'approve_all':
        if($sid){
          $this->approveAll($sid, $pid, $fids);
        }
        break;

      case  'notes':
        $note = $data->body->value['note'];
        $this->notes($sid, $pid,$note);
        break;

      case  'reject_img':
        $this->rejectImg($fids);
        break;

      case  'approve_img':
        $this->approveImg($fids);
        break;

      default:
        return new ResourceResponse("Implement REST State POST!");

    }


    return new ResourceResponse(array(rand(1, 999999), array($type, $data)));

  }

  /*
   *
   */
  public function get($type){
    \Drupal::service('page_cache_kill_switch')->trigger();


    switch($type){

      case  'reject_all':
        //$this->rejectAll();
        break;

      default:
        return new ResourceResponse("Implement REST State GET!");

    }


    return new ResourceResponse($_GET);
  }


  /*
   *
   */
  public function rejectAll($sid, $pid, $fids){
    $images = $this->fileStorage->loadMultiple($fids);

    foreach($images as $image){
      $image->field_qc_state->setValue(array('value'=>'rejected'));
      $image->save();
    }

    // update product as rejected
    $this->studioQc->addQcRecord($pid, $sid, '', 'rejected');

  }

  public function approveAll($sid, $pid, $fids){
    $images = $this->fileStorage->loadMultiple($fids);

    foreach($images as $image){
      $image->field_qc_state->setValue(array('value'=>'approved'));
      $image->save();
    }

    // update product as rejected
    $this->studioQc->addQcRecord($pid, $sid, '', 'approved');
  }


  public function notes($sid, $pid, $notes){

    // todo find the last updated state of this product.

    // todo get that record & add as new record.

    $this->studioQc->addQcRecord($pid, $sid, $notes, 'approved');
  }

  public function rejectImg($fid){
    $image = $this->fileStorage->load($fid);
    $image->field_qc_state->setValue(array('value'=>'rejected'));
    $image->save();
  }

  public function approveImg($fid){
    $image = $this->fileStorage->load($fid);
    $image->field_qc_state->setValue(array('value'=>'approved'));
    $image->save();
  }

}
