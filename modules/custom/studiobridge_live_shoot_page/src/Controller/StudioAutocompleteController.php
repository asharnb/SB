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

/**
 * Returns autocomplete responses for scanning products.
 */
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

  /**
   * Retrieves suggestions for products autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    // todo : ask external resource to get products.
    $input = $request->query->get('q');
    $matches = array();

    $matches[] = array('value' => 'value1', 'label' => Html::escape('Value1'));
    $matches[] = array('value' => 'value12', 'label' => Html::escape('Value2'));
    return new JsonResponse($matches);
  }

}
