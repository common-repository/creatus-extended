<?php
/**
 * @package      Thz Framework
 * @author       Themezly
 * @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @websites     http://www.themezly.com | http://www.youjoomla.com | http://www.yjsimplegrid.com
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access
}

class ThzDemos {

    /**
     * Global instance object
     */
    private static $_instance = null;

    /**
     * API url
     */   	
	private $api_uri;

	/**
	 * Allow or bypass request cache
	 * @var bool - allow cache (false) or bypass it (true)
	 */
	private $no_cache;
	
    /**
     * Transient name
     */   	
	private $transient;
	

    /**
     * Last update option
     */   	
	private $last_update;

	/**
	 * ThzBuilderTemplates constructor.
	 */
	public function __construct() {
		
		$this->no_cache 	= apply_filters( '_thz_filter_demos_list_no_cache', false );
		$this->api_uri 		= apply_filters( '_thz_filter_demos_api_url', 'https://resources.themezly.io/api/v1/demos' );
		$this->transient 	= 'thz:demos:list';
		$this->last_update 	= 'thz:demos:list:last:update';
		
		add_action('wp_ajax_thz_refresh_demos_list', array($this, 'demos_list_refresh'));

	}
	
    /**
     * Returns the class instance
     *
     * @return  Thz_Doc instance
     *
     * @since   1.0.0
     */
    
    public static function getInstance() {
        
        if ( self::$_instance == null ) {
            self::$_instance = new ThzDemos();
        }
        return self::$_instance;
    }
	
	
	public function demos_list(){
		
		$transient = $this->transient;

		if ( $this->no_cache || false === ( $demos_list = get_transient( $transient ) ) ) {
			
			delete_transient( $transient );
			
			$response = wp_remote_get( $this->api_uri , array( 'timeout' => 20 ) );
			$httpCode = wp_remote_retrieve_response_code( $response );
	
			if ( $httpCode >= 200 && $httpCode < 300 ) {
				
				$demos_list = wp_remote_retrieve_body( $response );
				
			} else {
				
				$demos_list = esc_html__( 'Not able to load demos', 'creatus-extended' );
				
			}
			
			update_option ($this->last_update, time() );
			set_transient( $transient, $demos_list, 7 * DAY_IN_SECONDS );
		
		}

		$data = json_decode($demos_list ,true );

		return $data;
							
	}	
	
	public function demos_list_refresh(){
		
		$transient = $this->transient;
		
		if( $this->can_refresh() && delete_transient( $transient )){

			wp_send_json_success();
			
		}else{
			
			wp_send_json_error();
			
		}
		
	}
		
	public function can_refresh(){
		
		$last_update = get_option($this->last_update, 0);
		
		if( $this->no_cache || $last_update <= strtotime('-15 minutes') ){
			
			return true;
		}
		
		return false;
	}

}

ThzDemos::getInstance();


/**
 * List of full demos
 */
function _thz_get_demos_list(){
	
	$ThzDemos = ThzDemos::getInstance();
	return $ThzDemos->demos_list();

}

/**
 * @param FW_Ext_Backups_Demo[] $demos
 * @return FW_Ext_Backups_Demo[] 
 * http://manual.unyson.io/en/latest/extension/backups/#create-demos
 */
function _thz_filter_theme_fw_ext_backups_demos($demos) {
	
	$demos_list  = _thz_get_demos_list();
	
	if(!$demos_list){
		
		return $demos;
	}
	
	$download_url = apply_filters( '_thz_filter_demos_download_url', 'https://updates.themezly.io/demos/' );

    foreach ($demos_list as $id => $data) {
        $demo = new FW_Ext_Backups_Demo($id, 'piecemeal', array(
            'url' => $download_url,
            'file_id' => $id,
        ));
        $demo->set_title($data['title']);
        $demo->set_screenshot($data['screenshot']);
        $demo->set_preview_link($data['preview_link']);
		
		if( isset($data['extra'])){
			$demo->set_extra($data['extra']);
		}
		
        $demos[ $demo->get_id() ] = $demo;

        unset($demo);
    }

    return $demos;
}

add_filter('fw:ext:backups-demo:demos', '_thz_filter_theme_fw_ext_backups_demos');


/**
 * Disable demo image sizes restore
 * https://github.com/ThemeFuse/Unyson-Backups-Extension/issues/15
 * https://github.com/ThemeFuse/Unyson-Backups-Extension/issues/30
 */
if ( ! function_exists( '_thz_filter_disable_demo_img_sizes_restore' ) ) {
	function _thz_filter_disable_demo_img_sizes_restore( $do, FW_Ext_Backups_Task_Collection $collection ) {
		
		$demos_list = _thz_get_demos_list();
		
		if (
			$collection->get_id() === 'demo-content-install'
			&&
			($task = $collection->get_task('demo:demo-download'))
			&&
			($task_args = $task->get_args())
			&&
			isset($task_args['demo_id'])
			&&
			isset($demos_list[$task_args['demo_id']]['sizes_removal'])
			&&
			$demos_list[$task_args['demo_id']]['sizes_removal'] === false
		) {
			$do = false;
		}
	
		return $do;
	}
}
add_filter('fw:ext:backups:add-restore-task:image-sizes-restore', '_thz_filter_disable_demo_img_sizes_restore', 10, 2);