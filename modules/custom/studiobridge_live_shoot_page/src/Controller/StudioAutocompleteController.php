<?php

/**
 * @file
 * Contains \Drupal\studiobridge_live_shoot_page\Controller\StudioAutocompleteController.
 */

namespace Drupal\studiobridge_live_shoot_page\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class StudioAutocompleteController implements ContainerInjectionInterface {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // todo : create service class for this
    return new static(
        $container->get('plugin.manager.block')
    );
  }
  public function content() {
    $form = \Drupal::formBuilder()->getForm('Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm');
    //$aa = drupal_render($form);
    return array(
        '#theme' => 'sbtheme_page',
        '#form' => $form,
        '#attached' => array(
            'library' =>  array(
                'core/jquery.ui.sortable'
            )
        ),
    );
  }
}