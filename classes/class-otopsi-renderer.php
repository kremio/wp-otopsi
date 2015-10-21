<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*
 * Wraps method that render forms and the Isotope layout
 */
class Otopsi_Renderer{

	/*
	 * Render the HTML code for an isotope layout configuration form populated
	 * from an optional $instance associative array 
	 */
	public static function render_instance_edit_form( $instance = array() ) {


		$instance = wp_parse_args( (array) $instance,  Otopsi::get_default_config() );

		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'otopsi_meta_box', 'otopsi_nonce' );


		$term_field = $instance['term'];
?>
		<script type="text/javascript" >

		function onchangeTerm(term_field,value) {
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

<table class="form-table otopsi_show_if_enabled" style="display: <?php echo $instance['enable'] ? 'block' : 'none'; ?>">
<tr valign="top">
<th><label for="otopsi[wrapperClass]"><?php _e( 'Wrapper CSS class:' ); ?></label></th>
<td><input class="widefat" id="otopsi[wrapperClass]" name="otopsi[wrapperClass]" type="text" value="<?php echo $instance['wrapperClass']; ?>"></td>
</tr>

<tr valign="top">
<th><label for="otopsi[taxonomy]"><?php _e( 'Taxonomy:', 'otopsi_textdomain' ); ?></label></th>
<td><select multiple="multiple" onchange="return onchangeTerm( 'otopsi_term', jQuery(this).val() );" id="otopsi_taxonomy" name="otopsi[taxonomy][]" style="width:90%;">
<?php
		//Taxonomies select
		//Will update the content of the terms select on change.
		$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ) );
		foreach ($taxonomies as $taxonomyslug ) {
			$taxonomy = get_taxonomy( $taxonomyslug );
			$option = '<option value="' . $taxonomyslug;
			if ( is_array( $instance['taxonomy'] ) && in_array( $taxonomyslug, $instance['taxonomy'] ) ) {
				$option .='" selected="selected';
			}
			$option .= '">';
			$option .= $taxonomy->labels->name;
			$option .= '</option>';
			echo $option;
		}
?>
</select>
</td>
</tr>

<tr valign="top">
<th><label for="otopsi[term]"><?php _e( 'Term:', 'otopsi_textdomain' ); ?></label></th>
<td><select multiple="multiple" id="otopsi_term" name="otopsi[term][]" style="width:90%;">
<?php
		//Terms select
		//Will be updated when the content of the taxonomies select changes.
		if ( $instance['taxonomy'] ) {
			foreach ( $instance['taxonomy'] as $itax ) {
				$terms = get_terms( $itax, 'hide_empty=0&orderby=term_group' );
				$optGroupTaxonomy = "";

				if ( !is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$option = "";
						if ( $optGroupTaxonomy != $term->taxonomy ) {
							if ( $optGroupTaxonomy ) {
								$option .= '</optgroup>';
							}
							$optGroupTaxonomy = $term->taxonomy;
							$optGroupTaxonomyObj = get_taxonomy( $optGroupTaxonomy );
							$option .= '<optgroup label="' . $optGroupTaxonomyObj->labels->name . '">';
						}
						$option .= '<option value="' . $term->taxonomy . ';' . $term->term_id;
						if ( is_array( $instance['term'] ) && in_array( $term->taxonomy . ';' . $term->term_id, $instance['term'] ) ) {
							$option .= '" selected="selected';
						}
						$option .= '">';
						$option .= $term->name;
						$option .= ' (' . $term->count . ')';
						$option .= '</option>';
						echo $option;
					}
				}

				if ( $optGroupTaxonomy ) {
					echo '</optgroup>';
				}
			}
		} else {
?>
<option value="0"><?php _e( 'Choose taxonomy:', 'otopsi_textdomain' ); ?></option>
<?php
		}
?>
</select>
</td>
</tr>

<tr valign="top">
<th><label for="otopsi[posttype]"><?php _e( 'Post types:', 'otopsi_textdomain' ); ?></label></th>
<td><select multiple="multiple"  id="otopsi[posttype]" name="otopsi[posttype][]" style="width:90%;">
<?php

		$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ) );

		foreach ( $post_types as $post_type ) {
			$pt = get_post_type_object( $post_type );

			$option = '<option value="' . $post_type;
			if ( is_array( $instance['posttype'] ) && in_array( $post_type, $instance['posttype'] ) ) {
				$option .= '" selected="selected';
			}
			$option .= '">';
			$option .= $pt->labels->singular_name;
			$option .= '</option>';
			echo $option;
		}
?>
</select>
</td>
</tr>

<tr valign="top">
<th><label for="otopsi[limit]"><?php _e( 'Limit number of items to:', 'otopsi_textdomain' ); ?></label>
<p>><?php _e( '-1 : no limit', 'otopsi_textdomain' ); ?></p></th>
<td><input type="number" name="otopsi[limit]" id="otopsi[limit]" value="<?php echo $instance['limit']; ?>"/></td>
</tr>


<tr valign="top">
<th><label for="otopsi[filtersEnabled]"><?php _e( 'Enable filtering:', 'otopsi_textdomain' ); ?></label></th>
<td><input type="checkbox" name="otopsi[filtersEnabled]" id="otopsi[filtersEnabled]" value="1" <?php echo $instance['filtersEnabled'] ? ' checked="checked"' : ''; ?>></td>
</tr>

<tr valign="top">
<th>
<label for="otopsi[contentTemplate]"><?php _e( 'Item content template:', 'otopsi_textdomain' ); ?></label>
<p><?php _e( 'Enter any valid HTML code and use placeholders in the format %template_tag_name% to configure
what part of the post will be displayed.', 'otopsi_textdomain' ); ?><br>
<?php _e( 'Use the special tag name %the_image% to retrieve the URL of the image of the post.', 'otopsi_textdomain' ); ?>
</p>
</th>
<td><textarea id="otopsi[contentTemplate]" name="otopsi[contentTemplate]" style="width:90%; height: 10em;">
<?php echo stripslashes( $instance['contentTemplate'] ); ?>          
</textarea>
<p><?php _e( 'See Wordpress template tags', 'otopsi_textdomain' ); ?><a href="http://codex.wordpress.org/Template_Tags" target="_blank"><?php _e( 'reference', 'otopsi_textdomain' ); ?></a>.</p>
</td>
</tr>

<tr valign="top">
<th><label for="otopsi[isotopeOptions]"><?php _e( 'Isotope options:', 'otopsi_textdomain' ); ?></label></th>
<td><textarea id="otopsi[isotopeOptions]" name="otopsi[isotopeOptions]]" style="width:90%; height: 10em;">
<?php echo stripslashes( $instance['isotopeOptions'] ); ?>
</textarea>
<p><?php _e( 'See Isotope options', 'otopsi_textdomain' ); ?><a href="http://isotope.metafizzy.co/options.html" target="_blank"><?php _e( 'reference', 'otopsi_textdomain' ); ?></a>.</p>
</td>

</tr>

</table>
<?php 
	}

	/* Render a template tag */
	public static function render_tag( $tag ) {
		global $post;

		$tag = $tag[1]; //the actual match
		//Custom Otopsi template tags
		if( $tag === 'the_image' ) { //The featured image
			if ( has_post_thumbnail() ) {
				$thumbURL = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) , '');
				return $thumbURL[0];
			}//hide the image
			return '" style="display:none';
			//$img = self::getFirstImage($post->ID);
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
	public static function render_template( $template ) {
		$r = preg_replace_callback ( '/%([^%\s]+)%/', 'Otopsi_Renderer::render_tag', $template ); 
		return $r;
	}


	public static function render_in_post( $post_content ) {
		global $post;

		$meta_code = '';

		//Retrieve the Otopsi meta data for this page, if any
		$instance = get_post_meta( $post->ID, OTOPSI_META_KEY, false ); //Returns an array

		if( ! is_array($instance) || sizeof($instance) < 1 ) {
			return $post_content;
		}

		$instance = $instance[0];

		if( $instance['enable'] != 1 ) {
			return $post_content;
		}

		return $post_content . "\n" . Otopsi_Renderer::render_instance( $instance );

	}


	/**
	 *
	 * Render an Instance of the plugin in a string and returns it.
	 *
	 */
	public static function render_instance( $instance ) {

		//Used to tag the items for interactive filtering on the client side
		$taxonomies = $instance['taxonomy'];
		$terms = $instance['term'];
		$filters = array();

		//Retrieve the posts to render
		$posts_query = Otopsi::search_blog( $instance, $filters );


		//Load CSS
		wp_enqueue_style( 'otopsi-base-style', plugins_url( 'css/otopsi.css', __FILE__ ) );
		wp_enqueue_style( 'otopsi-custom-style', plugins_url( 'css/custom.css', __FILE__ ), false, filemtime(plugin_dir_path( __FILE__ ) . 'css/custom.css' ) ); //Add cache busting


		//Load JS
		wp_enqueue_script( 'isotope-js', plugins_url( 'js/isotope.pkgd.min.js', __FILE__ ) );
		wp_enqueue_script( 'otopsi-js', plugins_url( 'js/otopsi.js', __FILE__), array( 'jquery', 'isotope-js' ), OTOPSI_VERSION );

		//start output buffering
		ob_start();
		//Wrapping DIV
?>
<div class="<?php echo $instance['wrapperClass']; ?> otopsi-init" data-otopsi="<?php echo str_replace(array("\r\n", "\r"), '', trim( htmlentities( stripslashes( $instance['isotopeOptions']) ) ) ); ?>">
<?php
		//Show filtering options if enabled
		$filterSlugs = array();
		if( isset($instance['filtersEnabled']) && $instance['filtersEnabled'] != 0 ) :
?>
<div class="otopsi-filters button-group">
<button data-filter="*" class="is-checked">show all</button>
<?php
			foreach($filters as $term) {
				$filterSlugs[$term['term_id']] = $term['slug'];
?>
<button data-filter=".<?php echo $term['slug']; ?>"><?php echo $term['name']; ?></button>
<?php } ?>
</div>
<?php endif; ?>

<div class="otopsi-container">
<div class="grid-sizer"></div>
<div class="gutter-sizer"></div>
<?php
				$i = 0;
				if ( $posts_query->have_posts()) : while ( $posts_query->have_posts() ) : $posts_query->the_post();
				$i++;
				global $post;
				//get the filtering terms for the post
				$postTerms = wp_get_post_terms( $post->ID, $taxonomies, array( 'fields' => 'all' ) );
				$postFilterTerms = array();
				foreach( $postTerms as $postTerm ) {
					//Directly match a filtering term
					if( isset($filterSlugs[ $postTerm->term_id ]) ) {
						$postFilterTerms[] = $postTerm->slug; 
						continue;
					}

					//Find the parent term
					if( isset($filterSlugs[ $postTerm->parent ]) ) {
						$postFilterTerms[] = $filterSlugs[ $postTerm->parent ];
						continue;
					}

					//WARNING: won't be filtered properly
					$postFilterTerms[] = $postTerm->slug;
				}
?>
<div class="item <?php echo implode(' ', $postFilterTerms); ?>">
<?php echo Otopsi_Renderer::render_template( stripslashes( $instance['contentTemplate'] ) ); ?>
</div>
<?php
endwhile;
endif;
?>
</div>
</div>

<?php

//Get the content of the buffer
$bufferContent = ob_get_contents(); 
//Stop buffering and discard content
ob_end_clean();

return $bufferContent;
	}




}
