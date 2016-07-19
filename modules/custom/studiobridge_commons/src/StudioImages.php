<?php

namespace Drupal\studiobridge_commons;

use \Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

Class StudioImages {

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
  public static function AddFileTransfer($fid, $pid, $sid=0,$cid=0) {
    db_insert('studio_file_transfers')
      ->fields(array(
        'fid' => $fid,
        'pid' => $pid,
        'sid' => $sid,
        'cid' => $cid,
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
  public static function DeleteFileTransfer($id) {
    db_delete('studio_file_transfers')
      //->condition('type', $entity->getEntityTypeId())
      ->condition('fid', $id)
      ->execute();
  }

  /*
   *
   */
  public static function ImagePhysicalName($dir, $filename, $fileObj){
    $folder = "public://$dir";
    if(file_prepare_directory($folder, FILE_CREATE_DIRECTORY)){
      //\Drupal::logger('GGG')->notice('');
      $uri = $folder.'/'.$filename;
      //file_build_uri();
      //return file_move($fileObj, $uri, FILE_EXISTS_REPLACE);
      return file_copy($fileObj, $uri, FILE_EXISTS_RENAME);
    }

  }

  public static function UpdateFileLog($fid,$uri){

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
  public static function FullShootImage($product, $fid){
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
  public static function TagImage($image, $tag=1, $session_id){

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


  public static function ImgUpdate($file, $session_id,$field_base_product_id,$i,$concept, $color_variant, $tag = false){
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
      $filename = $color_variant . '_' . $i . ".$filemime";
    }

    $dir = $session_id.'/'.$concept.'/'.$color_variant;

    if(StudioImages::ImagePhysicalName($dir,$filename,$file)){
    }

  }


  public static function PhysicalImageName($product, $sid){

    // $this->products = $this->nodeStorage->loadMultiple($this->pids);

    $concept = 'InValidConcept';
    $color_variant = 'InValidColorVariant';
    $product_bundle = $product->bundle();

    // Get base product id from mapped product.
    // Get identifier from unmapped product.
    if ($product_bundle == 'products') {
      $field_base_product_id = $product->field_base_product_id->getValue();
      if ($field_base_product_id) {
        $field_base_product_id = $field_base_product_id[0]['value'];
      }

      $product_concept = $product->field_concept_name->getValue();
      if($product_concept){
        $concept = $product_concept[0]['value'];
      }

      $product_color_variant = $product->field_color_variant->getValue();
      if($product_color_variant){
        $color_variant = $product_color_variant[0]['value'];
      }else{
        $color_variant = $field_base_product_id;
      }
    }
    elseif ($product_bundle == 'unmapped_products') {
      $field_identifier = $product->field_identifier->getValue();
      $title = $product->title->getValue();
      if ($field_identifier) {
        $field_base_product_id = $field_identifier[0]['value'];
      }elseif ($title) {
        $field_base_product_id = $title[0]['value'];
      }

      $concept = 'Unmapped';
      $color_variant = $field_base_product_id;
    }

    // Get images field from product.
    $images = $product->field_images->getValue();

    // push to last in row.
    $tag_img = \Drupal::state()->get('Image_tag' . '_' . $sid,false);

    // make sure both values are set.
    if ($field_base_product_id && $images) {
      $i = 1;
      foreach ($images as $img) {
        // load file entity.
        $file = File::load($img['target_id']);

        $session_id = $sid;

        if ($file && $session_id) {

          $tag = $file->field_tag->getValue();
          $tagged = $tag[0]['value'];

          //$file_name = $file->filename->getValue();
          if($tagged){
            StudioImages::ImgUpdate($file, $sid,$field_base_product_id,$i,$concept, $color_variant, true);
            continue;
          }else{
            StudioImages::ImgUpdate($file, $session_id,$field_base_product_id,$i,$concept, $color_variant,false);
            $i++;
          }

        }
      }

//      // update tag image to last.
      $a =1;
//      if($tag_img){
//        StudioImages::ImgUpdate(File::load($tag_img), $sid,$field_base_product_id,$i,$concept, $color_variant, true);
//      }

    }


  }


  public static function productImageCopy($file, $session_id,$field_base_product_id,$i,$concept, $color_variant, $tag = false){

    $filemime = 'jpg';
    // todo : filemime will be wrong
    // change file name as per sequence number and base product_id value.
    if($tag){
      $filename = 'Tag.jpg';
    }else{
      $filename = $color_variant . '_' . $i . ".$filemime";
    }

    $dir = $session_id.'/'.$concept.'/'.$color_variant;
    $dirs = array($session_id,$concept,$color_variant);

    //StudioImages::ImagePhysicalName($dir,$filename,$file);

    $config = \Drupal::config('studiobridge_global_settings.studiosettings');
    $img_dest = $config->get('image_destination');

    $file_uri = $file->getFileUri();
    $source = drupal_realpath($file_uri);
    $drupal_public_file_path = drupal_realpath('public://');
    $drupal_public_file_path_file_name = $drupal_public_file_path.'/'.$dir.'/'.$filename;

    $drupal_dest_dir = $drupal_public_file_path.'/'.$dir;
    $is_drupal_dir_writable = is_dir($drupal_dest_dir) && is_writable($drupal_dest_dir);



    // check destination was set in global configs.
    if($img_dest){

      $is_writable = is_dir($img_dest) && is_writable($img_dest);
      // check destination is writable
      if($is_writable){

        $destination_dir = $img_dest.''.$dir;

        // check directory exists or not.
        $is_destination_dir_writable = is_dir($destination_dir) && is_writable($destination_dir);
        if(!$is_destination_dir_writable){
          mkdir($destination_dir,0777,true);
          chmod($destination_dir,0777);
        }

        $destination = $destination_dir.'/'.$filename;

//        if($tag && file_exists($destination)){
//          $actual_name = pathinfo($destination,PATHINFO_FILENAME);
//          $extension = pathinfo($destination, PATHINFO_EXTENSION);
//          $destination = $img_dest.''.$actual_name.'_1.'.$extension;
//        }

        // on failure copy try to copy to drupal public folder.
        if(!copy($source,$destination)){

          if(!$is_drupal_dir_writable){
            mkdir($drupal_dest_dir,0777,true);
            chmod($drupal_dest_dir,0777);
          }

          // on failure copy try to copy to drupal public folder.(but by drupal copy methods)
          if(!copy($source,$drupal_public_file_path_file_name)){
            StudioImages::ImagePhysicalName($dir,$filename,$file);
          }

        }

      }else{

        if(!$is_drupal_dir_writable){
          mkdir($drupal_dest_dir,0777,true);
          chmod($drupal_dest_dir,0777);
        }

        if(!copy($source,$drupal_public_file_path_file_name)){
          StudioImages::ImagePhysicalName($dir,$filename,$file);
        }

      }

    }else{

      if(!$is_drupal_dir_writable){
        mkdir($drupal_dest_dir,0777,true);
        chmod($drupal_dest_dir,0777);
      }

      // on failure copy try to copy to drupal public folder.(but by drupal copy methods)
      if(!copy($source,$drupal_public_file_path_file_name)){
        StudioImages::ImagePhysicalName($dir,$filename,$file);
      }
    }






  }



}
