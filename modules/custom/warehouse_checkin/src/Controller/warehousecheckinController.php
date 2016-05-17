<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Controller\ViewSessionController.
 */

namespace Drupal\warehouse_checkin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class ViewSessionController.
 *
 * @package Drupal\studio_photodesk_screens\Controller
 */
class warehousecheckinController extends ControllerBase
{
    public static function create(ContainerInterface $container) {
        // todo : create service class for this
        return new static(

        );
    }
        //todos:
        //handle all checkin at the warehouse level
    public function content() {
        //$aa = drupal_render($form);
        return [
            '#theme' => 'checkin',
            '#cache' => ['max-age' => 0],
            '#attached' => array(
                'library' =>  array(
                    'warehouse_checkin/checkin-form',

                )
            ),

        ];
    }
}
