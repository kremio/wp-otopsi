<?php
/*
Plugin Name: Otopsi Widget
Plugin URI: http://j-u-t-t-u.net/
Description: Add Isotope.js filtering and layout functionality to a page.
Author: Kremiö Software Development
Version: 0.1
Author URI: http://j-u-t-t-u.net/
 */

define('OTOPSI_META_KEY', "otopsi_meta");

/**
 * Calls the class on the post edit screen.
 */
function call_Otopsi() {
    new Otopsi();
}

if ( is_admin() ) {
    add_action( 'load-post.php', 'call_Otopsi' );
    add_action( 'load-post-new.php', 'call_Otopsi' );
    //Setup AJAX handler to retrieve the terms of a taxonomy on the client side
    add_action( 'wp_ajax_otopsi_get_taxonomy_terms', array('Otopsi', 'get_taxonomy_terms') );
}
//render the grid and filters if enabled for the page
add_filter('the_content', array('Otopsi', 'render'), 100000 );

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


		// Check if our nonce is set.
		if ( ! isset( $_POST['otopsi_nonce'] ) )
			return $post_id;

		$nonce = $_POST['otopsi_nonce'];
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'otopsi_meta_box' ) )
			return $post_id;

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

    // Sanitize the user input
    $mydata = $_POST['otopsi'];
    $mydata['title'] = sanitize_text_field( $mydata['title'] );

    // Update the meta field.
    update_post_meta( $post_id, OTOPSI_META_KEY, $mydata );

    return $post_id;
  }

  /*
   * Build the HTML of the Otopsi metabox
   */
  public function render_meta_box_content($post){

    //Retrieve the Otopsi meta data for this page, if any
    $instance = get_post_meta( $post->ID, OTOPSI_META_KEY, false )[0]; //Returns an array 

    $defaults = array(
      'enable'=>0,
      'wrapperClass'=>'otopsi',
      'term'=>'',
      'title'=>'',
      'taxonomy'=>'',
      'posttype'=>'',
      'filtersEnabled'=>1,
      'layoutMode'=>'masonry',
      'layoutModeOptions'=>'"columnWidth": ".grid-sizer",'."\n".'"gutter": ".gutter-sizer"',
      'contentTemplate'=>'<a href="%the_permalink%" rel="bookmark" title="%the_title%"><img class="alignleft" src="%the_image%" alt="%the_title%" /></a>\n
    <h1>%the_title%</h1>');
    $instance = wp_parse_args((array) $instance, $defaults);

    $layoutModes = array(
      "masonry" => "Masonry",
      "fitRows"=>"Fit rows",
      "vertical"=>"Vertical",
    );



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
<?php echo $instance['contentTemplate']; ?>          
          </textarea>
        </p>

        <p>
          <label for="otopsi[layoutMode]"><?php _e('Isotope layout mode:', 'otopsi_textdomain'); ?></label>
          <select id="otopsi[layoutMode]" name="otopsi[layoutMode]" style="width:90%;">
<?php
        foreach($layoutModes as $layoutCode => $layoutName){
?>
  <option value="<?php echo $layoutCode; ?>"<?php echo $instance['layoutMode']==$layoutCode ?'  selected="selected"':""?>><?php echo $layoutName; ?></option>
<?php
  }
?>
          </select> 
        </p>

        <p>
          <label for="otopsi[layoutModeOptions]"><?php _e('Isotope layout options:', 'otopsi_textdomain'); ?></label>
          <textarea id="otopsi[layoutModeOptions]" name="otopsi[layoutModeOptions]]" style="width:90%;">
<?php echo $instance['layoutModeOptions']; ?>
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


/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
  public static function render( $post_content ){
		
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

    /* The settings. */
    $title = $instance['title'];
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

    //wp_enqueue_script("jquery1.11", "https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js");

    //start output buffering
    ob_start();
    //Wrapping DIV
?>
<div class="<?php echo $instance['wrapperClass']; ?>">
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
<?php echo Otopsi::renderTemplate( $instance['contentTemplate'] ); ?>
    </div>
<?php
  endwhile;
endif;
?>
  </div>
</div>

<script type="text/javascript" >

jQuery( function(){
  var $container = jQuery(".otopsi-container").isotope({
    "itemSelector": ".item",
    "layoutMode": '<?php echo $instance['layoutMode']; ?>',
    "<?php echo $instance['layoutMode']; ?>":{
    <?php echo $instance['layoutModeOptions']; ?>
    }
  });

  jQuery('.otopsi-filters').on( 'click', 'button', function() {
    var filterValue = jQuery( this ).attr('data-filter');
    // use filterFn if matches value
    //filterValue = filterFns[ filterValue ] || filterValue;
    $container.isotope({ filter: filterValue });
  });

  // change is-checked class on buttons
  jQuery('.button-group').each( function( i, buttonGroup ) {
    var $buttonGroup = jQuery( buttonGroup );
    $buttonGroup.on( 'click', 'button', function() {
      $buttonGroup.find('.is-checked').removeClass('is-checked');
      jQuery( this ).addClass('is-checked');
    });
  });

});

</script>
<?php

//Get the content of the buffer
$bufferContent = ob_get_contents(); 
//Stop buffering and discard content
ob_end_clean();
return $post_content."\n".$bufferContent;
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
