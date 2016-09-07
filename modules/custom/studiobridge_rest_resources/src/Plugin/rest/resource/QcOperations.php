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

    if(count($fids) == 0 && $type != 'notes'){
      return new ResourceResponse(array('message' => 'No images found!', 'type' => 'error'));
    }

    $sids = $this->getOpenSessionFromProduct($pid);

    if(count($sids) > 1 ){
      return new ResourceResponse(array('title'=> '', 'message' => 'There are multiple sessions opened for this product.', 'type' => 'error', 'data' => $sids));
    }elseif(count($sids) == 1){
      $sid = reset($sids);
    }elseif(count($sids) == 0){
      return new ResourceResponse(array('message' => 'This product not found in any open/pause session', 'type' => 'error'));
    }

    switch($type){

      case  'reject_all':
        if($sid){
          $this->rejectAll($sid, $pid, $fids);

          return new ResourceResponse(array('message' => 'Successfully rejected all images','type' => 'success', 'data' => array()));
        }
        break;

      case  'approve_all':
        if($sid){
          $this->approveAll($sid, $pid, $fids);
          return new ResourceResponse(array('message' => 'Successfully approved all images','type' => 'success', 'data' => array()));

        }
        break;

      case  'notes':
        if($sid){
          $note = $data->body->value['note'];
          $this->notes($sid, $pid,$note);
          return new ResourceResponse(array('message' => 'Successfully added note to this product', 'type' => 'success', 'data' => array()));

        }
        break;

      case  'reject_img':
        if($fids & $sid){
          $this->rejectImg($fids, $pid, $sid, 'rejected');
          return new ResourceResponse(array('message' => 'Successfully rejected this image', 'type' => 'success', 'data' => array()));
        }
        break;

      case  'approve_img':
        if($fids & $sid){
          $this->approveImg($fids,$pid, $sid, 'approve');
          return new ResourceResponse(array('message' => 'Successfully approved this image', 'type' => 'success', 'data' => array()));
        }
        break;

      default:
        return new ResourceResponse(array('message' => 'Invalid request sent', 'type' => 'error', 'data' => array()));

    }


    return new ResourceResponse(array('message' => 'Invalid request sent', 'type' => 'error', 'data' => array()));

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

    $uid = $this->currentUser->id();
    // todo find the last updated state of this product.
    $result = $this->database->select('studio_products_qc_records', 'spqr')
      ->fields('spqr', array('qc_state'))
      ->condition('spqr.sid', $sid)
      ->condition('spqr.pid', $pid)
      ->condition('spqr.uid', $uid)
      ->orderBy('spqr.id', 'desc')->range(0, 1);
    $state = $result->execute()->fetchField();
    // todo get that record & add as new record.
    if(!$state){
      $state = 'pending';
    }

    $this->studioQc->addQcRecord($pid, $sid, $notes, $state);
  }

  public function rejectImg($fid, $pid, $sid, $qc_state){
    $image = $this->fileStorage->load($fid);
    $image->field_qc_state->setValue(array('value'=>'rejected'));
    $image->save();

    // add record to our schema - studio_qc_img_records
    $this->studioQc->addQcRecordOfImg($fid, $pid, $sid, '', $qc_state);

    // if any image is rejected from the product then product itself rejected
    $this->studioQc->addQcRecord($pid, $sid, '', 'rejected');
  }

  public function approveImg($fid, $pid, $sid, $qc_state){
    $image = $this->fileStorage->load($fid);
    $image->field_qc_state->setValue(array('value'=>'approved'));
    $image->save();

    // add record to our schema - studio_qc_img_records
    $this->studioQc->addQcRecordOfImg($fid, $pid, $sid, '', $qc_state);
  }


  public function getOpenSessionFromProduct($pid){
    $sessions_nids = $this->database->select('node__field_product', 'c')
      ->fields('c', array('entity_id'))
      ->condition('field_product_target_id', $pid)
      ->condition('bundle', 'sessions')
      ->execute()->fetchAll();

    $sids = array();
    if($sessions_nids){
      foreach($sessions_nids as $session){
        $sids[] = $session->entity_id;
      }
    }

    if(count($sids) == 1){
      $sid = reset($sids);
      $session_obj = $this->nodeStorage->load($sid);
      $field_status = $session_obj->field_status->getValue();
      if($field_status[0]['value'] != 'closed'){
        return array($sid);
      }

    }elseif(count($sids) > 1){

      $sids_open = array();
      $sessions = $this->nodeStorage->loadMultiple($sids);
      foreach($sessions as $each_session){

        $field_status = $each_session->field_status->getValue();
        if($field_status[0]['value'] != 'closed'){
          $sids_open[] = $each_session->id();
        }

      }
      return $sids_open;


    }

    return array();

  }

}
