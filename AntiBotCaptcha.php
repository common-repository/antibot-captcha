<?php
/*
Plugin Name: AntiBot Captcha
Plugin URI: http://isaev.asia/en/my-wordpress-plugins/antibot-captchca/
Description: AntiBot Captcha - simple good-looking, but well-protected plugin against spam robots for your blog comments
Author: Alexey Isaev
Version: 2.0
Author URI: http://isaev.asia/

Based on:
Plugin URI (original): http://www.infor96.com/~nio/archives/369
Author URI (original): http://www.infor96.com/~nio/
w3captcha - script for generation CAPTCHA
w3captcha developer: http://w3box.ru

*/
add_filter("plugin_action_links", "ant_links", 10, 2 );

	function ant_links($links, $file){ 
	
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(dirname(__FILE__).'/AntiBotCaptcha.php');
	
	if ($file == $this_plugin){
		$settings_link = '<a href="http://isaev.asia/my-wordpress-plugins/donate/">Donate!</a>';
		array_unshift( $links, $settings_link); 
		
	}
	return $links;
}
if (!class_exists('AntiBotCaptcha')) {

@session_start();

class AntiBotCaptcha
{
	var $form_div;
	
	function AntiBotCaptcha()
	{
		$this->form_div = strtolower(substr(md5( get_bloginfo("siteurl")  ), 3, 12));
		
		add_action("comment_form", array(&$this, "ant_comment_form"));	
		add_action("comment_post", array(&$this, "ant_comment_post"));	
		
		
	}
	


	function ant_comment_form()
	{
		global $wpdb, $user_ID, $_SERVER;

		if (isset($user_ID) and $user_ID>0 ) {
		?>
		        <div align="center" style="margin:5px 0 5px 0;background-color:#f4f4f4; border:dashed 1px #646464; color: #646464; padding: 3px;">
Registered user do not use CAPTCHA.</div>
		<?php
			return $post_ID;
		}
		?>
		
<div class="secimgdiv" id="<?php echo $this->form_div ?>div">
<p><input type="text" class="textfield"  name="<?php echo $this->form_div ?>" id="<?php echo $this->form_div ?>" size="6"  tabindex="4" />
<label for="<?php echo $this->form_div ?>"><img src="<?php echo $_SERVER['PHP_SELF']; ?>?image=<?php echo time(); ?>"  alt="Input this protected code" align="absbottom" /><small>Please input symbols from picture</small></label>
</p>
</div>

<script language="JavaScript" type="text/javascript">
var urlinput = document.getElementById("url"); <?php // url ?>
var submitp = urlinput.parentNode;
var substitution2 = document.getElementById("<?php echo $this->form_div ?>div");
submitp.appendChild(substitution2, urlinput);
</script>

<?php
		return $post_ID;
	}

	function ant_comment_post($post_ID)
	{
		global $wpdb, $user_ID, $_POST, $_SESSION, $comment_type;

		$securitycode = $_POST[$this->form_div];
		// If the user is not logged in check the security code
		if ( !$user_ID && !$comment_type ) {
			if ( '' == $securitycode ) {
				$this->ant_comment_rollback($post_ID); 
				wp_die( 'ERROR: Input code from image' );
			}
			if ( $_SESSION['IMAGE_CODE'] != $securitycode ) {
				$this->ant_comment_rollback($post_ID); 
				wp_die( 'Invalid code. Return back and try input code again.' );
			} else {
				unset($_SESSION['IMAGE_CODE']);
			}
		}
		return $post_ID;
	}

	function ant_comment_rollback($post_ID)
	{
		global $wpdb, $wp_version;

		$pid = $wpdb->get_var("SELECT comment_post_ID FROM {$wpdb->comments} WHERE comment_ID = {$post_ID}");
		$wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_ID = {$post_ID}"); 
		if ($wp_version >= '2.0') {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID = {$pid} AND comment_approved = '1'");
			$wpdb->query( "UPDATE {$wpdb->posts} SET comment_count = {$count} WHERE ID = {$pid}" );
		}
	}

}	///:~
}	//end if


$secimg = new AntiBotCaptcha();
if (isset($_GET['image']) && preg_match('/^[0-9]+$/', $_GET['image'])) {  
session_start();



$count=4;	/* symbol count */
$width=100; /* picture width */
$height=48; /* picture height */
$font_size_min=20; /* minimum symobl height */
$font_size_max=32; /* maximum symobl height */
$font_file=(dirname(__FILE__))."/gothic.otf"; /* font name, otf or ttfs */
$char_angle_min=-10; /* maximum skew of the symbol to the left*/
$char_angle_max=10;	/*  maximum skew of the symbol to the right */
$char_angle_shadow=5;	/*shadow size */
$char_align=40;	/* align symbol verticaly */
$start=5;	/* first symbol position */
$interval=16;	/* interval between the start position of characters */
$chars="2345689"; /* charset */
$noise=0; /* noise level */

$image=imagecreatetruecolor($width, $height);

$background_color=imagecolorallocate($image, 255, 255, 255); /* rbg background color*/
$font_color=imagecolorallocate($image, 32, 64, 96); /* rbg shadow color*/

imagefill($image, 0, 0, $background_color);

$str="";

$num_chars=strlen($chars);
for ($i=0; $i<$count; $i++)
{
	$char=$chars[rand(0, $num_chars-1)];
	$font_size=rand($font_size_min, $font_size_max);
	$char_angle=rand($char_angle_min, $char_angle_max);
	imagettftext($image, $font_size, $char_angle, $start, $char_align, $font_color, $font_file, $char);
	imagettftext($image, $font_size, $char_angle+$char_angle_shadow*(rand(0, 1)*2-1), $start, $char_align, $background_color, $font_file, $char);
	$start+=$interval;
	$str.=$char;
}

if ($noise)
{
	for ($i=0; $i<$width; $i++)
	{
		for ($j=0; $j<$height; $j++)
		{
			$rgb=imagecolorat($image, $i, $j);
			$r=($rgb>>16) & 0xFF;
			$g=($rgb>>8) & 0xFF;
			$b=$rgb & 0xFF;
			$k=rand(-$noise, $noise);
			$rn=$r+255*$k/100;
			$gn=$g+255*$k/100;		
			$bn=$b+255*$k/100;
			if ($rn<0) $rn=0;
			if ($gn<0) $gn=0;
			if ($bn<0) $bn=0;
			if ($rn>255) $rn=255;
			if ($gn>255) $gn=255;
			if ($bn>255) $bn=255;
			$color=imagecolorallocate($image, $rn, $gn, $bn);
			imagesetpixel($image, $i, $j , $color);					
		}
	}
}

$_SESSION["IMAGE_CODE"]=$str;

if (function_exists("imagepng"))
{
	header("Content-type: image/png");
	imagepng($image);
}
elseif (function_exists("imagegif"))
{
	header("Content-type: image/gif");
	imagegif($image);
}
elseif (function_exists("imagejpeg"))
{
	header("Content-type: image/jpeg");
	imagejpeg($image);
}

imagedestroy($image);
exit;
}


?>