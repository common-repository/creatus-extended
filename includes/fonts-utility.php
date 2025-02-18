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
 * Get Typekit user fonts
 * @return array
 */	
function thz_get_typekit_fonts($tyk_token = false,$tykit_ids = array()) {
		
	if($tyk_token && !empty($tykit_ids)){
		
		$tyk_fonts			= array();
		
		foreach($tykit_ids as $kit_id){
		
			$url = "https://typekit.com/api/v1/json/kits/" . $kit_id;
			$args = array(
				'sslverify' => false,
				'timeout' => 20000,
			);
			$response = wp_remote_request($url."?token=$tyk_token", $args);		
			
			
			if (!is_wp_error($response)) {
				
				$response = wp_remote_retrieve_body($response);
				$response = json_decode($response,true);	
							
				if(isset($response['kit'])){
					
					// set typekit data
					$tyk_fonts['kits_data'][$kit_id] = array(
						'name' => $response['kit']['name'],
					);
					
					foreach( $response['kit']['families'] as $fam => $family ){
						
						foreach( $family['variations'] as $v => $variation ){
							
							$response['kit']['families'][$fam]['variations'][$v] = thz_set_typekit_variation($variation);
							
						}
						
						// set fonts data
						$slug = thz_typekit_slug($family['css_stack']);
						$tyk_fonts['fonts_data'][$slug] = array(
							
							'name' => $family['name'],
							'kitid' => $kit_id,
							'variations' => $response['kit']['families'][$fam]['variations'],
							'slug' => $family['slug'],
							'css_stack' => $family['css_stack'],
						
						);

					}
					
					// build select for typography option
					$tyk_fonts['select'] = thz_buld_typekit_select_list( $response['kit']['families'],$kit_id );
					//$tyk_fonts[$kit_id] = $response;
				}
				
			}
			
			unset($kit_id,$response);
		}
		
		unset($tykit_ids);
		
		
		if(!empty($tyk_fonts)){
			
			return $tyk_fonts;
			
		}else{
			
			return false;
			
		}
		
	}
	
	return false;
		
}

/**
 * Create custom Typekit font slug to be used by options
 * @return string
 */
function thz_typekit_slug($css_stack){
	
	$slug 		= explode(',',$css_stack);
	$slug 		= str_replace(array('"',"'"),'',$slug[0]);
	
	return $slug;	
}

/**
 * Replace Typekit font weights to match the typography option
 * @return string
 */
function thz_set_typekit_variation($variation) {
	
	$variation = str_replace('n','',$variation);
	$variation = str_replace($variation,$variation.'00',$variation);		
	
	if(thz_contains($variation,'i')){
		$variation = str_replace('i','',$variation);
		$variation = str_replace($variation,$variation.'italic',$variation);
	}
	
	return $variation;
}


/**
 * Build Typekit user fonts list for import fonts option
 * @return string
 */	
function thz_build_typekit_list($data) {
		
	if(!$data){
		
		return;
	}
	
	$kits_data = $data['kits_data'];
	$fonts_data = $data['fonts_data'];
	
	$html ='';
	foreach($kits_data as $k => $kitid){

		$html .= '<div class="thz-typekit-wrapper">';
		$html .= '<span class="thz-typekit-name">'.esc_html('Project name','creatus-extended').': '.$kitid['name'].'</span>';
		$html .= '<span class="thz-typekit-families">'.esc_html('Font families','creatus-extended').':</span>';
		$html .= '<ul>';
		foreach($fonts_data as $fontfamily){
			
			$html .= '<li>';
			$html .= '<a href="https://typekit.com/fonts/'.$fontfamily['slug'].'" target="_blank">';
			$html .= $fontfamily['name'];
			$html .= '</a>';
			$html .= '</li>';
		}
		
		$html .= '</ul>';
		$html .= '</div>';
		
		unset($kitid);
	}
	unset($data,$kits_data,$fonts_data);
	
	return $html;
}


/**
 * Build Typekit select list for typography option
 * @return string
 */
function thz_buld_typekit_select_list($families, $kit_id ) {

	$select = '';
	$fonts = array();
	
	foreach( $families as $family ){
		
		$fonts[$family['slug']] = $family;
		
		unset($family);
	}
	
	unset($families);
	
	foreach( $fonts as $font ){
		
		$font_variants 	= implode(',',$font['variations']);
		$css_stack = str_replace('"', "'",$font['css_stack']);
		$select .='<option value="'.(string) $css_stack.'" data-type="typekit" data-kitid="'.$kit_id.'" data-variants="'.$font_variants.'" data-subsets="'.$font['subset'].'">';
		$select .= $font['name'];
		$select .='</option>';		
		
	}
	
	
	return $select;
}



/**
 * Get Fontsquirrel fonts
 * @return json
 */	
function thz_get_fsq_fonts() {
		
	$saved_data = get_option( 'thz_fontsquirrel_fonts', false );
	
	$ttl        = 7 * DAY_IN_SECONDS;
	
	if (
		false === $saved_data
		||
		( $saved_data['last_update'] + $ttl < time() )
	) {
		$response = wp_remote_get( apply_filters( 'thz_fontsquirrel_webfonts_url',
			'https://www.fontsquirrel.com/api/fontlist/all' ) );
		$body     = wp_remote_retrieve_body( $response );
		if (
			200 === wp_remote_retrieve_response_code( $response )
			&&
			! is_wp_error( $body ) && ! empty( $body )
		) {
			update_option( 'thz_fontsquirrel_fonts',
				array(
					'last_update' => time(),
					'fonts'       => $body
				),
				false );
			return $body;
		} else {
			if ( empty( $saved_data['fonts'] ) ) {
				$saved_data['fonts'] = json_encode( array() );
			}
			update_option( 'thz_fontsquirrel_fonts',
				array(
					'last_update' => time() - $ttl + MINUTE_IN_SECONDS,
					'fonts'       => $saved_data['fonts']
				),
				false );
		}
	}
	return $saved_data['fonts'];
		
}

/**
 * Get Fontsquirrel classifications
 * @return json
 */	
function thz_get_fsq_classifications() {
		
	$saved_data = get_option( 'thz_fontsquirrel_classifications', false );
	
	$ttl        = 7 * DAY_IN_SECONDS;
	
	if (
		false === $saved_data
		||
		( $saved_data['last_update'] + $ttl < time() )
	) {
		$response = wp_remote_get( apply_filters( 'thz_fontsquirrel_classifications_url',
			'https://www.fontsquirrel.com/api/classifications' ) );
		$body     = wp_remote_retrieve_body( $response );
		if (
			200 === wp_remote_retrieve_response_code( $response )
			&&
			! is_wp_error( $body ) && ! empty( $body )
		) {
			update_option( 'thz_fontsquirrel_classifications',
				array(
					'last_update' => time(),
					'classifications'  => $body
				),
				false );
			return $body;
		} else {
			if ( empty( $saved_data['classifications'] ) ) {
				$saved_data['classifications'] = json_encode( array() );
			}
			update_option( 'thz_fontsquirrel_classifications',
				array(
					'last_update' => time() - $ttl + MINUTE_IN_SECONDS,
					'classifications'       => $saved_data['classifications']
				),
				false );
		}
	}
	
	return $saved_data['classifications'];
		
}

/**
 * Build Fontsquirrel fonts list
 * @return string
 */	
function thz_build_fsq_list() {
		
	
	$fonts = json_decode( thz_get_fsq_fonts() , true );
	
	if(!is_array($fonts) || empty($fonts)){
		
		return;
	}
	
	
	$imported = get_option('thz_imported_fonts');

	$classifications = json_decode( thz_get_fsq_classifications() , true ); 

	$html = '<div class="thz-fontsquirrel-wrapper">';
	$html .= '<div class="downloaded-fonts"></div>';
	$html .= '<div class="fsearch-container">';
	$html .= '<input name="search" class="fsearch" placeholder="'.esc_html('Quick search','creatus-extended').'" type="text" data-list=".fonts">';
	$html .= '<a href="#" class="dashicons dashicons-dismiss clear-fsearch"></a>';
	$html .= '</div>';
	
	$html .= '<ul class="categories">';
	
	foreach($classifications as $cat){
		
		$name =  urldecode($cat['name']);
		$class = '.cat_'.strtolower(str_replace(' ','_',$name));
		$html .= '<li class="'.$name.'">';
		$html .= '<a class="category-link" href="#" data-filter="'.$class.'">';
		$html .= $name. ' ('.$cat['count'].')';
		$html .= '</a>';
		$html .= '</li>';
		
		unset($cat);
	}
	unset($classifications);
	
	$html .= '</ul>';
	
	$html .= '<ul class="fonts">';
	foreach($fonts as $font){
		
		$cat 		=  urldecode($font['classification']);
		$class 		= 'cat_'.strtolower(str_replace(' ','_',$cat)).' '.$font['family_urlname'];
		$hide_del 	= ' hide-icon';
		$hide_down 	= '';
		
		if($cat != 'Blackletter'){
			$class .=' inactive';
		}
		
	
		if(isset($imported['fsqfonts'][$font['family_urlname']])){

			$hide_del 	= '';
			$hide_down 	= ' hide-icon';	
			$class		.= ' is-down';	
		}
		
		$html .= '<li class="'.$class.'" data-name="'.$font['family_name'].'">';
		$html .= '<span class="font-title">'.$font['family_name'].'</span>';
		$html .= '<a class="delete-font'.$hide_del.'" href="#" data-font="'.$font['family_urlname'].'">';
		$html .= '<span class="mati mati-cancel"></span>';
		$html .= '</a>';
		$html .= '<a class="download-font'.$hide_down.'" href="#" data-font="'.$font['family_urlname'].'">';
		$html .= '<span class="thzicon thzicon-cloud-download3"></span>';
		$html .= '</a>';
		$html .= '<a class="preview-font" href="https://www.fontsquirrel.com/fonts/'.$font['family_urlname'].'" target="_blank">';
		$html .= '<span class="thzicon thzicon-eye2"></span>';
		$html .= '</a>';
		$html .= '</li>';

		unset($font);
	}
	$html .= '</ul>';
	
	
	$html .= '</div>';
	
	unset($fonts);
	
	return $html;
}


/**
 * Get Fontsquirrel family info
 * @return array
 */	
function thz_get_fsq_familyinfo($family_urlname) {
		
	if($family_urlname){
		
		$fsq_font	= array();
		
		$url = "https://www.fontsquirrel.com/api/familyinfo/" . $family_urlname;
		$args = array(
			'sslverify' => false,
			'timeout' => 20000,
		);
		
		$response = wp_remote_request($url, $args);		
		
		if (!is_wp_error($response)) {

			$response = wp_remote_retrieve_body($response);
			$response = json_decode($response,true);	
						
			if(!empty($response)){
				$fsq_font[$family_urlname] = $response;
			}
			
		}
		
		if(!empty($fsq_font)){
			
			return $fsq_font;
			
		}else{
			
			return false;
			
		}
		
	}
	
	return false;
		
}


/**
 * Build Fontsquirrel font data
 * @return array
 */	
function thz_build_fsq_font_data($family_urlname) {
	
	$imported = get_option('thz_imported_fonts');
	
	if(isset($imported['fsqfonts'][$family_urlname])){
		
		return $imported['fsqfonts'][$family_urlname];
		
	}else{
	
		$data = thz_get_fsq_familyinfo($family_urlname);
		
		if(!$data){
			
			return;
		}
		
		$font_info 	=  array();
		$dirs 		= wp_upload_dir();
		$baseurl 	= $dirs['baseurl'];	
		$f_url 		= $baseurl.'/'.THEME_NAME.'/f/'.$family_urlname.'/';
		
		foreach($data[$family_urlname] as $variant){
			
			$info 		= pathinfo($variant['filename']);
			$ff 		= $variant['fontface_name'];
			$name 		= $variant['family_name'].' '.$variant['style_name'];
			$font_file 	= $f_url.$info['filename'].'-webfont.woff';
	
			$font_info[$family_urlname][$ff]['name'] = $name;
			$font_info[$family_urlname][$ff]['font_family'] = $ff;
			$font_info[$family_urlname][$ff]['font_file'] = $font_file;
			$font_info[$family_urlname][$ff]['css'] = thz_build_fsq_font_face_css( $ff, $font_file );
			
			unset($variant);
		}
		
		unset($data);
		
		$imported['fsqfonts'][$family_urlname] = $font_info[$family_urlname];
		
		update_option( 'thz_imported_fonts', $imported );
		
		return $font_info;
	
	}
}

/**
 * Build Fontsquirrel @font-face CSS
 * @return string
 */	
function thz_build_fsq_font_face_css( $font_family,$font_file ) {
	
	$css = '@font-face {';
	$css .= 'font-family: \''.$font_family.'\';';
	$css .= 'src: url(\''.$font_file.'\') format(\'woff\');';
	$css .= 'font-weight: normal;';
	$css .= 'font-style: normal;';
	$css .= '}';
		
	return $css;
}

/**
 * Get Fontsquirrel fontface kit
 * @return array
 */	
function thz_get_fsq_fontfacekit( $family_urlname ) {
	
	$thz_fs 	= thz_wp_file_system();
	$dirs 		= wp_upload_dir();
	$basedir 	= $dirs['basedir'];	
	$path 		= $basedir.THZDS.THEME_NAME.THZDS;
	$f_path		= $path.'f'.THZDS;
			
	if($thz_fs->is_dir( $f_path . $family_urlname)){
		
		return thz_build_fsq_font_data($family_urlname);
		
	}
	
	if($family_urlname){
		
		$url = 'https://www.fontsquirrel.com/fontfacekit/'. $family_urlname;
		
		$args = array(
			'sslverify' => false,
			'timeout' => 20000,
		);

		$response = wp_remote_request($url,$args);
		
		// we got the response let's dance :)
		if (!is_wp_error($response)) {
			
			$zip_file 	= wp_remote_retrieve_body($response);
			$zip_name	= $family_urlname.'-fontfacekit.zip';
			$zip_path	= $f_path.$zip_name;

			if (!is_dir( $f_path ) ){
				
				if (wp_mkdir_p($f_path)) {
					
					$thz_fs->put_contents($f_path.THZDS.'index.html','');
				
				}else{
					
					return;
					
				}
			}
			
			// zip the response and save it in uploads/creatus/f/font_name folder
			if ( $thz_fs->put_contents($zip_path, $zip_file, FS_CHMOD_FILE) && is_dir( $f_path ) ){
				
				// now unzip the file
				if( unzip_file($zip_path, $f_path.$family_urlname)){
					
					//is unziped, remove downloaded zip
					$thz_fs->delete($zip_path);
					
					// add index.html in font folder
					$thz_fs->put_contents($f_path.$family_urlname.THZDS.'index.html','');
					
					$dirs = $thz_fs->dirlist($f_path.$family_urlname.THZDS.'web fonts');
					
					// loop trough web fonts folder to get variants
					foreach($dirs as $name => $dir){
						
						$files_list = $thz_fs->dirlist($f_path.$family_urlname.THZDS.'web fonts'.THZDS.$name.THZDS);
						
						// loop trough variant folder to get files
						foreach ( $files_list as $f_name => $file ){
							
							$info = pathinfo($f_name);
							
							// only get woff files
							if (isset($info['extension']) && $info['extension'] == 'woff'){
								
								$file_path 	= $f_path.$family_urlname.THZDS.'web fonts'.THZDS.$name.THZDS.$f_name;
								$new_path	= $f_path.$family_urlname.THZDS.$f_name;
								
								// move the file from variant folder to main folder
								$thz_fs->move( $file_path, $new_path);

							}
							unset($file);
						}
						unset($files_list,$dir);
					}
					unset($dirs);
					
					// delete web font folder and all sub folders
					if( $thz_fs->delete($f_path.$family_urlname.THZDS.'web fonts'.THZDS, true)){
						
						// delete how to use font html
						$thz_fs->delete($f_path.$family_urlname.THZDS.'How_to_use_webfonts.html');
						
						return thz_build_fsq_font_data($family_urlname);
					}
					
				}
			}

		}

	}
	
	return false;	
	
}

/**
 * Delete Fontsquirrel fontface kit
 * @return array
 */	
function thz_delete_fsq_fontfacekit( $family_urlname ) {
	
	$thz_fs 	= thz_wp_file_system();
	$dirs 		= wp_upload_dir();
	$basedir 	= $dirs['basedir'];	
	$path 		= $basedir.THZDS.THEME_NAME.THZDS;
	$f_path		= $path.'f'.THZDS;
	
	if( $thz_fs->delete($f_path.$family_urlname,true) ){
		
		$imported 	= get_option('thz_imported_fonts');
		
		if(isset($imported['fsqfonts'][$family_urlname])){
			unset( $imported['fsqfonts'][$family_urlname] );
			update_option('thz_imported_fonts', $imported);
		}	
		
		return true;	
	}
	
	return false;
}



/**
 * Get Fontsquirrel fontface CSS
 * @return string
 */	
function thz_get_fsq_css( $family, $variant ) {
	
	$imported 	= get_option('thz_imported_fonts');
	
	$css = thz_akg('fsqfonts/'.$family.'/'.$variant.'/css',$imported, false);
	
	if($css){
		return $css;
	}
	
	return false;
}