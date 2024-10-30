<?php
function clientexec_bridge_options() {
	global $clientexec_bridge_shortname,$cc_login_type,$current_user;
	$clientexec_bridge_shortname = "clientexec_bridge";

	$is='This section customizes the way '.CLIENTEXEC_BRIDGE.' interacts with Wordpress.';
	$clientexec_bridge_options[100] = array(  "name" => "Integration Settings",
            "type" => "heading",
			"desc" => $is);
	$clientexec_bridge_options[110] = array(	"name" => CLIENTEXEC_BRIDGE_PAGE." URL",
			"desc" => "The site URL of your ".CLIENTEXEC_BRIDGE_PAGE." installation. Make sure this is exactly the same as the settings field 'Company URL'.",
			"id" => $clientexec_bridge_shortname."_url",
			"type" => "text");
	$clientexec_bridge_options[210] = array(	"name" => "jQuery library",
			"desc" => "Select the jQuery library you want to load. If you have a theme using jQuery, you may be able to solve conflicts by choosing a different library or no library. Note that ".CLIENTEXEC_BRIDGE." uses the jQuery $ function, hence it needs to be defined if you manage the loading of jQuery in your Wordpress theme.",
			"id" => $clientexec_bridge_shortname."_jquery",
			"options" => array('wp' => 'Wordpress'),
			"default" => 'wp',
			"type" => "selectwithkey");
	$clientexec_bridge_options[220] = array(	"name" => "Custom styles",
			"desc" => 'Enter your custom CSS styles here',
			"id" => $clientexec_bridge_shortname."_css",
			"type" => "textarea");
	$clientexec_bridge_options[310] = array(	"name" => "Debug",
			"desc" => "If you have problems with the plugin, activate the debug mode to generate a debug log for our support team",
			"id" => $clientexec_bridge_shortname."_debug",
			"type" => "checkbox");
	
	if (!get_option('clientexec_bridge_sso_active')) {
		$clientexec_bridge_options[320] = array(	"name" => "Footer",
				"desc" => "Show your support by displaying the ".CLIENTEXEC_BRIDGE_COMPANY." footer on your site.",
				"id" => $clientexec_bridge_shortname."_footer",
				"std" => 'None',
				"type" => "select",
				"options" => array('Page','Site','None'));
	}
	
	if (get_option('clientexec_bridge_sso_active') && defined('CLIENTEXEC_BRIDGE_PRO')) {
		require(get_option('clientexec_bridge_sso_active').'/includes/controlpanel.inc.php');
	}
	
	ksort($clientexec_bridge_options);
	
	return $clientexec_bridge_options;
}

function clientexec_bridge_add_admin() {

	global $clientexec_bridge_shortname;

	$clientexec_bridge_options=clientexec_bridge_options();

	if (isset($_GET['page']) && ($_GET['page'] == "clientexec-bridge-cp")) {
		
		if ( isset($_REQUEST['action']) && 'install' == $_REQUEST['action'] ) {
			delete_option('clientexec_bridge_log');
			foreach ($clientexec_bridge_options as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}

			foreach ($clientexec_bridge_options as $value) {
				if( isset( $_REQUEST[ $value['id'] ] ) ) {
					update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
				} else { delete_option( $value['id'] );
				}
			}
			clientexec_bridge_install();
			if (function_exists('clientexec_bridge_sso_update')) clientexec_bridge_sso_update();
			header("Location: options-general.php?page=clientexec-bridge-cp&installed=true");
			die;
		}
	}

	add_options_page(CLIENTEXEC_BRIDGE, CLIENTEXEC_BRIDGE, 'administrator', 'clientexec-bridge-cp','clientexec_bridge_admin');
}

function clientexec_bridge_admin() {

	global $clientexec_bridge_shortname;

	$controlpanelOptions=clientexec_bridge_options();

	if ( isset($_REQUEST['installed']) ) echo '<div id="message" class="updated fade"><p><strong>'.CLIENTEXEC_BRIDGE.' installed.</strong></p></div>';
	if ( isset($_REQUEST['error']) ) echo '<div id="message" class="updated fade"><p>The following error occured: <strong>'.$_REQUEST['error'].'</strong></p></div>';
	
	?>
<div class="wrap">
<div id="cc-left" style="position:relative;float:left;width:80%">
<h2><b><?php echo CLIENTEXEC_BRIDGE; ?></b></h2>

	<?php
	$clientexec_bridge_version=get_option("clientexec_bridge_version");
	$submit='Update';
	?>
<form method="post">

<?php require(dirname(__FILE__).'/includes/cpedit.inc.php')?>

<p class="submit"><input name="install" type="submit" value="<?php echo $submit;?>" /> <input
	type="hidden" name="action" value="install"
/></p>
</form>
<hr />
<?php  
	if ($clientexec_bridge_version && get_option('clientexec_bridge_debug')) {
		echo '<h2 style="color: green;">Debug log</h2>';
		$r=get_option('clientexec_bridge_log');
		if ($r) {
			echo '<table style="font-size:smaller">';
			$v=$r;
			foreach ($v as $m) {
				echo '<tr>';
				echo '<td style="padding-right:10px">';
				echo date('H:i:s',$m[0]);
				echo '</td>';
				echo '<td style="padding-right:10px">';
				echo $m[1];
				echo '</td>';
				echo '<td>';
				echo $m[2];
				echo '</td>';
				echo '</tr>';
			}
		echo '</table><hr />';
		}
	}
?>

</div> <!-- end cc-left -->
<?php
	require(dirname(__FILE__).'/support-us.inc.php');
	zing_support_us('clientexec-bridge','clientexec-bridge','clientexec-bridge-cp',CC_CLIENTEXEC_BRIDGE_VERSION);
}
add_action('admin_menu', 'clientexec_bridge_add_admin'); ?>