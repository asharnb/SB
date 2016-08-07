<?php

/**
 * @file
 * Contains Drupal\studiobridge_global_settings\Form\StudioSettingsForm.
 */

namespace Drupal\studiobridge_global_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StudioSettingsForm.
 *
 * @package Drupal\studiobridge_global_settings\Form
 */
class StudioSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'studiobridge_global_settings.studiosettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'studio_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('studiobridge_global_settings.studiosettings');

    $form['product_server_creds'] = array(
      '#type' => 'fieldset',
      '#title' => t('Product lookup server creds'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['other'] = array(
      '#type' => 'fieldset',
      '#title' => t('Other settings'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );


    $form['product_server_creds']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Enter server url to check products. Ex: http://beta.contentcentral.co/service/product-data?_format=json&product_identifier='),
      '#maxlength' => 200,
      '#size' => 200,
      '#default_value' => $config->get('url'),
    ];
    $form['product_server_creds']['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Name'),
      '#description' => $this->t('Enter server user name to authenticate'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('user_name'),
    ];
    $form['product_server_creds']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('password'),
    ];
    $form['other']['image_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination folder for saving images?'),
      '#description' => $this->t('Example : /var/www/studio/files/'),
      '#maxlength' => 200,
      '#size' => 200,
      '#default_value' => $config->get('image_destination'),
    ];

    $form['shootlist_email_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Shootlist: Email settings'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['shootlist_email_settings']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Available replaceable tokens  : <b>@session_name@</b>  - Name of the session'),
      '#default_value' => !empty($config->get('subject')) ? $config->get('subject'): 'Session closed : @session_name@',
    ];

    $form['shootlist_email_settings']['to_email'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CC all shootlists to'),
      '#description' => $this->t('Example : 	krishnakanth@valuebound.com, ashar.babar@landmarkgroup.com'),
      '#default_value' => !empty($config->get('to_email')) ? $config->get('to_email'): 'krishnakanth@valuebound.com, ashar.babar@landmarkgroup.com',
    ];

    $form['shootlist_email_settings']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this->t('Available replaceable tokens  : <b>@session_name@</b>  - Name of the session, <b>@shootlist_link@</b> - Link for shootlist file.'),
      '#default_value' => $config->get('body'),
    ];


    $form['shootlist_email_concepts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Shootlist: Email settings per concept'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['shootlist_email_concepts']['splash_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Splash Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('splash_email')) ? $config->get('splash_email'): '',
    ];
    $form['shootlist_email_concepts']['max_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('max_email')) ? $config->get('max_email'): '',
    ];
    $form['shootlist_email_concepts']['babyshop_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Babyshop Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('babyshop_email')) ? $config->get('babyshop_email'): '',
    ];
    $form['shootlist_email_concepts']['lifestyle_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lifestyle Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('lifestyle_email')) ? $config->get('lifestyle_email'): '',
    ];

    $form['shootlist_email_concepts']['homecentre_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Homecentre Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('homecentre_email')) ? $config->get('homecentre_email'): '',
    ];

    $form['shootlist_email_concepts']['shoemart_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shoemart Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('shoemart_email')) ? $config->get('shoemart_email'): '',
    ];

    $form['shootlist_email_concepts']['unmapped_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unmapped Email'),
      '#description' => $this->t('Email address of person processing concept shootlist'),
      '#default_value' => !empty($config->get('unmapped_email')) ? $config->get('unmapped_email'): '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $directory = $form_state->getValue('image_destination');
    if(!$directory){}else{
    $is_writable = is_dir($directory) && is_writable($directory);
    if(!$is_writable){
      $form_state->setErrorByName('image_destination','Directory not writable(Should have 0777 permissions) OR directory not exists');
    }
  }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('studiobridge_global_settings.studiosettings')
      ->set('url', $form_state->getValue('url'))
      ->set('user_name', $form_state->getValue('user_name'))
      ->set('password', $form_state->getValue('password'))
      ->set('image_destination', $form_state->getValue('image_destination'))
      ->set('subject', $form_state->getValue('subject'))
      ->set('body', $form_state->getValue('body'))
      ->set('to_email', $form_state->getValue('to_email'))

      ->set('splash_email', $form_state->getValue('splash_email'))
      ->set('max_email', $form_state->getValue('max_email'))
      ->set('lifestyle_email', $form_state->getValue('lifestyle_email'))
      ->set('homecentre_email', $form_state->getValue('homecentre_email'))
      ->set('babyshop_email', $form_state->getValue('babyshop_email'))
      ->set('shoemart_email', $form_state->getValue('shoemart_email'))
      ->set('unmapped_email', $form_state->getValue('unmapped_email'))
      ->save();
  }

}
