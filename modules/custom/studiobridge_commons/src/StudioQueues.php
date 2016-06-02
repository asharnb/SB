<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioQueues.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;

/**
 * Class StudioQueues.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioQueues implements StudioQueuesInterface {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage service.
   */
  protected $nodeStorage;

  /**
   * The user storage service.
   */
  protected $userStorage;

  /**
   * The entity type manager service.
   */
  protected $entityTypeManager;


  /**
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->database = $database;

  }

  /*
  *
  */
  public function CreateQueue($sid,$fid){
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('studio_img_queue_'.$sid);
    $item = new \stdClass();
    $item->item = array('sid' => $sid,'fid'=>$fid);
    $item->operation =
      $queue->createItem($item);
  }

  public function CreateQueueProductMapping($sid, $server_product, $pid){
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('studio_product_mapping_queue_'.$sid);
    $item = new \stdClass();
    $item->item = array('sid' => $sid,'server_product'=>$server_product, 'pid'=>$pid);
    $item->operation =
      $queue->createItem($item);
  }

  public function RunMappingQueues($sid){
    /** @var QueueInterface $queue */
    //$queue = $this->queueFactory->get('manual_node_publisher');
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('studio_product_mapping_queue_'.$sid);
    /** @var QueueWorkerInterface $queue_worker */
    //    $queue_worker = $this->queueManager->createInstance('manual_node_publisher');
    $queue_worker1 = \Drupal::service('plugin.manager.queue_worker');
    $queue_worker = $queue_worker1->createInstance('manual_node_publisher');


    while($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data, array());
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('studio_queues', $e);
      }
    }
  }

  public function runQueue($sid, $fids = array()) {
    /** @var QueueInterface $queue */
    //$queue = $this->queueFactory->get('manual_node_publisher');
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('studio_img_queue_'.$sid);
    /** @var QueueWorkerInterface $queue_worker */
    //    $queue_worker = $this->queueManager->createInstance('manual_node_publisher');
    $queue_worker1 = \Drupal::service('plugin.manager.queue_worker');
    $queue_worker = $queue_worker1->createInstance('manual_node_publisher');


    while($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data, $fids);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('studio_queues', $e);
      }
    }
  }

}
