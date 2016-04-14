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
    $session_id = studiobridge_store_images_open_session_recent();
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

      $result = \Drupal::entityQuery('node')
        ->condition('type', array('products','unmapped_products'),'IN')
        ->sort('created', 'DESC')
        ->condition('title', $_GET['identifier']) // todo : title will be changed as per response
        ->range(0, 1)
        ->execute();

      if($result){
        $new_or_old_product_nid = reset($result);
      }else{
        drupal_set_message('Invalid identifier','warning');
        return new RedirectResponse(base_path());
      }

      if($new_or_old_product_nid){
        \Drupal::state()->set('last_scan_product_nid'.$uid.'_'.$session_id,$new_or_old_product_nid);
        studiobridge_store_images_update_product_as_open($_GET['identifier']);
        \Drupal::state()->set('last_scan_product_'.$uid.'_'.$session_id,$_GET['identifier']);
      }
    }else{
      $result = \Drupal::entityQuery('node')
        ->condition('type', array('products','unmapped_products'),'IN')
        ->sort('created', 'DESC')
        ->condition('title', $identifier_hidden) // todo : title will be changed as per response
        ->range(0, 1)
        ->execute();

      if($result){
        $new_or_old_product_nid = reset($result);
      }
    }

    $identifier = $identifier_hidden;


<<<<<<< Updated upstream

    // @ashar : this does not need to be refreshed
<<<<<<< Updated upstream
=======
    $form['sortable'] = array(
        '#markup' => '<ul id="sortable"></ul>',
    );

>>>>>>> Stashed changes


    $form['identifier'] = array(
<<<<<<< Updated upstream
        '#theme' => 'sbtheme_scan',
        '#type' => 'textfield',
        '#description' => $this->t('description will come here'),
        '#default_value' => $identifier,
        '#ajax' => array(
            'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::productGetOrUpdateCallback',
          //'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::randomUsernameCallback',
            'event' => 'enter',
            'progress' => array(
                'type' => 'throbber',
              //'type' => 'bar',
                'message' => 'Getting Product',
            ),
        ),
=======
      '#type' => 'textfield',
      '#description' => $this->t('description will come here'),
      '#default_value' => $identifier,

>>>>>>> Stashed changes
    );

    $productdetails =$this->getProductData($new_or_old_product_nid);

    $form['markup_product_details'] = array(
      '#theme' => 'sbtheme_product',
        '#concept' => $productdetails['concept'],
        '#styleno' => $productdetails['styleno'],
        '#colorvariant' => $productdetails['colorvariant'],
        '#gender' => $productdetails['gender'],
        '#color' => $productdetails['color'],
        '#description' => $productdetails['description'],
        '#identifier' => $identifier,
        '#visible' => TRUE,
=======
    $list = '<ul id="sortable"></ul>';

    $form['identifier'] = array(
        //'#theme' => 'sbtheme_product',
        '#type' => 'textfield',
        '#description' => $this->t('description will come here'),
        '#default_value' => $identifier,
>>>>>>> Stashed changes
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

    $productdetails = Products::getProductInformation($identifier_hidden);

    $form['markup_product_details'] = array(

        '#product' => array(
          'concept' => $productdetails['concept'],
          'styleno' => $productdetails['styleno'],
          'colorvariant' => $productdetails['colorvariant'],
          'gender' => $productdetails['gender'],
          'color' => $productdetails['color'],
          'description' => $productdetails['description'],
          'identifier' => $identifier,
        ),
        '#visible' => TRUE,
    );

    $images = array();
    $pid = \Drupal::state()->get('last_scan_product_nid'.$uid.'_'.$session_id,false);
    if($pid){
      $images = self::getProductImages($pid);
    }else{
      $result = \Drupal::entityQuery('node')
        ->condition('type', array('products','unmapped_products'),'IN')
        ->sort('created', 'DESC')
        ->condition('title', $identifier) // todo : title will be changed as per response
        ->range(0, 1)
        ->execute();

      if($result){
        $images = self::getProductImages(reset($result));
      }
    }
// @ashar : seperate this image container so we can apply theme formatting to it

    $form['resequence'] = array(
      '#markup' => '<a id="studio-resequence-bt" class="btn btn-xs btn-info">Resequence</a>',
    );
    $form['delete'] = array(
        '#markup' => '<a id="studio-delete-bt" class="btn btn-xs btn-danger">Delete</a>',
    );
    $form['random_user'] = array(
      '#type' => 'button',
      '#value' => 'Apply',
      '#visible' => FALSE,
      //'#suffix' => '<div id="studio-img-container"></div><div id="js-holder"></div><div id="studio-img-container1">'.$block.'</div>',
<<<<<<< Updated upstream
      //'#suffix' => '<div id="studio-img-container"></div><div id="js-holder"></div><a id="studio-resequence-bt" class="btn btn-warning">Resequence</a><div id="msg-up"></div>',

=======
      '#suffix' => '<div id="studio-img-container"></div><div id="js-holder"></div><div id="msg-up"></div>',
        '#ajax' => array(
            'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::productGetOrUpdateCallback',
          //'callback' => 'Drupal\studiobridge_live_shoot_page\Form\StudioBridgeLiveShootingForm::randomUsernameCallback',
            'event' => 'enter',
            'progress' => array(
                'type' => 'throbber',
              //'type' => 'bar',
                'message' => 'Getting Product',
            ),
        ),
    );

    $productdetails = Products::getProductInformation($identifier_hidden);

    $form['productdetails'] = array(
            'concept' => $productdetails['concept'],
            'styleno' => $productdetails['styleno'],
            'colorvariant' => $productdetails['colorvariant'],
            'gender' => $productdetails['gender'],
            'color' => $productdetails['color'],
            'description' => $productdetails['description'],
            'identifier' => $identifier,
        '#visible' => TRUE,
>>>>>>> Stashed changes
    );


<<<<<<< Updated upstream


    $form['product_container'] = array(
      '#theme' => 'sbtheme_image_container',
      '#variables' => '',

      //'#suffix' => '<div id="studio-img-container1"><div id="sortable" class="ui-sortable">',
    );

    $form['resequence_button'] = array(
        //'#theme' => 'sbtheme_image_container',
        '#type' => 'button',
        '#value' => 'Resequence',
        '#name' => '',

        '#markup' => '<input id="studio-resequence-bt" class="btn btn-warning"></input><div id="msg-up"></div>',

    );


    $i = 1;
        foreach($images as $fid => $src){
    
          $form['markup_product_details__'.$fid] = array(
              '#visible' => TRUE,
              '#theme' => 'sbtheme_image',
              '#url' => $src['uri'],
              '#name' => $src['name'],
              '#fid' => $fid,
              '#id' => $i,
          );
    
          $form['images['.$fid.']'] = array(
            '#type' => 'hidden',
            '#value' => $fid,
          );
    
          $i ++;
        }
=======
    $form['markup_product_details_first'] = array(
        '#suffix' => '<div id="studio-img-container1"><div id="sortable" class="ui-sortable">',
    );

<<<<<<< Updated upstream

=======
>>>>>>> Stashed changes
    $array_images = array();
    $i = 1;
    foreach($images as $fid => $src){

      $array_images[] = array('url' => $src['uri'],
          'name' => $src['name'],
          'fid' => $fid,
          'id' => $i);
      $i ++;


    }
<<<<<<< Updated upstream
>>>>>>> Stashed changes

    $form['markup_product_details__'.$fid] = array(
         '#theme' => 'sbtheme_image',
          '#images' => $array_images,
    );

//    $form['images['.$fid.']'] = array(
//        '#type' => 'hidden',
//        '#value' => $fid,
//    );

=======
    $form['images'] = array(
        'images' => $array_images,

    );
    $form['markup_product_details__'.$fid] = array(
        '#theme' => 'sbtheme_image',
        '#images' => $array_images,
    );
>>>>>>> Stashed changes

    $form['markup_product_details_second'] = array(
        '#suffix' => '</div></div>',
    );
<<<<<<< Updated upstream

=======
    //$form['#attributes'] = array('class' => 'smart-form no-padding no-margin');
    $form['#theme'][= 'sbtheme_page';
>>>>>>> Stashed changes
    $form['#attached']['library'][] = 'core/jquery.ui.sortable';

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
    $session_id = studiobridge_store_images_open_session_recent();
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

    // todo : if identifier found in our products then skip importing

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('products','unmapped_products'),'IN')
      ->sort('created', 'DESC')
      ->condition('title', $identifier) // todo : title will be changed as per response
      ->range(0, 1)
      ->execute();

    if (!$result) {
      // Get product from server
      $product = self::getProductExternal($identifier);
      // validate product
      if(isset($product->msg)){
        // product not found on the server so save it as unmapped product.
        studiobridge_store_images_create_unmapped_product(array(),$session_id,$identifier,false);
        $is_unmapped_product = true;
      }else{
        // import it in our drupal
        $new_product = self::createNodeProduct($product, $identifier);
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
      studiobridge_store_images_update_product_as_open($identifier);
    }

    if($last_scan_product != $identifier){
      // todo : update last product as closed status
      studiobridge_store_images_update_product_as_closed($last_scan_product);
    }

    // Once product is scanned update it to session
    if(!$is_unmapped_product){
      studiobridge_store_images_add_product_to_session($session_id, \Drupal\node\Entity\Node::load($new_or_old_product_nid));
    }

    \Drupal::state()->set('last_scan_product_'.$uid.'_'.$session_id,$identifier);

    if($new_or_old_product_nid){
      \Drupal::state()->set('last_scan_product_nid'.$uid.'_'.$session_id,$new_or_old_product_nid);
    }

    $images = self::getProductImages($new_or_old_product_nid);

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
    $user = \Drupal::currentUser();
    $uid = $user->id();
    if (is_object($product)) {
      $values = array(
        'nid' => NULL,
        'type' => 'products',
        'title' => $identifier,
        'uid' => $uid,
        'status' => TRUE,
        'field_base_product_id' => array('value'=>$product->base_product_id),
        'field_style_family' => array('value'=>$product->style_no),
        'field_concept_name' => array('value'=> $product->concept),
        'field_gender' => array('value'=> $product->gender),
        'field_description' => array('value'=> $product->description),
        'field_color_variant' => array('value'=> $product->color_variant), // todo: may be multiple
        'field_color_name' => array('value'=> $product->color_name),  //  todo: may be multiple
        'field_size_name' => array('value'=> $product->size_name),  // todo: may be multiple
        'field_size_variant' => array('value'=> $product->size_variant),  // todo: may be multiple
      );
      $node = \Drupal::entityManager()->getStorage('node')->create($values);
      $node->save();
      // todo : add exceptions
      return $node;
    }
  }

  public function getProductImages($nid){

    $sid = studiobridge_store_images_open_session_recent();
    $image_uri = array();

    $product = \Drupal\node\Entity\Node::load($nid);
    if($product){
      $images = $product->field_images->getValue();
      if($images){
        foreach($images as $img){
          $fid = $img['target_id'];
          $file = File::load($fid);
          $file_name = $file->filename->getValue();
          $file_name = $file_name[0]['value'];
          $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
          $image_uri[$fid] = array('uri'=>$image_uri_value,'name'=>$file_name);
        }
        return $image_uri;
      }
    }
    return false;
  }


  function getProductData($nid){

    $product = \Drupal\node\Entity\Node::load($nid);
    $bundle = $product->bundle();
    $style_no = '';
    $color_variant = '';
    $gender = '';
    $description = '';
    $color = '';


    if($bundle == 'unmapped_products'){
      $concept = 'Unmapped';
    }else{

      $product_concept = $product->field_concept_name->getValue();
      if($product_concept){
        $concept = $product_concept[0]['value'];
      }

      $product_style_no = $product->field_style_family->getValue();
      if($product_style_no){
        $style_no = $product_style_no[0]['value'];
      }
      $product_color_variant = $product->field_color_variant->getValue();
      if($product_color_variant){
        $color_variant = $product_color_variant[0]['value'];
      }
      $product_gender = $product->field_gender->getValue();
      if($product_gender){
        $gender = $product_gender[0]['value'];
      }
      $product_color = $product->field_color_name->getValue();
      if($product_color){
        $color = $product_color[0]['value'];
      }

      $product_description = $product->field_description->getValue();
      if($product_description){
        $description = $product_description[0]['value'];
      }

    }


    $output_array = array("concept" => $concept,
        "styleno" => $style_no,
        "colorvariant" => $color_variant,
        "gender" => $gender,
        "color" => $color,
        "description" => $description);

    $output = $output_array;
    return $output;
  }


}
