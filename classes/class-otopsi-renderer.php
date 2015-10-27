<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*
 * Wraps method that render forms and the Isotope layout
 */
class Otopsi_Renderer{

	/*
	 * Build the HTML of the Otopsi metabox
	 */
	public static function render_meta_box_content( $post ) {
		//Load CSS
		wp_enqueue_style( 'otopsi-admin-style', plugins_url( 'css/otopsi-admin.css',  dirname(__FILE__) ) );
		//Retrieve the Otopsi meta data for this page, if any
		//Returns an array

		$metaData = get_post_meta( $post->ID, OTOPSI_META_KEY, false );
		if( !empty( $metaData ) ) {
			$instance = $metaData[0];
		}else{
			$instance = array( 'enable' => 0 );
		}
?>

<table class="form-table">
	<tr valign="top">
		<th><label for="otopsi[enable]"><?php _e('Enable Otopsi', 'otopsi-domain'); ?></label></th>
		<td><input type="checkbox" name="otopsi[enable]" id="otopsi[enable]" value="1" <?php echo $instance['enable']?' checked="checked"':''; ?>onchange="return Otopsi.ontoggleOtopsi(jQuery(this).is(':checked'));"></td>
	</tr>
</table>

<?php
		Otopsi_Renderer::render_instance_edit_form( $instance );
	}


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


<table class="form-table otopsi_show_if_enabled" style="display: <?php echo $instance['enable'] ? 'block' : 'none'; ?>">
	<tr valign="top">
		<th><label for="otopsi[wrapperClass]"><?php _e( 'Wrapper CSS class' ); ?></label></th>
		<td>
			<input class="regular-text" id="otopsi[wrapperClass]" name="otopsi[wrapperClass]" type="text" value="<?php echo $instance['wrapperClass']; ?>">
			<p class="description"><?php _e('Use this class name to customize the appearance of the Isotope widget.', 'otopsi-domain'); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th><label for="otopsi[taxonomy]"><?php _e( 'Taxonomies', 'otopsi-domain' ); ?></label></th>
		<td>
			<select multiple="multiple" onchange="return Otopsi.onchangeTerm( 'otopsi_term', jQuery(this).val() );" id="otopsi_taxonomy" name="otopsi[taxonomy][]" style="width:90%;">
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
			<p class="description"><?php _e('One or more taxonomy to fetch content from', 'otopsi-domain'); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th><label for="otopsi[term]"><?php _e( 'Terms', 'otopsi-domain' ); ?></label></th>
		<td>
			<select multiple="multiple" id="otopsi_term" name="otopsi[term][]" style="width:90%;">
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
				<option value="0"><?php _e( 'First select taxonomies in the field above', 'otopsi-domain' ); ?></option>
<?php
}
?>
			</select>
			<p class="description"><?php _e('Refine your content selection based on terms in the selected taxonomies.', 'otopsi-domain'); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th><label for="otopsi[posttype]"><?php _e( 'Post types', 'otopsi-domain' ); ?></label></th>
		<td>
			<select multiple="multiple"  id="otopsi[posttype]" name="otopsi[posttype][]" style="width:90%;">

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
			<p class="description"><?php _e('Filter content selection based on the selected types.', 'otopsi-domain'); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th>
			<label for="otopsi[limit]"><?php _e( 'Limit number of displayed items to', 'otopsi-domain' ); ?></label>
		</th>
		<td><input type="number" name="otopsi[limit]" id="otopsi[limit]" value="<?php echo $instance['limit']; ?>"/>
			<p class="description"><?php _e( '-1 : no limit', 'otopsi-domain' ); ?></p>
		</td>
	</tr>

	<tr valign="top">
		<th><label for="otopsi[filtersEnabled]"><?php _e( 'Enable Isotope filtering', 'otopsi-domain' ); ?></label></th>
		<td><input type="checkbox" class="filter-chkbox" name="otopsi[filtersEnabled]" id="otopsi[filtersEnabled]" value="1" <?php echo $instance['filtersEnabled'] ? ' checked="checked"' : ''; ?>><span class="dashicons dashicons-filter"></span>
			<p class="description"><?php _e( 'When checked, the widget will provide buttons to hide/show content based on the terms.', 'otopsi-domain' ); ?></p>

		</td>
	</tr>

	<tr valign="top">
		<th>
			<label for="otopsi[contentTemplate]"><?php _e( 'Item content template', 'otopsi-domain' ); ?></label>
			<p class="description"><?php _e( 'Enter any valid HTML code and use placeholders in the format %template_tag_name% to configure
			what part of the post will be displayed.', 'otopsi-domain' ); ?><br>
			<?php _e( 'Use the special tag name %the_image% to retrieve the URL of the image of the post.', 'otopsi-domain' ); ?>
			</p>
		</th>
		<td>
			<textarea id="otopsi[contentTemplate]" name="otopsi[contentTemplate]" cols="70" rows="25" class="editor"><?php echo stripslashes( $instance['contentTemplate'] ); ?></textarea>
			<p class="description"><a href="http://codex.wordpress.org/Template_Tags" target="_blank"><?php _e( 'Click here to learn about template tags in Wordpress', 'otopsi-domain' ); ?> </a></p>
		</td>
	</tr>

	<tr valign="top">
		<th>
			<label for="otopsi[isotopeOptions]"><?php _e( 'Isotope options', 'otopsi-domain' ); ?></label>i
			<p class="description"><?php _e( 'Enter properly formated JSON', 'otopsi-domain' ); ?></p>
			<p class="description"><?php _e( 'If you want to use a <u>layoutMode</u> that requires JavaScript code not included in the Isotope core library, make sure you first install it using <a href="' .  admin_url( 'admin.php?page=otopsi_layout_modes' ) . '">the Layout Modes admin page</a>.', 'otopsi-domain' ); ?></p>
		</th>
		<td>
			<textarea id="otopsi[isotopeOptions]" name="otopsi[isotopeOptions]]" cols="70" rows="25" class="editor"><?php echo stripslashes( $instance['isotopeOptions'] ); ?></textarea>
			<p class="description"><a href="http://isotope.metafizzy.co/options.html" target="_blank"><?php _e( 'Click here to read the documentation for Isotope options', 'otopsi-domain' ); ?></a></p>
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
		wp_enqueue_style( 'otopsi-base-style', plugins_url( 'css/otopsi.css',  dirname(__FILE__) ) );
		wp_enqueue_style( 'otopsi-custom-style', plugins_url( 'css/custom.css',  dirname(__FILE__) ), false, filemtime(  __OTOPSI_ROOT__ . 'css/custom.css' ) ); //Add cache busting


		//Load JS
		wp_enqueue_script( 'isotope-js', plugins_url( 'js/isotope.pkgd.min.js',  dirname(__FILE__) ) );
		wp_enqueue_script( 'otopsi-js', plugins_url( 'js/otopsi.js',  dirname(__FILE__) ), array( 'jquery', 'isotope-js' ), OTOPSI_VERSION );
		//Check if an external layout mode library needs to be loaded. Note that we are not checking if the file is actually available locally.
		$isotope_options_json = str_replace(array("\r\n", "\r"), '', trim( htmlentities( stripslashes( $instance['isotopeOptions']) ) ) );

		//Extract the layoutMode value from the JSON string
		if( preg_match( '/"layoutMode":\s?"([^"]*)"/', trim( $instance['isotopeOptions'] ), $matches ) ){
			echo $matches[1];
			if( ! Otopsi::is_default_layout_mode(  $matches[1]) ){ //Add the script to the page
				wp_enqueue_script(   $matches[1] . '-js', plugins_url( 'js/layout-modes/' .  $matches[1] . '.js',  dirname(__FILE__) ), NULL, OTOPSI_VERSION );
			}

		}
		//start output buffering
		ob_start();
		//Wrapping DIV
?>
<div class="<?php echo $instance['wrapperClass']; ?> otopsi-init" data-otopsi="<?php echo $isotope_options_json; ?>">
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

	public static function render_shortcode_list_header(){
?>
		<tr>
			<th id="name" scope="col" class="manage-column column-name column-primary sorted asc"><a href="#"><span><?php _e( 'Reference Name', 'otopsi-domain' ); ?></span></a></th>
			<th id="shortcode" scope="col" class="manage-column column-shortcode"><?php _e( 'Shortcode', 'otopsi-domain' ); ?></th>
		</tr>
<?php
	}




}
