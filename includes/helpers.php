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

/**
 * Protect email
*/

if ( ! function_exists( 'thz_core_protect_email' ) ) {
	
	function thz_core_protect_email ( $email,$mailto = false ){
		 
		 $mailto = $mailto ? 'mailto:' :'';
		 $link = $mailto.$email;
		 $html = "";
		 for ($i=0; $i<strlen($link); $i++){
			 $html .= "&#" . ord($link[$i]) . ";";
		 }
		 if($html !=''){
			return $html;
		 }	 
	}

}

/**
 * Get theme version
*/
if ( ! function_exists( 'thz_core_theme_version' ) ) {
	
	function thz_core_theme_version(){
		
		if ( function_exists( 'thz_theme_version' ) ) {
			
			return thz_theme_version();
			
		}else{
		
			$theme = wp_get_theme();
			$current_version = 'creatus-extended' == $theme->get('Template') ? $theme->parent()->get('Version') : $theme->get('Version');
			return $current_version;
		
		}
	}
}

/**
 * @param string $code name of the shortcode
 * @param string $content
 *
 * @return string content with shortcode striped
 */
if ( thz_core_theme_version() > '1.5.0' && !function_exists( '_thz_strip_shortcode' ) ) {
	function _thz_strip_shortcode( $code, $content ) {
	
		global $shortcode_tags;
	
		$stack          = $shortcode_tags;
		$shortcode_tags = array( $code => 1 );
	
		$content = strip_shortcodes( $content );
	
		$shortcode_tags = $stack;
	
		return $content;
	}
}