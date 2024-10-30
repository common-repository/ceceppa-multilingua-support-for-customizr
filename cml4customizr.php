<?php
/*
Plugin Name: Ceceppa Multilingua support for Customizr
Plugin URI: http://www.ceceppa.eu/portfolio/ceceppa-multilingua/
Description: Plugin to make Ceceppa Multilingua work with Customizr.\nThis plugin required Ceceppa Multilingua 1.4.10.
Version: 0.6
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
      add_action( 'cml_register_addons', array( & $this, 'register_addon' ), 10, 1 );

      add_action( 'admin_init', array( & $this, 'add_meta_box' ) );
      add_action( 'cml_addon_customizr_content', array( & $this, 'addon_content' ) );
      add_filter( 'cml_my_translations_hide_default', array( & $this, 'hide_default' ) );

      add_action( 'admin_enqueue_scripts', array( & $this, 'enqueue_style' ) );

      //Customizr frontend
      add_filter( 'tc_slide_text', array( & $this, 'translate_slide_text' ), 10, 2 );
      add_filter( 'tc_slide_button_text', array( & $this, 'translate_button_text' ), 10, 2 );
      add_filter( 'tc_slide_title', array( & $this, 'translate_slide_title' ), 10, 2 );
      add_filter( 'tc_fp_button_text', array( & $this, 'translate_featured_button_text' ), 10, 2 );

      //Url
      add_filter( 'tc_slide_link_url', array( & $this, 'translate_link_url' ), 10, 2 );

      //Notices
      add_action( 'admin_notices', array( & $this, 'admin_notices' ) );

      //Home featured
      add_filter( 'tc_fp_link_url', array( & $this, 'featured_link' ), 10, 2 );
      add_filter( 'tc_fp_title', array( & $this, 'featured_title' ), 10, 3 );
      add_filter( 'tc_fp_text', array( & $this, 'featured_text' ), 10, 3 );

      if( isset( $_POST[ 'add' ] ) ) {
        $ctab = isset( $_GET[ 'ctab' ] ) ? intval( $_GET[ 'ctab' ] ) : 1;

        if( $ctab == 1 ) {
            add_action( 'admin_init', array( & $this, 'save_media' ) );
        } else {
            add_action( 'admin_init', array( & $this, 'save_featured' ) );
        }
      }
	}

  function register_addon( & $addons ) {
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
			<strong>Ceceppa Multilingua for Customizr</strong>
			<br /><br />
			Hi there!	I'm just an addon for <a href="http://wordpress.org/plugins/ceceppa-multilingua/">Ceceppa Multilingua</a>, I can't work alone :(
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

            $link = CMLUtils::_get( '_customizr_addon_page' );
echo <<< EOT
	<div class="updated">
		<p>
			You can translate slider text in "Ceceppa Multilingua" -> "<a href="$link" class="button" target="_blank">Addons</a>" in "Customizr sliders" tab.
		</p>
	</div>
EOT;
		}

		if( defined( 'CUSTOMIZR_VER' ) ) {
			$msg = '<strong>Custmoizr addon</strong>';
			$msg .= '<br /><br />';

			$link = CMLUtils::_get( "_customizr_addon_page" );
			$msg .= sprintf( __( 'You can translate featured page options in "Ceceppa Multilingua" -> <%s>"Customizr"</a> addon page', 
														'customizr' ), 'a class="button" href="' . $link . '"' );

			cml_admin_print_notice( "_cml_customizr_featured", $msg );
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
		$ctab = isset( $_GET[ 'ctab' ] ) ? intval( $_GET[ 'ctab' ] ) : 1;
		$mactive = ( $ctab == 1 ) ? "nav-tab-active" : "";
		$factive = ( $ctab == 2 ) ? "nav-tab-active" : "";

        $page = CMLUtils::_get( '_customizr_addon_page' );
echo <<< EOT
		<h2 class="nav-tab-wrapper tab-strings">
		&nbsp;
	    <a class="nav-tab $mactive" href="$page&ctab=1">Media</a>
      <a class="nav-tab $factive" href="$page&ctab=2">Featured</a>
    </h2>
EOT;
		
		if( $ctab == 1 ) {
			$this->addon_content_media();
		} else {
			$this->addon_content_featured();
		}
?>
			<input type="hidden" name="ctab" value="<?php echo $ctab ?>" />
      <div style="text-align:right">
        <p class="submit" style="float: right">
        <?php submit_button( __( 'Update', 'ceceppaml' ), "button-primary", "action", false, 'class="button button-primary"' ); ?>
        </p>
      </div>
<?php
	}

	function addon_content_media() {
		global $wpdb;

		require_once( "class-customizr.php" );

    $table = new CMLCustomizr_Table();
    $table->prepare_items();
  
    $table->display();
	}

	function addon_content_featured() {
		require_once( CML_PLUGIN_LAYOUTS_PATH . "class-mytranslations.php" );

		$options = get_option( 'tc_theme_options', array() );
		if( ! empty( $options ) ) {
			foreach ( $options as $key => $value ) {
				if( 'tc_featured_text_' == substr( $key, 0, 17 ) ||
						'tc_featured_page_button_text' == $key ) {
					CMLTranslations::add( "_customizr_featured_$key", $value, "_customizr_featured", true );
				}
			}
		}

    $table = new MyTranslations_Table( 
    																	array( "_customizr_featured" => "CUSTOMIZR"
    																	) );
    $table->prepare_items();
  
    $table->display();
    
    $lkeys = array_keys( CMLLanguage::get_all() );
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

	function save_media() {
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

	function save_featured() {
		if( ! wp_verify_nonce( $_POST[ "ceceppaml-nonce" ], "security" ) ) return;

    global $wpdb;

    $langs = CMLLanguage::get_no_default();
    $max = count( $_POST[ 'id' ] );

    for( $i = 0; $i < $max; $i++ ) {
      //record id
      $id = intval( $_POST[ 'id' ][ $i ] );
      $text = esc_attr( $_POST[ 'string' ][ $i ] );
      $group = esc_attr( $_POST[ 'group' ][ $i ] );

      foreach( $langs as $lang ) {
        $value = esc_attr( $_POST[ 'values' ][ $lang->id ][ $i ] );

        CMLTranslations::set( $lang->id,
                            $text,
                            $value,
                            $group );
      }
    }

    if( isset( $_POST[ 'delete' ] ) ) {
      $max = count( @$_POST[ 'delete' ] );
      for( $i = 0; $i < $max; $i++ ) {
        $wpdb->delete( CECEPPA_ML_TRANSLATIONS,
                      array(
                        'cml_text' => bin2hex( $_POST[ 'delete' ][ $i ] ),
                      ),
                      array(
                        '%s'
                      )
                     );
      }
    }

    //generate .po
    cml_generate_mo_from_translations( "S", true );
	}

	function featured_link( $permalink, $single_id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) || CMLLanguage::is_default()  ) return $permalink;

		$id = tc__f( '__get_option' , 'tc_featured_page_' . $single_id );

		$tid = CMLPost::get_translation( CMLLanguage::get_current_id(),
																			$id );

			if( $tid == $id ) return $permalink;

		return get_permalink( $tid );
	}

	function featured_title( $title, $fp_single_id = null, $featured_page_id = null) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) || CMLLanguage::is_default()  ||
		    null == $fp_single_id || null == $featured_page_id ) return $title;

		$tid = CMLPost::get_translation( CMLLanguage::get_current_id(),
						  $featured_page_id );

		if( $tid == $featured_page_id ) return $title;

		return get_the_title( $tid );
	}

	function featured_text( $text, $fp_single_id, $featured_page_id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) || CMLLanguage::is_default() ) return $text;

		if( null == CMLUtils::_get( '_customizr_options' ) ) {
			$options = get_option( 'tc_theme_options', array() );
			CMLUtils::_set( '_customizr_options', $options );
		} else {
			$options = CMLUtils::_get( '_customizr_options' );
		}

		$tid = CMLPost::get_translation( CMLLanguage::get_current_id(),
																			$featured_page_id );

		if( $tid == $featured_page_id ) return $text;

		if( isset( $options[ 'tc_featured_text_' . $fp_single_id ] ) ) {
			$v = CMLTranslations::get( CMLLanguage::get_current_id(),
																			'_customizr_featured_tc_featured_text_' . $fp_single_id,
																			'_customizr_featured', true );
			if( ! empty( $v ) ) return $v;
		}

		if( $tid > 0 ) {
			$page = get_post( $tid );

			$excerpt = ( ! empty( $page->post_excerpt ) ) ? $page->post_excerpt : $page->post_content;

			return apply_filters( 'the_content' , $excerpt );
		} else {
			return $text;
		}
	}

	function translate_featured_button_text( $text, $id ) {
		if( ! defined( 'CECEPPA_DB_VERSION' ) ||
			CMLLanguage::is_default() ) return $text;

		$return = CMLTranslations::get( CMLLanguage::get_current_id(),
										"_customizr_featured_tc_featured_page_button_text",
										"_customizr_featured" );

		return ( ! empty( $return ) ) ? $return : $text;
	}

	/*
	 * I don't have to translate "featured" field for default language
	 */
	function hide_default( $array ) {
		return array( '_customizr_featured' );
	}
}

$cml4customizr = new Cml4Customizr();
?>