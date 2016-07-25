(function ($) {

    //TO DO: disable delete button by default, only enable when something is checked to delete

    'use strict';

    Drupal.behaviors.tagfordelete = {
        attach: function (context, settings) {

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

                var id = Math.floor((Math.random() * 1000000) + 1);

                img.id = 'img-' + id;
                //img.width = snapshot.width / 2
                img.width = 250;
                //img.height = snapshot.height / 2
                img.height = 375;


                filmroll.setAttribute("class", 'imagecontainer');


                //var imgWrapper = imgContainer(img,id);
                var imgWrapper = createImageContainer(id,id,1,snapshot.toDataURL("image/png"),0);

                filmroll.appendChild(imgWrapper);

                var fullview = document.getElementById('warehouse-fullview-'+id);
                var tag = document.getElementById('warehouse-tag-'+id);

                tag.addEventListener("click", function(){
                    processImageToServer(id,1,0);
                }, false);

                fullview.addEventListener("click", function(){
                    processImageToServer(id,0,0);
                }, false);

            });

            function videoError(e) {


            }

        }



    };


    function newDom(dom, id, class_attr) {
        var wrap = document.createElement('div');
        wrap.setAttribute("class", class_attr);
        wrap.setAttribute("id", id);
        return wrap;
    }

    function imgContainer(img,id){
        //var imagecontainer = newDom('div','imagecontainer-inner','');
        var li = document.createElement("div");
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle");
        li.setAttribute("id", id);

        //li.appendChild();
        //imagecontainer.appendChild(li);

        var scancontainer = newDom('div', '', 'scancontainer');
        scancontainer.appendChild(img);

        li.appendChild(scancontainer);

        var div = document.createElement('div');

        var block = imageTags(1, 1, id);

        var block = createImageContainer(1,0,seq,src,0)

        div.innerHTML = block;

        li.appendChild(div);

        //imagecontainer.appendChild(li);
        return li;
    }


    function imageTags(fid, img, div_id) {

        var block = '';

        block += '<div class="file-name">';

        block += '<div id="tag-seq-img-' + fid + '" type="hidden"></div>';

        block += '<div class="row">';


        block += '<div class="col col-sm-8"><span id= "warehouse-tags-container-'+div_id+'" data-value= "' + div_id + '" ><a class="label label-info" id= "warehouse-fullview-'+div_id+'"><i class="fa fa-lg fa-fw fa-arrows-alt"></i></a><a class="label label-warning studio-img-tag" id= "warehouse-tag-'+div_id+'"><i class="fa fa-lg fa-fw fa-tag"></i></a><a class="label label-success studio-img-fullshot" id= "warehouse-fullshot-'+div_id+'"><i class="fa fa-lg fa-fw fa-copy"></i></a></span></div>';

        block += '<div class="col col-sm-4"><div class="onoffswitch2 pull-right"><span id="warehouse-delete-checkbox'+div_id+'"><input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox form-checkbox" id="del-img-'+div_id+'" value="'+div_id+'"><label class="onoffswitch-label" for="del-img-'+div_id+'"><span class="onoffswitch-inner"></span><span class="onoffswitch-switch"></span></label></span></div></div>';

        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="' + fid + '"></div>';
        return block;

    }

    function getCsrfTokenForImgActions(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                //var csrfToken = data;
                callback(data);
            });
    }

    function postImage(csrfToken, node, div_id) {

        $.ajax({
            url: Drupal.url('entity/file?_format=hal_json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken,
                'Cache-Control': 'max-age=0'
            },
            data: JSON.stringify(node),
            success: function (file) {
                console.log(file);

                //todo replace the image with uploaded one.
                imageContainerFromServer(file, div_id, node);

            },
            error: function () {
                alert('Failed!');
                imageContainerFromServer('x', div_id, node);
            }

        });
    }

    function SendImageToServer(cid, tag, ref, src, div_id) {
        var img = {
            "_links": {
                "type": {
                    "href": Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
            "filename": [
                {    "value": "Tag.jpg"   }
            ],
            "filemime": [
                {    "value": "image/jpeg"   }
            ],
            "field_container": [
                {     "target_id": cid   }
            ],
            "filesize": [
                {    "value": "488"   }
            ],
            "type": [
                {    "target_id": "image"   }
            ],
            "data": [
                {    "value": src   }
            ],
            "field_reference": [
                {
                    "value": ref
                }
            ],
            "field_tag": [
                {
                    "value": tag
                }
            ]
        };

      getCsrfTokenForImgActions(function (csrfToken) {
            postImage(csrfToken, img, div_id);
        });
    }

    function processImageToServer(id,tag,ref){

        var container = document.getElementById('warehouse-container-id').value;
        var container_nid = document.getElementById('warehouse-container-nid').value;
        var imgSrc = document.getElementById('src-'+id).getAttribute('data-src');
        imgSrc = imgSrc.replace('data:image/png;base64,',"");

        if(tag || ref){
            SendImageToServer(container_nid, tag, ref, imgSrc, id);
            //sendfile(imgSrc,container_nid);
        }
    }

    function imageContainerFromServer(file, div_id, file_sent) {
        var src = 'data:image/png;base64,'+file_sent.data[0].value;
        var container, inputs, index;

        // Get the container element
        container = document.getElementById('imagecontainer');

        // Find its child `input` elements
        //inputs = container.getElementsByTagName('input');
        //inputs = container.getElementsByClassName("form-checkbox");
        var seq = 1;

        var fid = div_id;
        //var img = {'uri' : 'https://www.drupal.org/files/druplicon-small.png', 'tag':1};
        var tag = file.field_tag[0].value;
        var ref = file.field_reference[0].value;

        var ul = document.getElementById('filmroll');
        //var li = document.createElement("div");
        var li = document.getElementById(div_id);


        var li = createImageContainer(fid,fid,seq,src,1)

       //ul.appendChild(li);
    }


    function createImageContainer(fid, id, seq, url, tag){

      var height = '375';
      var width = '250';

      var li = document.createElement("div");
      li.setAttribute('class','bulkviewfiles imagefile')

      var block = '';

      if(tag==1){
        block += '<div class="ribbon" id="ribboncontainer"><span data-id="'+fid+'" class="for-tag tag" id="seq-' + fid +'" name="' + seq +'"><i class="fa fa-lg fa-barcode txt-color-white"></i></span></div>';
      } else{
        block += '<div class="ribbon" id="ribboncontainer"><span class="for-tag" id="seq-' + fid +'" name="' + seq +'">' + seq +'</span></div>';
      }

      block +=  '<div class="scancontainer"><div class="hovereffect">';
      block += '<div class="hidden" id="src-'+fid+'" data-src="'+url+'"></div>'
      block +=  '<img src="'+ url +'" class="scanpicture" data-imageid="'+ fid +'" >';
      block += '<div class="overlay"><input type="checkbox" class="form-checkbox" id="del-img-'+ fid +'" hidden value="'+ fid +'"><a class="info select-delete" data-id="'+ fid +'" data-click="no">Select image</a></div>';

      block +=  '</div>';

      block +=  '<div class="file-name">';

      block +=  '<div id="tag-seq-img-'+fid+'" type="hidden"></div>';

      block += '<div class="row">';

      block += '<div class="col col-sm-12"><span id= "warehouse-tags-container-'+fid+'" data-value="'+fid+'">';
      block += '<a class="col-sm-4 text-info " id= "warehouse-fullview-'+fid+'"><i class="fa fa-lg fa-fw fa-search"></i></a>';
      block += '<a class="col-sm-4 text-info" id= "warehouse-tag-'+fid+'"><i class="fa fa-lg fa-fw fa-barcode"></i></a>';
      block += '<a class="col-sm-4 text-info" id= "warehouse-fullshot-'+fid+'"><i class="fa fa-lg fa-fw fa-trash"></i></a>';
      block += '</span></div>';

      block += '</div>';
      block += '</div>';
      block += '</div>';
      block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
      block += '</div>';

      li.innerHTML = block;
      return li;
    }






}(jQuery));
