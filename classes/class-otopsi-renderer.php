<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*
 * Wraps method that render forms and the Isotope layout
 */
class Otopsi_Renderer{

/*
 * Render the list of available sort options
 */
 	public static function render_sort_options( $state = false ){
		$sort_options = array(
			'author'        => __('by author', 'otopsi-domain'),
			'title'         => __('by title', 'otopsi-domain'),
			'name'          => __('by post name', 'otopsi-domain'),
			'type'          => __('by post type', 'otopsi-domain'),
			'date'          => __('by date', 'otopsi-domain'),
			'modified'      => __('by last modified date', 'otopsi-domain'),
			'rand'          => __('randomly', 'otopsi-domain'),
			'comment_count' => __('by number of comments', 'otopsi-domain'),
		);

		foreach($sort_options as $option_code => $option_label){
			$direction = $state && isset( $state[ $option_code ] ) && $state[ $option_code ] ? $state[ $option_code ] : 'off';
?>
<div class="sort-option <?php echo $direction; ?>" data-code="<?php echo $option_code; ?>" data-direction="<?php echo $direction; ?>">
	<span class="dashicons dashicons-minus off" data-state="off"></span>
	<span class="dashicons dashicons-arrow-up-alt2 ascending" data-state="ASC"></span>
	<span class="dashicons dashicons-arrow-down-alt2 descending" data-state="DESC"></span>
	<?php echo $option_label; ?>
</div>
<?php
		}
	}

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
	<tr >
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


<div class="otopsi_show_if_enabled js metabox-holder" style="display: <?php echo $instance['enable'] ? 'block' : 'none'; ?>">
	<div class="meta-box-sortables">
		
		<div id="content-selection-options" class="settings postbox closed">
			<div class="handlediv" title="Click to toggle"><br /></div>
			<h3 class='hndle'><span><?php _e('Content Selection', 'otopsi-domain'); ?></span></h3>
			<table class="form-table">
				<tbody>
				<tr >
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

				<tr >
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

			<tr >
				<th><label for="otopsi[posttype]"><?php _e( 'Post types', 'otopsi-domain' ); ?></label></th>
				<td>
					<select multiple="multiple"  id="otopsi_posttype" name="otopsi[posttype][]" style="width:90%;">

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

			<tr >
				<th>
					<label for="otopsi[limit]"><?php _e( 'Limit number of displayed items to', 'otopsi-domain' ); ?></label>
				</th>
				<td><input type="number" class="otopsi-no-submit" name="otopsi[limit]" id="otopsi[limit]" value="<?php echo $instance['limit']; ?>"/>
					<p class="description"><?php _e( '-1 : no limit', 'otopsi-domain' ); ?></p>
				</td>
			</tr>

			<tr >
				<th>
					<label for="otopsi[sort]"><?php _e( 'Default items order', 'otopsi-domain' ); ?></label>
					<p class="description">
<?php _e('This determines the order the items will be fetched from the database and how they will be initially displayed by the widget.', 'otopsi-domain' ); ?>
					</p>
				</th>
				<td><input type="hidden" name="otopsi[sort]" id="otopsi[sort]" value="<?php echo $instance['sort']; ?>"/>
					<?php Otopsi_Renderer::render_sort_options( Otopsi::expand_sort_setting( $instance ) ); ?>
					<p class="description"><?php _e( '', 'otopsi-domain' ); ?></p>
				</td>
			</tr>
			
			</tbody>
		</table>
	</div> <!-- END content-selection-options -->

	<div id="isotope-options" class="settings postbox closed">
		<div class="handlediv" title="Click to toggle"><br /></div>
		<h3 class="hndle"><span><?php _e('Isotope Options', 'otopsi-domain'); ?></span></h3>
		<table class="form-table">
			<tbody>
			<tr >
				<th>
					<label for="otopsi[filtersEnabled]"><?php _e( 'Filtering', 'otopsi-domain' ); ?></label>
					<p class="description"><?php _e( 'Filter groups allow the page visitors to interactively filter the content of the widget.', 'otopsi-domain' ); ?></p>
					<p class="description filter-warning"><?php _e( 'One or more of the filters has no effect!', 'otopsi-domain' ); ?></p>
				</th>
				<td>
					<select id="filters-select-source" class="filters-select">
					</select>
					
					<div class="filter-group-template">
						<div class="handlediv" title="Click to toggle"><br /></div>
						<h3 class="hndle"></h3>
						
						<div class="filter-group-box inside">
						<ul class="category-tabs">
								<li class="tabs"><a href="#group-tab-"><?php _e( 'Group options', 'otopsi-domain' ); ?></a></li>
								<li class=""><a href="#filters-tab-"><?php _e( 'Filters', 'otopsi-domain' ); ?></a></li>
							</ul>

							<div id="group-tab-" class="wp-tab-panel">
								<label for="group-tab-name"><?php _e( 'Group name', 'otopsi-domain' ); ?></label>
								<input type="text" class="regular-text filter-group-name-input" name="group-tab-name"><br/>
								<label for="group-tab-display">
								<input name="group-tab-display" type="checkbox" value="hide"><?php _e( 'Display group name', 'otopsi-domain' ); ?></label>
								<br/>
								<label for="group-tab-operator"><?php _e( 'Add or Combine with other filters ?', 'otopsi-domain' ); ?></label>
								<select name="group-tab-operator">
									<option value="combine"><?php _e( 'Combine', 'otopsi-domain' ); ?></option>
									<option value="add"><?php _e( 'Add', 'otopsi-domain' ); ?></option>
								</select>
							</div>

							<div id="filters-tab-" class="wp-tab-panel">
								<ul class="filters"></ul>
							</div>

							<div class="filter-adder wp-hidden-children">
								<h4><a href="#filter-add">+ <?php _e('Add New Filter', 'otopsi-domain'); ?></a></h4>
								<p class="filter-adder-form wp-hidden-child">
									<label for="filters-tab-label"><?php _e( 'Label', 'otopsi-domain' ); ?></label>
									<input type="text" class="regular-text filter-label-input" name="filters-tab-label"><br/>

									<label for="filters-tab-type"><?php _e( 'Filter on', 'otopsi-domain' ); ?></label>
									<select  multiple="multiple" name="filters-tab-type" class="filters-select">
									</select>
									<br/>

									<button type="button" class="button add-filter-button"><?php _e( 'Add filter', 'otopsi-domain' ); ?></button>
									<button type="button" class="button edit-button update-filter-button"><?php _e( 'Update filter', 'otopsi-domain' ); ?></button>
									<button type="button" class="button edit-button cancel-filter-button"><?php _e( 'Cancel', 'otopsi-domain' ); ?></button>
								</p>
							</div>

							<a href="#" class="delete-filter-group"><span class="dashicons dashicons-dismiss"></span><?php _e( 'Remove this filter group', 'otopsi-domain'); ?></a>

						</div>
					</div> <!-- END OF TEMPLATE -->

					<div class="filters-js-messages">
						<span class="confirm-deletion"><?php _e('Are you sure?', 'otopsi-domain'); ?></span>
					</div>

					<input type="text" class="regular-text" name="new-filter-group"/><button id="add-filter-group" type="button" class="button"><?php _e('Add a filter group', 'otopsi-domain'); ?></button>
					<div id="filter-groups-accordion" class="postbox">
<?php
		$filter_groups = Otopsi::expand_filters_setting( $instance );
		foreach( $filter_groups as $group_name => $group_data ){
?>
						<div class="filters-group-wrap closed">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><?php echo $group_name; ?></h3>

							<div class="filter-group-box inside">
								<ul class="category-tabs">
									<li class="tabs"><a href="#group-tab-"><?php _e( 'Group options', 'otopsi-domain' ); ?></a></li>
									<li class=""><a href="#filters-tab-"><?php _e( 'Filters', 'otopsi-domain' ); ?></a></li>
								</ul>

								<div id="group-tab-" class="wp-tab-panel">
									<label for="group-tab-name"><?php _e( 'Group name', 'otopsi-domain' ); ?></label>
									<input type="text" class="regular-text filter-group-name-input" name="group-tab-name" value="<?php echo $group_name; ?>"><br/>
									<label for="group-tab-display">
									<input name="group-tab-display" type="checkbox" value="hide" <?php echo '1' == $group_data[ 'display_group_name' ] ? 'checked="true"' : ''; ?>><?php _e( 'Display group name', 'otopsi-domain' ); ?></label><br/>
									<label for="group-tab-operator"><?php _e( 'Add or Combine with other filters ?', 'otopsi-domain' ); ?></label>
									<select name="group-tab-operator">
										<option value="combine"<?php echo 'combine' == $group_data[ 'operator' ] ? ' selected="true"' : ''; ?>><?php _e( 'Combine', 'otopsi-domain' ); ?></option>
										<option value="add"<?php echo 'combine' == $group_data[ 'operator' ] ? ' selected="true"' : ''; ?>><?php _e( 'Add', 'otopsi-domain' ); ?></option>
									</select>
								</div>

								<div id="filters-tab-" class="wp-tab-panel">
								<ul class="filters">
<?php
			foreach( $group_data[ 'filters' ] as $filter_label => $filter_types ){
?>
	<li data-filters="<?php echo $filter_types; ?>" data-name="<?php echo $filter_label; ?>"><b class="name"><?php echo $filter_label; ?></b><span class="dashicons dashicons-edit edit"></span><span class="dashicons dashicons-trash delete"></span></li>
<?php
			}
?>
								</ul>
								</div>

								<div class="filter-adder wp-hidden-children">
									<h4><a href="#filter-add">+ <?php _e('Add New Filter', 'otopsi-domain'); ?></a></h4>
									<p class="filter-adder-form wp-hidden-child">
										<label for="filters-tab-label"><?php _e( 'Label', 'otopsi-domain' ); ?></label>
										<input type="text" class="regular-text filter-label-input" name="filters-tab-label"><br/>

										<label for="filters-tab-type"><?php _e( 'Filter on', 'otopsi-domain' ); ?></label>
										<select  multiple="multiple" name="filters-tab-type" class="filters-select">
										</select>
										<br/>

										<button type="button" class="button add-filter-button"><?php _e( 'Add filter', 'otopsi-domain' ); ?></button>
										<button type="button" class="button edit-button update-filter-button"><?php _e( 'Update filter', 'otopsi-domain' ); ?></button>
										<button type="button" class="button edit-button cancel-filter-button"><?php _e( 'Cancel', 'otopsi-domain' ); ?></button>
									</p>
								</div>

								<a href="#" class="delete-filter-group"><span class="dashicons dashicons-dismiss"></span><?php _e( 'Remove this filter group', 'otopsi-domain'); ?></a>

							</div>
						</div>
<?php
		}
?>
					</div>
					<input type="hidden" name="otopsi[filters]" id="otopsi[filters]" value="<?php echo $instance['filters'] ?>">

				</td>
			</tr>

			<tr >
				<th>
					<label for="otopsi[isotopeOptions]"><?php _e( 'Isotope options', 'otopsi-domain' ); ?></label>
					<p class="description"><?php _e( 'Enter properly formated JSON', 'otopsi-domain' ); ?></p>
					<p class="description"><?php _e( 'If you want to use a <u>layoutMode</u> that requires JavaScript code not included in the Isotope core library, make sure you first install it using <a href="' .  admin_url( 'admin.php?page=otopsi_layout_modes' ) . '">the Layout Modes admin page</a>.', 'otopsi-domain' ); ?></p>
				</th>
				<td>
					<textarea id="otopsi[isotopeOptions]" name="otopsi[isotopeOptions]]" cols="70" rows="25" class="editor"><?php echo stripslashes( $instance['isotopeOptions'] ); ?></textarea>
					<p class="description"><a href="http://isotope.metafizzy.co/options.html" target="_blank"><?php _e( 'Click here to read the documentation for Isotope options', 'otopsi-domain' ); ?></a></p>
				</td>
			</tr>


			<tr >
				<th><label for="otopsi[wrapperClass]"><?php _e( 'Wrapper CSS class' ); ?></label></th>
				<td>
					<input class="regular-text otopsi-no-submit" id="otopsi[wrapperClass]" name="otopsi[wrapperClass]" type="text" value="<?php echo $instance['wrapperClass']; ?>">
					<p class="description"><?php _e('This class name will be prepended to all the rules defined in the following field', 'otopsi-domain'); ?></p>
				</td>
			</tr>


			<tr >
				<th>
					<label for="otopsi[cssTemplate]"><?php _e( 'Custom CSS rules', 'otopsi-domain' ); ?></label>
					<p class="description"><?php _e( 'Here you can define rules to customize the appearance of the Isotope widget.', 'otopsi-domain' ); ?><br>
					<?php _e( 'NOTE: each rules will be automatically prepended by the class name defined in the previous field, no need to add it yourself!', 'otopsi-domain' ); ?>
					</p>
				</th>
				<td>
					<textarea id="otopsi[cssTemplate]" name="otopsi[cssTemplate]" cols="70" rows="25" class="editor"><?php echo stripslashes( $instance['cssTemplate'] ); ?></textarea>
					<p class="description"><a href="<?php echo admin_url('plugin-editor.php?file=otopsi/defaults/custom.css&amp;plugin=otopsi/otopsi.php'); ?>" target="_blank"><?php _e( 'Click here to see a template with all the predefined CSS classes', 'otopsi-domain' ); ?> </a></p>
				</td>
			</tr>

			<tr >
				<th>
					<label for="otopsi[cssTemplate]"><?php _e( 'Item content template', 'otopsi-domain' ); ?></label>
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

			</tbody>
		</table>
	</div> <!-- END isotope-options -->
</div>
</div>
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

	{
		//Get the custom CSS and prepend the custom class name
		$isotope_custom_css = preg_replace( '/(\/\*.*\*\/)/Ums', '', $instance['cssTemplate'] ); //remove all comments
		preg_match_all('/(.*)\{([^\}]*)\}/Um', $isotope_custom_css, $output_array); //split the CSS into selectors and rules, leaving curlies out

		$isotope_custom_css = '';
		//verify the parsing
		if( count( $output_array ) === 3 ){
			// $output_array[1] contains the selectors,  $output_array[2] contains the rules
			//prepend the custom class name and re-assemble the CSS
			$customClassSelector = '.'.$instance['wrapperClass'];
			for( $i = 0; $i < count( $output_array[1] ); $i++ ){
				$isotope_custom_css .= $customClassSelector  . ' ' . str_replace( ',', ',' . $customClassSelector, $output_array[1][$i] ) . '{' . $output_array[2][$i] . '} ';
			}
			$isotope_custom_css = str_replace( array("\r\n", '\r'), ' ', trim( htmlentities( stripslashes( $isotope_custom_css ) ) ) ); //sanitize a bit, hej?

			//Add the custom style to the page
			wp_add_inline_style( 'otopsi-base-style', $isotope_custom_css );

		}
	}
		//Load JS
		wp_enqueue_script( 'isotope-js', plugins_url( 'js/isotope.pkgd.min.js',  dirname(__FILE__) ) );
		wp_enqueue_script( 'otopsi-js', plugins_url( 'js/otopsi.js',  dirname(__FILE__) ), array( 'jquery', 'isotope-js' ), OTOPSI_VERSION );
		//Check if an external layout mode library needs to be loaded. Note that we are not checking if the file is actually available locally.
		$isotope_options_json = str_replace(array("\r\n", "\r"), '', trim( htmlentities( stripslashes( $instance['isotopeOptions'] ) ) ) );

		//Extract the layoutMode value from the JSON string
		if( preg_match( '/"layoutMode":\s?"([^"]*)"/', stripslashes( $instance['isotopeOptions'] ), $matches ) ){
			if( ! Otopsi::is_default_layout_mode(  $matches[1]) ){ //Add the script to the page
				wp_enqueue_script(   $matches[1] . '-js', plugins_url( 'js/layout-modes/' .  $matches[1] . '.js',  dirname(__FILE__) ), NULL, OTOPSI_VERSION );
			}
		}

		$filter_groups = Otopsi::expand_filters_setting( $instance );
		//start output buffering
		ob_start();
		//Wrapping DIV
?>
<div class="<?php echo $instance['wrapperClass']; ?> otopsi otopsi-init" data-otopsi="<?php echo $isotope_options_json; ?>">
<?php
		$enabled_filters = array(
			'categories' => false,
			'tags' => false,
			'authors' => false,
			'posttypes' => false
		);
		//Create filter groups
		foreach( $filter_groups as $group_name => $group_data ){
?>
	<div class="otopsi-filter-group">
		<p class="group_name<?php echo '0' == $group_data[ 'display_group_name' ] ? 'hidden' : ''; ?>"><?php echo $group_name; ?></p>
		<div class="button-group <?php echo $group_data[ 'operator' ]; ?>">
<?php
			foreach( $group_data[ 'filters' ] as $filter_label => $filter_types ){
				Otopsi::get_enabled_filter_types_from_settings( $filter_types,  $enabled_filters );
				$filter_types = explode( ',', $filter_types );
				for( $i = 0 ; $i < count( $filter_types ); $i++ ){
					$filter_types[ $i ] = Otopsi::format_filter_term( $filter_types[ $i ], '.'); //already prefixed
					if( '.' == $filter_types[ $i ] ){ //special case of the filter that does nothing!
						$filter_types[ $i ] = '';
					}
				}

?>
			<button class="button<?php echo '' === $filter_types[0] ? ' is-checked' : ''; ?>" data-filter="<?php echo implode( ',', $filter_types ); ?>"><?php echo $filter_label; ?></button>
<?php
			}
?>
		</div>
	</div>
<?php
		}
?>

<div class="otopsi-container">
<div class="grid-sizer"></div>
<div class="gutter-sizer"></div>
<?php
				if ( $posts_query->have_posts()) :
					while ( $posts_query->have_posts() ) :
						$posts_query->the_post();
						global $post;
						//gather the filtering terms for the post
						$postFilterTerms = Otopsi::get_post_filter_terms( $post, $enabled_filters );
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
