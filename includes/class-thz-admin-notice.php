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
 *
 * @author CodeFlavors
 *
 */

/**
 * Class Thz_Admin_Notice
 */
class Thz_Admin_Notice{
	/**
	 * @var string
	 */
	private $notice;
	/**
	 * @var string
	 */
	private $class;

	/**
	 * CreatusPRO_Admin_Notice constructor.
	 *
	 * @param string $notice
	 * @param string $class
	 */
	public function __construct( $notice, $class = 'notice is-dismissible' ) {
		if( did_action( 'admin_notices' ) ){
			_doing_it_wrong( __METHOD__, _e( "Notice won't be displayed because it was generated after admin_notices filter", 'creatus-extended' ) );
		}
		$this->notice = $notice;
		$this->class = $class;
		if( !empty( $notice ) ){
			add_action( 'admin_notices', array( $this, 'show_notice' ) );
		}
	}

	/**
	 * admin_notices callback
	 */
	public function show_notice(){
?>
<div class="<?php esc_attr_e( $this->class );?>">
	<p><?php esc_html_e( $this->notice );?></p>
</div>
<?php
	}
}