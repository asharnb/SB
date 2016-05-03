

(function ($) {

    //TO DO: disable delete button by default, only enable when something is checked to delete

    'use strict';

    Drupal.behaviors.tagfordelete = {
        attach: function(context, settings) {

          var video = document.querySelector("#videoElement");

          navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia || navigator.oGetUserMedia;

          if (navigator.getUserMedia) {
              navigator.getUserMedia({video: true}, handleVideo, videoError);
          }

          function handleVideo(stream) {
              video.src = window.URL.createObjectURL(stream);
          }

          var snapshot = document.querySelector("#snapshot");
          var filmroll = document.querySelector("#filmroll");
          $("#snapapicture").click(function () {


            // Make the canvas the same size as the live video
            snapshot.width = video.clientWidth
            snapshot.height = video.clientHeight

            // Draw a frame of the live video onto the canvas
            var c = snapshot.getContext("2d")
            var abc = 2;
            c.drawImage(video, 0, 0, snapshot.width, snapshot.height)

            // Create an image element with the canvas image data
            var img = document.createElement("img")
            img.src = snapshot.toDataURL("image/png")
            img.style.padding = 5
            img.width = snapshot.width / 2
            img.height = snapshot.height / 2


            // Add the new image to the film roll
            filmroll.appendChild(img)
          });

          function videoError(e) {
              // do something
          }
    }



    };


}(jQuery));
