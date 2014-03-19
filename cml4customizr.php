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
		//Add slider strings in "My translations" page
		add_filter( 'cml_my_translations', array( & $this, 'customizr_slider_strings' ), 10, 1 );
		add_filter( 'cml_my_translations_hide_default', array( & $this, 'customizr_hide_default_language' ), 10, 1 );

		//Translate group name
		/*
		 * Change customizr "meta key" with its real name.
		 *  slide_title_key_2720 => "Slide Text"
		 */
		add_filter( 'cml_my_translations_label', array( & $this, 'customizr_slider_label' ), 10, 2 );

		//Customizr frontend
		add_filter( 'tc_slide_text', array( & $this, 'translate_slide_text' ), 10, 2 );
		add_filter( 'tc_slide_button_text', array( & $this, 'translate_button_text' ), 10, 2 );
		add_filter( 'tc_slide_title', array( & $this, 'translate_slide_title' ), 10, 2 );

		//Notices
		add_action( 'admin_notices', array( & $this, 'admin_notices' ) );
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

	function customizr_hide_default_language( $arr ) {
		$arr[] = "_customizr";

		return $arr;
	}

	//Check all media with "slider_check_key" meta
	function customizr_slider_strings( $types ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'slider_check_key'";
		$results = $wpdb->get_results( $query );

		foreach( $results as $rec ) {
			$keys = array( 'slide_title_key', 'slide_text_key', 'slide_button_key' );

			foreach( $keys as $key ) {
				$value = esc_attr( get_post_meta( $rec->post_id, $key, true ) );

				CMLTranslations::add( "_customizr_{$key}_{$rec->post_id}",
					$value, "_customizr" );
			}
		}

		$types[ "_customizr" ] = "Customizr sliders";

		return $types;
	}

	function customizr_slider_label( $label, $group ) {
		if( "_customizr" !== $group ) return $label;

		//Remove post id number
		preg_match( "/_\d.*/", $label, $match );
		$post_id = substr( $match[0], 1 );
		$label = preg_replace( "/_\d.*/", "", $label );

		//I can't use array_replace because it require php >= 5.3.0
		$labels = array( 
						'slide_title_key' => __( 'Slide Text', 'customizr' ),
						'slide_text_key' => __( 'Description text', 'customizr' ),
						'slide_button_key' => __( 'Button Text', 'customizr' ) );

		$key = array_search( $label, $labels );
		if( null !== $key ) {
			$label = $labels[ $label ];
		}

		$title = get_the_title( $post_id );
		return "$label ( $title )";
	}

	function translate_slide_text( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		return CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_text_key_{$id}",
										"_customizr" );
	}

	function translate_button_text( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		return CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_button_key_{$id}",
										"_customizr" );
	}

	function translate_slide_title( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		return CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_slide_title_key_{$id}",
										"_customizr" );
	}
}

$cml4customizr = new Cml4Customizr();
?>