

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
            //img.width = snapshot.width / 2
            img.width = 250;
            //img.height = snapshot.height / 2
            img.height = 375;


            // Add the new image to the film roll
            //filmroll.appendChild(img)


              var imgWrapper = imgContainer(img);

              filmroll.appendChild(imgWrapper);

          });

          function videoError(e) {
              // do something
          }
    }



    };

    function newDom(dom, id, class_attr){
        var wrap = document.createElement('div');
        wrap.setAttribute("class", class_attr);
        wrap.setAttribute("id", id);
        return wrap;
    }

    function imgContainer(img){
        //var imagecontainer = newDom('div','imagecontainer-inner','');
        var li = document.createElement("div");
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle");

        //li.appendChild();
        //imagecontainer.appendChild(li);

        var scancontainer = newDom('div','','scancontainer');
        scancontainer.appendChild(img);

        li.appendChild(scancontainer);

        var div = document.createElement('div');

        var block = imageTags(1,1);

        div.innerHTML = block;

        li.appendChild(div);

        //imagecontainer.appendChild(li);
        return li;
    }


    function imageTags(fid,img){

        var block = '';

        block +=  '<div class="file-name">';

        block +=  '<div id="tag-seq-img-'+fid+'" type="hidden"></div>';

        block += '<div class="row">';


        block += '<div class="col col-sm-8"><span id= "'+fid+'" ><a class="label label-info"><i class="fa fa-lg fa-fw fa-arrows-alt"></i></a><a class="label label-warning studio-img-tag" ><i class="fa fa-lg fa-fw fa-tag"></i></a><a class="label label-success studio-img-fullshot"><i class="fa fa-lg fa-fw fa-copy"></i></a></span></div>';

        block += '<div class="col col-sm-4"><div class="onoffswitch2 pull-right"><span id="'+fid+'"><input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox form-checkbox" id="del-img-'+fid+'" value="'+fid+'"><label class="onoffswitch-label" for="del-img-'+fid+'"><span class="onoffswitch-inner"></span><span class="onoffswitch-switch"></span></label></span></div></div>';

        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
        return block;

    }


}(jQuery));
