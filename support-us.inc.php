<?php 
//v2.02.06
if (!function_exists('zing_support_us')) {
	function zing_support_us($shareName,$wpPluginName,$adminLink,$version,$donations=true,$pluginUrl=false) {
		if (!$pluginUrl) $pluginUrl=plugins_url().'/'.$wpPluginName.'/';
		if (get_option('clientexec_bridge_sso_license_key')) $donations=false;
?>
		<div style="width:20%;float:right;position:relative">
				<div style="margin:5px 15px;">
					<script type="text/javascript" src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
					<div style="float:left;">
						<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.zingiri.com" data-text="Zingiri">Tweet</a>
						<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>				
					</div>
					<div style="float:left;">
						<fb:share-button href="http://www.zingiri.com/bookings/<?php echo $shareName;?>/" type="button" >
					</div>
				</div>
				<div style="clear:both"></div>
			<div class="cc-support-us">
			<h3>The bridge page</h3>
			<p>A CLIENTEXEC front end page has been created on your Wordpress site. This page is the main interaction page between Wordpress and CLIENTEXEC.</p>
		<p>The full url is:<a href="<?php echo clientexec_bridge_home($home,$pid);?>"><code><?php echo clientexec_bridge_home($home,$pid);?></code></a>. You can edit the page link by editing the page and changing the permalink.</p>
		<p style="color:red">Do not delete this page!</p>
			
			</div>
			<div class="cc-support-us">
			<h3>Not sure where to start?</h3>
			<!-- 
			<p>Download our <a href="http://go.zingiri.com/downloads.php?action=displaycat&catid=6">documentation</a></p><br />
			<p>Check out our <a href="http://forums.zingiri.com/forumdisplay.php?fid=74">forums</a></p><br />
			<p>Pro users can open a <a href="https://go.zingiri.com/submitticket.php">support ticket</a></p>
			 -->
			</div>
			<div class="cc-support-us">
				<h3>Support us by rating our plugin on Wordpress</h3>
				<a href="http://wordpress.org/extend/plugins/<?php echo $wpPluginName;?>" alt="Rate our plugin">
				<img src="<?php echo $pluginUrl?>images/5-stars-125pxw.png" />
				</a>
				<?php 
				$option=$wpPluginName.'-support-us';
				if (get_option($option) == '') {
					update_option($option,time());
				} elseif (isset($_REQUEST['support-us']) && ($_REQUEST['support-us'] == 'hide')) {
					update_option($option,time()+7776000);
				} else {
					if ((time() - get_option($option)) > 1209600) { //14 days 
						if ($donations) echo "<div id='zing-warning' style='background-color:red;color:white;font-size:large;margin:20px;padding:10px;'>Looks like you've been using this plugin for quite a while now. Have you thought about showing your appreciation through a small donation?<br /><br /><a href='http://www.zingiri.com/donations'><img src='https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif' /></a><br /><br />If you already made a donation, you can <a href='?page=".$adminLink."&support-us=hide'>hide</a> this message.</div>";
					}
				}
				?>
			</div>
			<div style="text-align:center;margin-top:15px">
				<a href="http://www.zingiri.com" target="_blank"><img width="150px" src="<?php echo $pluginUrl?>images/logo.png" /></a>
			</div>
		</div>
<?php 
	}
}
?>