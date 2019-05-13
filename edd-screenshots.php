<?php
/**
 * Plugin Name: Easy Digital Downloads - Screenshots
 * Version: 2.0.2
 * Plugin URI: http://www.hudsonatwell.co
 * Description: Adds screenshots metabox to Downloads custom post type meta area. Screenshots are displayed under download description if enabled. Use &lt;?php edd_add_screenshots(); ?&gt; in your template files or [edd-screenshots] in your post content to render screenshots block.
 * Author:  Hudson Atwell (@atwellpub)
 * Author URI: http://www.hudsonatwell.co
 * Text Domain: edd-screenshots
 * Domain Path: languages
 */



if (!class_exists('EDD_Screenshots') && class_exists('Easy_Digital_Downloads') ) {

	class EDD_Screenshots {

		/**
		 *  Initiate Class
		 */
		public function __construct() {
			self::define_constants();
			self::load_hooks();
			self::load_text_domain();
		}

		/**
		 *  Define Constants
		 */
		public static function define_constants() {
			define('EDD_SCREENSHOTS_CURRENT_VERSION', '2.0.2' );
			define('EDD_SCREENSHOTS_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('EDD_SCREENSHOTS_PATH', ABSPATH.'wp-content/plugins/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('EDD_SCREENSHOTS_ITEM_NAME', __( 'Screenshots' , 'edd-screenshots' ) );
		}

		/**
		 *  Load Hooks & Filters
		 */
		public static function load_hooks() {

			/* Enqueue frontend scripts */
			add_action( 'wp_enqueue_scripts' , array( __CLASS__ , 'enqueue_frontend_scripts' ) );

			/* enqueue admin scripts */
			add_action( 'admin_enqueue_scripts' , array( __CLASS__ , 'enqueue_admin_scripts' ) );

			/* add metaboxes to download post type */
			add_action( 'add_meta_boxes', array( __CLASS__ , 'add_metaboxes' ) );

			/* add handler to save metabox data */
			add_action( 'save_post', array( __CLASS__ , 'save_screenshots' ) );

			/* register shortcode */
			add_shortcode( 'edd-screenshots' , array( __CLASS__ , 'register_screenshots_shortcode' ) );
		}

		/**
		 *  Enqueue admin css & javascript
		 */
		public static function enqueue_admin_scripts() {
			wp_enqueue_script("jquery");
			wp_enqueue_script("jquery-ui");
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script('edd-screenshots-admin', EDD_SCREENSHOTS_URLPATH . 'assets/js/admin.js');
		}

		/**
		 *  Enqueue frontend css and javascript
		 */
		public static function enqueue_frontend_scripts()	{

			wp_enqueue_script("jquery");
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			/* check if Fancy-Gallery Plugin is active */
			if (!is_plugin_active('radykal-fancy-gallery-2/radykal-fancy-gallery.php')) {
				/* load FancyBox3 */
				wp_dequeue_script('jquery-fancybox');
				wp_enqueue_script('jquery-fancybox', EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox/jquery.fancybox.min.js');
				wp_dequeue_script('css-fancybox');
				wp_enqueue_style('css-fancybox', EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox/jquery.fancybox.min.css');

				wp_enqueue_script('edd-screenshots-frontend', EDD_SCREENSHOTS_URLPATH . 'assets/js/frontend.js');
				wp_enqueue_style('edd-screenshots-frontend', EDD_SCREENSHOTS_URLPATH . 'assets/css/styles.css');
			} else {

				add_action('wp_footer' , function() {
					echo "<script src='" . EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox/jquery.fancybox.min.js' . "' type='text/javascript'></script>";
					echo "<script src='" . EDD_SCREENSHOTS_URLPATH . 'assets/js/frontend.js' . "' type='text/javascript'></script>";
					echo "<link rel='stylesheet'  href='" . EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox/jquery.fancybox.min.css' . "'>";
					echo "<link rel='stylesheet'  href='" .EDD_SCREENSHOTS_URLPATH . 'assets/css/styles.css' . "'>";

				} , 900);
				/* Load FancyBox2 JS assets */
				//wp_enqueue_script('jquery-fancybox', EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox2/jquery.fancybox.js');
				//wp_enqueue_script('jquery-fancybox-helper-buttons', EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox2/helpers/jquery.fancybox-buttons.js');
				//wp_enqueue_script('jquery-fancybox-helper-thumbs', EDD_SCREENSHOTS_URLPATH . 'assets/libraries/fancybox2/helpers/jquery.fancybox-thumbs.js');

			}


		}

		/**
		 *  Add metabox to 'download' cpt
		 */
		public static function add_metaboxes() {
			add_meta_box( 'edd_screenshots', sprintf( __( '%1$s Screenshots', 'edd-screenshots' ), edd_get_label_singular(), edd_get_label_plural() ),  array( __CLASS__ , 'display_screenshots_metabox' ) , 'download', 'normal', 'default' );
		}


		/**
		 *  Renders metabox
		 */
		public static function display_screenshots_metabox() {
			global $post;

			require_once( EDD_SCREENSHOTS_PATH . 'aq_resizer.php');

			$screenshots = get_post_meta($post->ID,'edd_screenshots',true);
			$screenshots = explode(',',$screenshots);

			$thumbnail_width = get_post_meta($post->ID,'edd_screenshots_thumbnail_width',true);
			$thumbnail_height = get_post_meta($post->ID,'edd_screenshots_thumbnail_height',true);

			$render_before = get_post_meta($post->ID,'edd_screenshots_render_before',true);
			$render_after = get_post_meta($post->ID,'edd_screenshots_render_after',true);

			if (!$thumbnail_width){$thumbnail_width='190';}
			if (!$thumbnail_height){$thumbnail_height='130';}

			$captions_primary = get_post_meta($post->ID,'edd_screenshots_captions_primary',true);
			$captions_primary = explode(',',$captions_primary);


			echo "<div id='edd-screenshots-container'>";
			echo "<input type='hidden' id='edd-screenshots-count' value='".count($screenshots)."'>";

			echo "<h2 style=\"margin-left: -8px;\">". __( 'Thumbnail Size' , 'edd-screenshots' ) ."</h2>";
			echo "<input name='edd_screenshots_thumbnail_width'  id='edd_thumbnail_width' type='text' size='2' value='{$thumbnail_width}' /> <small>px</small> ";
			echo "&nbsp;&nbsp;&nbsp;<span class='description'> ".__('Screenshot Thumbnail Width','edd-screenshots')."</span><br>";
			echo "<input name='edd_screenshots_thumbnail_height'  id='edd_thumbnail_height' type='text' size='2' value='{$thumbnail_height}' /> <small>px</small> ";
			echo "&nbsp;&nbsp;&nbsp;<span class='description'> ".__('Screenshot Thumbnail Height','edd-screenshots')."</span><br><br><br>";

			echo '<h2 style="margin-left: -8px;">'.__('Screenshots' , 'edd-screenshots').'</h2>';
			echo '<div  class="edd-gallery-row" >';
			if (count($screenshots)>0) {
				$i = 0;

				foreach ($screenshots as $key=>$image) {
					$c = $i+1;
					$open_thumb_link = false;
					$close_thumb_link = false;

					if (strstr($image,'youtube.')||strstr($image,'youtu.be')) {
						$thumbnail = EDD_SCREENSHOTS_URLPATH . 'assets/images/youtube-play-icon.png';
					} else {
						$thumbnail = $thumbnail = aq_resize($image, 30, 30 , true);
						$thumbnail = ($thumbnail) ? $thumbnail : "";
						$attachment_id = self::get_attachment_id_from_url($image);
						$open_thumb_link = "<a href='".admin_url('upload.php?item='.$attachment_id)."' target='_blank' title='edit image meta'>";
						$close_thumb_link = "</a>";
					}


					echo '<table  style="width:100%">';
					echo '<tr><td colspan="2" style="text-align:right"><span class="dashicons dashicons-move" style="cursor: grab; font-size: 10px;margin-top: 4px;float:right"></span> #'.$c.' </td></tr>';
					echo '<tr>';
					echo '	<td style="min-width:150px;">';
					echo '	'.__('Screenshot URL','edd-screenshots');
					echo '	</td>';
					echo '	<td>';
					echo '		<input name="edd_screenshots['.$i.']"  id="edd_screenshots_'.$i.'" type="text" style="width:60%; min-width: 370px;" value="'.$image.'" />';
					echo '		<input id="upload_image_button" class="edd_screenshots_'.$i.'"  type="button" value="Upload Image" />';
					echo ($open_thumb_link) ? $open_thumb_link : '';
					echo ($thumbnail) ? '<img src="'.$thumbnail.'" style="position: relative;top: 10px;width:30px;height:30px;margin-left:4px;">' : '';
					echo ($close_thumb_link) ? $open_thumb_link : '';
					echo '	</td>';
					echo '</tr>';
					echo '<tr>';
					echo '	<td>';
					echo 	__('Caption Override','edd-screenshots');
					echo '	</td>';
					echo '	<td>';
					echo '		<input name="edd_screenshots_captions_primary['.$i.']"  id="edd_screenshots_primary_label_0" type="text" style="width:100%;" value="'.$captions_primary[$key].'" />';
					echo '	</td>';
					echo '</tr>';
					echo '</table>';
					$i++;
				}

			} else {
				$i=0;
				$c = $i+1;
				echo '<label for="upload_image">';
				echo '<input name="edd_screenshots['.$i.']"  id="edd_screenshots_'.$i.'" type="text" size="82" value="" />';
				echo '<input id="upload_image_button" class="edd_screenshots_'.$i.'"  type="button" value="Upload Image" />';
				echo '&nbsp;&nbsp;<span class="description">'.__('Screenshot','edd-screenshots').' '.$c.'</span><br>';
				echo '<input name="edd_screenshots_captions_primary['.$i.']"  id="edd_screenshots_primary_label_0" type="text" style="width:100%;" value="" />';
				echo '&nbsp;&nbsp;<span class="description">'.__('Caption Override','edd-screenshots').'</span>=<br>';
				$i++;
			}

			echo "</div>";
			echo "</div>";
			echo "<div style='text-align:right;'><img src='".EDD_SCREENSHOTS_URLPATH."assets/images/add.png' title='add screenshot' style='cursor:pointer' id='edd-add-screenshot'></div>";



			wp_nonce_field( basename( __FILE__ ), 'add_metaboxes_nonce' );

		}

		/**
		 *  Save screenshot data
		 */
		public static function save_screenshots( $post_id ) {
			global $post;

			if (!isset($post)) {
				return;
			}

			// check autosave
			if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) return $post_id;

			//don't save if only a revision
			if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
				return $post_id;
			}

			//don't save if only a revision
			if ( isset( $post->post_type ) && $post->post_type != 'download' )  {
				return $post_id;
			}

			// check permissions
			if ( ! current_user_can( 'edit_pages', $post_id ) ) {
				return $post_id;
			}

			$screenshots = $_POST['edd_screenshots'];
			$thumbnail_width = $_POST['edd_screenshots_thumbnail_width'];
			$thumbnail_height = $_POST['edd_screenshots_thumbnail_height'];
			$captions_primary = $_POST['edd_screenshots_captions_primary'];

			if (is_array($screenshots)) {
				foreach ($screenshots as $key=>$value){
					if (!trim($value)){
						unset($captions_primary[$key]);
						unset($screenshots[$key]);
					}
				}

				$screenshots = implode(',',$screenshots);
				$captions_primary = implode(',',$captions_primary);

				//echo $array;exit;
				update_post_meta( $post_id, 'edd_screenshots', $screenshots);
				update_post_meta( $post_id, 'edd_screenshots_thumbnail_width', $thumbnail_width);
				update_post_meta( $post_id, 'edd_screenshots_thumbnail_height', $thumbnail_height);
				update_post_meta( $post_id, 'edd_screenshots_captions_primary', $captions_primary);
			}
		}

		/**
		 *  Register [edd-screenshots] shortcode
		 */
		public static function register_screenshots_shortcode() {
			global $post;

			if (!isset($post)) {
				return;
			}

			require_once( EDD_SCREENSHOTS_PATH . 'aq_resizer.php');

			//return "hello";exit;
			//echo $post->ID;exit;
			$screenshots = get_post_meta($post->ID,'edd_screenshots',true);
			$screenshots = explode(',',$screenshots);
			$screenshots = array_filter($screenshots);

			$thumbnail_width = get_post_meta($post->ID,'edd_screenshots_thumbnail_width',true);
			$thumbnail_height = get_post_meta($post->ID,'edd_screenshots_thumbnail_height',true);

			$captions_primary = get_post_meta($post->ID,'edd_screenshots_captions_primary',true);
			$captions_primary = explode(',',$captions_primary);

			if (count($screenshots)>0){

				$html = "<div class='edd-screenshots-container'>";
				$html .= "<ul class='edd-ul-screenshots' >";

				foreach ($screenshots as $key=>$image) {
					$image = trim($image);

					/* check if youtube */
					if (
						strstr($image,'youtube.')
						||
						strstr($image,'youtu.be')
					){

						preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $image, $match);
						$youtube_id = $match[1];
						$external_thumb = "https://img.youtube.com/vi/{$youtube_id}/mqdefault.jpg";
						$thumbnail = $external_thumb;
						$class = "yt-thumb";
					} else {
						$thumbnail = aq_resize($image,$thumbnail_width, $thumbnail_height ,true);
						$class = "img-thumb";
						$attachment_id = self::get_attachment_id_from_url($image);
						$image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', TRUE);

						$captions_primary[$key] = ($captions_primary[$key]) ? $captions_primary[$key] : $image_alt;
					}

					$html .= "<li id='gallery-item-{$key}'  class='edd-gallery-item' data-id='id-{$key}' >
									<span class='image-block'>
									<a data-fancybox='images' href='{$image}'  data-caption='".str_replace("'","", $captions_primary[$key])."'>
										<div class='gallery-item-overlay-container'>
											<img src='".$thumbnail."' alt='".str_replace("'","", $captions_primary[$key])."' title='".str_replace("'","", $captions_primary[$key])."' style='width:".$thumbnail_width."px;height:".$thumbnail_height."px; max-width: 100%; max-height: 100%;' class='".$class."' loading='lazy'/>
										</div>
									</a>
									</span>
							  </li>";
				}

				$html .= "</ul>";
				$html .= "</div>";

				$html .= "<style>
                            .edd-ul-screenshots {
                            	padding:5px;
                            	margin-left:-21px;
                                display: grid;
                                grid-template-columns: repeat(auto-fill, 100px);
                                flex-wrap: wrap;
                                justify-content:  space-between;
                            	text-align:center;
                            }

                            .edd-ul-screenshots::after {
                                content: '';
                                flex: auto;
                            }

                            .edd-screenshots-container .edd-gallery-item {
                            	//flex:1 0 ".$thumbnail_width."px;
                            	padding-bottom: 0px;
                            	margin-bottom: 0px;
                            	margin-right: 0px;
                            	overflow: visible;
                            	padding-left:0px;
                            	padding-right:0px;
                            	list-style:none;
                            	overflow: hidden;

                            }

                            .gallery-item-overlay-container {
                            	background-color:#e6e4e4;
                                max-height: ".($thumbnail_height+4)."px;
                                max-width: ".$thumbnail_width."px;
                                padding: 1px;
                                border: solid 1px rgb(196, 196, 196);
                            }
                        </style>";


				return $html;
			}
		}

		/**
		 * retrieves the attachment ID from the file URL
		 * */
		public static function get_attachment_id_from_url($image_url) {
			global $wpdb;

			$image_url = str_replace(site_url() , '' , $image_url );

			$attachment = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE guid LIKE '%{$image_url}%'" );

			return $attachment[0];
		}

		/**
		 *  Load text domain
		 */
		public static function load_text_domain() {
			load_plugin_textdomain( 'edd-screenshots' , false , dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}
	}

	/**
	 *  Load Class
	 */
	new EDD_Screenshots;

	/**
	 *  Register legacy function
	 */
	function edd_add_screenshots() {
		EDD_Screenshots::register_screenshots_shortcode();
	}
}
