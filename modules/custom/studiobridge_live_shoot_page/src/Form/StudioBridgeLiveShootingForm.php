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
use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\studiobridge_commons\Sessions;
use Drupal\studiobridge_commons\Products;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Entity;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;


class StudioBridgeLiveShootingForm extends FormBase {

  public function getFormId() {
    return 'studiobridge_live_shoot_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    $uid = $user->id();

    // current open session
    $session_id = Sessions::openSessionRecent();

    // if no session found then redirect to some other page
    if(!$session_id){
      drupal_set_message('No open sessions found','warning');
      return new RedirectResponse(base_path().'view-sessions');
    }

    $new_or_old_product_nid =0;

    $identifier_hidden = '';
    $identifier_hidden = \Drupal::state()->get('last_scan_product_'.$uid.'_'.$session_id,false);

    // todo : identifier might available in query
    // todo : get default product (current open product last)
    if(!empty($_GET['identifier']) && isset($_GET['reshoot'])){
      $identifier_hidden = $_GET['identifier'];

      $result = Products::getProductByIdentifier($_GET['identifier']);

      if($result){
        $new_or_old_product_nid = reset($result);
      }else{
        drupal_set_message('Invalid identifier','warning');
        return new RedirectResponse(base_path());
      }

      if($new_or_old_product_nid){
        \Drupal::state()->set('last_scan_product_nid'.$uid.'_'.$session_id,$new_or_old_product_nid);
        //studiobridge_store_images_update_product_as_open($_GET['identifier']);
        Products::updateProductState($_GET['identifier'],'open');
        \Drupal::state()->set('last_scan_product_'.$uid.'_'.$session_id,$_GET['identifier']);
      }
    }else{
      $result = Products::getProductByIdentifier($identifier_hidden);
      if($result){
        $new_or_old_product_nid = reset($result);
      }
    }

    $identifier = $identifier_hidden;


    $list = '<ul id="sortable"></ul>';

    // @ashar : this does not need to be refreshed

    $form['markup_product_details'] = array(
      '#suffix' => '<div id="studio-bridge-product-details"></div>',
    );
    // @ashar : add the ajax call back to the identifier

    $form['identifier'] = array(
      '#type' => 'textfield',
      '#title' => 'Scan product',
      '#description' => $this->t('description will come here'),
      '#default_value' => $identifier,
    );

    $form['identifier_hidden'] = array(
      '#type' => 'hidden',
      '#value' => $identifier_hidden,
      '#default_value' => $identifier_hidden,
    );
    $form['identifier_nid'] = array(
      '#type' => 'hidden',
      '#value' => $new_or_old_product_nid,
      '#default_value' => $new_or_old_product_nid,
    );

    $images = array();
    $pid = \Drupal::state()->get('last_scan_product_nid'.$uid.'_'.$session_id,false);
    if($pid){
      $images = Products::getProductImages($pid);
    }else{

      $result = Products::getProductByIdentifier($identifier);
      if($result){
        $images = Products::getProductImages(reset($result));
      }
    }
// @ashar : seperate this image container so we can apply theme formatting to it

    $form['random_user'] = array(
      '#type' => 'button',
      '#value' => 'Apply',
      //'#suffix' => '<div id="studio-img-container"></div><div id="js-holder"></div><div id="studio-img-container1">'.$block.'</div>',
      '#suffix' => '<div id="studio-img-container"></div><div id="js-holder"></div><a id="studio-resequence-bt" class="btn btn-warning">Resequence</a><div id="msg-up"></div>',
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

    $form['markup_product_details_first'] = array(
      '#suffix' => '<div id="studio-img-container1"><div id="sortable" class="ui-sortable">',
    );

    $i = 1;
    foreach($images as $fid => $src){

        $block = '';
        $block .= '<div class="bulkviewfiles imagefile">';
        $block .= '<div class="box" style="max-width: 250px;">';

        $block .=  '<div class="ribbon"><span id="seq-'. $fid .'">'.$i.'</span></div>';

        $block .=  '<div class="scancontainer">';
        $block .=  '<img src="'.$src['uri'].'" class="scanpicture">';
        $block .=  '</div>';
        $block .=  '<div class="file-name">';
        $block .=  '<span class="bkname"><i class="fa fa-camera"></i><b id="seq-img-'. $fid .'">'.$src['name'].'</b></span>';
        $block .=  '<hr class="simple">';

        $block .= '<div class="row">';
        $block .= '<div class="col col-sm-6">';
        $block .= '<span><a class=" dropdown-toggle label label-default dropdown mr-5" data-toggle="dropdown" ><i class="fa fa-cog"></i> <i class="fa fa-caret-down"></i></a>';
        $block .=	'<ul class="dropdown-menu pull-right"><li><a class="label label-default no-margin" onclick="return false;">Use this as full shot</a></li></ul>';
        $block .= '<span ><a target ="_blank" href="/file/'.$fid.'" class="label label-info"><i class="glyphicon glyphicon-fullscreen"></i></a>';
        $block .= '</div>';
        $block .= '<div class="col col-sm-6">';
        $block .= '<span><a onclick="return false;" class="label label-danger mr5 pull-right">Delete</a>';
        $block .= '</div>';
        $block .= '</div>';

        $block .= '</div>';
        $block .= '</div>';
        //$block .= '</div>';


      $form['markup_product_details_'.$fid] = array(
        '#suffix' => "'$block''",
        '#tree' => TRUE,

      );
      $form['images['.$fid.']'] = array(
        '#type' => 'hidden',
        '#value' => $fid,
      );
      $form['markup_product_details__'.$fid] = array(
        '#suffix' => "</div>",
      );
      $i ++;
    }

    $form['markup_product_details_second'] = array(
      '#suffix' => '</div></div>',
    );

    $form['#attached']['library'][] = 'core/jquery.ui.sortable';
    //$form['#attached']['library'][] = 'studiobridge_store_images/studio-bridge-view-product';

    //$form_state->setRebuild(TRUE);
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $v = $form_state->getValues();
    $form_state->setRebuild(TRUE);

    drupal_set_message('Nothing Submitted. Just an Example.');
  }

  public function productUpdateSeqCallback(array &$form, FormStateInterface $form_state) {
    $v = $form_state->getValues();
  }

  public function productGetOrUpdateCallback(array &$form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    $uid = $user->id();

    // Get current session
    $session_id = Sessions::openSessionRecent();
    // If no session found then redirect to some other page
    if(!$session_id){
      return new RedirectResponse(base_path().'sessions');
    }

    // Generate new ajax response object
    $ajax_response = new AjaxResponse();
    $reshoot = false;
    $is_unmapped_product = false;

    $identifier = $form_state->getValue('identifier');
    $identifier_old = $form_state->getValue('identifier_hidden');  // @note : this will be the recent product.

    $last_scan_product = \Drupal::state()->get('last_scan_product_'.$uid.'_'.$session_id,false);

    if (empty(trim($identifier))) {
      $ajax_response->addCommand(new HtmlCommand('#studio-img-container', 'No identifier entered'));
      $ajax_response->addCommand(new InvokeCommand('#studio-img-container', 'css', array('color', 'red')));
      return $ajax_response;
    }

    //if identifier found in our products then skip importing.
    $result = Products::getProductByIdentifier($identifier);

    if (!$result) {
      // Get product from server
      $product = Products::getProductExternal($identifier);
      $product = json_decode($product);
      // validate product
      if(isset($product->msg)){
        // product not found on the server so save it as unmapped product.
        //studiobridge_store_images_create_unmapped_product(array(),$session_id,$identifier,false);
        Products::createUnmappedProduct(array(),$session_id,$identifier,false);
        $is_unmapped_product = true;
      }else{
        // import it in our drupal.
        $new_product = Products::createMappedProduct($product, $identifier);
        $new_or_old_product_nid = $new_product->id();
      }
    }
    else {
      // todo : code for product re shoot
      $new_or_old_product_nid = reset($result);

      $db =  \Drupal::database();
      $sessions_nids = $db->select('node__field_product','c')
        ->fields('c')
        ->condition('field_product_target_id',$new_or_old_product_nid)
        ->execute()->fetchAll();

      // todo : if count is more than 1
      if(count($sessions_nids)){
        // $session_id
        foreach($sessions_nids as $field){
          if($field->entity_id != $session_id) {
            $reshoot = true;
            break;
          }
        }
      }
      // If current product is reshoot then prompt user to confirm
      if($reshoot && !isset($_GET['reshoot'])){
        $inject_script = '<script>
        var result = confirm("Do you want to reshoot this product ?")
        if (result) {
          window.location="'.base_path().'live-shooting-page1?reshoot&identifier='.$identifier.'"
        }else{
          //window.location="'.base_path().'live-shooting-page1"
          //document.getElementById("edit-identifier").value = "'.$last_scan_product.'";
          //document.getElementById("edit-identifier-hidden").value = "'.$last_scan_product.'";
        }
        </script>';
        // return ajax here.
        $ajax_response->addCommand(new HtmlCommand('#js-holder', $inject_script));
        return $ajax_response;
      }

      // update the current product as open status
      //studiobridge_store_images_update_product_as_open($identifier);
      Products::updateProductState($_GET['identifier'],'open');
    }

    if($last_scan_product != $identifier){
      // todo : update last product as closed status
      Products::updateProductState($last_scan_product,'completted');
    }

    // Once product is scanned update it to session
    if(!$is_unmapped_product){
      //studiobridge_store_images_add_product_to_session($session_id, \Drupal\node\Entity\Node::load($new_or_old_product_nid));
      Products::addProductToSession($session_id, \Drupal\node\Entity\Node::load($new_or_old_product_nid));
    }

    \Drupal::state()->set('last_scan_product_'.$uid.'_'.$session_id,$identifier);

    if($new_or_old_product_nid){
      \Drupal::state()->set('last_scan_product_nid'.$uid.'_'.$session_id,$new_or_old_product_nid);
    }

    $images = Products::getProductImages($new_or_old_product_nid);

    $block = '<div id="sortable" class="ui-sortable">';
    //$block = '';
    $i = 1;
    foreach($images as $fid => $src){
      //$block = '';
      $block .= '<div class="bulkviewfiles imagefile">';
      $block .= '<div class="box" style="max-width: 250px;">';

      $block .=  '<div class="ribbon"><span id="seq-'. $fid .'">'.$i.'</span></div>';

      $block .=  '<div class="scancontainer">';
      $block .=  '<img src="'.$src['uri'].'" class="scanpicture">';
      $block .=  '</div>';
      $block .=  "<input name='image[" . $fid . "]' type='hidden' value='" . $fid . "'/>";

      $block .=  '<div class="file-name">';
      $block .=  '<span class="bkname"><i class="fa fa-camera"></i><b id="seq-img-'. $fid .'">'.$src['name'].'</b></span>';
      $block .=  '<hr class="simple">';

      $block .= '<div class="row">';
      $block .= '<div class="col col-sm-6">';
      $block .= '<span><a class=" dropdown-toggle label label-default dropdown mr-5" data-toggle="dropdown" ><i class="fa fa-cog"></i> <i class="fa fa-caret-down"></i></a>';
      $block .=	'<ul class="dropdown-menu pull-right"><li><a class="label label-default no-margin" onclick="return false;">Use this as full shot</a></li></ul>';
      $block .= '<span ><a target ="_blank" href="/file/'.$fid.'" class="label label-info"><i class="glyphicon glyphicon-fullscreen"></i></a>';
      $block .= '</div>';
      $block .= '<div class="col col-sm-6">';
      $block .= '<span><a onclick="return false;" class="label label-danger mr5 pull-right">Delete</a>';
      $block .= '</div>';
      $block .= '</div>';

      $block .= '</div>';
      $block .= '</div>';
      $block .= '</div>';
      $i++;
    }
    $block .= '</div>';

    $sort_js = '<script>!function(e){e(function(){e("#sortable").sortable(),e("#sortable").disableSelection()})}(jQuery);</script>';

    $ajax_response->addCommand(new HtmlCommand('#studio-img-container1', $block));
    $ajax_response->addCommand(new HtmlCommand('#studio-img-container', ''));
    $ajax_response->addCommand(new HtmlCommand('#js-holder', $sort_js));
    //$ajax_response->addCommand(new HtmlCommand('#sortable', $block));
    $ajax_response->addCommand(new InvokeCommand('#edit-identifier-hidden', 'val', array($identifier)));
    $ajax_response->addCommand(new InvokeCommand('#edit-identifier-nid', 'val', array($new_or_old_product_nid)));
    $ajax_response->addCommand(new InvokeCommand('#edit-identifier-hidden', 'change'));
    return $ajax_response;
  }

}
