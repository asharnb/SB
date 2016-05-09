(function ($) {
    $(function() {
     var identifier = document.getElementById('edit-identifier-hidden').value;
        //alert(identifier)
     if(identifier != ''){
        setInterval(function() {
            var rand = Math.floor((Math.random() * 1000000) + 1);
            var identifier = document.getElementById('edit-identifier-hidden').value;
            triggerit(identifier, rand);
            //document.getElementById('views-exposed-form-individual-project-view-page-1').submit();

        }, 500);
         var rand = Math.floor((Math.random() * 1000000) + 1);
         triggerit(identifier, rand);

     }else{
         //document.getElementById('studio-img-container').innerHTML = 'No Product Scanned';
         setInterval(function() {

             var identifier = document.getElementById('edit-identifier-hidden').value;
             if(identifier != ''){
                var rand = Math.floor((Math.random() * 1000000) + 1);
                triggerit(identifier, rand);
             }

         }, 500);
     }
    });

    function triggerit(identifier, rand){
        $.get(Drupal.url('live-shoot-image-container/' + identifier + "/"+ rand +"?_format=json"), function(data, status){
            //alert("Data: " + data + "\nStatus: " + status);
            //document.getElementById('studio-img-container').innerHTML = data.content;
            //document.getElementById('block-currentsessionviewblock').innerHTML = data.block1;
            //document.getElementById('studio-bridge-product-details').innerHTML = data.block2;

            var a = data.block3;
            //console.log(a);
            if(a){
//                a.forEach(function(img) {
//                    //console.log(img);
//                    append_img(img);
//                });

                for(var i in a){
                    //console.log(i + '----' + a[i]);
                    //alert(a[i]);
                    append_img(a[i],i)
                }
            }

            //console.log(data.content);
        });

    }

    function append_img(img,fid) {

        var container, inputs, index;

        // Get the container element
        container = document.getElementById('imagecontainer');

        // Find its child `input` elements
        //inputs = container.getElementsByTagName('input');
        inputs = container.getElementsByClassName("form-checkbox");
        var seq = inputs.length + 1;



        var ul = document.getElementById('imagecontainer');
        var li = document.createElement("div");
        //li.appendChild(document.createTextNode(100));
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle"); // added line
        li.setAttribute('id','warpper-img-' + fid);


        var block = '';
        if(img.tag==1){
          block += '<div class="ribbon"><span class="for-tag tag" id="seq-' +fid+ '">Tag</span></div>';
        } else{
          block += '<div class="ribbon"><span class="for-tag" id="seq-' +fid+ '">' +seq+ '</span></div>';
        }

        block +=  '<div class="scancontainer">';
        block +=  '<img src="'+ img.uri +'" class="scanpicture">';
        block +=  '</div>';

        block +=  '<div class="file-name">';

        block +=  '<div id="tag-seq-img-'+fid+'" type="hidden"></div>';

        block += '<div class="row">';


        block += '<div class="col col-sm-8"><span id= "'+fid+'" ><a class="label label-info"><i class="fa fa-lg fa-fw fa-arrows-alt"></i></a><a class="label label-warning studio-img-tag" ><i class="fa fa-lg fa-fw fa-tag"></i></a><a class="label label-success studio-img-fullshot"><i class="fa fa-lg fa-fw fa-copy"></i></a></span></div>';

        block += '<div class="col col-sm-4"><div class="onoffswitch2 pull-right"><span id="'+fid+'"><input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox form-checkbox" id="del-img-'+fid+'" value="'+fid+'"><label class="onoffswitch-label" for="del-img-'+fid+'"><span class="onoffswitch-inner"></span><span class="onoffswitch-switch"></span></label></span></div></div>';

        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
        block += '</div>';

        li.innerHTML = block;
        ul.appendChild(li);
    }

    $(function() {
        $("#imagecontainer").sortable({
            tolerance: 'pointer',
            start: function(event, ui){
                ui.placeholder.html("<div class='bulkviewfiles file gray-bkground' style='width: 250px; height: 250px; background: #D2D2D2;'></div>");
            },
            stop: function(event, ui){
                ui.placeholder.html("");
            }
        });


        $( "#imagecontainer" ).disableSelection();
    });



})(jQuery);
