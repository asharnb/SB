<?php

namespace Drupal\studiobridge_commons;

Class Sessions {

  /*
   * Helper function, to return open session for current loggedIn photographer.
   */
  public static function openSessionRecent() {
    // Get current logged in user.
    $user = \Drupal::currentUser();
    // Get uid of user.
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
   *
   * @param uid
   *   User uid.
   */
  public static function getSessionByUid($uid) {
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
  public static function openSessionsAll() {
    $result = \Drupal::entityQuery('node')
      ->condition('type', 'sessions')
      ->sort('created', 'DESC')
      ->condition('field_status', 'open') // todo : poc on structure.
      ->range(0, 100)
      ->execute();
    return $result;
  }

}