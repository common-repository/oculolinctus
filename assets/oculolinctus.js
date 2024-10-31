var is_sent 	= false;

jQuery(".feline_oculolinctus_button").click(function(e) {
	e.preventDefault();

	var image 	= jQuery(this).find(".feline_oculolinctus_img");
	var audio 	= jQuery(this).parent().find(".feline_oculolinctus_sound");
	var source 	= image.prop("src");
	if( /gif$/.test(source) ) {
		image.prop( "src", source.substr(0, source.length - 3) + "png" );
	} else {
		image.prop( "src", source.substr(0, source.length - 3) + "gif" );
	}

	if( !is_sent ) {

		is_sent = true;

		//Send results
		jQuery.post(jQuery("#feline_oculolinctus_ajaxurl").val(), {
			action: "the_licking",
			post_id: jQuery("#feline_oculolinctus_post_id").val(),
			user_id: jQuery("#feline_oculolinctus_user_id").val(),
		}, function(response) {
			jQuery(".feline_oculolinctus_user_list").find("p").html(response);
		});

	}

	if( audio[0] ) {
		audio[0].play();
	}
});