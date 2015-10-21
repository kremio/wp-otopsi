<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handles the creation and edition of Isotope layouts inside the page editor
 */
class Otopsi{

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		//Add the Otopsi metabox for the pages only
		add_action( 'add_meta_boxes_page', array( $this, 'on_add_meta_box' ) );
		//Save the meta data related to the Otopsi plugin when the page is saved
		add_action( 'save_post', array( $this, 'on_save_meta_data' ) );

	}

	/*
	 * When plugin gets activated or updated.
	 */
	public static function on_activation() {
		$primaryKey = get_option( OTOPSI_SC_KEY );
		if ( empty( $primaryKey ) ) {
			update_option( OTOPSI_SC_KEY, '1' );
		}
	}

	/* 
	 * Setup the plugin settings interface on the page editor
	 */
	public function on_add_meta_box( $post_type ) {
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
	public function on_save_meta_data( $post_id ) {
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// If this is an autosave, our form has not been submitted,
		//     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		if ( 'page' !== $_POST['post_type'] ) {
			//Not a page
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_page', $post_id ) ){
			return $post_id;
		}

		$mydata = Otopsi::parse_form_data_post();

		if( FALSE === $mydata ) { //the nonce is not valid or the plugin is not enabled
			return $post_id;
		}

		// Update the meta field.
		update_post_meta( $post_id, OTOPSI_META_KEY, $mydata );

		return $post_id;
	}

	/*
	 * Returns a default instance configuration.
	 */
	public static function get_default_config() {
		return array(
			'enable'       => 0, //(0 or 1) - 0: the plugin won't render on the page (only applies in the context of a page, not for shortcodes)
			'wrapperClass' => 'otopsi', //(String) - HTML class that allows to style the plugin layout from CSS
			/* 
			 * Paraleters for the content search query.
			 * See https://codex.wordpress.org/Taxonomies
			 */
			'taxonomy' => '', //(Array) - Configure which taxonomies are included  
			'term'     => '', //(Array) - Configure which terms of the taxonomies are included
			'posttype' => '', //(Array) - Limit search to the specified post types
			'limit'    => 10, //Integer - Limit the number of posts returned
			/*
			 * Isotope settings
			 */
			'filtersEnabled' => 1, //(0 or 1) - 0:disable filtering based on terms, 1:enable filtering based on terms
			//see http://isotope.metafizzy.co/options.html
			'isotopeOptions' => '"itemSelector": ".item",
			"layoutMode": "masonry",
			"masonry":{
				"columnWidth": ".grid-sizer",
					"gutter": ".gutter-sizer"
	}',
	//HTML template for the items content
	'contentTemplate' => '<a href="%the_permalink%" rel="bookmark" title="%the_title%" class="the_image"><img src="%the_image%" alt="%the_title%"/></a>
	<h3 class="the_title"><a href="%the_permalink%" rel="bookmark">%the_title%</a></h3>
	<p class="the_date">%the_date%</p>
	%the_excerpt%
	%the_category%'
);
	}


	public static function parse_form_data_post() {

		// Check if our nonce is set.
		if ( ! isset( $_POST['otopsi_nonce'] ) )
			return FALSE;

		$nonce = $_POST['otopsi_nonce'];
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'otopsi_meta_box' ) )
			return FALSE;

		// Sanitize the user input
		$mydata = $_POST['otopsi'];

		if( !array_key_exists( 'filtersEnabled', $mydata) ) {
			$mydata['filtersEnabled'] = 0;
		}
		if( !array_key_exists( 'enable', $mydata) ) {
			$mydata['enable'] = 0;
		}

		return $mydata;

	}

	/*
	 * Build the HTML of the Otopsi metabox
	 */
	public function render_meta_box_content($post) {

		//Retrieve the Otopsi meta data for this page, if any
		//Returns an array

		$metaData = get_post_meta( $post->ID, OTOPSI_META_KEY, false );
		if( !empty( $metaData ) ) {
			$instance = $metaData[0];
		}else{
			$instance = array( 'enable' => 0 );
		}
?>
		<script type="text/javascript" >

		function ontoggleOtopsi(value) {
			//console.log(value);
			if( value ) {
				jQuery('.otopsi_show_if_enabled').show();
			}else{
				jQuery('.otopsi_show_if_enabled').hide();
			}
		}

		</script>

<table class="form-table">
<tr valign="top">
<th><label for="otopsi[enable]"><?php _e('Enable Otopsi', 'otopsi_textdomain'); ?></label></th>
<td><input type="checkbox" name="otopsi[enable]" id="otopsi[enable]" value="1" <?php echo $instance['enable']?' checked="checked"':''; ?>onchange="return ontoggleOtopsi(jQuery(this).is(':checked'));"></td>
</tr>
</table>

<?php 
		Otopsi_Renderer::render_instance_edit_form( $instance );
	}




	/**
	 * Execute a WordPress post query based on the settings of an instance
	 * $instance: associative array specifying the search parameters
	 * $filters: array passed by referenece which will contain the filtering terms when the function returns
	 * returns a WP_Query instance
	 * (see https://codex.wordpress.org/Class_Reference/WP_Query and https://gist.github.com/luetkemj/2023628)
	 */
	public static function search_blog( $instance, &$filters ) {

		//constructor options for WP_Query
		$args = array(
			'post_type'      => $instance['posttype'],
			'posts_per_page' => $instance['limit'],
			'orderby'        => 'date',
			'order'          => 'DESC'
		); 

		//Create the query search conditions based on the taxonomies and terms provided
		$terms = $instance['term'];
		$taxonomies_terms = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				list( $taxonomyName, $termId ) = explode( ';', $term );
				if( !isset( $termId ) ) {
					continue;
				}

				$term_array = get_term_by( 'id', $termId, $taxonomyName, 'ARRAY_A' );
				$filters[] = $term_array;

				if ( !isset( $taxonomies_terms[$taxonomyName] ) ) {
					$taxonomies_terms[$taxonomyName] = array(
						'taxonomy'         => $taxonomyName,
						'field'            => 'slug',
						'terms'            => $term_array['slug'],
						'include_children' => true,
						'operator'         => 'IN'
					);
				} else {
					$taxonomies_terms[$taxonomyName]['terms'] .= ',' . $term_array['slug'];
				}
			}
		}


		if ( isset( $taxonomies_terms['category'] ) ) { //For the category taxonomy use the special field 'category_name'
			$args['category_name'] = $taxonomies_terms['category']['terms'];
			unset( $taxonomies_terms['category'] );
		}

		//The taxonomy query
		$args['tax_query'] = array( 'relation' => 'OR' );
		$args['tax_query'] = array_merge( $args['tax_query'], array_values( $taxonomies_terms ) );


		//Run the query
		return new WP_Query( $args );
	}



	/*
	 * Return a list of the terms under a taxonomy in answer to a HTTP POST request
	 * $_POST['otopsi_term'] : array of taxonomy names whose terms we want to retrieve
	 */  
	public static function get_taxonomy_terms() {
		if ( $_POST['otopsi_term'] ) {
			$pterm = $_POST['otopsi_term'];
			foreach ( $pterm as $tax ) {
				$terms = get_terms( $tax, 'hide_empty=0&orderby=term_group' );
				$optGroupTaxonomy = '';

				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$option = '';
						if ( $optGroupTaxonomy != $term->taxonomy ) {
							if ( $optGroupTaxonomy ) {
								$option .= '</optgroup>';
							}
							$optGroupTaxonomy = $term->taxonomy;
							$optGroupTaxonomyObj = get_taxonomy( $optGroupTaxonomy );
							$option .= '<optgroup label="' . $optGroupTaxonomyObj->labels->name . '">'    ;
						}
						$option .= '<option value="' . $term->taxonomy . ';' . $term->term_id . '">';
						$option .= $term->name;
						$option .= ' (' . $term->count . ')';
						$option .= '</option>';
						echo $option;
					}
				}
			}
			if ( $optGroupTaxonomy ) {
				echo '</optgroup>';
			}
			exit;
		}
	}
	
};
