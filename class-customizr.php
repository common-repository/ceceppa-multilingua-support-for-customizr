<?php
if ( ! defined( 'ABSPATH' ) ) die( 'Access denied' ); // Exit if accessed directly

class CMLCustomizr_Table extends WP_List_Table {
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'mytranslation',     //singular name of the listed records
            'plural'    => 'mytranslations',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
    }

    function column_title($item){
      //Build row actions
      $actions = array(
          'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
          'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
      );
      
      //Return the title contents
      return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
          /*$1%s*/ $item['title'],
          /*$2%s*/ $item['ID'],
          /*$3%s*/ $this->row_actions($actions)
      );
    }

    function column_cb($item){
      return sprintf( '<img src="%sremove.png" title="Remove" />',
                     CML_PLUGIN_IMAGES_URL );
        //return sprintf(
        //    '<input type="checkbox" name="%1$s[]" value="%2$s" />',
        //    /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
        //    /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        //);
    }

    function get_columns(){
        $columns = array(
            // 'remove' => sprintf( '<img src="%sremove.png" alt="Remove" />',
            //               CML_PLUGIN_IMAGES_URL ),
            'media' => __( 'Media', 'cmlcustomizr' ),
            'translation' => __( 'Translation', 'cmlcustomizr' ),
        );

      return $columns;
    }
    
    function get_sortable_columns() {
      $sortable_columns = array(
          'group' => array( 'group', true ),
          'string'  => array( 'string',false ),
      );

      return $sortable_columns;
    }
    
    
    function get_bulk_actions() {
      return array();
    }
    
    function process_bulk_action() {
      global $wpdb;
    }
    
    function prepare_items() {
      global $wpdb;


      /**
       * First, lets decide how many records per page to show
       */
      $per_page = 40;
      
      /**
       */
      $columns = $this->get_columns();
      $hidden = array( 'id' );			//L'id mi serve ma non deve essere visibile ;)
      $sortable = array(); //$this->get_sortable_columns();

      $this->_column_headers = array( $columns, $hidden, $sortable );
      $this->process_bulk_action();
        
      /* -- Preparing your query -- */
      $query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'slider_check_key'";
      $data = $wpdb->get_results( $query );

      $current_page = $this->get_pagenum();
      
      $total_items = count( $data );
      
      $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
      
      $this->items = $data;
      
      $this->set_pagination_args( array(
          'total_items' => $total_items,                  //WE have to calculate the total number of items
          'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
          'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
      ) );
    }
    
    function display_rows() {
      //Get the records registered in the prepare_items method
      $records = $this->items;

      //Get the columns registered in the get_columns and get_sortable_columns methods
      list( $columns, $hidden ) = $this->get_column_info();

      $alternate = "";

      //Loop for each record
      if( ! empty( $records ) ) {
        //Check for what language I have to hide translation field for default language
        $hide_for = apply_filters( "cml_my_translations_hide_default", array( 'S' ) );

        $langs = CMLLanguage::get_all();

        foreach( $records as $rec ) {

          $keys = array( 'slide_title_key', 'slide_text_key', 'slide_button_key' );

          foreach( $keys as $key ) {
            $value = esc_attr( get_post_meta( $rec->post_id, $key, true ) );

            CMLTranslations::add( "_customizr_{$key}_{$rec->post_id}",
              $value, "_customizr" );
          }

          //Open the line
          $alternate = ( empty ( $alternate ) ) ? "alternate" : "";
  
          $labels = array( 
                          'slide_title_key' => __( 'Slide Text', 'customizr' ),
                          'slide_text_key' => __( 'Description text', 'customizr' ),
                          'slide_button_key' => __( 'Button Text', 'customizr' ) );

          foreach ( $columns as $column_name => $column_display_name ) {
            //Style attributes for each col
            $attributes = "class='$column_name column-$column_name'";
    
            //Display the cell
            switch ( $column_name ) {
            case "remove":
              echo '<td ' . $attributes . '>';

              echo '<input type="checkbox" name="id[]" value="' . intval( $rec->post_id ) . '" class="id-' . $rec->post_id . '" />';

              echo '</td>';
              break;
            case "media":
              echo '<td ' . $attributes . '>';
              echo '<input type="hidden" name="id[]" value="' . intval( $rec->post_id ) . '" class="id-' . $rec->post_id . '" />';
              echo wp_get_attachment_image( $rec->post_id, 'thumbnail' );
              echo '</td>';
              break;
            case "translation":
              echo '<td ' . $attributes . '>';

              foreach ( CMLLanguage::get_no_default() as $lang ) {
                echo '<div class="media-items">';
                echo '<div class="image">';
                echo CMLLanguage::get_flag_img( $lang->id );
                echo '&nbsp;';
                echo $lang->cml_language;
                echo '</div>';

                foreach( $labels as $key => $label ) {
                  echo '<div class="media-item">';
                  echo '<span>' . $label . '</span>';
                  $value = get_post_meta( $rec->post_id, $key, true );

                  $trans = CMLTranslations::get( $lang->id, 
                                          "_customizr_{$key}_{$rec->post_id}",
                                          "_customizr", true, true );
                  if( ! empty( $trans ) ) $value = $trans;

                  echo "<input type=\"text\" name=\"{$key}[$lang->id][$rec->post_id]\" value=\"$value\" />";
                  echo '</div>';
                }
                echo '</div>';
              }

              echo '</td>';
              break;
            case "translation":
              echo '<td ' . $attributes . '>';

              /*
               * Number of elements $values must be same for each language !
               */
              foreach( $langs as $lang ) {
                $class = ( in_array( $rec->cml_type, $hide_for )
                         && CMLLanguage::is_default( $lang->id )
                         ) ? "cml-hidden" : "";

                echo '<div class="cml-myt-flag ' . $class . '">';
                echo CMLLanguage::get_flag_img( $lang->id );
                
                $value = CMLTranslations::get( $lang->id,
                                           $rec->cml_text,
                                           $rec->cml_type, true );

                echo '&nbsp;<input type="text" name="values[' . $lang->id .  '][]" value="' . $value . '" style="width: 90%" />';
                echo '</div>';
              }
              echo '</td>';
              break;
            default:
              echo $column_name;
            } //switch
          } //endforeach; 	//$columns as $column_name
          
      	  echo'</tr>';
        } //foreach
      } //if
    }
}
?>
