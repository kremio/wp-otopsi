<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


$scData = Otopsi_Shortcode::get_shortcodes_data();
$currentSc = array();

$screen_mode = "list";

if( isset( $_GET['edit'] ) ){
	$currentSc = $scData[ $_GET['edit'] ];
	$screen_mode = "edit";
}else if( isset( $_GET['delete'] ) ){
	unset( $scData[ $_GET['delete'] ] );
	Otopsi_Shortcode::save_shortcodes_data( $scData );
}else{
	$mydata = Otopsi::parse_form_data_post();
	if( $mydata && ! empty( $mydata ) ){ //save the shortcode
		Otopsi_Shortcode::save_shortcode( $scData, $mydata );
	}
}

if( isset( $_GET['new'] ) ){
	$screen_mode = "edit";
}



	//Decide whether to render the list od shortcode or the edit form
if( "edit" === $screen_mode ){

	$isNewForm = empty( $currentSc );

	if( $isNewForm ){
		$currentSc = array();
	}

	$formTitle =  $isNewForm ?  __( 'Create a new shortcode', 'otopsi-domain' ) : __( 'Modify a shortcode', 'otopsi-domain' );
	$buttonLabel = $isNewForm ? __( 'Create shortcode', 'otopsi-domain' ) : __( 'Save changes', 'otopsi-domain' );
	$currentSc['enable'] = 1;
	$currentSc['scName'] = $isNewForm ? 'NewOtopsiShortCode' : $currentSc['scName'] ;

?>

<div class="wrap">
<h2><?php echo $formTitle; ?></h2>

	<form id="otopsi_shortcode_form" action="<?php menu_page_url('otopsi_admin_menu'); ?>" method="post">

		<table class="form-table">
			<tr valign="top">
				<th><label for="otopsi[scName]"><?php _e( 'Reference Name', 'otopsi-domain' ); ?></label></th>
				<td>
					<input class="regular-text" id="otopsi[scName]" name="otopsi[scName]" type="text" value="<?php echo $currentSc['scName']; ?>">
					<p class="description"><?php _e( 'Giving a meaningful name to the shortcode makes it easier to know what it does!', 'otopsi-domain' ); ?></p>
				</td>
			</tr>
		</table>
<?php
	Otopsi_Renderer::render_instance_edit_form( $currentSc );
	if( !$isNewForm ){
		echo '<input type="hidden" name="otopsi[sc_id]" value="' .  $currentSc['sc_id'] . '"/>';
	}
?>
		<button class="button button-primary button-large" value="<?php echo $buttonLabel; ?>"><?php echo $buttonLabel; ?></button>
	</form>
<?php
	return; //Don't render the list of shortcodes
	}
?>


<div class="wrap">
<h2><?php _e('Otopsi Shortcodes', 'otopso-domain'); ?> <a href="<?php menu_page_url('otopsi_admin_menu'); ?>&new" class="page-title-action"><?php _e('Add New', 'otopso-domain'); ?></a></h2>

<table class="wp-list-table widefat fixed striped otopsi-shortcodes">

	<thead>
<?php Otopsi_Renderer::render_shortcode_list_header(); ?>
	</thead>

	<tbody>

<?php
if( ! empty( $scData ) ):
	foreach( $scData as $scId => $sc):
?>
						<tr>
							<td>
							 <strong><a href="<?php menu_page_url('otopsi_admin_menu'); ?>&edit=<?php echo $scId; ?>" title="<?php _e( 'Edit this shortcode', 'otopsi-domain' ); ?>"><?php echo $sc['scName']; ?></a></strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php menu_page_url('otopsi_admin_menu'); ?>&edit=<?php echo $scId; ?>" title="<?php _e( 'Edit this shortcode', 'otopsi-domain' ); ?>"><?php _e( 'Edit', 'otopsi-domain' ); ?></a> |
									</span>
									<span class="trash">
										<a onclick="return confirm( '<?php _e( 'Are you sure?', 'otopsi-domain' ); ?>' );" href="<?php menu_page_url('otopsi_admin_menu'); ?>&delete=<?php echo $scId; ?>" title="<?php _e( 'Delete this shortcode', 'otopsi-domain' ); ?>"><?php _e( 'Delete', 'otopsi-domain' ); ?></a>
									</span>
								</div>
							</td>
							<td>
							<input type="text" class="shortcode-in-list-table wp-ui-text-highlight code" value='[<?php echo OTOPSI_SC_NAME; ?> name="<?php echo $sc['scName'] ?>" id="<?php echo $scId; ?>"]' readonly="readonly" onfocus="this.select();" style="width:90%;"></td>
						</tr>
<?php
	endforeach;
endif;
?>

	</tbody>

	<tfoot>
<?php Otopsi_Renderer::render_shortcode_list_header(); ?>
	</tfoot>


</table>


