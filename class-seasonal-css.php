<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Seasonal CSS Class
 *
 * @package Seasonal CSS
 * @author  uamv
 */
class Seasonal_CSS {

	/*---------------------------------------------------------------------------------*
	 * Attributes
	 *---------------------------------------------------------------------------------*/

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1
	 *
	 * @var     string
	 */
	protected $version = SEASONAL_CSS_VERSION;

	/**
	 * Notices.
	 *
	 * @since    1.0
	 *
	 * @var      array
	 */
	protected $notices;

	/**
	 * Seasonal CSS.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $seasonal_css;

	/*---------------------------------------------------------------------------------*
	 * Consturctor
	 *---------------------------------------------------------------------------------*/

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	public function run() {

			if ( $this->verify_dependencies( ['carbon_fields' => '2.0.3'] ) ) {

				add_action( 'after_setup_theme', array( $this, 'load_carbon' ) );

				add_action( 'carbon_fields_register_fields', array( $this, 'theme_options' ) );

				add_filter( 'carbon_fields_seasonal_css_button_label', array( $this, 'button_text' ) );
				// add_filter( 'carbon_fields_theme_options_container_access_capability', array( $this, 'set_capability' ), 10, 2 );

				add_action( 'init', array( $this, 'determine_active_rules' ) );

				add_action( 'wp_head', array( $this, 'add_css' ) );
				add_action( 'admin_head', array( $this, 'add_css' ) );
			}

			add_action( 'admin_notices', array( $this, 'show_notices' ) );

	} // end constructor

	/*---------------------------------------------------------------------------------*
	 * Public Functions
	 *---------------------------------------------------------------------------------*/

	 /**
 	 * Load Carbon Fields
 	 *
 	 * @since    0.1
 	 */
	public function load_carbon() {

	    require_once( 'vendor/autoload.php' );
	    \Carbon_Fields\Carbon_Fields::boot();

	} // end load_carbon

	private function verify_dependencies( $deps ) {

		// Check if outdated version of Carbon Fields loaded
		if ( ! defined( '\\Carbon_Fields\\VERSION' ) || version_compare( \Carbon_Fields\VERSION, $deps['carbon_fields'], '>=' )  ) {
			return true;
		} else if ( version_compare( \Carbon_Fields\VERSION, $deps['carbon_fields'], '<' ) ) {
			$this->notices[] = array( 'message' => '<a href="http://wordpress.org/plugins/seasonal-css/"><img src="' . SEASONAL_CSS_DIR_URL . 'seasonal-css-icon.png" style="float: left; width: 1.5em; height: 1.5em; margin-right: 1em;" /></a><strong>' . __('Seasonal CSS is not accessible as it is unable to load the proper version of Carbon Fields. An older version (' . \Carbon_Fields\VERSION) . ') has already been loaded on this site.</strong>', 'class' => 'error' );
			return false;
		}

	}

	/**
	 * Define available seasonal CSS theme options
	 *
	 * @since    0.1
	 */
	public function theme_options() {

	    Container::make('theme_options', __( 'Seasonal CSS' , 'seasonal-css' ) )
	        ->set_page_parent( 'themes.php' )
	        ->add_fields( array(
	            Field::make('complex', 'seasonal_css_rules', 'These styles will be applied during the set time range.' )
	                ->add_fields(array(
	                    Field::make('text', 'seasonal_css_comment', 'Style Documentation')
	                        ->set_width(100),
	                    Field::make('textarea', 'seasonal_css_rules', 'CSS')
	                        ->set_width(100),
	                    Field::make( 'date_time', 'seasonal_css_start_datetime', 'Start Date' )
	                        ->set_input_format( 'j F Y h:i A', 'j F Y h:i K' )
	                        ->set_width(42),
	                    Field::make( 'date_time', 'seasonal_css_end_datetime', 'End Date' )
	                        ->set_input_format( 'j F Y h:i A', 'j F Y h:i K' )
	                        ->set_width(42),
	                    Field::make( 'select', 'seasonal_css_repeat', 'Repeat?' )
	                        ->set_options( array(
	                            'never'  => 'Never',
	                            'yearly' => 'Yearly'
	                        ))
	                        ->set_width(16)
	                ))
	                ->set_layout('tabbed-vertical')
	            ));

	} // end theme_options

	/**
	 * Set button text
	 *
	 * @since    0.2
	 */
	public function button_text( $text ) {

		return 'Save Seasonal CSS Rules';

	} // end set_capability

	/**
	 * Set capability required to add rules
	 *
	 * @since    0.2
	 */
	public function set_capability( $enable, $title ) {

		if ( 'Seasonal CSS' == $title ) {
			return apply_filters( 'seasonal_css_capability', 'switch_themes' );
		}

	} // end set_capability

	/**
	 * Adds active CSS to the site header
	 *
	 * @since    0.1
	 */
	public function add_css() {

		echo '<style type="text/css">' . $this->seasonal_css . '</style>';

	} // end add_css

	/**
	 * Fetch rules and determine which are active
	 *
	 * @since    1.0
	 */
	public function determine_active_rules() {

		// get the field value
		$rules = carbon_get_theme_option( 'seasonal_css_rules' );

		foreach ( $rules as &$rule ) {

			switch ( $rule['seasonal_css_repeat'] ) {
				case 'yearly':
					if ( $this->is_active_yearly( $rule['seasonal_css_start_datetime'], $rule['seasonal_css_end_datetime'] ) ) {
						// $this->seasonal_css .= ' /*' . $rule['seasonal_css_comment'] . '*/ ';
						$this->seasonal_css .= $rule['seasonal_css_rules'];
					}
					break;
				case 'never':
					if ( $this->is_active_once( $rule['seasonal_css_start_datetime'], $rule['seasonal_css_end_datetime'] ) ) {
						$this->seasonal_css .= $rule['seasonal_css_rules'];
					}
					break;

				default:
					# code...
					break;
			}

		}

	} // end determine_active_rules

	/**
	 * Check if a yearly repeating rule is active
	 *
	 * @since    0.1
	 */
	public function is_active_yearly( $start, $end ) {

		$upperBound = strtotime( $end );
	    $lowerBound = strtotime( $start );
	    $checkDate = current_time( 'timestamp' ) + date('Z');

	    if ($lowerBound < $upperBound) {
	        $between = $lowerBound < $checkDate && $checkDate < $upperBound;
	    } else {
	        $between = $checkDate < $upperBound || $checkDate > $lowerBound;
	    }

		return $between;

	} // end is_active_yearly

	/**
	 * Check if a one-time rule is active
	 *
	 * @since    0.1
	 */
	public function is_active_once( $start, $end ) {

		$upperBound = strtotime( $end );
	    $lowerBound = strtotime( $start );
	    $checkDate = current_time( 'timestamp' ) + date('Z');

	    if ( $checkDate > $lowerBound && $checkDate < $upperBound ) {
	        return true;
	    } else {
	        return false;
	    }

	} // end is_active_once

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    1.0
	 */
	public function show_notices() {

		$message = '';

		if ( ! empty( $this->notices ) ) {
			foreach ( $this->notices as $key => $notice ) {
				if ( 'error' == $notice['class'] )
					$message .= '<div class="notice notice-error gt-message"><p><strong>' . $notice['message'] . '</strong></p></div>';
				elseif ( 'warning' == $notice['class'] )
					$message .= '<div class="notice notice-warning gt-message">' . $notice['message'] . '</div>';
				elseif ( 'info' == $notice['class'] )
					$message .= '<div class="notice notice-info gt-message">' . $notice['message'] . '</div>';
				else
					$message .= '<div class="notice notice-success fade gt-message"><p>' . $notice['message'] . '</p></div>';
			}
		}

		echo $message;

	} // end show_notices

} // end class

?>
