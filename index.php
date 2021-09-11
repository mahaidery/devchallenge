<?php
/*
Plugin Name: Formidable Dev Challenge
Description: This is a nice Developer Challenge plugin
Version: 1.1
Plugin URI: https://devchal.dev.new.wf/
Author URI: https://pie-solutions.com/
Author: Johnibom
Text Domain: formidc
*/

if(!defined('ABSPATH')){
	die('You are not allowed to call this page directly.');
}
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class pieFormidableWPList extends WP_List_Table {
	var $pieFormbidableLoadTable_results;
	var $pieFormbidableLoadTable_columns;
	var $pieFormbidableLoadTable_rows;
	function __construct(){
		parent::__construct( array(
				'singular'	=> 'Amazing User',
				'plural'	=> 'Amazing Users',
				'ajax'		=> false
			)
		);

		if ( false === ( $pieFormbidableLoadTable_results = get_transient( 'pFrmLTR' ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			$pieFormbidableLoadTable_results = formiDableDevChallengePie::pieFormidableFetchRecords();
			set_transient( 'pFrmLTR', $pieFormbidableLoadTable_results, HOUR_IN_SECONDS );
		}

		$decoded_array = json_decode(get_transient( 'pFrmLTR' ));
		//print_r($decoded_array);
		$this->pieFormbidableLoadTable_columns = $decoded_array->data->headers;
		$this->pieFormbidableLoadTable_rows = json_decode(wp_json_encode($decoded_array->data->rows),true);
	}

	function get_bulk_actions() {
		$actions = array(
			'delete'    => 'Delete'
		);
		return $actions;
	}

	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="ids[]" value="%s" />', $item['id']
		);    
	}

	function column_default($item,$column){
		switch($column){
			case 'date':
				return date_i18n( get_option( 'date_format' ), $item[$column] );
			default:
				return $item[$column];
		}

	}

	function column_fname($item) {
	  $actions = array(
				'delete'    => sprintf('<a href="?page=pie_formidable_dev_challenge&orderby=%s&order=%s&action=%s&id=%d">Delete</a>',$_REQUEST['orderby'],$_REQUEST['order'],'delete',$item['id']),
			);

	  return sprintf('%1$s %2$s', $item['fname'], $this->row_actions($actions) );
	}
	function get_columns(){
		$keys = array_keys($this->pieFormbidableLoadTable_rows[array_keys($this->pieFormbidableLoadTable_rows)[0]]);
		$cols = array_combine($keys,$this->pieFormbidableLoadTable_columns);
		$cols = array_reverse($cols, true);
		$cols['cb']	= '<input type="checkbox" />';

		return array_reverse($cols, true);
	}

	function prepare_items(){


		//$sortable = $this->get_sortable_columns();
		//$this->_column_headers = array($this->get_columns(), array(), $sortable);
		$this->_column_headers = $this->get_column_info();
		if(is_array($this->pieFormbidableLoadTable_rows)){
			$rows = [];
			foreach($this->pieFormbidableLoadTable_rows as $k =>$row){
				if(isset($_REQUEST['action']) && 'delete' == sanitize_text_field(sprintf('%s',$_REQUEST['action']))){
					if(isset($_GET['id']) && $row['id'] == sanitize_text_field(sprintf('%d',$_GET['id']))){
						continue;
					}
					if(isset($_REQUEST['ids']) && is_array($_REQUEST['ids'])){
						//echo 'ids array';die();
						if(in_array($row['id'], map_deep( $_REQUEST['ids'], 'sanitize_text_field' ))){
							//map_deep( $_REQUEST['ids'], 'sanitize_text_field' )
							continue;
						}
					}
				}
				$id		= sanitize_text_field(sprintf('%d',$row['id']));
				$fname	= sanitize_text_field(sprintf('%s',$row['fname']));
				$lname	= sanitize_text_field(sprintf('%s',$row['lname']));
				$email	= sanitize_text_field(sprintf('%s',$row['email']));
				$date	= sanitize_text_field(sprintf('%d',$row['date']));
				$rows[] = array('id' => $id, 'fname' => $fname, 'lname' => $lname, 'email' => $email, 'date' => $date );
			}
			$this->pieFormbidableLoadTable_rows = $rows;
		}
		usort( $this->pieFormbidableLoadTable_rows, array( &$this, 'uSortReOrder' ) );

		$per_page = $this->get_items_per_page('pie_items_per_page', 3);
		$current_page = $this->get_pagenum();
		$total_items = count($this->pieFormbidableLoadTable_rows);
		$found_data = array_slice($this->pieFormbidableLoadTable_rows,(($current_page-1)*$per_page),$per_page);

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
		$this->items = $found_data;


	}

	function get_sortable_columns(){
		return array(
			'id'	=> array('id', false),
			'fname'	=> array('fname', false),
			'lname'	=> array('lname', false),
			'email'	=> array('email', false),
			'date'	=> array('date', false),
		);
	}
	function uSortReOrder( $a, $b ) {
	  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'fname';
	  $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
	  $result = strcmp( $a[$orderby], $b[$orderby] );
	  return ( $order === 'asc' ) ? $result : -$result;
	}
	
	function deleteAnItem(){
		
	}
}
class formiDableDevChallengePie {
	//frontend class to trigger ajax request on frontend
	private $frontendClass = 'pieFormbidable';
	
	
	function __construct(){
		
		//formiDableDevChallengePie
		add_shortcode( 'formidablePieDev', array( $this, 'displayFrontendShortcode' ) );
		//enqueue script
		add_action( 'wp_enqueue_scripts', array( $this, 'pie_enqueue_script') );
		//action //load from ajax
		add_action( 'wp_ajax_pieFormbidableLoadTable', array( $this, 'pieFormbidableLoadTable' ) );
		add_action( 'wp_ajax_nopriv_pieFormbidableLoadTable', array( $this, 'pieFormbidableLoadTable' ) );
		//admin menu
		add_action( 'admin_menu',  array( $this, 'addPanel' ) );
		
		add_filter( 'query_vars', array( $this, 'pie_query_vars' ) );
		add_filter('set-screen-option', array($this, 'pieFrmAdminSetScreenOptions'), 11, 3);
	}
	static function pieFormidableFetchRecords(){
		$apiURl = 'http://api.strategy11.com/wp-json/challenge/v1/1';
		$args = array(
			'headers' => array(
			'Content-Type' => 'application/json'
			)
		);
		$response = wp_remote_get($apiURl,$args);
		$body = wp_remote_retrieve_body( $response );
		$sanitized_body = map_deep( $body, 'sanitize_text_field' );
		return $sanitized_body;
	}
	function addPanel(){
		$pieFrmMenu = add_menu_page(
			__( 'Formidable Dev Challenge', 'formidc' ),
			'Formidable Dev',
			'manage_options',
			'pie_formidable_dev_challenge',
			array($this,'backendPage'),
			'',
			6
		);
		
		add_action( 'admin_print_styles-' . $pieFrmMenu, array($this, 'adminCustomCSS') );
		add_action( 'load-'.$pieFrmMenu, array($this, 'pieSetAdminScreenOptions') );
	}
	
	function addBodyClass($classes){
		$classes.= ' frm-white-body';
		return $classes;
	}
	
	function adminCustomCSS(){
		if(!wp_style_is( 'formidable-admin', 'registered')){
			wp_register_style( 'formidable-admin', plugin_dir_url( __FILE__ ) . 'css/frm_admin.css', array(), $version );
		}
		wp_enqueue_style( 'formidable-admin' );
		
		add_filter('admin_body_class', array($this, 'addBodyClass'));
	}
	
	function pieSetAdminScreenOptions(){
		global $pieFormidableWPList;
		
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Items', 'formidc' ),
				'default' => 3,
				'option'  => 'pie_items_per_page',
			)
		);
		$pieFormidableWPList = new pieFormidableWPList();
	}
	
	function backendPage(){
		//
		
		global $pieFormidableWPList;
		require_once(dirname( __FILE__ ).'/admin-page.php');
	}
	
	function displayFrontendShortcode( $atts ){
		return $this->frontEndShortcodeWrap($a);
	}
	
	
	function pie_enqueue_script(){
		global $post;
		$shortcode_found = false;
		if ( has_shortcode($post->post_content, 'formidablePieDev') ) {
			$shortcode_found = true;
		}
		if ( $shortcode_found ) {
			if ( ! wp_script_is( 'jquery', 'done' )){
				wp_enqueue_script( 'jquery' );
			}
			wp_add_inline_script( 'jquery', 'jQuery(document).ready(function($){
				var pieFormCodeToBeTrigger = true;
				if(pieFormCodeToBeTrigger && $("div.pieFormbidable").length > 0){
					pieFormCodeToBeTrigger = false;
					$.ajax({
						url : "'.admin_url('admin-ajax.php?action=pieFormbidableLoadTable').'"

					}).done(function ( json_response ) {
						let populate_data;
						let json_response_data = json_response;
						if(typeof json_response_data.data === "object" && json_response_data.data !== null){
							let title = document.createElement("h4");
							title.textContent = json_response_data.title;
							let table = document.createElement("table");
							let tableHead = table.createTHead();
							let tableHeadRow = tableHead.insertRow(-1);
							for( let i = 0; i < json_response_data.data.headers.length; i++ ) {
								let headcell = tableHeadRow.insertCell();
								headcell.appendChild(document.createTextNode(json_response_data.data.headers[i]));
							}

							let tbody = document.createElement("tbody");
							let i =0;
							for(var key in json_response_data.data.rows) {
								//console.log(json_response_data.data.rows[key]);

								let this_row = tbody.insertRow(i);
								Object.values(json_response_data.data.rows[key]).forEach(function(j) {
									let cell = this_row.insertCell();
									cell.appendChild(document.createTextNode(j));
								});
							}

							table.appendChild(tbody);
							$("div.pieFormbidable").append(title);
							$("div.pieFormbidable").append(table);
						}else{
							$("div.pieFormbidable").append("Could not read data from server");
						}
					}).fail(function ( err ) {
						$("div.pieFormbidable").append("Could not read data from server");
					});
				}
			});' );
		}
		
	}
	
	function pieFormbidableLoadTable() {
		// Get any existing copy of our transient data
		if ( false === ( $pieFormbidableLoadTable_results = $this->pieGetTransient( 'pFrmLTR' ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			$pieFormbidableLoadTable_results = formiDableDevChallengePie::pieFormidableFetchRecords();
			$this->pieSetTransient( 'pFrmLTR', $pieFormbidableLoadTable_results, HOUR_IN_SECONDS );
		}
		//$pieFormbidableLoadTable_results = formiDableDevChallengePie::pieFormidableFetchRecords();
		//$output = json_encode( get_post( $the_post_id ) );
		header('Content-Type: application/json');
		//echo 'do I call';
		echo $pieFormbidableLoadTable_results;
		wp_die();
	}
	
	function pieGetTransient($name){
		return get_transient( $name );
	}
	
	function pieSetTransient($name,$data,$time){
		return set_transient( $name, $data, $time );
	}
	
	function frontEndShortcodeWrap( $atts ){
		return '<div class="'.$this->frontendClass.'"></div>';
	}
	function pie_query_vars( $qvars ) {
		$qvars[] = 'pie_frm_refresh_data';
		return $qvars;
	}
	
	function pieFrmAdminSetScreenOptions($status, $option, $value){
		if ($option === 'pie_items_per_page') {
			if ($value < 0) {
				$value = 0;
			} elseif ($value > 100) {
				$value = 100;
			}
		}
		return $value;
	}
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class pieFormidableCLI extends WP_CLI_Command {
		
		function delete($args, $assoc_args){
			$dry_run = $assoc_args['dry-run'];
			if(!$dry_run){
				delete_transient( 'pFrmLTR' );
			}
			echo 'Cache Cleared';
		}
	}
}
$formiDableDevChallengePie = new formiDableDevChallengePie();