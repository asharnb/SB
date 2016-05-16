(function ($) {

    function getCsrfTokenForDelete(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function postSessionTime(csrfToken, node, sid, init) {

        $.ajax({
            url: Drupal.url('studio/time/' + init + '/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                //console.log(node);
//                swal({
//                    title: "Session paused",
//                    text: "Your selected session to pause",
//                    type: "success",
//                    showConfirmButton: false,
//                    timer: 1000
//                });
                window.location = Drupal.url('view-session/' + sid);
            },
            error: function(){
                alert('Failed! **');
            }

        });
    }

    function updateSessionPeriod(sid,pause, init) {
        //        alert(11111);
        var data = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/test')
                }
            },
            "body": [
                {
                    "value": {
                        "pause": pause,
                        "sid": sid
                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type":[{"target_id":"test"}]
        };

        getCsrfTokenForDelete(function (csrfToken) {
            postSessionTime(csrfToken, data, sid, init);
        });
        //return 'done';
    }

    $("#fa-pause-session").click(function () {
        var sid = document.getElementById('fa-pause-session').getAttribute("data-id");
        updateSessionPeriod(sid,1,'start');
    });

    $("#fa-req-resume-session").click(function () {
        var sid = document.getElementById('fa-req-resume-session').getAttribute("data-id");
        updateSessionPeriod(sid,1,'end');
    });

//    $(window).bind('beforeunload', function() {
//        if(/Firefox[\/\s](\d+)/.test(navigator.userAgent) && new Number(RegExp.$1) >= 4) {
//            if(confirm("Are you Sure do you want to leave?")) {
//                alert('hellow inside');
//                updateSessionPeriod();
//                history.go();
//            } else {
//                alert('hellow insidessss');
//                window.setTimeout(function() {
//                    window.stop();
//                }, 1);
//            }
//        } else {
//            alert('hellow insideddddddd');
//            return "Are you Sure do you want to leave?";
//        }
//    });

//    window.onbeforeunload = function (e) {
//        e = e || window.event;
//
//        // For IE and Firefox prior to version 4
//        if (e) {
//             updateSessionPeriod(989898,1,'start');
//        }
//
//        // For Safari
//       // return 'Any string';
//        updateSessionPeriod(989898,1,'start');
//
//    };
//
//    $( window ).unload(function() {
//        updateSessionPeriod(989898,1,'start');
//        return "Bye now!";
//    });
//
//    $(window).bind('unload', function(){
//        alert('sadfs');
//        updateSessionPeriod(989898,1,'start');
//    });
//
//    $(function () {
//        $("a").click(function() {
//            window.onbeforeunload = null;
//        });
//    });

//alert(12);

    (function ($) {
        var unloaded = false;
        $(window).on('beforeunload', unload);
        $(window).on('unload', unload);
        var sid;
        sid = document.getElementById('fa-req-resume-session').getAttribute("data-id");
        function unload(){
            if(!unloaded){
                $('body').css('cursor','wait');
                //alert(1234);
                //var sid = document.getElementById('fa-req-resume-session').getAttribute("data-id");
                updateSessionPeriod(sid,1,'end');
                $.ajax({
                    type: 'get',
                    async: false,
                    url: 'live-shoot-image-container/un01/2121?_format=json',
                    success:function(){
                        unloaded = true;
                        $('body').css('cursor','default');
                    },
                    timeout: 5000
                });
            }
        }
    })(jQuery);

})(jQuery);
