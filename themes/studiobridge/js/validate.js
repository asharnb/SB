/**
* Created by asharbabar on 4/22/16.
*/
(function ($) {


  //'use strict';

  Drupal.behaviors.validate = {
    attach: function(context) {

      $(".node-form").validate();

    }
  };


}(jQuery));
