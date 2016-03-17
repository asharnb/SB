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
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StudioBridgeLiveShootingForm extends FormBase {

  public function getFormId() {
    return 'studiobridge_live_shoot_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // todo : current open session
    $session_id = studiobridge_store_images_open_session_recent();
    // todo : if no session found then redirect to some other page
    if(!$session_id){
      drupal_set_message('No open sessions found','warning');
      return new RedirectResponse('/view-sessions');
    }

    //echo '<pre>'; print_r($_GET); die;
    $identifier_hidden = '';

    // On page load we need to identify that current open product
    $node = studiobridge_store_images_get_open_product();
    if($node){
      //print_r($node->title->getValue());
      $identifier_hidden = $node->getTitle();
    }

    // todo : identifier might available in query
    // todo : get default product (current open product last)
    if(!empty($_GET['identifier']) && isset($_GET['reshoot'])){
      $identifier_hidden = $_GET['identifier'];
    }

    $identifier = $identifier_hidden;

    $form['identifier'] = array(
      '#type' => 'textfield',
      '#title' => 'Scan product',
      //'#autocomplete_route_name' => 'studiobridge_live_shoot_page.autocomplete',
      '#description' => $this->t('description will come here'),
      '#default_value' => $identifier,
    );

    $form['identifier_hidden'] = array(
      '#type' => 'hidden',
      '#value' => $identifier_hidden,
      '#default_value' => $identifier_hidden,
    );

    $form['random_user'] = array(
      '#type' => 'button',
      '#value' => 'Apply',
      '#suffix' => '<div id="studio-img-container"></div><div id="tmp-delete"></div>',
      '#ajax' => array(
        'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::productGetOrUpdateCallback',
        //'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::randomUsernameCallback',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          //'type' => 'bar',
          'message' => 'Getting Product',
        ),
      ),
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Nothing Submitted. Just an Example.');
  }

  public function productGetOrUpdateCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $reshoot = false;

    // todo : current open session
    $session_id = studiobridge_store_images_open_session_recent();
    // todo : if no session found then redirect to some other page
    if(!$session_id){
      //return new TrustedRedirectResponse('/sessions');
      return new RedirectResponse('/sessions');
    }

    $identifier = $form_state->getValue('identifier');
    $identifier_old = $form_state->getValue('identifier_hidden');  // @note : this will be the recent product.

    if (empty(trim($identifier))) {
      $ajax_response->addCommand(new HtmlCommand('#studio-img-container', 'No identifier entered'));
      $ajax_response->addCommand(new InvokeCommand('#studio-img-container', 'css', array('color', 'red')));
      return $ajax_response;
    }

    if($identifier != $identifier_old){
      // todo : update last product as closed status
      studiobridge_store_images_update_product_as_closed($identifier_old);
    }

    // todo : if identifier found in our products then skip importing

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('products','unmapped_products'),'IN')
      ->sort('created', 'DESC')
      ->condition('title', $identifier) // todo : title will be changed as per response
      //->condition('field_status','open')
      ->range(0, 1)
      ->execute();

    if (!$result) {
      // Get product from server
      $product = self::getProductExternal($identifier);
      // validate product
      if(isset($product->msg)){
        // product not found on the server so save it as unmapped product.
        studiobridge_store_images_create_unmapped_product(array(),$session_id,$identifier);
      }else{
        // import it in our drupal
        $new_product = self::createNodeProduct($product, $identifier);
        $new_or_old_product_nid = $new_product->id();
      }
    }
    else {
      // todo : code for product re shoot
      $new_or_old_product_nid = reset($result);
      // todo : update node by re shoot
      if($identifier != $identifier_old){
        $reshoot = true;
      }
      studiobridge_store_images_update_product_as_open($identifier);
    }

    // If current product is reshoot then prompt user to confirm
    if($reshoot){
      $inject_script = '<script>
        var result = confirm("Do you want to reshoot this product ?")
        if (result) {
          window.location="'.base_path().'live-shooting-page1?reshoot&identifier='.$identifier.'"
        }
        </script>';
    }else{
      $inject_script = '';
    }

    $block = \Drupal::service('renderer')
      ->renderPlain(views_embed_view('individual_project_view', 'block_2', $new_or_old_product_nid), FALSE);
    $block = (string) $block;

    $ajax_response->addCommand(new HtmlCommand('#studio-img-container', $block));
    $ajax_response->addCommand(new HtmlCommand('#tmp-delete', $inject_script));
    $ajax_response->addCommand(new InvokeCommand('#studio-img-container', 'css', array('color', 'red')));
    $ajax_response->addCommand(new InvokeCommand('#edit-identifier-hidden', 'val', array($identifier)));
    $ajax_response->addCommand(new InvokeCommand('#edit-identifier-hidden', 'change'));

    return $ajax_response;
  }

  public function randomUsernameCallback(array &$form, FormStateInterface $form_state) {
    // todo :: need to call external api to get products
    $all_nodes = entity_load_multiple('node');
    array_shift($all_nodes);
    $random_node = $all_nodes[array_rand($all_nodes)];
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name', 'val', array(
      $random_node->get('title')
        ->getString()
    )));
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name', 'change'));

    return $ajax_response;
  }

  /*
   * @todo : product should lookup
   */
  public function getProductExternal($input) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "http://staging.dreamcms.me/service/product-data?sku_id=$input",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return NULL;
    }
    else {
      return json_decode($response);
    }
  }

  /*
   * Helper function to create unmapped products.
   */
  public function createNodeProduct($product, $identifier) {
    if (is_object($product[0])) {
      $title = $product[0]->field_style_no_value;
    }
    $values = array(
      'nid' => NULL,
      'type' => 'products',
      'title' => $identifier,
      'uid' => 1,
      'status' => TRUE,
      //'field_images' => $image
    );
    $node = \Drupal::entityManager()->getStorage('node')->create($values);
    $node_created = $node->save();
    // todo : add exceptions
    return $node;
    //studiobridge_store_images_add_product_to_session($session_id, $node);
  }

}
