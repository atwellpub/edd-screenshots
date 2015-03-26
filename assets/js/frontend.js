jQuery.noConflict();
jQuery(document).ready(function($){
								
							
function lightboxPhoto() {
	
	jQuery("a[rel^='prettyPhoto']").prettyPhoto({
			slideshow:5000,
			autoplay_slideshow:false, 
			show_title:false,
			overlay_gallery: false

		});
	
	}
	
	if(jQuery().prettyPhoto) {
	
		lightboxPhoto(); 
			
	}
	
	
});