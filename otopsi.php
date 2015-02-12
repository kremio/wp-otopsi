<?php
/*
Plugin Name: Otopsi Widget
Plugin URI: http://j-u-t-t-u.net/
Description: Add Isotope.js filtering and layout functionality to a page.
Author: Kremiö Software Development
Version: 0.1
Author URI: http://j-u-t-t-u.net/
 */
class Otopsi_Widget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    // widget actual processes
    parent::__construct(

      'Otopsi', // Base ID of your widget 
      __('Otopsi Widget', 'otopsi_widget_domain'), // Widget name will appear in UI
      array(
        'description' => __( 'Add Isotope.js filtering and layout functionality to a page.', 'otopsi_widget_domain' ),
      ) 
    );
  }

/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
  public function widget( $args, $instance ) {
    extract($args);

    /* Our variables from the widget settings. */
    $title = $instance['title'];
    $taxonomy = $instance['taxonomy'];
    $terms = $instance['term'];

    //print_r($terms);

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
?>

<div class="otopsi-filters button-group">
  <button data-filter="*">show all</button>
<?php foreach($filters as $term){ ?>
  <button data-filter=".<?php echo $term['slug']; ?>"><?php echo $term['name']; ?></button>
<?php } ?>
</div>

<div class="otopsi-container">

<?php
    $i = 0;
    if ($posts_query->have_posts()) : while ($posts_query->have_posts()) : $posts_query->the_post();
      $i++;
    global $post;
    //get the taxonomy for the post
?>
  <div class="item <?php echo implode(" ",wp_get_post_terms( $post->ID, $taxonomy, array('fields' => 'slugs') ) ); ?>">
<?php
      unset($img);
      if (has_post_thumbnail()) {
        $thumbURL = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), '');
        $img = $thumbURL[0];
      } else {
        $img = self::getFirstImage($post->ID);
      }

      if ($img) {
?>

  <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
  <img class="alignleft" src="<?php echo $img ?>" alt="<?php the_title(); ?>" width="<?php echo $thumbnail_w ?>" height="<?php echo $thumbnail_h ?>" />
 </a>
    <?php } ?>
    <h1><?php echo the_title(); ?> </h1>
  </div>
<?php
  endwhile;
endif;
?>



  </div>
<script type="text/javascript" >

jQuery( function(){
  var $container = jQuery(".otopsi-container").isotope({
    "columnWidth": 200,
    "itemSelector": ".item"
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




 //   echo $args['before_widget'];
    //print_r( $instance );
    /*
    if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		echo __( 'Hello, World!', 'text_domain' );
     */
   // echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
  public function form( $instance ) {

    $defaults = array();
    $instance = wp_parse_args((array) $instance, $defaults);
    
    $term_field = $this->get_field_id('term');

		//$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
    ?>
    <script type="text/javascript" >

    function onchangeTerm(term_field,value){
      var data = {
        action: 'srp_get_taxonomy_terms',
          srp_term: value
      };

      // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
      jQuery.post(ajaxurl, data, function(response) {
        jQuery('#'+term_field).html(response);
      });
    }

    </script>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $instance['title']; ?>">
    </p>

        <p>
            <label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:', 'otopsi_widget_domain'); ?></label>
            <select multiple="multiple" onchange="return onchangeTerm('<?php echo $term_field ?>',jQuery(this).val());" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>[]" style="width:90%;">
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
            <label for="<?php echo $this->get_field_id('term'); ?>"><?php _e('Term:', 'otopsi_widget_domain'); ?></label>
            <select multiple="multiple" id="<?php echo $term_field ?>" name="<?php echo $this->get_field_name('term'); ?>[]" style="width:90%;">
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
                    <option value="0"><?php _e('Choose taxonomy:', 'otopsi_widget_domain'); ?></option>
                    <?php
                }
                ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('posttype'); ?>"><?php _e('Post types:', 'otopsi_widget_domain'); ?></label>
            <select multiple="multiple"  id="<?php echo $this->get_field_id('posttype'); ?>" name="<?php echo $this->get_field_name('posttype'); ?>[]" style="width:90%;">
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


		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;

    /* Strip tags for title and name to remove HTML (important for text inputs). */
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['taxonomy'] = $new_instance['taxonomy'];
    $instance['term'] = $new_instance['term'];
    //$instance['posts'] = absint($new_instance['posts']);
    $instance['posttype'] = $new_instance['posttype'];
/*
    $instance['show_excerpt'] = (boolean) $new_instance['show_excerpt'];
    $instance['excerpt_length'] = absint($new_instance['excerpt_length']);
    if (!$instance['excerpt_length']) {
      $instance['excerpt_length'] = "";
    }
    $instance['excerpt_readmore'] = strip_tags($new_instance['excerpt_readmore']);


    $instance['show_thumbnail'] = (boolean) $new_instance['show_thumbnail'];
    $instance['thumbnail_h'] = absint($new_instance['thumbnail_h']);
    $instance['thumbnail_w'] = absint($new_instance['thumbnail_w']);
    if (!$instance['thumbnail_h']) {
      $instance['thumbnail_h'] = "";
    }
    if (!$instance['thumbnail_w']) {
      $instance['thumbnail_w'] = "";
    }
 */
    return $instance;
	}
};


// Load the widget on widgets_init
function load_otopsi_widget() {
	register_widget( 'Otopsi_Widget' );
};

add_action('widgets_init', 'load_otopsi_widget');

?>
