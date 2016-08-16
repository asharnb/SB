(function ($) {
  $(".fancybox-thumb").fancybox({
		prevEffect	: 'none',
		nextEffect	: 'none',
    type : 'image',

		helpers	: {
			title	: {
				type: 'outside'
			},
			thumbs	: {
				width	: 50,
				height	: 50
			}
		}
	});
}(jQuery));
