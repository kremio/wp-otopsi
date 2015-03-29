<?php
/*
Plugin Name: Otopsi Widget
Plugin URI: http://j-u-t-t-u.net/
Description: Add Isotope.js filtering and layout functionality to a page.
Author: Kremiö Software Development
Version: 0.1
Author URI: http://j-u-t-t-u.net/
 */

/*
TODO:
Add option to control the number of post returned and the sorting on DB query
Documentation
Template Custom CSS
 */

define('OTOPSI_META_KEY', "otopsi_meta");
define('OTOPSI_SC_NAME', "otopsi");
define('OTOPSI_SC_KEY', "otopsi_sc_key");
define('OTOPSI_SC_DATA', "otopsi_sc_data");


//Setup initial state when the plugin is activated
register_activation_hook( __FILE__, 'activate' );


/**
 * Calls the class on the post edit screen.
 */
function call_Otopsi() {
    new Otopsi();
}

function call_OtopsiAdmin() {
    new OtopsiAdmin();
}

//Create a new OtopsiShortCode instance with the shortcode attributes
function OtopsiShortCode($atts) {
  if ( array_key_exists('id',$atts) && !empty( $atts['id'] ) ){ // Check if shortcode ID is passed
    $scData = OtopsiAdmin::getShortCodesData();
    if( array_key_exists($atts['id'], $scData) ){
      $currentSc = $scData[ $atts['id'] ];
      $currentSc['enable'] = 1;
      return Otopsi::render( $currentSc );
    }else{
      return 'Otopsi : Could not find a shortcode with the ID '.$atts['id'];
    }
  }else{
    return 'Otopsi : Shortcode ID empty or undefined.';
  }
}

function activate(){
  Otopsi::onActivation();
}


if ( is_admin() ) {
    add_action( 'load-post.php', 'call_Otopsi' );
    add_action( 'load-post-new.php', 'call_Otopsi' );
    //Setup AJAX handler to retrieve the terms of a taxonomy on the client side
    add_action( 'wp_ajax_otopsi_get_taxonomy_terms', array('Otopsi', 'get_taxonomy_terms') );
    //Add an admin menu
    add_action( 'admin_menu',  'call_OtopsiAdmin' );
}
//render the grid and filters if enabled for the page
add_filter('the_content', array('Otopsi', 'renderInPost'), 100000 );

add_shortcode( OTOPSI_SC_NAME, 'OtopsiShortCode');


class OtopsiAdmin{
  /*
   * Create the Administration menu
   */
  public function __construct() {
    add_menu_page( 'Otopsi Admin', 'Otopsi', 'manage_options', 'otopsi_admin_menu', array( $this, 'shortcodes_admin' ), '' );
    add_submenu_page( 'otopsi_admin_menu', 'Shortcodes', 'Create Shortcodes', 'manage_options', 'otopsi_admin_menu', array( $this, 'shortcodes_admin' ));
    add_submenu_page( 'otopsi_admin_menu', 'Documentation', 'Documentation', 'manage_options', 'otopsi_documentation', array( $this, 'shortcodes_admin' ));
  }

  public function shortcodes_admin(){ // Main page
    include('includes/admin/_header.php');
    include('includes/admin/_shortcodes.php');
    include('includes/admin/_footer.php');
  }


  public function documentation_page(){
    include('includes/admin/_header.php');
    include('includes/admin/_documentation.php');
    include('includes/admin/_footer.php');
  }

  public static function saveShortCode( &$scData, $mydata ){
    if( !isset($mydata["sc_id"]) ){ //obtain a new shortcode id
      $mydata["sc_id"] = get_option(OTOPSI_SC_KEY);
      //update the shortcode id for next one
      update_option(OTOPSI_SC_KEY, $mydata["sc_id"] + 1);
    }

    $scData[$mydata["sc_id"]]	= $mydata;
    OtopsiAdmin::saveShortCodesData($scData);
  }

  /*
   * Return the array containing data off all
   */
  public static function getShortCodesData(){
    $scData	= json_decode( get_option(OTOPSI_SC_DATA), true );
    if (empty($scData)):
      return array();
    endif;
    
    return $scData;
  }

  /*
   * Save the shortcodes data
   */
  public static function saveShortCodesData($scData){
    update_option(OTOPSI_SC_DATA,json_encode( $scData, JSON_HEX_APOS | JSON_HEX_QUOT ) );
  }


}

// SHORTCODE




class Otopsi{

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {

    //Add the Otopsi metabox for the pages only
    add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
    //Save the meta data related to the Otopsi when the page is saved
    add_action( 'save_post', array( $this, 'save_meta_data' ) );

  }
  
  /*
   * When plugin gets activated or updated.
   */
  public static function onActivation(){
    $primaryKey = get_option(OTOPSI_SC_KEY);
    if (empty($primaryKey)){
      update_option(OTOPSI_SC_KEY,'1');
    }
  }

  /* 
   * Setup the plugin settings interface on the page editor
   */
  public function add_meta_box( $post_type ){
    add_meta_box(
      'otopsi_meta_box'
      ,__( 'Otopsi filtering and layout', 'otopsi_textdomain' )
      ,array( $this, 'render_meta_box_content' )
      ,'page'
      ,'normal'
      ,'high'
    );
  }

  /*
   * Update/save Otopsi meta data for the page
   */
  public function save_meta_data($post_id){
    /*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
     */

    		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
    }else{
      //Not a page
      return $post_id;
    }


    $mydata = Otopsi::parseFormDataPost();

    if( $mydata === FALSE || $mydata['enable'] != 1 ){ //the nonce is not valid or the plugin is not enabled
      return $post_id;
    }
   
    // Update the meta field.
    update_post_meta( $post_id, OTOPSI_META_KEY, $mydata );

    return $post_id;
  }

  public static function parseFormDataPost(){

    	// Check if our nonce is set.
		if ( ! isset( $_POST['otopsi_nonce'] ) )
			return FALSE;

		$nonce = $_POST['otopsi_nonce'];
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'otopsi_meta_box' ) )
			return FALSE;

     // Sanitize the user input
    $mydata = $_POST['otopsi'];
    //$mydata['title'] = sanitize_text_field( $mydata['title'] );

    return $mydata;

  }

  /*
   * Build the HTML of the Otopsi metabox
   */
  public function render_meta_box_content($post){

    //Retrieve the Otopsi meta data for this page, if any
    //Returns an array

    $metaData = get_post_meta( $post->ID, OTOPSI_META_KEY, false );
    if( !empty($metaData) ){
      $instance = $metaData[0];
    }else{
      $instance = array('enable'=>0);
    }
?>
    <script type="text/javascript" >

    function ontoggleOtopsi(value){
      //console.log(value);
      if( value ){
        jQuery('.otopsi_show_if_enabled').show();
      }else{
        jQuery('.otopsi_show_if_enabled').hide();
      }
    }

    </script>

    <p>
      <label for="otopsi[enable]">
      <input type="checkbox" name="otopsi[enable]" id="otopsi[enable]" value="1" <?php echo $instance['enable']?' checked="checked"':''; ?>onchange="return ontoggleOtopsi(jQuery(this).is(':checked'));">
      Enabled</label>
    </p>

<?php 
    Otopsi::renderInstanceEditForm( $instance );
  }

  public static function renderInstanceEditForm( $instance = array() ){

    $defaults = array(
      'enable'=>0,
      'wrapperClass'=>'otopsi',
      'term'=>'',
      'title'=>'',
      'taxonomy'=>'',
      'posttype'=>'',
      'filtersEnabled'=>1,
      'isotopeOptions'=>'
      "itemSelector": ".item",
      "layoutMode": "masonry",
      "masonry":{
        "columnWidth": ".grid-sizer",
          "gutter": ".gutter-sizer"
      }',
      'contentTemplate'=>'<a href="%the_permalink%" rel="bookmark" title="%the_title%" class="the_image"><img src="%the_image%" alt="%the_title%"/></a>
<h3 class="the_title"><a href="%the_permalink%" rel="bookmark">%the_title%</a></h3>
<p class="the_date">%the_date%</p>
%the_excerpt%
%the_category%');

    $instance = wp_parse_args((array) $instance, $defaults);

/*
    $layoutModes = array(
      "masonry" => "Masonry",
      "fitRows"=>"Fit rows",
      "vertical"=>"Vertical",
    );
 */


    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'otopsi_meta_box', 'otopsi_nonce' );


    $term_field = $instance['term'];

    //$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
?>
    <script type="text/javascript" >

    function onchangeTerm(term_field,value){
      var data = {
        action: 'otopsi_get_taxonomy_terms',
          otopsi_term: value
      };

      // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
      jQuery.post(ajaxurl, data, function(response) {
        jQuery('#'+term_field).html(response);
      });
    }

    </script>

    <div class="otopsi_show_if_enabled" style="display: <?php echo $instance['enable']?'block':'none'; ?>">

      <p>
      <label for="otopsi[wrapperClass]"><?php _e( 'Wrapper CSS class:' ); ?></label> 
      <input class="widefat" id="otopsi[wrapperClass]" name="otopsi[wrapperClass]" type="text" value="<?php echo $instance['wrapperClass']; ?>">
      </p>

        <p>
            <label for="otopsi[taxonomy]"><?php _e('Taxonomy:', 'otopsi_textdomain'); ?></label> <!-- <?php echo $term_field ?> -->
            <select multiple="multiple" onchange="return onchangeTerm('otopsi_term',jQuery(this).val());" id="otopsi_taxonomy" name="otopsi[taxonomy][]" style="width:90%;">
<?php
    $taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true));
    foreach ($taxonomies as $taxonomyslug) {
      $taxonomy = get_taxonomy($taxonomyslug);
      $option = '<option value="' . $taxonomyslug;
      if (is_array($instance['taxonomy']) && in_array($taxonomyslug, $instance['taxonomy'])) {
        $option .='" selected="selected';
      }
      $option .= '">';
      $option .= $taxonomy->labels->name;
      $option .= '</option>';
      echo $option;
    }
?>
            </select>
        </p>
        <p>
            <label for="otopsi[term]"><?php _e('Term:', 'otopsi_textdomain'); ?></label>
            <select multiple="multiple" id="otopsi_term" name="otopsi[term][]" style="width:90%;">
<?php
    if ($instance['taxonomy']) {
      foreach ($instance['taxonomy'] as $itax) {
        $terms = get_terms($itax, 'hide_empty=0&orderby=term_group');
        $optGroupTaxonomy = "";

        if (!is_wp_error($terms)) {
          foreach ($terms as $term) {
            $option = "";
            if ($optGroupTaxonomy != $term->taxonomy) {
              if ($optGroupTaxonomy) {
                $option .= '</optgroup>';
              }
              $optGroupTaxonomy = $term->taxonomy;
              $optGroupTaxonomyObj = get_taxonomy($optGroupTaxonomy);
              $option .= '<optgroup label="' . $optGroupTaxonomyObj->labels->name . '">';
            }
            $option .= '<option value="' . $term->taxonomy . ";" . $term->term_id;
            if (is_array($instance['term']) && in_array($term->taxonomy . ";" . $term->term_id, $instance['term'])) {
              $option .='" selected="selected';
            }
            $option .= '">';
            $option .= $term->name;
            $option .= ' (' . $term->count . ')';
            $option .= '</option>';
            echo $option;
          }
        }
        if ($optGroupTaxonomy) {
          echo '</optgroup>';
        }
      }
    } else {
?>
                    <option value="0"><?php _e('Choose taxonomy:', 'otopsi_textdomain'); ?></option>
<?php
    }
?>
            </select>
        </p>
        <p>
            <label for="otopsi[posttype]"><?php _e('Post types:', 'otopsi_textdomain'); ?></label>
            <select multiple="multiple"  id="otopsi[posttype]" name="otopsi[posttype][]" style="width:90%;">
<?php
    $post_types = get_post_types(array('public' => true, 'show_ui' => true));

    foreach ($post_types as $post_type) {
      $pt = get_post_type_object($post_type);

      $option = '<option value="' . $post_type;
      if (is_array($instance['posttype']) && in_array($post_type, $instance['posttype'])) {
        $option .='" selected="selected';
      }
      $option .= '">';
      $option .= $pt->labels->singular_name;
      $option .= '</option>';
      echo $option;
    }
?>
            </select>
        </p>

        <p>
          <label for="otopsi[filtersEnabled]">
          <input type="checkbox" name="otopsi[filtersEnabled]" id="otopsi[filtersEnabled]" value="1" <?php echo $instance['filtersEnabled']?' checked="checked"':''; ?>>
          <?php _e('Enable filtering:', 'otopsi_textdomain'); ?></label>

        <p>
          <label for="otopsi[contentTemplate]"><?php _e('Item content template:', 'otopsi_textdomain'); ?></label>
          <textarea id="otopsi[contentTemplate]" name="otopsi[contentTemplate]" style="width:90%;">
<?php echo stripslashes( $instance['contentTemplate'] ); ?>          
          </textarea>
          <span>Wordpress template tags <a href="http://codex.wordpress.org/Template_Tags" target="_blank">reference.</a></span>
        </p>

        <p>
          <label for="otopsi[isotopeOptions]"><?php _e('Isotope options:', 'otopsi_textdomain'); ?></label>
          <textarea id="otopsi[isotopeOptions]" name="otopsi[isotopeOptions]]" style="width:90%;">
<?php echo stripslashes( $instance['isotopeOptions'] ); ?>
          </textarea> 
        </p>

</div>


<?php 
  }


  /* Render a template tag */
  public static function renderTag( $tag ){
    global $post;

    $tag = $tag[1]; //the actual match
    //Custom Otopsi template tags
    if( $tag === "the_image" ){ //The featured image
      if (has_post_thumbnail()) {
        $thumbURL = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), '');
        $img = $thumbURL[0];
      } else {
        $img = self::getFirstImage($post->ID);
      }
      return $img;
    }

    //Wordpress template tags (http://codex.wordpress.org/Template_Tags)
    //start output buffering
    ob_start();
    call_user_func( $tag );
    $bufferContent = ob_get_contents(); 
    //Stop buffering and discard content
    ob_end_clean();


    return $bufferContent;
  }

  /* Render a template */
  public static function renderTemplate( $template ){
    //echo "First\n";
    $r = preg_replace_callback ( "/%([^%\s]+)%/", "Otopsi::renderTag", $template); 
    //echo "result:\n".$r;
    return $r;
  }


  public static function renderInPost( $post_content ){
    global $post;
		
    $meta_code = '';

    //Retrieve the Otopsi meta data for this page, if any
    $instance = get_post_meta( $post->ID, OTOPSI_META_KEY, false ); //Returns an array

    if( !is_array($instance) || sizeof($instance) < 1 ){
      return $post_content;
    }

    $instance = $instance[0];
		
    if( $instance['enable'] != 1){
      return $post_content;
    }

    return $post_content."\n".Otopsi::render( $instance );

  }

/**
 *
 * Render an Instance of the plugin in a string and returns it.
 *
 */
  public static function render( $instance ){
		
		
    /* The settings. */
    $taxonomy = $instance['taxonomy'];
    $terms = $instance['term'];


    $posts = 10;//$instance['posts'];
    $posttypes = $instance['posttype'];

    //Setup the posts query
    $query_terms = array();
    $first_term = null;
    $filters = array();
    if (is_array($terms)) {
      foreach ($terms as $term) {
        $term = explode(";", $term);
        if (isset($term[1])) {
          $term_array = get_term_by('id', $term[1], $term[0], 'ARRAY_A');
          $filters[] = $term_array;
          if (!$first_term) {
            $first_term = $term_array;
          }
          if (isset($query_terms[$term[0]])) {
            $query_terms[$term[0]] = $query_terms[$term[0]] . "," . $term_array["slug"];
          } else {
            $query_terms[$term[0]] = $term_array["slug"];
          }
        }
      }
    }
    if (isset($query_terms['category'])) {
      $query_terms['category_name'] = $query_terms['category'];
    }

    

    $args = $query_terms;

    //print_r($filters);

    $args['showposts'] = $posts;
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';
    $args['post_type'] = $posttypes;

    //Run the query
    $posts_query = new WP_Query($args);

    //Load CSS
    wp_enqueue_style("otopsi-style", plugins_url( "css/otopsi.css", __FILE__ ) );

    //Load JS
    wp_enqueue_script("isotope-js", plugins_url( "js/isotope.pkgd.min.js", __FILE__ ) );
    wp_enqueue_script("otopsi-js", plugins_url( "js/otopsi.js", __FILE__ ) );

    //start output buffering
    ob_start();
    //Wrapping DIV
?>
  <div class="<?php echo $instance['wrapperClass']; ?> otopsi-init" data-otopsi="<?php echo str_replace(array("\r\n", "\r"), "", trim( htmlentities( stripslashes( $instance['isotopeOptions']) ) ) ); ?>">
<?php
  //Show filtering options if enabled
  if( isset($instance['filtersEnabled']) && $instance['filtersEnabled'] ) :
?>
  <div class="otopsi-filters button-group">
    <button data-filter="*" class="is-checked">show all</button>
<?php foreach($filters as $term){ ?>
    <button data-filter=".<?php echo $term['slug']; ?>"><?php echo $term['name']; ?></button>
<?php } ?>
  </div>
<?php endif; ?>

  <div class="otopsi-container">
    <div class="grid-sizer"></div>
    <div class="gutter-sizer"></div>
<?php
    $i = 0;
    if ($posts_query->have_posts()) : while ($posts_query->have_posts()) : $posts_query->the_post();
      $i++;
    global $post;
    //get the taxonomy for the post
?>
    <div class="item <?php echo implode(" ",wp_get_post_terms( $post->ID, $taxonomy, array('fields' => 'slugs') ) ); ?>">
<?php echo Otopsi::renderTemplate( stripslashes( $instance['contentTemplate'] ) ); ?>
    </div>
<?php
  endwhile;
endif;
?>
  </div>
</div>

<script type="text/javascript" >

jQuery( function(){
 Otopsi(); 
});

</script>
<?php

//Get the content of the buffer
$bufferContent = ob_get_contents(); 
//Stop buffering and discard content
ob_end_clean();

return $bufferContent;
  }


  /*
   * Return a list of the terms under a taxonomy
   */  
  public static function get_taxonomy_terms() {
    if ($_POST['otopsi_term']) {
      $pterm = $_POST['otopsi_term'];
      foreach ($pterm as $tax) {
        $terms = get_terms($tax, 'hide_empty=0&orderby=term_group');
        $optGroupTaxonomy = "";

        if (!is_wp_error($terms)) {
          foreach ($terms as $term) {
            $option = "";
            if ($optGroupTaxonomy != $term->taxonomy) {
              if ($optGroupTaxonomy) {
                $option .= '</optgroup>';
              }
              $optGroupTaxonomy = $term->taxonomy;
              $optGroupTaxonomyObj = get_taxonomy($optGroupTaxonomy);
              $option .= '<optgroup label="' . $optGroupTaxonomyObj->labels->name . '">'    ;
            }
            $option .= '<option value="' . $term->taxonomy . ";" . $term->term_id . '">';
            $option .= $term->name;
            $option .= ' (' . $term->count . ')';
            $option .= '</option>';
            echo $option;
          }
        }
      }
      if ($optGroupTaxonomy) {
        echo '</optgroup>';
      }
      exit;
    }
  }


};

?>
