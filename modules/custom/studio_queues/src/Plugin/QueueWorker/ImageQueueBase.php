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
   * {@inheritdoc}
   */
  public function processItem($data, $fids = array()) {
    //    $item->item = array('sid' => $sid,'server_product'=>$server_product, $pid);
    $this->ConvertNode($data->item['sid'],$data->item['server_product'],$data->item['pid']);
    //drupal_set_message('Yes called here in queue base.');
  }

  public function ConvertNode($sid, $server_product, $pid){
    $field_size_variant = array();
    $field_size_name = array();
    $field_barcode = array();

    if ($server_product->barcode) {
      foreach ($server_product->barcode as $barcode) {
        //$field_size_variant[] = ['value' => $barcode];
        $tmp1 = array('value' => $barcode);
        array_push($field_barcode, $tmp1);
      }
    }

    if ($server_product->size_name) {
      foreach ($server_product->size_name as $sn) {
        //$field_size_variant[] = ['value' => $barcode];
        $tmp2 = array('value' => $sn);
        array_push($field_size_name, $tmp2);
      }
    }

    if ($server_product->size_variant) {
      foreach ($server_product->size_variant as $sv) {
        //$field_size_variant[] = ['value' => $barcode];
        $tmp3 = array('value' => $sv);
        array_push($field_size_variant, $tmp3);
      }
    }


    $node = $this->nodeStorage->load($pid);

    $node->field_base_product_id->setValue(array('value' => $server_product->base_product_id));
    $node->field_style_family->setValue(array('value' => $server_product->style_no));
    $node->field_concept_name->setValue(array('value' => $server_product->concept));
    $node->field_gender->setValue(array('value' => $server_product->gender));

    $node->field_description->setValue(array('value' => $server_product->description));
    $node->field_color_variant->setValue(array('value' => $server_product->color_variant));
    $node->field_color_name->setValue(array('value' => $server_product->color_name));

    $node->field_size_name->setValue($field_size_name);
    $node->field_size_variant->setValue($field_size_variant);
    $node->field_barcode->setValue($field_barcode);

    $node->save();
  }

}
