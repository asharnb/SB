<?php

/**
 * @file
 * Contains \Drupal\studiobridge_live_shoot_page\Plugin\Block\CurrentSessionViewBlock.
 */

namespace Drupal\studiobridge_live_shoot_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\node\Entity\Node;


/**
 * Provides a 'CurrentSessionViewBlock' block.
 *
 * @Block(
 *  id = "current_session_view_block",
 *  admin_label = @Translation("Current session view block"),
 * )
 */
class CurrentSessionViewBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['current_session_view_block']['#cache']['max-age'] = 0;

    $session_id = studiobridge_store_images_open_session_recent();

    if(!$session_id){
      $build['current_session_view_block']['#markup'] = '--NO session found--' . date('Y m d - H:i:s');
    }else{
      $session = Node::load($session_id);
      //$session = $session->toArray();
      $title = $session->title->getValue();
      $status = $session->field_status->getValue();
      $status = isset($status[0]['value']) ? $status[0]['value'] : '';
      $products =  $session->field_product->getValue();

      $output = '<div id="current-open-session-container">';
        $output .= '<div id="current-open-session-div1">';
          $output .= '<h4>Session</h4>';
          $output .= '<span>'.$status.'</span>';
        $output .= '</div>';
        $output .= '<div id="current-open-session-div2">';
          $output .= '<h4>Current Session</h4>';
          $output .= '<span id="current-open-session-session-name">'.$title[0]['value'].'</span>';
        $output .= '</div>';
      $output .= '<div id="current-open-session-div2">';
        $output .= '<h4>Total Products</h4>';
        $output .= '<span id="current-open-session-session-name">'.count($products).'</span>';
      $output .= '</div>';

      $output .= '</div>';

      //$build['current_session_view_block']['#markup'] = '<pre>'.print_r($session->getTitle()).'</pre>';
      $build['current_session_view_block']['#markup'] = $output;
    }

    return $build;
  }

}
