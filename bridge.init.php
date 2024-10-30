<?php
if (!defined('CLIENTEXEC_BRIDGE')) define('CLIENTEXEC_BRIDGE','ClientExec Bridge');
if (!defined('CLIENTEXEC_BRIDGE_COMPANY')) define('CLIENTEXEC_BRIDGE_COMPANY','Zingiri');
if (!defined('CLIENTEXEC_BRIDGE_PAGE')) define('CLIENTEXEC_BRIDGE_PAGE','ClientExec');

define("CC_CLIENTEXEC_BRIDGE_VERSION","1.0.0");

$compatibleCLIENTEXECBridgeProVersions=array('2.0.1'); //kept for compatibility with older Pro versions, not used since version 2.0.0

// Pre-2.6 compatibility for wp-content folder location
if (!defined("WP_CONTENT_URL")) {
	define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
}
if (!defined("WP_CONTENT_DIR")) {
	define("WP_CONTENT_DIR", ABSPATH . "wp-content");
}

if (!defined("CC_CLIENTEXEC_BRIDGE_PLUGIN")) {
	$clientexec_bridge_plugin=str_replace(realpath(dirname(__FILE__).'/..'),"",dirname(__FILE__));
	$clientexec_bridge_plugin=substr($clientexec_bridge_plugin,1);
	define("CC_CLIENTEXEC_BRIDGE_PLUGIN", $clientexec_bridge_plugin);
}

if (!defined("BLOGUPLOADDIR")) {
	$upload=wp_upload_dir();
	define("BLOGUPLOADDIR",$upload['path']);
}

define("CC_CLIENTEXEC_BRIDGE_URL", WP_CONTENT_URL . "/plugins/".CC_CLIENTEXEC_BRIDGE_PLUGIN."/");

$clientexec_bridge_version=get_option("clientexec_bridge_version");
if ($clientexec_bridge_version) {
	add_action("init","clientexec_bridge_init");
	if (get_option('clientexec_bridge_footer')=='Site') add_filter('wp_footer','clientexec_bridge_footer');
	add_filter('the_content', 'clientexec_bridge_content', 10, 3);
	add_filter('the_title', 'clientexec_bridge_title');
	add_action('wp_head','clientexec_bridge_header',10);
	add_action('admin_head','clientexec_bridge_admin_header');
}
add_action('admin_head','clientexec_bridge_admin_header');
add_action('admin_notices','clientexec_admin_notices');

require_once(dirname(__FILE__) . '/includes/shared.inc.php');
require_once(dirname(__FILE__) . '/includes/http.class.php');
require_once(dirname(__FILE__) . '/includes/footer.inc.php');
require_once(dirname(__FILE__) . '/includes/integrator.inc.php');
require_once(dirname(__FILE__) . '/bridge_cp.php');
if (!class_exists('simple_html_dom_node')) require_once(dirname(__FILE__) . '/includes/simple_html_dom.php');
require(dirname(__FILE__).'/includes/parser.inc.php');

function clientexec_admin_notices() {
	global $wpdb;
	$errors=array();
	$warnings=array();
	$notices=array();
	$files=array();
	$dirs=array();

	$clientexec_bridge_version=get_option("clientexec_bridge_version");
	if ($clientexec_bridge_version && $clientexec_bridge_version != CC_CLIENTEXEC_BRIDGE_VERSION) $warnings[]='You downloaded version '.CC_CLIENTEXEC_BRIDGE_VERSION.' and need to update your settings (currently at version '.$clientexec_bridge_version.') from the <a href="options-general.php?page=clientexec-bridge-cp">control panel</a>.';
	$upload=wp_upload_dir();

	if (clientexec_bridge_mainpage()) {
		if (session_save_path() && !is_writable(session_save_path())) $warnings[]='It looks like PHP sessions are not properly configured on your server, the sessions save path <'.session_save_path().'> is not writable. This may be a false warning, contact us if in doubt.';
		if ($upload['error']) $errors[]=$upload['error'];
		if (!get_option('clientexec_bridge_url')) $warnings[]="Please update your CLIENTEXEC connection settings on the plugin control panel";
		if (get_option('clientexec_bridge_debug')) $warnings[]="Debug is active, once you finished debugging, it's recommended to turn this off";
		if (phpversion() < '5') $warnings[]="You are running PHP version ".phpversion().". We recommend you upgrade to PHP 5.3 or higher.";
		if (ini_get("zend.ze1_compatibility_mode")) $warnings[]="You are running PHP in PHP 4 compatibility mode. We recommend you turn this option off.";
		if (!function_exists('curl_init')) $errors[]="You need to have cURL installed. Contact your hosting provider to do so.";
	}

	if (get_option("clientexec_bridge_url") && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', get_option("clientexec_bridge_url"))) $errors[]='Your CLIENTEXEC URL '.get_option("clientexec_bridge_url").' seems to be incorrect, please verify it and make sure it starts with http or https.';

	if (count($errors) > 0) {
		foreach ($errors as $message)  {
			echo "<div id='zing-warning' style='background-color:pink' class='updated fade'><p><strong>";
			echo CLIENTEXEC_BRIDGE.':'.$message.'<br />';
			echo "</strong> "."</p></div>";
		}
	}
	if (count($warnings) > 0) {
		foreach ($warnings as $message) {
			echo "<div id='zing-warning' style='background-color:greenyellow' class='updated fade'><p><strong>";
			echo CLIENTEXEC_BRIDGE.': '.$message.'<br />';
			echo "</strong> "."</p></div>";
		}
	}
	if (isset($_REQUEST['page']) && ($_REQUEST['page']=='clientexec-bridge-cp') && count($notices) > 0) {
		foreach ($notices as $message) {
			echo "<div id='zing-warning' style='background-color:lightyellow' class='updated fade'><p><strong>";
			echo $message.'<br />';
			echo "</strong> "."</p></div>";
		}
	}

	return array('errors'=> $errors, 'warnings' => $warnings);
}


function clientexec_bridge_install() {
	global $wpdb,$current_user,$wp_rewrite;

	ob_start();
	clientexec_log();
	set_error_handler('clientexec_log');
	error_reporting(E_ALL & ~E_NOTICE);

	$clientexec_bridge_version=get_option("clientexec_bridge_version");
	if (!$clientexec_bridge_version) add_option("clientexec_bridge_version",CC_CLIENTEXEC_BRIDGE_VERSION);
	else update_option("clientexec_bridge_version",CC_CLIENTEXEC_BRIDGE_VERSION);

	//create pages
	if (!$clientexec_bridge_version) {
		clientexec_log(0,'Creating pages');
		$pages=array();
		$pages[]=array(CLIENTEXEC_BRIDGE_PAGE.'-bridge',CLIENTEXEC_BRIDGE_PAGE,"*",0);

		$ids="";
		foreach ($pages as $i =>$p)
		{
			$my_post = array();
			$my_post['post_title'] = $p['0'];
			$my_post['post_content'] = '';
			$my_post['post_status'] = 'publish';
			$my_post['post_author'] = 1;
			$my_post['post_type'] = 'page';
			$my_post['menu_order'] = 100+$i;
			$my_post['comment_status'] = 'closed';
			$id=wp_insert_post( $my_post );
			if (empty($ids)) { $ids.=$id; } else { $ids.=",".$id; }
			if (!empty($p[1])) add_post_meta($id,'clientexec_bridge_page',$p[1]);
		}
		update_option("clientexec_bridge_pages",$ids);
	}

	restore_error_handler();

	$wp_rewrite->flush_rules();

	return true;
}

/**
 * Deactivation: nothing to do
 * @return void
 */
function clientexec_bridge_deactivate() {
	$ids=get_option("clientexec_bridge_pages");
	$ida=explode(",",$ids);
	foreach ($ida as $id) {
		wp_delete_post($id);
	}
	$clientexec_bridge_options=clientexec_bridge_options();

	delete_option('clientexec_bridge_log');
	foreach ($clientexec_bridge_options as $value) {
		delete_option( $value['id'] );
	}

	delete_option("clientexec_bridge_log");
	delete_option("clientexec_bridge_version");
	delete_option("clientexec_bridge_pages");
	delete_option('clientexec_bridge_support-us');
}

function clientexec_bridge_output($page=null) {
	global $post;
	global $wpdb;
	global $wordpressPageName;
	global $clientexec_bridge_loaded,$clientexec_bridge_to_include;

	$ajax=false;

	$cf=get_post_custom($post->ID);
	if ($page) {
		$clientexec_bridge_to_include=$page;
	} elseif (isset($_REQUEST['ccce']) && (isset($_REQUEST['ajax']) && $_REQUEST['ajax'])) {
		$clientexec_bridge_to_include=$_REQUEST['ccce'];
		$ajax=intval($_REQUEST['ajax']);
	} elseif (isset($_REQUEST['ccce'])) {
		$clientexec_bridge_to_include=$_REQUEST['ccce'];
	} elseif (isset($cf['clientexec_bridge_page']) && $cf['clientexec_bridge_page'][0]==CLIENTEXEC_BRIDGE_PAGE) {
		$clientexec_bridge_to_include="index";
	} else {
		$clientexec_bridge_to_include="index";
	}

	$http=clientexec_bridge_http($clientexec_bridge_to_include);

	$news = new bridgeHttpRequest($http,'clientexec-bridge');
	$news->debugFunction='clientexec_log';
	if (function_exists('clientexec_bridge_sso_httpHeaders')) $news->httpHeaders=clientexec_bridge_sso_httpHeaders($news->httpHeaders);

	if (isset($news->post['clientexecname'])) {
		$news->post['name']=$news->post['clientexecname'];
		unset($news->post['clientexecname']);
	}

	$news=apply_filters('bridge_http',$news);

	$news->forceWithRedirect['systpl']=get_option('clientexec_bridge_template') ? get_option('clientexec_bridge_template') : 'portal';

	if ($clientexec_bridge_to_include=='dologin') {
		$news->post['rememberme']='on';
	}

	if (!$news->curlInstalled()) {
		clientexec_log('Error','CURL not installed');
		return "cURL not installed";
	} elseif (!$news->live()) {
		clientexec_log('Error','A HTTP Error occured');
		return "A HTTP Error occured";
	} else {
		if ($clientexec_bridge_to_include=='verifyimage') {
			$output=$news->DownloadToString();
			while (count(ob_get_status(true)) > 0) ob_end_clean();
			header("Content-Type: image");
			echo $news->body;
			die();
		} elseif ($clientexec_bridge_to_include=='dl') {
			while (count(ob_get_status(true)) > 0) ob_end_clean();
			$output=$news->DownloadToString();
			header("Content-Disposition: ".$news->headers['content-disposition']);
			header("Content-Type: ".$news->headers['content-type']);
			echo $news->body;
			die();
		} elseif ($ajax==1) {
			$output=$news->DownloadToString();

			if (!$news->redirect) {
				while (count(ob_get_status(true)) > 0) ob_end_clean();
				$body=$news->body;
				$body=clientexec_bridge_parser_ajax1($body);
				echo $body;
				die();
			} else {
				header('Location:'.$output);
				die();
			}
		} elseif ($ajax==2) {
			while (count(ob_get_status(true)) > 0) ob_end_clean();
			$output=$news->DownloadToString();
			$body=$news->body;
			$body=clientexec_bridge_parser_ajax2($body);
			header('HTTP/1.1 200 OK');
			echo $body;
			die();
		} elseif ($news->redirect) {
			$output=$news->DownloadToString();
			if ($wordpressPageName) $p=$wordpressPageName;
			else $p='/';
			$f[]='/.*\/([a-zA-Z\_]*?).php.(.*?)/';
			$r[]=get_option('home').$p.'?ccce=$1&$2';
			$f[]='/([a-zA-Z\_]*?).php.(.*?)/';
			$r[]=get_option('home').$p.'?ccce=$1&$2';
			$output=preg_replace($f,$r,$news->location,-1,$count);
			clientexec_log('Notification','Redirect to: '.$output);
			header('Location:'.$output);
			die();
		} else {
			if (isset($_REQUEST['aff'])) $news->follow=false;
			$output=$news->DownloadToString();
			if ($news->redirect) {
				header('Location:'.$output);
				die();
			}
			if (isset($_REQUEST['aff']) && isset($news->headers['location'])) {
				if (strstr($news->headers['location'],get_option('home'))) header('Location:'.$news->headers['location']);
				else header('Location:'.get_option('home'));
				die();
			}
			return $output;
		}
	}
}

/**
 * Page content filter
 * @param $content
 * @return unknown_type
 */
function clientexec_bridge_content($content) {
	global $clientexec_bridge_content,$post;

	if (!is_page()) return $content;


	$cf=get_post_custom($post->ID);
	if (isset($_REQUEST['ccce']) || (isset($cf['clientexec_bridge_page']) && $cf['clientexec_bridge_page'][0]==CLIENTEXEC_BRIDGE_PAGE)) {
		if (!$clientexec_bridge_content) { //support Gantry framework
			$clientexec_bridge_content=clientexec_bridge_parser();
		}
		if ($clientexec_bridge_content) {
			$content='';
			ob_start();
			$content.=ob_get_clean();
			$content.='<div id="bridge">';
			$content.=$clientexec_bridge_content['main'];
			$content.='</div><!--end bridge-->';
			ob_start();
			$content.=ob_get_clean();
			if (get_option('clientexec_bridge_footer')=='Page') $content.=clientexec_bridge_footer(true);
		}
	}

	return $content;
}

function clientexec_bridge_header() {
	global $clientexec_bridge_content,$post;

	if (!(isset($post->ID))) return;
	$cf=get_post_custom($post->ID);
	if (isset($_REQUEST['ccce']) || (isset($cf['clientexec_bridge_page']) && $cf['clientexec_bridge_page'][0]==CLIENTEXEC_BRIDGE_PAGE)) {
		//$p='clientexec_bridge_parser_'.get_option('clientexec_bridge_template');
		if (!$clientexec_bridge_content) {
			$clientexec_bridge_content=clientexec_bridge_parser();
		}

		if (isset($clientexec_bridge_content['head'])) echo $clientexec_bridge_content['head'];

		echo '<link rel="stylesheet" type="text/css" href="' . CC_CLIENTEXEC_BRIDGE_URL . 'cc.css" media="screen" />';
		echo '<script type="text/javascript" src="'. CC_CLIENTEXEC_BRIDGE_URL . 'cc.js"></script>';
		if (get_option('clientexec_bridge_css')) {
			echo '<style type="text/css">'.get_option('clientexec_bridge_css').'</style>';
		}
	}
	if(get_option('clientexec_bridge_jquery')=='wp') echo '<script type="text/javascript">$=jQuery;</script>';
}

function clientexec_bridge_admin_header() {
	echo '<link rel="stylesheet" type="text/css" href="' . CC_CLIENTEXEC_BRIDGE_URL . 'cc.css" media="screen" />';
}

function clientexec_bridge_http($page="index") {
	global $wpdb;

	$clientexec=clientexec_bridge_url();
	if (substr($clientexec,-1)!='/') $clientexec.='/';
	if ((strpos($clientexec,'https://')!==0) && isset($_REQUEST['sec']) && ($_REQUEST['sec']=='1')) $clientexec=str_replace('http://','https://',$clientexec);
	$vars="";
	if ($page=='verifyimage') $http=$clientexec.'includes/'.$page.'.php';
	elseif (isset($_REQUEST['ccce']) && ($_REQUEST['ccce']=='js')) {
		$http=$clientexec.$_REQUEST['js'];
		return $http;
	} elseif (substr($page,-1)=='/') $http=$clientexec.substr($page,0,-1);
	else $http=$clientexec.$page.'.php';
	$and="";
	if (count($_GET) > 0) {
		foreach ($_GET as $n => $v) {
			if ($n!="page_id" && $n!="ccce" && $n!='clientexecpage')
			{
				if (is_array($v)) {
					foreach ($v as $n2 => $v2) {
						$vars.= $and.$n.'['.$n2.']'.'='.urlencode($v2);
					}
				}
				else $vars.= $and.$n.'='.urlencode($v);
				$and="&";
			}
		}
	}

	if (isset($_GET['clientexecpage'])) {
		$vars.=$and.'page='.$_GET['clientexecpage'];
		$and='&';
	}
	$vars.=$and.'systpl=portal';
	$and="&";

	if (function_exists('clientexec_bridge_sso_http')) clientexec_bridge_sso_http($vars,$and);

	if ($vars) $http.='?'.$vars;

	return $http;
}

function clientexec_bridge_title($title,$id=0) {
	global $clientexec_bridge_content;
	if (!in_the_loop()) return $title;
	if ($id==0) return $title;

	if (isset($clientexec_bridge_content['title'])) return $clientexec_bridge_content['title'];
	else return $title;
}

function clientexec_bridge_default_page($pid) {
	$isPage=false;
	$ids=get_option("clientexec_bridge_pages");
	$ida=explode(",",$ids);
	foreach ($ida as $id) {
		if (!empty($id) && $pid==$id) $isPage=true;
	}
	return $isPage;
}

function clientexec_bridge_mainpage() {
	$ids=get_option("clientexec_bridge_pages");
	$ida=explode(",",$ids);
	return $ida[0];
}

/**
 * Initialization of page, action & page_id arrays
 * @return unknown_type
 */
function clientexec_bridge_init()
{
	ob_start();
	if (function_exists('cc_clientexecbridge_sso_session')) cc_clientexecbridge_sso_session();
	if (!session_id()) @session_start();
	if(get_option('clientexec_bridge_jquery')=='wp'){
		wp_enqueue_script(array('jquery','jquery-ui','jquery-ui-slider','jquery-ui-button'));
	}
}

function clientexec_log($type=0,$msg='',$filename="",$linenum=0) {
	if ($type==0) $type='Debug';
	if (get_option('clientexec_bridge_debug')) {
		if (is_array($msg)) $msg=print_r($msg,true);
		$v=get_option('clientexec_bridge_log');
		if (!is_array($v)) $v=array();
		array_unshift($v,array(time(),$type,$msg));
		if (count($v) > 100) array_pop($v);
		update_option('clientexec_bridge_log',$v);
	}
}

function clientexec_bridge_url() {
	$url=get_option('clientexec_bridge_url');
	if (substr($url,-1)=='/') $url=substr($url,0,-1);
	return $url;
}

//Kept for compatibility reasons
if (class_exists('bridgeHttpRequest')) {
	class HTTPRequestCLIENTEXEC extends bridgeHttpRequest {}
}
