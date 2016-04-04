<?php

namespace Drupal\studiobridge_commons;

Class StudioImages {

  /*
   * Helper function, to insert log into {studio_file_transfers} table.
   */
  public static function AddFileTransfer($fid,$pid, $sid){
    db_insert('studio_file_transfers')
      ->fields(array(
        'fid' => $fid,
        'pid' => $pid,
        'sid' => $sid,
        'created' => REQUEST_TIME,
      ))
      ->execute();
  }

  public static function DeleteFileTransfer($id){
    db_delete('studio_file_transfers')
      //->condition('type', $entity->getEntityTypeId())
      ->condition('id', $id)
      ->execute();
  }

}