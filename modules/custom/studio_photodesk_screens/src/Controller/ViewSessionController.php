<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewSessionController.
 */

namespace Drupal\studio_photodesk_screens\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ViewSessionController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class ViewSessionController extends ControllerBase {
  /**
   * Content.
   *
   * @param $session
   *   session nid.
   * @return array
   *   Return session data.
   */
  public function content($session) {
    // build the variables here.


    return [
      '#theme' => 'view_session',
      '#cache' => ['max-age' => 0],
      '#session' => [
        'data' => $this->t('Test Value' . $session),
        'data1' => 'Lipsum text here'
      ],
    ];
  }

}
