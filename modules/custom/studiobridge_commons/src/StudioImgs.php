<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioImgs.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

/**
 * Class StudioImgs.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioImgs implements StudioImgsInterface {
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
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser = array();

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The node storage service.
   */
  protected $fileStorage;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, AccountProxyInterface $current_user, StateInterface $state) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->state = $state;

  }

  /*
 * Helper function, to insert log into {studio_file_transfers} table.
 *
 * @param fid
 *   File object fid.
 * @param pid
 *   Product node nid.
 * @param sid
 *   Session node nid.
 */
  public function AddFileTransfer($fid, $pid, $sid = 0, $cid = 0, $weight=0) {
    $this->database->insert('studio_file_transfers')
      ->fields(array(
        'fid' => $fid,
        'pid' => $pid,
        'sid' => $sid,
        'cid' => $cid,
        'created' => REQUEST_TIME,
        'weight' => $weight,
      ))
      ->execute();

    // Move it to service :todo
    \Drupal::logger('StudioImages Logs')->notice('New file log saved - ' . $fid);

  }

  /*
   * Helper function, to delete log from {studio_file_transfers} table.
   *
   * @param id
   *   id of {studio_file_transfers} table row.
   */
  public function DeleteFileTransfer($id) {
    $this->database->delete('studio_file_transfers')
      //->condition('type', $entity->getEntityTypeId())
      ->condition('fid', $id)
      ->execute();
  }

  /*
   *  Helper function, to move file from location.
   */
  public function ImagePhysicalName($dir, $filename, $fileObj) {
    $folder = "public://$dir";
    if (file_prepare_directory($folder, FILE_CREATE_DIRECTORY)) {
      //\Drupal::logger('GGG')->notice('');
      $uri = $folder . '/' . $filename;
      //file_build_uri();
      return file_move($fileObj, $uri, FILE_EXISTS_REPLACE);
    }

  }

  /*
   * Helper function, to update file uri.
   */
  public function UpdateFileLog($fid, $uri) {

    $fields = array(
      'uri' => $uri,
    );
    $query = $this->database->update('file_managed')
      //$query = \Drupal::database()->update('file_managed')
      ->fields($fields)
      ->condition('fid', $fid);
    $query->execute();

  }

  /*
   * @param product
   *   Product node object
   * @param fid
   *   Image fid
   */
  public function FullShootImage($product, $fid) {
    $images = $product->field_images->getValue();
    $image = array('target_id' => $fid);

    // Get the available images

    // add new image to existing
    if (count($images)) {
      $images = array_merge($images, array($image));
    }
    else {
      $image = array(0 => $image);
      $images = array_merge($images, $image);
      //$images = array_push($images,$image);
    }

    $product->field_images->setValue($images);
    $product->save();
  }

  /*
   *  Helper function, To tag image as tagged.
   */
  public function TagImage($image, $tag = 1, $session_id) {
    $file = $this->fileStorage->load($image);
    $file->field_tag->setValue($tag);
    $file->save();
  }

  /*
   * Helper function, to update image physical path.
   */
  public function ImgUpdate($file, $session_id, $field_base_product_id, $i, $concept, $color_variant, $tag = FALSE) {
    //    $filemime = $filemime[0]['value'];
    //    $filemime = explode('/', $filemime);
    //    $filemime = $filemime[1];
    //    if ($filemime == 'octet-stream') {
    $filemime = 'jpg';
    //    }
    // todo : filemime will be wrong
    // change file name as per sequence number and base product_id value.
    if ($tag) {
      $filename = 'Tag.jpg';
    }
    else {
      $filename = $field_base_product_id . '_' . $i . ".$filemime";
    }

    $dir = $session_id . '/' . $concept . '/' . $color_variant;

    //if(StudioImages::ImagePhysicalName($dir,$filename,$file)){
    if ($this->ImagePhysicalName($dir, $filename, $file)) {
      $folder = "public://$dir";
      $uri = $folder . '/' . $filename;
      $file->uri->setValue($uri); //public://fileKVxEHe
    }
    $file->filename->setValue($filename);
    $file->save();
    //
    $folder = "public://$dir";
    $uri = $folder . '/' . $filename;
    $this->UpdateFileLog($file->id(), $uri);

  }

  /*
   * Helper function to get deleted images count.
   *
   * @param pid
   *  product pid
   */
  public function getDeletedImgsCount($pid){
    $product = $this->nodeStorage->load($pid);
    if($product){
      $images = $product->field_images->getValue();
      $images_count = count($images);

      $result = $this->database->select('studio_file_transfers', 'spsp')
        ->fields('spsp', array('id'))
        ->condition('spsp.pid', $pid);
      $result = $result->execute()->fetchAll();
      $result_count = count($result);
      return $result_count - $images_count;
    }
    return 0;
  }

  /*
 *
 */
  public static function deleteImagesFromNodes($fid){
    $a = 1;
    $result = \Drupal::database()->select('studio_file_transfers', 'spsp')
      ->fields('spsp', array('pid'))
      ->condition('spsp.fid', $fid);
    $result = $result->execute()->fetchAll();

    if($result){
      foreach($result as $pid){
        if($pid->pid){

          $node = Node::load($pid->pid);
          $field_images = $node->field_images->getValue();

          if($field_images){

            foreach($field_images as $key=>$image){
              if($image['target_id'] == $fid){
                unset($field_images[$key]);
              }
            }

            // Set the image array to image field
            $node->field_images->setValue($field_images);


            // finally save the node
            $node->save();

          }

        }
      }
    }

  }

  /*
  * Helper function, to insert/update weight into {studio_file_transfers} table.
  *
  * @param fid
  *   File object fid.
  * @param pid
  *   Product node nid.
  * @param sid
  *   Session node nid.
  */
  public function updateWeightToFileTransfer($fid, $pid, $sid = 0, $cid = 0, $weight) {

    $fields = array(
      'weight' => $weight,
    );

    $query = $this->database->update('studio_file_transfers')
      ->fields($fields)
      ->condition('fid', $fid)
      ->condition('pid', $pid);
    if($sid){
      $query->condition('sid', $sid);
    }elseif($cid){
      $query->condition('cid', $cid);
    }

    $update_status = $query->execute();
    if(!$update_status){
      $this->AddFileTransfer($fid, $pid, $sid, $cid = 0, $weight);
    }

  }

  /*
   *
   */
  public function getProductImagesOfSessionFromFileLogs($sid, $pid){

    $result = $this->database->select('studio_file_transfers', 'spsp')
      ->fields('spsp', array('fid'))
      ->condition('spsp.pid', $pid)
      ->condition('spsp.sid', $sid)
      ->orderBy('spsp.weight', 'asc');
    return $result->execute()->fetchAll();

  }

}
