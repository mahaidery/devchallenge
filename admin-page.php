<?php

if(get_query_var('pie_frm_refresh_data') && wp_verify_nonce( $_REQUEST['_wpnonce'], 'pie_refresh_data_frm' )){
	delete_transient( 'pFrmLTR' );
}

?>

<div class="frm_wrap">
	<div id="frm_top_bar">
		<div id="frm-publishing">
			<div class="action-wrap">
				<a class="button button-secondary frm-button-secondary" href="<?php echo add_query_arg( array('_wpnonce' => wp_create_nonce( 'pie_refresh_data_frm' ),'pie_frm_refresh_data' => 'yes'), menu_page_url('pie_formidable_dev_challenge',false));?>">Refresh Data</a>
			</div>
		</div>
		<span class="frm-header-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 599.68 601.37" width="35" height="35">
				<path fill="#f05a24" d="M289.6 384h140v76h-140z"></path>
				<path fill="#4d4d4d" d="M400.2 147h-200c-17 0-30.6 12.2-30.6 29.3V218h260v-71zM397.9 264H169.6v196h75V340H398a32.2 32.2 0 0 0 30.1-21.4 24.3 24.3 0 0 0 1.7-8.7V264zM299.8 601.4A300.3 300.3 0 0 1 0 300.7a299.8 299.8 0 1 1 511.9 212.6 297.4 297.4 0 0 1-212 88zm0-563A262 262 0 0 0 38.3 300.7a261.6 261.6 0 1 0 446.5-185.5 259.5 259.5 0 0 0-185-76.8z"></path>
			</svg></span>
		<div class="frm_top_left frm_top_wide">
			<h1><?php _e('Formidable Dev Challenge','formidc')?></h1>
		</div>
		<div style="clear:right;"></div>
	</div>
	<div class="wrap">
		
		
		<div class="data-wrap">
			<form method="post">
		<?php
			
			$pieFormidableWPList->prepare_items();
			$pieFormidableWPList->display();
		?>
			</form>
		</div>
	</div>
</div>
<?php
//echo '<pre>';
//$decoded_array = json_decode(get_transient( 'pFrmLTR' ));
////$this->pieFormbidableLoadTable_rows = json_decode(wp_json_encode($decoded_array->data->rows),true);
//print_r(array_slice($this->pieFormbidableLoadTable_rows, 0, 3));