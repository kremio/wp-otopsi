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
	public static function activate() {
		$primaryKey = get_option( OTOPSI_SC_KEY );
		if ( empty( $primaryKey ) ) {
			update_option( OTOPSI_SC_KEY, '1' );
		}
	}

	/*
 	* Remove the options saved in the database when the plugin is uninstalled
 	*/
	public static function uninstall(){
		delete_option( OTOPSI_SC_DATA );
		delete_option( OTOPSI_SC_KEY );
	}



	/* 
	 * Setup the plugin settings interface on the page editor
	 */
	public function on_add_meta_box( $post_type ) {
		add_meta_box(
			'otopsi_meta_box'
			,__( 'Otopsi filtering and layout', 'otopsi_textdomain' )
			,array( 'Otopsi_Renderer', 'render_meta_box_content' )
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
		//read the default HTML template from the HTML file
		$html_template = file_get_contents ( __OTOPSI_ROOT__ . '/defaults/html-template.html' );
		if( ! $html_template ){
			$html_template = sprintf('WARNING! Could not read %s/defaults/html-template.html.\n Make sure the file permission settings allow the file to be read by PHP.', __OTOPSI_ROOT__ );
		}

		//read the default Isototope configuration from the JSON file
		$isotope_options = file_get_contents ( __OTOPSI_ROOT__ . '/defaults/isotope-options.json' );
		if( ! $isotope_options  ){
			$isotope_options = sprintf('WARNING! Could not read %s/defaults/isotope-options.json.\n Make sure the file permission settings allow the file to be read by PHP.', __OTOPSI_ROOT__ );
		}
		
		//read the default CSS template from the HTML file
		$css_template = file_get_contents ( __OTOPSI_ROOT__ . '/defaults/custom.css' );
		if( ! $html_template ){
			$css_template = sprintf('WARNING! Could not read %s/defaults/custom.css.\n Make sure the file permission settings allow the file to be read by PHP.', __OTOPSI_ROOT__ );
		}

		return array(
			'enable'       => 0, //(0 or 1) - 0: the plugin won't render on the page (only applies in the context of a page, not for shortcodes)
			'wrapperClass' => 'otopsi', //(String) - HTML class that allows to style the plugin layout from CSS
			/* 
			 * Parameters for the content search query.
			 * See https://codex.wordpress.org/Taxonomies
			 */
			'taxonomy' => '', //(Array) - Configure which taxonomies are included  
			'term'     => '', //(Array) - Configure which terms of the taxonomies are included
			'posttype' => '', //(Array) - Limit search to the specified post types
			'limit'    => 10, //Integer - Limit the number of posts returned
			'sort'     => 'date|DESC',
			/*
			 * Isotope settings
			 */
			'filters' => '', //(0 or 1) - 0:disable filtering based on terms, 1:enable filtering based on terms
			//see http://isotope.metafizzy.co/options.html
			'isotopeOptions' => $isotope_options,
			//HTML template for the items content
			'contentTemplate' => $html_template,
			//Custom CSS rules
			'cssTemplate' => $css_template,
		);
	}

	/*
	 * Return an associative array representation of the sort settings of an instance.
	 * The array is laid out like so : sortcode => ASC|DESC
	 * Return FALSE if the sort settings are not set or if the settings are not formatted properly.
	 */
	public static function expand_sort_setting( $instance ){
		if( !isset( $instance['sort'] ) || trim( $instance['sort']  ) === '' ){
			return FALSE;
		}
		
		$sortcodes_directions = explode( '|', $instance['sort'] );
		$sortcodes = explode(',', $sortcodes_directions[0] );
		$directions = explode(',', $sortcodes_directions[1] );

		return array_combine( $sortcodes, $directions );
	}
	

	public static function expand_filters_setting( $instance ){
  		if( !isset( $instance['filters'] ) || trim( $instance['filters']  ) === '' ){
			return FALSE;
		}

		$expanded_filter_groups = array();
		$filter_groups = explode( '|', $instance['filters'] );
		foreach( $filter_groups as $filter_group ){
			list($group_name, $display_group_name) = explode( ';', $filter_group );
			$filters = array_slice( explode( ';', $filter_group ) , 2 );
			
   			$expanded_filter_groups[ $group_name ] = array(
				'display_group_name' => $display_group_name,
				'filters' => array()
			);

			for($i = 0; $i < count( $filters ); $i+=2){
    			$expanded_filter_groups[ $group_name ][ 'filters' ][ $filters[$i] ] = $filters[$i+1];
			}
		}

		return $expanded_filter_groups;
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
		$my_data = $_POST['otopsi'];
		/*
		if( !array_key_exists( 'filtersEnabled', $my_data) ) {
			$my_data['filtersEnabled'] = 0;
		}
		 */
		if( !array_key_exists( 'enable', $my_data) ) {
			$my_data['enable'] = 0;
		}

		$my_data['sort'] = trim( $my_data['sort'] );
		$my_data['filters'] = trim( $my_data['filters'] );
		$my_data['isotopeOptions'] = trim( $my_data['isotopeOptions'] );
		$my_data['contentTemplate'] = trim( $my_data['contentTemplate'] );
		$my_data['cssTemplate'] = trim( $my_data['cssTemplate'] );


		return $my_data;
	}


	/**
	 * Execute a WordPress post query based on the settings of an instance
	 * $instance: associative array specifying the search parameters
	 * $filters: array passed by referenece which will contain the filtering terms when the function returns
	 * returns a WP_Query instance
	 * (see https://codex.wordpress.org/Class_Reference/WP_Query and https://gist.github.com/luetkemj/2023628)
	 */
	public static function search_blog( $instance, &$filters ) {

		$sort_options = Otopsi::expand_sort_setting( $instance );

		//constructor options for WP_Query
		$args = array(
			'post_type'      => $instance['posttype'],
			'posts_per_page' => $instance['limit'],
			'orderby'        => $sort_options,
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
						'terms'            => array( $term_array['slug'] ),
						'include_children' => true,
						'operator'         => 'IN'
					);
				} else {
					$taxonomies_terms[$taxonomyName]['terms'][] = $term_array['slug'];
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
	 * Construct optgroup for filter creation by extracting all possible filters from the result of running the blog search
	 * specified in the HTTP POST request
	 * $_POST['search_terms'] : taxonomy and terms search criteria
	 * $_POST['search_posttype'] : post_type search criteria
	 * $_POST['search_sort'] : result sorting
	 * $_POST['search_limit'] : search result limit setting
	 */
	public static function get_possible_filters() {
  		if (
			! isset( $_POST['search'] ) || ! is_array( $_POST['search'] ) ||
			! isset( $_POST['search']['term'] )    || empty( $_POST['search']['term'] )    ||
			! isset( $_POST['search']['posttype'] ) || empty( $_POST['search']['posttype'] ) ||
			! isset( $_POST['search']['sort'] )     || empty( $_POST['search']['sort'] )     ||
			! isset( $_POST['search']['limit'] )    || empty( $_POST['search']['limit'] )
		){
			return;
		}
		
		$search_query = $_POST['search'];
		$not_used = array();
		$search_results = Otopsi::search_blog($search_query, $not_used);
		
		if( ! $search_results->have_posts() ){
			return;
		}

		$filters = array(
			'tags' => array(),
			'categories' => array(),
			'authors' => array(),
			'posttypes' => array(),
		);
		
		while ( $search_results->have_posts() ){
			$search_results->the_post();
			
			global $post;
			//Retrieve the filters value for the post and aggregate into the $filters array
			$post_tags = wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) );
      $post_categories = wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) );

			$filters['tags'] = array_merge( $filters['tags'], $post_tags );
			$filters['categories'] = array_merge( $filters['categories'], $post_categories );
			$filters['authors'][] = get_the_author();
	   		$post_types = get_post_type();
			if( false != $post_types ){
    			if( is_array( $post_types ) ){
					$filters['posttypes'] = array_merge( $filters['posttypes'], $post_types );
				}else{
     				$filters['posttypes'][] = $post_types ;
				}
			}

		}
		//remove doubles
		$filters['tags'] = array_unique( $filters['tags'] );
		$filters['categories'] = array_unique( $filters['categories'] );
		$filters['authors'] = array_unique( $filters['authors'] );
		$filters['posttypes'] = array_unique( $filters['posttypes'] );

?>
	<option value="*"><?php _e( 'Show all', 'otopsi-domain' ); ?></option>
	<optgroup label="<?php _e( 'Categories', 'otopsi-domain' ); ?>">
<?php
		foreach ( $filters['categories'] as $category ) {
   	echo sprintf( '<option value="category_%s">%1$s</option>', $category );
		}
?>
</optgroup>
<optgroup label="<?php _e( 'Tags', 'otopsi-domain' ); ?>">
<?php
		foreach ( $filters['tags'] as $tag ) {
   	echo sprintf( '<option value="tag_%s">%1$s</option>', $tag );
		}
?>
</optgroup>
<optgroup label="<?php _e( 'Authors', 'otopsi-domain' ); ?>">
<?php
		foreach ( $filters['authors'] as $author ) {
   	echo sprintf( '<option value="author_%s">%1$s</option>', $author );
		}
?>
</optgroup>
<optgroup label="<?php _e( 'Types', 'otopsi-domain' ); ?>">
<?php
		foreach ( $filters['posttypes'] as $posttype ) {
   	echo sprintf( '<option value="postype_%s">%1$s</option>', $posttype );
		}
?>
</optgroup>
<?php
		exit();
	}
	/*
	 * Construct optgroup from the list of the terms under a taxonomy in answer to a HTTP POST request
	 * $_POST['otopsi_term'] : array of taxonomy names whose terms we want to retrieve
	 */  
	public static function get_taxonomy_terms() {
		if ( ! isset( $_POST['otopsi_term'] ) ||  empty( $_POST['otopsi_term'] ) ){
			return;
		}

		foreach ( $_POST['otopsi_term'] as $tax ) {
			$terms = get_terms( $tax, 'hide_empty=0&orderby=term_group' );
			$opt_group_taxonomy = '';

			if ( is_wp_error( $terms ) ) {
				continue; //TODO: Better way to handle/report error
			}
			
			foreach ( $terms as $term ) {
				if ( $term->taxonomy != $opt_group_taxonomy  ) {
					if ( $opt_group_taxonomy ) {
						echo '</optgroup>'; //close the previous optgroup tag
					}
					$opt_group_taxonomy = $term->taxonomy;
					$opt_group_taxonomy_obj = get_taxonomy( $opt_group_taxonomy );
					echo sprintf( '<optgroup label="%s">', $opt_group_taxonomy_obj->labels->name );
				}
				echo sprintf( '<option value="%s;%s">%s (%d)</option>', $term->taxonomy, $term->term_id, $term->name, $term->count );
			}

		}

		if ($opt_group_taxonomy ) {
			echo '</optgroup>'; //close the last optgroup tag, if any
		}
	}

	public static function check_credentials_for_download(){

		//Check how to deal with the filesystem
		$filesystem_method = get_filesystem_method( array(), __OTOPSI_ROOT__ . '/js/layout-modes');

		if( 'direct' != $filesystem_method ){ //The user needs to provide credentials
			echo 'credentials needed';
			exit;
		}

		//We are good to go!
		$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array(), '', false, false, null);
		
		$result = Otopsi::install_layout_mode( $creds );
		if( is_wp_error( $result ) ){
			if( 'credentials' === $result->get_error_code() ){
				echo 'credentials needed';
				exit;
			}

			if( 'download_failed' === $result->get_error_code() ){
				echo __('Download failed', 'otopsi-domain') . ':' . $result->get_error_message( 'download_failed' );
				exit;
			}

			if( 'installation_failed' === $result->get_error_code() ){
				echo __('Installation failed', 'otopsi-domain') . ':' . $result->get_error_message( 'installation_failed' );
				exit;
			}

		}

		echo 'ok';
		exit;
	}

	public static function install_layout_mode( $creds ){

		if ( ! WP_Filesystem($creds) ) {
			request_filesystem_credentials($url, '', true, false, null);
			return new WP_Error( 'credentials' );
		}

		$layout_modes =  Otopsi::get_isotope_layout_modes();
		if ( ! isset( $_POST['otopsi_layout_mode'] ) || empty( trim($_POST['otopsi_layout_mode']) ) || !isset( $layout_modes[ trim( $_POST['otopsi_layout_mode'] ) ] ) || '' === $layout_modes[ trim( $_POST['otopsi_layout_mode'] ) ] ){
			//This case should not occur when using the plugin admin, something is fishy so let's just fail silently
			return true;
		}

		$layout_name = trim( $_POST['otopsi_layout_mode'] );

		//Download the file to a temporary location on the local server
		$download_file = download_url( $layout_modes[ $layout_name ] /*, 300 seconds timeout */ );
		if( is_wp_error( $download_file ) ){
			return new WP_Error( 'download_failed', $download_file->get_error_message() );
		}

		global $wp_filesystem;
		/* replace the 'direct' absolute path with the Filesystem API path */
		$plugin_path = str_replace(ABSPATH, $wp_filesystem->abspath(), __OTOPSI_ROOT__);
		//Move the file to Otopsi's js/layout-modes/ folder
		if( ! $wp_filesystem->move ( $download_file, $plugin_path . '/js/layout-modes/' . $layout_name . '.js', true ) ){ //Let's overwrite to be able to update
			return new WP_Error( 'installation_failed',  __('The layout mode library could not be installed in the plugin folder.', 'otopsi-domain') );
		}

		return true;
	}


	/*
 	* Returns an associative array where the keys are the Isotope layout modes names and the values are the URLs to the associated JS library.
 	* Set the value to an empty string if the layout mode is included in the main Isotope JS file.
 	*/
	public static function get_isotope_layout_modes(){
		return array(
			'masonry'           => '',
			'fitRows'           => '',
			'vertical'          => '',
			'packery'           => 'https://cdnjs.cloudflare.com/ajax/libs/packery/1.4.3/packery.pkgd.min.js',
			'cellsByRow'        => 'https://raw.github.com/metafizzy/isotope-cells-by-row/master/cells-by-row.js',
			'masonryHorizontal' => 'https://raw.github.com/metafizzy/isotope-masonry-horizontal/master/masonry-horizontal.js',
			'fitColumns'        => 'https://raw.github.com/metafizzy/isotope-fit-columns/master/fit-columns.js',
			'cellsByColumn'     => 'https://raw.github.com/metafizzy/isotope-cells-by-column/master/cells-by-column.js',
			'horizontal'        => 'https://raw.github.com/metafizzy/isotope-horizontal/master/horizontal.js',
		);
	}

	public static function is_default_layout_mode( $layout_mode_name ){
		$layout_modes = Otopsi::get_isotope_layout_modes();
		return isset( $layout_modes[ $layout_mode_name  ] ) && '' === $layout_modes[ $layout_mode_name  ];
	}

};
