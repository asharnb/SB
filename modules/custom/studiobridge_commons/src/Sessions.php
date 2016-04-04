<?php

namespace Drupal\studiobridge_commons;

Class Sessions {

  /*
   * Helper function to return open session for current loggedIn photographer.
   *
   * todo : remove -   replacement of studiobridge_store_images_open_session_recent()
   */
  public static function openSessionRecent() {
    $user = \Drupal::currentUser();
    $uid = $user->id();

    $result = \Drupal::entityQuery('node')
      ->condition('type', 'sessions')
      ->sort('created', 'DESC')
      ->condition('field_status', 'open') // todo : poc on structure.
      ->condition('uid', $uid)
      ->range(0, 1)
      ->execute();
    if (count($result)) {
      return $node_id = reset($result);
    }
    return FALSE;
  }

  /*
   * Helper function, to get session by its author.
   */
  public static function getSessionByUid($uid){
    return \Drupal::entityQuery('node')
      ->condition('type', 'sessions')
      ->sort('created', 'DESC')
      ->condition('field_status', 'open') // todo : poc on structure.
      ->condition('uid', $uid)
      ->range(0, 1)
      ->execute();
  }

  /*
   * Helper function, to return all open sessions.
   */
  public static function openSessionsAll(){
    $result = \Drupal::entityQuery('node')
      ->condition('type', 'sessions')
      ->sort('created', 'DESC')
      ->condition('field_status','open')  // todo : poc on structure.
      ->range(0,100)
      ->execute();
    return $result;
  }

}