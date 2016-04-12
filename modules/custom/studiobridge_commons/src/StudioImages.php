<?php

namespace Drupal\studiobridge_commons;

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
  public static function AddFileTransfer($fid, $pid, $sid) {
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
  public static function DeleteFileTransfer($id) {
    db_delete('studio_file_transfers')
      //->condition('type', $entity->getEntityTypeId())
      ->condition('fid', $id)
      ->execute();
  }

  /*
   *
   */
  public static function ImagePhysicalName($dir, $filename, &$fileObj){
    $folder = "public://$dir";
    if(file_prepare_directory($folder, FILE_CREATE_DIRECTORY)){
      $uri = $folder.'/'.$filename;
      //file_build_uri();
      return file_move($fileObj, $uri, FILE_EXISTS_REPLACE);
    }

  }

  public static function UpdateFileLog($fid,$uri){

    $fields = array(
      'uri' => $uri,
    );
    $query = \Drupal::database()->update('studio_file_transfers')
      ->fields($fields)
      ->condition('fid', $fid);
    $query->execute();

  }

}