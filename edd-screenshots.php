<?php 
/**
 * Plugin Name: Easy Digital Downloads - Screenshots
 * Version: 2.0.1
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
			define('EDD_SCREENSHOTS_CURRENT_VERSION', '2.0.1' );
			define('EDD_SCREENSHOTS_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('EDD_SCREENSHOTS_PATH', ABSPATH.'wp-content/plugins/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('EDD_SCREENSHOTS_PLUGIN_SLUG', 'edd-screenshots' );
			define('EDD_SCREENSHOTS_STORE_URL', 'https://easydigitaldownloads.com/' );
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
	
			/* enable licesning */
			add_action( 'admin_init' , array( __CLASS__ , 'setup_licesning' ) , 1 );
			
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
			wp_enqueue_script('edd-screenshots-admin', EDD_SCREENSHOTS_URLPATH . 'assets/js/admin.js');
		}
		
		/**
		*  Enqueue frontend css and javascript
		*/
		public static function enqueue_frontend_scripts()	{

			wp_enqueue_script("jquery");	

			wp_dequeue_script('jquery-prettyphoto');
			wp_enqueue_script('jquery-prettyphoto', EDD_SCREENSHOTS_URLPATH . 'assets/js/jquery-photo-gallery/jquery.prettyPhoto.js');
			
			wp_enqueue_script('edd-screenshots-frontend', EDD_SCREENSHOTS_URLPATH . 'assets/js/frontend.js');
			
			wp_dequeue_script('css-prettyphoto');
			wp_enqueue_style('css-prettyphoto', EDD_SCREENSHOTS_URLPATH . 'assets/js/jquery-photo-gallery/css/prettyPhoto.css');
			wp_enqueue_style('edd-css-frontend', EDD_SCREENSHOTS_URLPATH . 'assets/css/styles.css');
			
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
			
			$captions_secondary = get_post_meta($post->ID,'edd_screenshots_captions_secondary',true);
			$captions_secondary = explode(',',$captions_secondary);

			echo "<div id='edd-screenshots-container'>";
			echo "<input type='hidden' id='edd-screenshots-count' value='".count($screenshots)."'>";
			
			echo "<h2>". __( 'Thumbnail Size' , 'edd-screenshots' ) ."</h2>";
			echo "<input name='edd_screenshots_thumbnail_width'  id='edd_thumbnail_width' type='text' size='2' value='{$thumbnail_width}' /> <small>px</small> ";
			echo "&nbsp;&nbsp;&nbsp;<span class='description'> ".__('Screenshot Thumbnail Width','edd-screenshots')."</span><br>";
			echo "<input name='edd_screenshots_thumbnail_height'  id='edd_thumbnail_height' type='text' size='2' value='{$thumbnail_height}' /> <small>px</small> ";
			echo "&nbsp;&nbsp;&nbsp;<span class='description'> ".__('Screenshot Thumbnail Height','edd-screenshots')."</span><br><br><br>";
			
			echo "<h2>". __( 'HTML Setup' , 'edd-screenshots' ) ."</h2>";
			echo "<span class='description'>".__('HTML before screenshots','edd-screenshots')."</span><br>";
			echo "<textarea name='edd_screenshots_render_before'  id='edd_before_render' cols=60 rows=4 />{$render_before}</textarea><br>";
			echo "<span class='description'>".__('HTML after screenshots','edd-screenshots')."</span><br>";
			echo "<textarea name='edd_screenshots_render_after'  id='edd_after_render' cols=60 rows=4 />{$render_after}</textarea><br><br>";
			
			if (count($screenshots)>0) {
				$i = 0;
				
				foreach ($screenshots as $key=>$image) {
					$c = $i+1;
					echo '<hr><h2>'.__('Screenshot','edd-screenshots').' '.$c.'</h2>';
					echo '<h4 for="upload_image">Screenshot URL</h4>';
					echo '<input name="edd_screenshots['.$i.']"  id="edd_screenshots_'.$i.'" type="text" size="82" value="'.$image.'" />';
					echo '<input id="upload_image_button" class="edd_screenshots_'.$i.'"  type="button" value="Upload Image" />';
					echo '<h4>'.__('Primary Caption','edd-screenshots').'</h4>';
					echo '<input name="edd_screenshots_captions_primary['.$i.']"  id="edd_screenshots_primary_label_0" type="text" style="width:100%;" value="'.$captions_primary[$key].'" />';	
					
					echo '<h4>'.__('Secondary Caption','edd-screenshots').'</h4>';
					echo '<input name="edd_screenshots_captions_secondary['.$i.']"  id="edd_screenshots_secondary_label_0" type="text" style="width:100%;" value="'.$captions_secondary[$key].'" />';	
					echo '<br><br>';
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
				echo '&nbsp;&nbsp;<span class="description">'.__('Caption','edd-screenshots').'</span>=<br>';
				echo '<input name="edd_screenshots_captions_secondary['.$i.']"  id="edd_screenshots_secondary_label_0" type="text" style="width:100%;" value="" />';	
				echo '&nbsp;&nbsp;<span class="description">'.__('Secondary Caption','edd-screenshots').'</span><br><br>';
				$i++;
			}
			
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
			$render_before = $_POST['edd_screenshots_render_before'];
			$render_after =  $_POST['edd_screenshots_render_after'];
			$captions_primary = $_POST['edd_screenshots_captions_primary'];
			$captions_secondary = $_POST['edd_screenshots_captions_secondary'];

			if (is_array($screenshots)) {
				foreach ($screenshots as $key=>$value){
					if (!trim($value)){
						unset($captions_primary[$key]);
						unset($captions_secondary[$key]);
						unset($screenshots[$key]);
					}
				}
				
				$screenshots = implode(',',$screenshots);
				$captions_primary = implode(',',$captions_primary);
				$captions_secondary = implode(',',$captions_secondary);
				
				//echo $array;exit;		
				update_post_meta( $post_id, 'edd_screenshots', $screenshots);	
				update_post_meta( $post_id, 'edd_screenshots_thumbnail_width', $thumbnail_width);			
				update_post_meta( $post_id, 'edd_screenshots_thumbnail_height', $thumbnail_height);			
				update_post_meta( $post_id, 'edd_screenshots_render_before', $render_before);			
				update_post_meta( $post_id, 'edd_screenshots_render_after', $render_after);			
				update_post_meta( $post_id, 'edd_screenshots_captions_primary', $captions_primary);			
				update_post_meta( $post_id, 'edd_screenshots_captions_secondary', $captions_secondary);		
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
			
			$render_before = get_post_meta($post->ID,'edd_screenshots_render_before',true);
			$render_after = get_post_meta($post->ID,'edd_screenshots_render_after',true);

			$captions_primary = get_post_meta($post->ID,'edd_screenshots_captions_primary',true);
			$captions_primary = explode(',',$captions_primary);
			
			$captions_secondary = get_post_meta($post->ID,'edd_screenshots_captions_secondary',true);
			$captions_secondary = explode(',',$captions_secondary);
			
			if (count($screenshots)>0){
			
				$html = "<div class='edd-screenshots-container'>";
				$html .= $render_before;
				$html .= "<ul class='edd-ul-screenshots' >";
				
				foreach ($screenshots as $key=>$image) {			
					$image = trim($image);
					
					$html .= "<li class='screenshot-item{$key}' data-id='id-{$key}' >	
								<div>
									<span class='image-block'>
									<a rel='prettyPhoto[gallery]' href='{$image}' title='".str_replace("'","", $captions_primary[$key])." - ".str_replace("'","", $captions_secondary[$key])." '>
										<img  src='".aq_resize($image,$thumbnail_width, $thumbnail_height)."' alt='".str_replace("'","", $captions_secondary[$key])."' title='".str_replace("'","", $captions_primary[$key])."' />                    
									</a>
									</span>
									<div class='home-portfolio-text'>
										<h2 class='screenshot-caption-primary'>{$captions_primary[$key]}</h2>
										<p class='screenshot-caption-secondary'>{$captions_secondary[$key]}</p>
									</div>                    
								</div>	
							  </li>";
				}

				$html .= "</ul>";
				$html .= $render_after;
				$html .= "</div>";
			
			
				return $html;
			}
		}
		
		/**
		*  Setup licensing
		*/
		public static function setup_licesning() {
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__ , EDD_SCREENSHOTS_ITEM_NAME , EDD_SCREENSHOTS_CURRENT_VERSION , 'Hudson Atwell' );
			}
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
