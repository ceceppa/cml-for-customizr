<?php
/*
Plugin Name: Ceceppa Multilingua support for Customizr
Plugin URI: http://www.ceceppa.eu/portfolio/ceceppa-multilingua/
Description: Plugin to make Ceceppa Multilingua work with Customizr.\nThis plugin required Ceceppa Multilingua 1.4.10.
Version: 0.1
Author: Alessandro Senese aka Ceceppa
Author URI: http://www.alessandrosenese.eu/
License: GPL3
Tags: multilingual, multi, language, admin, tinymce, qTranslate, Polyglot, bilingual, widget, switcher, professional, human, translation, service, multilingua, customizr, theme
*/
// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

class Cml4Customizr {
	public function __construct() {
		add_filter( 'cml_addons', array( & $this, 'addon' ) );

		add_action( 'admin_init', array( & $this, 'add_meta_box' ) );
		add_action( 'cml_addon_customizr_content', array( & $this, 'addon_content' ) );

    add_action( 'admin_enqueue_scripts', array( & $this, 'enqueue_style' ) );

		//Customizr frontend
		add_filter( 'tc_slide_text', array( & $this, 'translate_slide_text' ), 10, 2 );
		add_filter( 'tc_slide_button_text', array( & $this, 'translate_button_text' ), 10, 2 );
		add_filter( 'tc_slide_title', array( & $this, 'translate_slide_title' ), 10, 2 );

		//Url
		add_filter( 'tc_slide_link_url', array( & $this, 'translate_link_url' ), 10, 2 );

		//Notices
		add_action( 'admin_notices', array( & $this, 'admin_notices' ) );

		if( isset( $_POST[ 'add' ] ) ) {
			add_action( 'admin_init', array( & $this, 'save' ) );
		}
	}

	function addon( $addons ) {
		$addon = array(
									'addon' => 'customizr',
									'title' => 'Customizr',
									);
		$addons[] = $addon;
		return $addons;
	}

	function enqueue_style() {
    wp_enqueue_style( 'cml-customizr-style', plugin_dir_url( __FILE__ ) . '/admin.css' );
	}

	function add_meta_box() {
		add_meta_box( 'cml-box-addons', 
									__( 'Customizr', 'cmlcustomizr' ), 
									array( & $this, 'meta_box' ), 
									'cml_box_addons_customizr' );
	}

	function admin_notices() {
		global $pagenow;

		if( ! defined( 'CECEPPA_DB_VERSION' ) ) {
echo <<< EOT
	<div class="error">
		<p>
			Ceceppa Multilingua for Customizr require Ceceppa Multilingua >= 1.4.9
		</p>
	</div>
EOT;
			return;
		}

		if( 'post.php' == $pagenow ) {
			$id = intval( @$_GET[ 'post' ] );

			if( $id <= 0 ) return;

			$slider = get_post_meta( $id, 'slider_check_key', true );
			if( 1 != $slider ) return;

			$link = add_query_arg( array( 'page' => 'ceceppaml-translations-page' ), admin_url( 'admin.php' ) );
echo <<< EOT
	<div class="updated">
		<p>
			You can translate slider text in "Ceceppa Multilingua" -> "<a href="$link" target="_blank">My translations</a>" in "Customizr sliders" tab.
		</p>
	</div>
EOT;
		}

	}

	function meta_box() {
?>
	  <div id="minor-publishing">
			<?php _e( 'Support to customizr theme', 'cmlcustomizr' ); ?>
		</div>
<?php
	}

	function addon_content() {
		global $wpdb;

		require_once( "class-customizr.php" );

    $table = new CMLCustomizr_Table();
    $table->prepare_items();
  
    $table->display();
?>
      <div style="text-align:right">
        <p class="submit" style="float: right">
        <?php submit_button( __( 'Update', 'ceceppaml' ), "button-primary", "action", false, 'class="button button-primary"' ); ?>
        </p>
      </div>
<?php
	}

	function translate_slide_text( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		$return = CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_text_key_{$id}",
										"_customizr" );

		return ( ! empty( $return ) ) ? $return : $text;
	}

	function translate_button_text( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		$return = CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_button_key_{$id}",
										"_customizr" );

		return ( ! empty( $return ) ) ? $return : $text;
	}

	function translate_slide_title( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		$return = CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_title_key_{$id}",
										"_customizr" );

		return ( ! empty( $return ) ) ? $return : $text;
	}

	function translate_link_url( $link, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ) return $link;

		//I need post id, not media $id...
		$post_id = cml_get_page_id_by_path ( $link, array( 'post' ) );

		//Look for pages
		if( 0 == $post_id ) {
			$post_id = cml_get_page_id_by_path ( $link, array( 'page' ) );
		}

		$lang = CMLPost::get_language_id_by_id( $post_id );

		if( CMLLanguage::is_current( $lang ) ) return $link;

		$linked = CMLPost::get_translation( CMLLanguage::get_current_id(), $post_id );
		if( $linked == 0 ) return $link;

		return get_permalink( $linked );
	}

	function save() {
		if( ! wp_verify_nonce( $_POST[ "ceceppaml-nonce" ], "security" ) ) return;

		$labels = array( 
						'slide_title_key' => __( 'Slide Text', 'customizr' ),
						'slide_text_key' => __( 'Description text', 'customizr' ),
						'slide_button_key' => __( 'Button Text', 'customizr' ) );

		$ids = $_POST[ 'id' ];
		foreach( $ids as $id ) {
			foreach( CMLLanguage::get_no_default() as $lang ) {
				foreach ( $labels as $key => $label ) {
					$value = @$_POST[ $key ][ $lang->id ][ $id ];

					if( empty( $value ) ) continue;

					CMLTranslations::set( $lang->id, 
																"_customizr_{$key}_{$id}", 
																$value,
																"_customizr" );
				}
			}
		}
	}
}

$cml4customizr = new Cml4Customizr();
?>