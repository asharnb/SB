<?php

/**
 * @file
 * Contains Drupal\studiobridge_live_shoot_page\StudioBridgeLiveShootingForm
 *
 * @Note : As of now this are only development code
 */

namespace Drupal\studiobridge_live_shoot_page\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

class StudioBridgeLiveShootingForm extends FormBase {

    public function getFormId() {
        return 'studiobridge_live_shoot_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['block_category1'] = array(
            '#type' => 'textfield',
            '#title' => 'Scan product',
            '#autocomplete_route_name' => 'studiobridge_live_shoot_page.autocomplete',
            '#description' => $this->t('description will come here'),
            '#default_value' => 'abcd',
        );

//        $users = entity_load_multiple('user');
//        $default_value = EntityAutocomplete::getEntityLabels($users);
//        $form['value'] = array(
//            '#type' => 'entity_autocomplete',
//            '#title' => $this->t('Usernames'),
//            '#description' => $this->t('Enter a comma separated list of user names.'),
//            '#target_type' => 'node',
//            '#tags' => TRUE,
//            '#default_value' => $default_value,
//            '#process_default_value' => FALSE,
//        );

        $form['user_name'] = array(
            '#type' => 'textfield',
            '#title' => 'Scan Product Tag',
            '#description' => 'Please enter in a product',

            '#ajax' => array(
                'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::usernameValidateCallback',
                'effect' => 'fade',
                'event' => 'change',
                'progress' => array(
                    'type' => 'throbber',
                    'message' => NULL,
                ),
            ),
        );

        $form['random_user'] = array(
            '#type' => 'button',
            '#value' => 'Apply',
            '#suffix' => '<div id="studio-img-container"></div>',
            '#ajax' => array(
                'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::randomUsernameCallback',
                'event' => 'click',
                'progress' => array(
                    'type' => 'throbber',
                    'message' => 'Getting Product',
                ),

            ),
        );
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        drupal_set_message('Nothing Submitted. Just an Example.');
    }

    public function usernameValidateCallback(array &$form, FormStateInterface $form_state) {
        $ajax_response = new AjaxResponse();

        if (user_load_by_name($form_state->getValue('user_name')) && $form_state->getValue('user_name') != false) {
            $text = 'User Found';
            $color = 'green';
        } else {
            $text = 'No User Found';
            $color = 'red';
        }

        $ajax_response->addCommand(new HtmlCommand('#studio-img-container', $text));
        $ajax_response->addCommand(new InvokeCommand('#studio-img-container', 'css', array('color', $color)));

        return $ajax_response;
    }

    public function randomUsernameCallback(array &$form, FormStateInterface $form_state) {
        // todo :: need to call external api to get products
        $all_nodes = entity_load_multiple('node');
        array_shift($all_nodes);
        $random_node = $all_nodes[array_rand($all_nodes)];
        $ajax_response = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand('#edit-user-name', 'val' , array($random_node->get('title')->getString())));
        $ajax_response->addCommand(new InvokeCommand('#edit-user-name', 'change'));

        return $ajax_response;
    }
}
