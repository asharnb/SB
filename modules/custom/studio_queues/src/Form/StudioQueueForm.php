<?php

/**
 * @file
 * Contains \Drupal\npq\Form\NodePublisherQueueForm.
 */

namespace Drupal\studio_queues\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\studiobridge_commons;

//class NodePublisherQueueForm extends FormBase {
class StudioQueueForm extends FormBase {

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var QueueWorkerManagerInterface
   */
  protected $queueManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'studio_queue_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var QueueInterface $queue */
    //$queue = $this->queueFactory->get('studio_img_queue');

    $queue_factory = \Drupal::service('queue');
    $queue  = $queue_factory->get('studio_img_queue_260');

    $form['help'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('Submitting this form will process the Manual Queue which contains @number items.', array('@number' => $queue->numberOfItems())),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Process queue'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    studiobridge_commons\Queues::runQueue(260,array(1,2));

//    /** @var QueueInterface $queue */
//    //$queue = $this->queueFactory->get('manual_node_publisher');
//    $queue = $this->queueFactory->get('studio_img_queue_260');
//    /** @var QueueWorkerInterface $queue_worker */
//    $queue_worker = $this->queueManager->createInstance('manual_node_publisher');
//
//    while($item = $queue->claimItem()) {
//      try {
//        $queue_worker->processItem($item->data);
//        $queue->deleteItem($item);
//      }
//      catch (SuspendQueueException $e) {
//        // If the worker indicates there is a problem with the whole queue,
//        // release the item and skip to the next queue.
//        $queue->releaseItem($item);
//        break;
//      }
//      catch (\Exception $e) {
//        // In case of any other kind of exception, log it and leave the item
//        // in the queue to be processed again later.
//        watchdog_exception('studio_queues', $e);
//      }
//    }





  }

}
