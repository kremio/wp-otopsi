<!--
<?php
$scData = OtopsiAdmin::getShortCodesData();
$currentSc = array();

if( isset($_GET['edit']) ){
  $currentSc = $scData[ $_GET['edit'] ];
  print_r( $currentSc );
}else if( isset($_GET['delete']) ){
  unset( $scData[ $_GET['delete'] ] );
  OtopsiAdmin::saveShortCodesData($scData);
}else{
  $mydata = Otopsi::parseFormDataPost();
  if( !empty($mydata) && $mydata != false ){ //save the shortcode
    print_r( $scData );
     OtopsiAdmin::saveShortCode( $scData, $mydata );
  }
}

?>
-->
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
            <th><strong>Shortcodes</strong></th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td>
			<table cellspacing="0" class="wp-list-table widefat fixed bookmarks">
                <thead>
                    <tr>
                        <th width="20"><strong>Sn</strong></th>
                        <th width="250"><strong>Reference Name</strong></th>
                        <th><strong>Shortcode</strong></th>
                        <th width="100"><strong>Actions</strong></th>
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
                          <td><button onclick="otopsi_editShortCode('<?php echo $scId; ?>')">Edit</button> | <button onclick="otopsi_deleteShortCode('<?php echo $scId; ?>')">Delete</button></td>
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

<table class="wp-list-table widefat fixed bookmarks">
    <thead>
        <tr>
        <th><strong><?php echo empty( $currentSc ) ? "Create":"Edit"; ?> a shortcode</strong></th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td>
      <form id="otopsi_shortcode_form" action="admin.php?page=otopsi_admin_menu" method="post">

<?php
if( empty( $currentSc ) ):
  $currentSc = array('enable'=>1);
?>
        <p>
      <label for="otopsi[scName]">Reference Name</label> 
      <input class="widefat" id="otopsi[scName]" name="otopsi[scName]" type="text" value="NewOtopsiShortCode">
      </p>
<?php
  Otopsi::renderInstanceEditForm( $currentSc );
?>
        <button>Create</button>
<?php
else:
?>
          <p>
      <label for="otopsi[scName]">Reference Name</label> 
      <input class="widefat" id="otopsi[scName]" name="otopsi[scName]" type="text" value="<?php echo $currentSc['scName']; ?>">
      </p>
<?php
 $currentSc['enable'] = 1;
  Otopsi::renderInstanceEditForm( $currentSc );
?>

        <input type="hidden" name="otopsi[sc_id]" value="<?php echo $currentSc['sc_id']; ?>"/>
        <button>Update</button>
<?php
  endif;
?>
      </form>
			  </td>
    </tr>
    </tbody>
</table>



