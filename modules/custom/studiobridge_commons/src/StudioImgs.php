<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioImgs.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

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
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->database = $database;

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
  public function AddFileTransfer($fid, $pid, $sid) {
    db_insert('studio_file_transfers')
      ->fields(array(
        'fid' => $fid,
        'pid' => $pid,
        'sid' => $sid,
        'created' => REQUEST_TIME,
      ))
      ->execute();
    \Drupal::logger('StudioImages Logs')->notice('New file log saved - '. $fid);

  }

  /*
   * Helper function, to delete log from {studio_file_transfers} table.
   *
   * @param id
   *   id of {studio_file_transfers} table row.
   */
  public function DeleteFileTransfer($id) {
    db_delete('studio_file_transfers')
      //->condition('type', $entity->getEntityTypeId())
      ->condition('fid', $id)
      ->execute();
  }

  /*
   *
   */
  public function ImagePhysicalName($dir, $filename, $fileObj){
    $folder = "public://$dir";
    if(file_prepare_directory($folder, FILE_CREATE_DIRECTORY)){
      //\Drupal::logger('GGG')->notice('');
      $uri = $folder.'/'.$filename;
      //file_build_uri();
      return file_move($fileObj, $uri, FILE_EXISTS_REPLACE);
    }

  }

  public function UpdateFileLog($fid,$uri){

    $fields = array(
      'uri' => $uri,
    );
    $query = \Drupal::database()->update('file_managed')
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
  public function FullShootImage($product, $fid){
    $images = $product->field_images->getValue();
    $image = array('target_id' => $fid);

    // Get the available images

    // add new image to existing
    if(count($images)){
      $images = array_merge($images, array($image));
    }else{
      $image = array(0=>$image);
      $images = array_merge($images, $image);
      //$images = array_push($images,$image);
    }

    $product->field_images->setValue($images);
    $product->save();
  }

  /*
   *
   */
  public function TagImage($image, $tag=1, $session_id){

    $user = \Drupal::currentUser();
    $uid = $user->id();

    $last_scan_product = \Drupal::state()->get('last_scan_product_' . $uid . '_' . $session_id, false);
//    if($last_scan_product){
//      $product = Node::load($last_scan_product);
//      $images = $product->field_images->getValue();
//
//      $title = $product->title->getValue();
//      $title = $title[0]['value'];
//
//      $count = count($images);
//      if($count == 1){
//        $title = $title.'_1.jpg';
//      }elseif($count > 1){
//
//      }
//    }


    $file = File::load($image);
    //$file->title->setValue('');
    $file->field_tag->setValue($tag);
    $file->save();
  }


  public function ImgUpdate($file, $session_id,$field_base_product_id,$i,$concept, $color_variant, $tag = false){
//    $filemime = $filemime[0]['value'];
//    $filemime = explode('/', $filemime);
//    $filemime = $filemime[1];
//    if ($filemime == 'octet-stream') {
    $filemime = 'jpg';
//    }
    // todo : filemime will be wrong
    // change file name as per sequence number and base product_id value.
    if($tag){
      $filename = 'Tag.jpg';
    }else{
      $filename = $field_base_product_id . '_' . $i . ".$filemime";
    }

    $dir = $session_id.'/'.$concept.'/'.$color_variant;

    if(StudioImages::ImagePhysicalName($dir,$filename,$file)){
      $folder = "public://$dir";
      $uri = $folder.'/'.$filename;
      $file->uri->setValue($uri); //public://fileKVxEHe
    }
    $file->filename->setValue($filename);
    $file->save();
    //
    $folder = "public://$dir";
    $uri = $folder.'/'.$filename;
    StudioImages::UpdateFileLog($file->id(),$uri);

  }

}
