(function($){
	$(document).ready(function(){
		$('.field-youtube_video').each(function(i, el){
			var $root = $(el),
				$span = $("span", root),
				$input = $("input.hidden", root);

			$("a.change", root).bind("click", function(e){
				$input.css("display", "block").val("");
				$span.css("display", "none");
				e.preventDefault();
			});
		});
	});
})(jQuery);