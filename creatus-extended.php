<?php
/*
Plugin Name: Creatus Extended
Plugin URI: https://github.com/Themezly/Creatus-Extended
Description: Creatus Extended adds demos installation utility, page builder template library and additional functionalities to Creatus WordPress Theme.
Author: Themezly
Author URI: http://themezly.com
Version: 1.0.0
License: GNU/GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2
*/

if(!defined('ABSPATH')){
	 exit;
}

if( ! class_exists( 'CreatusExtended_Plugin' ) ) {
	
	class CreatusExtended_Plugin{
	
		const VERSION = '1.0.0';
		protected static $_instance = null;

		private function __construct() {
			
			// run only if creatus
			if ( !$this->is_creatus() ) {
				return;
			}
		
			// if thz-core is active advise admin to turn it off
			if( $this->is_thz_core_active() ){
				$this->thz_core_is_active_notice();
				return;
			}
			
			// actions
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'widgets_init', array( $this, '_action_widgets_init' ) );
			add_action( 'in_widget_form', array( $this, 'thz_core_in_widget_form' ), 10, 3);
			add_action( 'fw_init', array( $this, '_thz_action_load_builder_templates' ) );
			
			// filters
			add_filter( 'widget_update_callback', array( $this, 'thz_core_widget_update_callback'), 10, 3);
			add_filter( 'widget_display_callback', array( $this, 'thz_core_widget_display_callback'), 10, 3);


		}

		/**
		 * Check if creatus theme
		 */	
		public function is_creatus() {
			
			$is_creatus 	= false;
			$current_theme 	= wp_get_theme();

			if( 'creatus' == $current_theme->get( 'Template' ) ){
				$is_creatus = true;
			}
						
			if ( 'creatus' == get_option( 'template' ) ) {
				$is_creatus = true;
			}
			
			if ( isset($_GET['theme']) ){
				
				if( strpos($_GET['theme'], 'creatus') !== false ){
					$is_creatus = true;
				}else{
					$is_creatus = false;
				}
			}
			
			if ( isset($_GET['customize_theme']) ){
				
				if( strpos($_GET['customize_theme'], 'creatus') !== false ){
					$is_creatus = true;
				}else{
					$is_creatus = false;
				}
			}
			
			return $is_creatus;
		}
		
		/**
		 * Check if thz-core is active
		 */			
		public function is_thz_core_active(){
			
			$active = false;
		
			$active_plugins = get_option( 'active_plugins' );
		
			if ( in_array( 'thz-core/thz-core.php', $active_plugins, true ) ) {
				$active = true;
			}
		
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		
			if ( is_plugin_active_for_network( 'thz-core/thz-core.php' ) ) {
				$active = true;
			}
		
			return $active;
			
		}
		
		/**
		 * Show admin notice if thz-core is active
		 */			
		public function thz_core_is_active_notice(){
			$this->load();
			new Thz_Admin_Notice( __( 'Thz Core Plugin is depreciated. Please disable it.', 'creatus-extended' ), 'notice notice-error is-dismissible' );
		}
		
		/**
		 * Init
		 * @since   1.0.0
		 */	
		public function init() {
			
			$this->thz_action_taxonomies_register();
			
			// theme libs
			$this->load_libs();
			
			// theme helpers
			$this->load_helpers();
		}
		
		/**
		 * Admin init
		 * @since   1.0.0
		 */		
		public function admin_init(){
	
			load_plugin_textdomain( 'creatus-extended', false, dirname(plugin_basename(__FILE__)).'/languages/');
			
			$this->thz_action_editor_shortcodes_buttons();

		}
		
		/**
		 * Add editor shortcodes button
		 */		
		public function thz_filter_add_editor_buttons( $plugin_array ) {
			$plugin_array['thz_editor_shortcodes'] = plugin_dir_url( __FILE__ ) . 'assets/js/thz-core-editor-plugin.js';
			return $plugin_array;
		}

		/**
		 * Add button to the editor
		 */			
		public function thz_filter_register_editor_buttons( $buttons ) {
			array_push( $buttons, 'thz_editor_shortcodes'); 
			return $buttons;
		}

		/**
		 * Register and add editor buttons
		 */			
		protected function thz_action_editor_shortcodes_buttons() {
			
			if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
				add_filter( 
					'mce_external_plugins', 
					array($this, 'thz_filter_add_editor_buttons')
				);
				
				add_filter( 
					'mce_buttons', 
					array($this, 'thz_filter_register_editor_buttons') 
				);
			}
			
		}

		/**
		 * Load dependencies
		 */
		private function load(){
			$path = plugin_dir_path( __FILE__ );
			require_once $path . 'includes/class-thz-admin-notice.php';
		}
	
		/**
		 * Include theme helpers
		 */
		public static function load_helpers() {
			
			$path = plugin_dir_path( __FILE__ );
			require_once $path . 'includes/helpers.php';
			if( !thz_theme_file_path( '/inc/includes/class-thz-demos.php' ) ){
				require_once $path . 'includes/class-thz-demos.php';
				require_once $path . 'includes/fonts-utility.php';
			}
			require_once $path . 'includes/hooks.php';
			require_once $path . 'includes/shortcodes.php';
			
			define( 'THZHELPERS', true);
			define( 'CREATUSEXTENDED', true);
			
		}
		
		/**
		 * Include libraries
		 */
		public static function load_libs() {
			$path = plugin_dir_path( __FILE__ );
			
			require_once $path . 'includes/parsedown/parsedown.php';
			require_once $path . 'includes/parsedown/parsedownextra.php';
			require_once $path . 'includes/twitteroauth/twitteroauth.php';
		}

		/**
		 * Returns the class instance
		 * @return  ThzCore_Plugin instance
		 */
		public static function get_instance() {
	
			if ( null == self::$_instance ) {
				self::$_instance = new self;
			}
	
			return self::$_instance;
		}

		/**
		 *  Check if Unyson is active
		 */
		public function thz_fw_active() {
		
			$active = false;
		
			$active_plugins = get_option( 'active_plugins' );
		
			if ( in_array( 'unyson/unyson.php', $active_plugins, true ) ) {
				$active = true;
			}
		
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		
			if ( is_plugin_active_for_network( 'unyson/unyson.php' ) ) {
				$active = true;
			}
		
			return $active;
		
		}
		
		/**
		 * Register taxonimies
		 */
		function thz_action_taxonomies_register() {
			
			$thz_taxonomies = array();
			
			// Page category
			$args = array(
				'hierarchical' => true,
				'public' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud' => false,
			);
			
			$thz_taxonomies []= array(
				'taxonomy'  => 'page_category',
				'post_type' => 'page',
				'args'      => $args
			);
			
			// Page tags
			register_taxonomy_for_object_type( 'post_tag', 'page' );
				
        	$thz_taxonomies = apply_filters( 'thz_filter_taxonomies_register', $thz_taxonomies );
			
			if( !empty($thz_taxonomies)){
				
				foreach( $thz_taxonomies as $thz_tax ){
					
					register_taxonomy(
						$thz_tax['taxonomy'],
						$thz_tax['post_type'],
						$thz_tax['args']
					);				
				}
				
				unset($thz_taxonomies,$thz_tax);
			}

		}
		
		/**
		 * Get term by ID
		 */
		static function thz_get_term_by_id( $term_id, $output = OBJECT, $filter = 'raw' ) {
			
			if( !is_numeric($term_id) ){
				return false;
			}
			
			global $wpdb;
		
			$_tax     = $wpdb->get_row( $wpdb->prepare( "SELECT t.* FROM $wpdb->term_taxonomy AS t WHERE t.term_id = %s LIMIT 1", $term_id ) );
			$taxonomy = $_tax->taxonomy;
		
			return get_term( $term_id, $taxonomy, $output, $filter );
		
		}

		/**
		 * Render widget form ID and class options
		 */
		public function thz_core_in_widget_form( $instance, $empty, $options  ) {

			$value_id 		= isset( $options['thz_wi_id']) ?  $options['thz_wi_id'] : '';
			$value_class 	= isset( $options['thz_wi_class']) ?  $options['thz_wi_class'] : '';
	
			echo $this->thz_core_backend_render(dirname(__FILE__) .'/includes/views/widgets-view.php', array(
				'instance' 			=> $instance,
				'options' 			=> $options,
				'value_id' 			=> $value_id,
				'value_class' 		=> $value_class,
			));
			
		}
				
		/**
		 * Update widget form
		 * @return array
		 */
		public function thz_core_widget_update_callback ( $instance, $new_instance) {

			$instance['thz_wi_id'] 		= $new_instance['thz_wi_id'];
			$instance['thz_wi_class'] 	= $new_instance['thz_wi_class'];
									
			return $instance;
		}

		/**
		 * Replace widget ID or add class
		 * @return object
		 */		
		public function thz_core_widget_display_callback ( $instance, $widget, $args ) {
			
			$id_changed 	= false;
			$class_changed 	= false;
			
			if(isset($instance['thz_wi_id']) && $instance['thz_wi_id'] !=''){
				
				$widget_id = $widget->id;
				$new_id = $instance['thz_wi_id'];
				$args['before_widget'] = str_replace($widget_id, "{$new_id}", $args['before_widget']);
				$id_changed = true;
			}
						
			if(isset($instance['thz_wi_class']) && $instance['thz_wi_class'] !=''){
				
				$classname = $widget->widget_options['classname'];
				$add_class = $instance['thz_wi_class'];
				$args['before_widget'] = str_replace($classname, "{$classname} {$add_class}", $args['before_widget']);
				$class_changed = true;
			
			}
			
			if( $id_changed || $class_changed){
				
				$widget->widget($args, $instance);
				return false;
				
			}else{
				return  $instance;
			}

		}
				
		/**
		 * Load render
		 * @return string
		 */	
		public function thz_core_backend_render($file_path, $view_variables = array(), $return = true) {
			extract($view_variables, EXTR_REFS);
			unset($view_variables);
			if ($return) {
				ob_start();
				require $file_path;
				return ob_get_clean();
			} else {
				require $file_path;
			}
		}
		
		/**
		 * Check if widget exists
		 * @param string $dirname - widget folder name
		 */
		public static function widget_exists( $dirname ) {
			return thz_theme_file_path( '/inc/widgets/'. $dirname .'/class-widget-' . $dirname . '.php' );
		}

		/**
		 * Include plugin widgets
		 */			
		public static function _action_widgets_init() {
			
			$path 		= plugin_dir_path( __FILE__ ).'includes/widgets';
			$dirs 		= glob( $path . '/*', GLOB_ONLYDIR );

			if ( $dirs ) {
				
				foreach ( $dirs as $dir ) {
					$dirname = basename( $dir );

					if( self::widget_exists( $dirname ) ) {
						continue;
					}else{
						include( $dir . '/class-widget-' . $dirname . '.php' );
						register_widget( 'Widget_' . self::dirname_to_classname( $dirname ) );
					}
	
				}
			
			}
		}
		
		/**
		 * @param string $dirname 'foo-bar'
		 * @return string 'Foo_Bar'
		 */
		private static function dirname_to_classname( $dirname ) {
			$class_name = explode( '-', $dirname );
			$class_name = array_map( 'ucfirst', $class_name );
			$class_name = implode( '_', $class_name );
	
			return $class_name;
		}
		
		
		/*
		 * Load page builder templates
		*/
		public static function _thz_action_load_builder_templates() {
			
			$init_builder_templates = plugin_dir_path( __FILE__ ).'/includes/builder-templates/init.php';
			
			if( !thz_theme_file_path( '/inc/includes/builder-templates/init.php' ) ){
						
				require_once $init_builder_templates;
			
			}
			
		}

	}
}

add_action( 'plugins_loaded', array( 'CreatusExtended_Plugin', 'get_instance' ) );