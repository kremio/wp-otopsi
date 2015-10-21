<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$scData = Otopsi_Shortcode::get_shortcodes_data();
$currentSc = array();

if( isset($_GET['edit']) ){
  $currentSc = $scData[ $_GET['edit'] ];
}else if( isset($_GET['delete']) ){
  unset( $scData[ $_GET['delete'] ] );
  Otopsi_Shortcode::save_shortcodes_data($scData);
}else{
  $mydata = Otopsi::parse_form_data_post();
  if( !empty($mydata) && $mydata != false ){ //save the shortcode
     Otopsi_Shortcode::save_shortcode( $scData, $mydata );
  }
}

?>

<script type="text/javascript">
  function otopsi_getBaseUrl(){
    return jQuery("#otopsi_shortcode_form").attr("action");
  }

  function otopsi_editShortCode(scId){
    window.location.href = otopsi_getBaseUrl()+"&edit="+scId;
  };

  function otopsi_deleteShortCode(scId){
    if( !confirm("Are you sure?") ){
      return;
    }

    window.location.href = otopsi_getBaseUrl()+"&delete="+scId;
  };

</script>

<table class="wp-list-table widefat fixed bookmarks">
    <thead>
        <tr>
            <th><strong><?php _e( 'Shortcodes', 'otopsi_textdomain' ); ?></strong></th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td>
			<table cellspacing="0" class="wp-list-table widefat fixed bookmarks">
                <thead>
                    <tr>
                        <th width="20"><strong>Sn</strong></th>
                        <th width="250"><strong><?php _e( 'Reference Name', 'otopsi_textdomain' ); ?></strong></th>
                        <th><strong><?php _e( 'Shortcode', 'otopsi_textdomain' ); ?></strong></th>
                        <th width="100"><strong><?php _e( 'Actions', 'otopsi_textdomain' ); ?></strong></th>
                    </tr>
                </thead>
                
                <tbody>
<?php
  if( !empty($scData) ):
    foreach($scData as $scId => $sc):
?>
                    <tr>
                          <td><?php echo $scId; ?></td>
                          <td><?php echo $sc['scName']; ?></td>
                          <td>
                            <input type="text" class="shortcode-in-list-table wp-ui-text-highlight code" value='[<?php echo OTOPSI_SC_NAME; ?> name="<?php echo $sc['scName'] ?>" id="<?php echo $scId; ?>"]' readonly="readonly" onfocus="this.select();" style="width:90%;"></td>
                          <td>
                            <button onclick="otopsi_editShortCode('<?php echo $scId; ?>')"><?php _e( 'Modify', 'otopsi_textdomain' ); ?></button> |
                            <button onclick="otopsi_deleteShortCode('<?php echo $scId; ?>')"><?php _e( 'Delete', 'otopsi_textdomain' ); ?></button></td>
                    </tr>
<?php
    endforeach;
  endif;
?>
                </tbody>
      </table>
      </td>
    </tr>
    </tbody>
</table>

<?php
$isNewForm = empty( $currentSc );

if( $isNewForm ){
  $currentSc = array();
}

$formTitle =  $isNewForm ?  __( 'Create a new shortcode', 'otopsi_textdomain' ) : __( 'Modify a shortcode', 'otopsi_textdomain' );
$buttonLabel = $isNewForm ? __( 'Create', 'otopsi_textdomain' ) : __( 'Update', 'otopsi_textdomain' );
$currentSc['enable'] = 1;
$currentSc['scName'] = $isNewForm ? 'NewOtopsiShortCode' : $currentSc['scName'] ;

?>

<table class="wp-list-table widefat fixed bookmarks">
    <thead>
        <tr>
        <th><strong><?php echo $formTitle; ?></strong></th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td>
      <form id="otopsi_shortcode_form" action="admin.php?page=otopsi_admin_menu" method="post">

      <table class="form-table">
        <tr valign="top">
          <th><label for="otopsi[scName]"><?php _e( 'Reference Name', 'otopsi_textdomain' ); ?></label></th>
          <td><input class="widefat" id="otopsi[scName]" name="otopsi[scName]" type="text" value="<?php echo $currentSc['scName']; ?>"></td>
        </tr>
      </table>
<?php
  Otopsi_Renderer::render_instance_edit_form( $currentSc );
  if( !$isNewForm ):
?>
        <input type="hidden" name="otopsi[sc_id]" value="<?php echo $currentSc['sc_id']; ?>"/>
<?php endif; ?>
        <button><?php echo $buttonLabel; ?></button>

      </form>
			  </td>
    </tr>
    </tbody>
</table>
