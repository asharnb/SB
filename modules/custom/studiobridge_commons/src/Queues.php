<?php
/**
 * Created by PhpStorm.
 * User: Krishna Kanth
 * Date: 19/4/16
 * Time: 5:18 PM
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\Core\DependencyInjection\ContainerInjectionInterface;



class Queues {

  /*
   *
   */
  public static function CreateQueue($sid,$fid){
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('studio_img_queue_'.$sid);
    $item = new \stdClass();
    $item->item = array('sid' => $sid,'fid'=>$fid);
    $item->operation =
    $queue->createItem($item);
  }

  public static function CreateQueueProductMapping($sid, $server_product, $pid){
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('studio_product_mapping_queue_'.$sid);
    $item = new \stdClass();
    $item->item = array('sid' => $sid,'server_product'=>$server_product, 'pid'=>$pid);
    $item->operation =
    $queue->createItem($item);
  }

  public static function RunMappingQueues($sid){
    drupal_set_message('yes im here');
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

  public static function runQueue($sid, $fids = array()) {
    drupal_set_message('yes im here');
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

