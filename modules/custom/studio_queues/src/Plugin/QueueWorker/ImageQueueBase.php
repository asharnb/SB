<?php

/**
 * @file
 * Contains Drupal\npq\Plugin\QueueWorker\NodePublishBase.php
 */

namespace Drupal\studio_queues\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides base functionality for the NodePublish Queue Workers.
 */
abstract class ImageQueueBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Creates a new NodePublishBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * Publishes a node.
   *
   * @param NodeInterface $node
   * @return int
   */
  protected function publishNode($node) {
    $node->setPublished(TRUE);
    return $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data, $fids = array()) {
    /** @var NodeInterface $node */
//    $node = $this->nodeStorage->load($data->nid);
//    if (!$node->isPublished() && $node instanceof NodeInterface) {
//      return $this->publishNode($node);
//    }

    //    $item->item = array('sid' => $sid,'server_product'=>$server_product, $pid);
    $this->ConvertNode($data->sid,$data->server_product,$data->pid);

    drupal_set_message('Yes called here in queue base.');

  }

  public function ConvertNode($sid, $server_product, $pid){
    $node = $this->nodeStorage->load(462);
    $node->field_base_product_id->setValue(array('value' => date("H:i:s")));
    $node->save();
  }

}
