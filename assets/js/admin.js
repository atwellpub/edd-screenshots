jQuery(document).ready(function ($) {

	var orig_send_to_editor = window.send_to_editor;
	jQuery('#upload_image_button').live('click',function(){
		var media_name = jQuery(this).attr('class');

		tb_show('', 'media-upload.php?type=image&type=image&amp;TB_iframe=true');
		jQuery('#tab-type_url').hide();

		//temporarily redefine send_to_editor()
		window.send_to_editor = function(html) {
			imgurl = jQuery('img',html).attr('src');

			if (!imgurl) {
				var array = html.match("src=\"(.*?)\"");
				imgurl = array[1];
			}

			jQuery('#' + media_name).val(imgurl);
			tb_remove();

			window.send_to_editor = orig_send_to_editor;
		}

		return false;
	 });


	jQuery('#edd-add-screenshot').live('click',function(){
		var count = jQuery("#edd-screenshots-count").val();
		//alert(count);
		var nid = parseInt(count) + 1;
		var html = '<table  style="width:100%" >'
				+ '<tr><td colspan=2 style="text-align:right"><span class="dashicons dashicons-move" style="cursor: grab; font-size: 10px;margin-top: 4px;float:right"></span> #'+ nid+' </td></tr>'
				+ '<tr>'
				+ '		<td style="min-width:150px;">'
				+ '		Screenshot URL'
				+ ' 	</td>'
				+ '		<td>'
				+ '			<input name="edd_screenshots['+count+']"  id="edd_screenshots_'+count+'" type="text" style="width:60%;min-width: 445px;"  value="" />'
				+ '			<input id="upload_image_button" class="edd_screenshots_'+count+'"  type="button" value="Upload Image" />'
				+ '		</td>'
				+ '</tr>'
				+ '<tr>'
				+ '		<td>'
				+ '			Caption'
				+ '		</td>'
				+ '		<td>'
				+ '			<input name="edd_screenshots_captions_primary['+count+']"  id="edd_screenshots_primary_label_0" type="text" style="width:100%;" value="" />'
				+ '		</td>'
				+ '	</tr>'
				+ '</table>'

		jQuery('.edd-gallery-row').append(html);
		jQuery("#edd-screenshots-count").val(nid);
		jQuery(".edd-gallery-row").sortable('refresh');
	});

	/* make rows draggable */
	jQuery(".edd-gallery-row").sortable();
});
