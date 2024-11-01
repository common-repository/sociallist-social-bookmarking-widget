<?php
/*
Plugin Name: Sociallist
Plugin URI: http://sociallist.org/plugin-wp.php
Description: Automatically add links on your posts to popular <a href="http://sociallist.org/submit.php">social bookmarking service</a>. Go to Options -> Sociallist for setup.
Version: 1.5.1
Author: Codemaster
Author URI: http://ktulhu.net/
*/
$sociallist_version = '1.5.1'; // url-safe version string
$sociallist_date = '2008-05-08'; // date this version was released, beats a version #

/*
Copyright 2008 Codemaster (codemaster@ktulhu.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

load_plugin_textdomain('sociallist', 'wp-content/plugins/sociallist-social-bookmarking-widget/i18n');

$sociallist_files = Array(
	'description_selection.js',
	'sociallist-admin.css',
	'sociallist.css',
	'sociallist.php',
	'sociallist.css',
	'sociallist-admin.css',
);

function sociallist_html () {
	global $sociallist_version, $sociallist_alllangset;

	$html = "";

	// Load the post's data
	$blogname = urlencode(get_bloginfo('wpurl'));
	global $wp_query; 
	$post = $wp_query->post;
	$permalink = get_permalink($post->ID);
	if (is_callable("get_the_tags", false)) {
		$posttags = get_the_tags ($post->ID);
		if ($posttags) {
			$a = array ();
			foreach($posttags as $tag) {
				$a []= $tag->name;
			}
			$thisTags = join (",", $a);
		} else {
			$thisTags = "";
		}
	} else {
		$thisTags = "";
	}
	$title = $post->post_title;
	$rss = urlencode(get_bloginfo('ref_url'));

	$html .= "\n<div class=\"sociallist\">\n<span class=\"sociallist_tagline\">\n";
	$html .= get_option("sociallist_tagline");
	
	$thisurl = $permalink;
	$thistitle = $title;
	
	$lang = get_option("sociallist_lang");
	$button_img = trim (get_option("sociallist_button_img"));
	$button_dx = get_option("sociallist_button_dx");
	$button_dy = get_option("sociallist_button_dy");
	$cols = get_option("sociallist_cols");
	$rows = get_option("sociallist_rows");
	
	$uid = substr (md5 (mt_rand (1,1000000000)), 0, 8);
	
	if (strlen ($button_img) > 0) {
		$buttonImgSrc = $button_img;
	} else {
		$buttonImgSrc = 'http://sociallist.org/buttons/' . $lang . $button_dx . 'x' . $button_dy . '.gif';
	}
	
	$thisurl = addslashes ($thisurl);
	$thistitle = addslashes ($thistitle);
	$thisTags = addslashes ($thisTags);

	$s = '<!-- SocialList.org BEGIN -->
<script type="text/javascript">
sociallist_'.$uid.'_url = \''.$thisurl.'\';
sociallist_'.$uid.'_title = \''.$thistitle.'\';
sociallist_'.$uid.'_text = \'\';
sociallist_'.$uid.'_tags = \''.$thisTags.'\';
</script><script type="text/javascript" src="http://sociallist.org/widget.js?type=1'.
'&amp;cols='.$cols.
'&amp;rows='.$rows.
'&amp;button_img='.$button_img.
'&amp;button_dx='.$button_dx.
'&amp;button_dy='.$button_dy.
'&amp;lang='.$lang.
'&amp;uid='.$uid.
'"></script>
<noscript>
<a href="http://sociallist.org/submit.php?type=1'.
'&amp;lang=' . $lang .
'&amp;url=' . urlencode ($thisurl) . 
'&amp;title=' . urlencode ($thistitle) . 
'&amp;tag=' . urlencode ($thisTags) . 
'" target="_blank" title="Bookmark this Website"><img src="'.$buttonImgSrc.'" border="0" width="' . $button_dx . '" height="' . $button_dy .
'" alt="Bookmark" /></a>
</noscript>
<!-- SocialList.org END -->';

	$html .= $s;
	$html .= "</span></div>\n";

	return $html;
}

// Hook the_content to output html if we should display on any page
$sociallist_contitionals = get_option('sociallist_conditionals');
if (is_array($sociallist_contitionals) and in_array(true, $sociallist_contitionals)) {
	add_filter('the_content', 'sociallist_display_hook');
	add_filter('the_excerpt', 'sociallist_display_hook');
	
	function sociallist_display_hook($content='') {
		$conditionals = get_option('sociallist_conditionals');
		if ((is_home()     and $conditionals['is_home']) or
		    (is_single()   and $conditionals['is_single']) or
		    (is_page()     and $conditionals['is_page']) or
		    (is_category() and $conditionals['is_category']) or
		    (is_date()     and $conditionals['is_date']) or
		    (is_search()   and $conditionals['is_search']) or
		     0)
			$content .= sociallist_html();
	
		return $content;
	}
}

// Hook wp_head to add css
add_action('wp_head', 'sociallist_wp_head');
function sociallist_wp_head() {
	echo '<script language="JavaScript" type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/sociallist-social-bookmarking-widget/description_selection.js"></script>';
	echo '<link rel="stylesheet" type="text/css" media="screen" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/sociallist-social-bookmarking-widget/sociallist.css" />';
}

// load wp rss functions for update checking.
if (!function_exists('parse_w3cdtf')) {
	require_once(ABSPATH . WPINC . '/rss-functions.php');
}

// Plugin config/data setup
if (function_exists('register_activation_hook')) {
	// for WP 2
	register_activation_hook(__FILE__, 'sociallist_activation_hook');
}
function sociallist_activation_hook() {
	return sociallist_restore_config(False);
}

// restore built-in defaults, optionally overwriting existing values
function sociallist_restore_config($force=False) {
	global $sociallist_alllangset;

	// tagline defaults to a Hitchiker's Guide to the Galaxy reference
	if ($force or !is_string(get_option('sociallist_tagline')))
		update_option('sociallist_tagline', "<strong>" . __("Bookmark and Share:", 'sociallist') . "</strong><br/>");
		
	// only display english button by default
	update_option('sociallist_lang', 'en');
	
	update_option('sociallist_button_img', '');
	update_option('sociallist_button_dx', 160);
	update_option('sociallist_button_dy', 24);
	update_option('sociallist_cols', 3);
	update_option('sociallist_rows', 5);

	// only display on single posts and pages by default
	if ($force or !is_array(get_option('sociallist_conditionals')))
		update_option('sociallist_conditionals', array(
			'is_home' => False,
			'is_single' => True,
			'is_page' => True,
			'is_category' => False,
			'is_date' => False,
			'is_search' => False,
		));

	// last-updated date defaults to 0000-00-00
	// this is to trigger the update check on first run
	if ($force or !get_option('sociallist_updated'))
		update_option('sociallist_updated', '0000-00-00');
}

// Hook the admin_menu display to add admin page
add_action('admin_menu', 'sociallist_admin_menu');
function sociallist_admin_menu() {
	add_submenu_page('options-general.php', 'Sociallist', 'Sociallist', 8, 'Sociallist', 'sociallist_submenu');
}

// Admin page header
add_action('admin_head', 'sociallist_admin_head');
function sociallist_admin_head() {
?>

<script language="JavaScript" type="text/javascript"><!--

/* make checkbox action prettier */
function toggle_checkbox(id) {
	var checkbox = document.getElementById(id);

	checkbox.checked = !checkbox.checked;
	if (checkbox.checked)
		checkbox.parentNode.className = 'active';
	else
		checkbox.parentNode.className = 'inactive';
}
--></script>

<link rel="stylesheet" type="text/css" media="screen" href="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sociallist-social-bookmarking-widget/sociallist-admin.css" />
<?php
}

function sociallist_message($message) {
	echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
}

// Sanity check the upload worked
function sociallist_upload_errors() {
	global $sociallist_files;

	$cwd = getcwd(); // store current dir for restoration
	if (!@chdir('../wp-content/plugins'))
		return __("Couldn't find wp-content/plugins folder. Please make sure WordPress is installed correctly.", 'sociallist');
	if (!is_dir('sociallist-social-bookmarking-widget'))
		return __("Can't find sociallist-social-bookmarking-widget folder.", 'sociallist');
	chdir('sociallist-social-bookmarking-widget');

	foreach($sociallist_files as $file) {
		if (substr($file, -1) == '/') {
			if (!is_dir(substr($file, 0, strlen($file) - 1)))
				return __("Can't find folder:", 'sociallist') . " <kbd>$file</kbd>";
		} else if (!is_file($file))
		return __("Can't find file:", 'sociallist') . " <kbd>$file</kbd>";
	}

	$header_filename = '../../themes/' . get_option('template') . '/header.php';
	if (!file_exists($header_filename) or strpos(@file_get_contents($header_filename), 'wp_head()') === false)
		return __("Your theme isn't set up for Sociallist to load its style. Please edit <kbd>header.php</kbd> and add a line reading <kbd>&lt?php wp_head(); ?&gt;</kbd> before <kbd>&lt;/head&gt;</kbd> to fix this.", 'sociallist');

	chdir($cwd); // restore cwd

	return false;
}

// The admin page
function sociallist_submenu() {
	global $sociallist_known_sites, $sociallist_date, $sociallist_files;

	// update options in db if requested
	if ($_REQUEST['restore']) {
		sociallist_restore_config(True);
		sociallist_message(__("Restored all settings to defaults.", 'sociallist'));
		
	} else if ($_REQUEST['save']) {
		// update langset displays
		$langset = Array();
		if (!$_REQUEST['lang']) $_REQUEST['lang'] = 'en';
		update_option('sociallist_lang', $_REQUEST['lang']);
		
		update_option('sociallist_button_img', $_REQUEST['button_img']);
		update_option('sociallist_button_dx', max(5, min (1024, $_REQUEST['button_dx'])));
		update_option('sociallist_button_dy', max(5, min (1024, $_REQUEST['button_dy'])));
		update_option('sociallist_cols', max(1, min (20, $_REQUEST['cols'])));
		update_option('sociallist_rows', max(1, min (20, $_REQUEST['rows'])));
	
		// update conditional displays
		$conditionals = Array();
		if (!$_REQUEST['conditionals'])
			$_REQUEST['conditionals'] = Array();
		foreach(get_option('sociallist_conditionals') as $condition=>$toggled)
			$conditionals[$condition] = array_key_exists($condition, $_REQUEST['conditionals']);
		update_option('sociallist_conditionals', $conditionals);

		// update tagline
		if (!$_REQUEST['tagline'])
			$_REQUEST['tagline'] = "";
		update_option('sociallist_tagline', $_REQUEST['tagline']);
		
		sociallist_message(__("Saved changes.", 'sociallist'));
	}

	if ($str = sociallist_upload_errors())
		sociallist_message("$str</p><p>" . __("In your plugins/sociallist folder, you must have these files:", 'sociallist') . ' <pre>' . implode("\n", $sociallist_files) ); 

	// load options from db to display
	$lang = get_option('sociallist_lang');
	$tagline = get_option('sociallist_tagline');
	$button_img = get_option('sociallist_button_img');
	$button_dx = get_option('sociallist_button_dx');
	$button_dy = get_option('sociallist_button_dy');
	$cols = get_option('sociallist_cols');
	$rows = get_option('sociallist_rows');
	$conditionals = get_option('sociallist_conditionals');
	$updated = get_option('sociallist_updated');

	// display options
?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

<div class="wrap" id="sociallist_options">

<h3><?php _e("Sociallist Options", 'sociallist'); ?></h3>

<div style="clear: left; display: none;"><br/></div>

<fieldset id="sociallist_langset">
<p><?php _e("Select the displaying bookmarking services language.", 'sociallist'); ?></p>
<select name="lang">
<?php
	$config ["sociallist_languages"] = array (
		array ("code" => "en", "title" => "en", "desc" => "English"),
		array ("code" => "fr", "title" => "fr", "desc" => "French"),
		array ("code" => "es", "title" => "es", "desc" => "Spanish"),
		array ("code" => "ru", "title" => "ru", "desc" => "Russian"),
		array ("code" => "de", "title" => "de", "desc" => "German"),
		array ("code" => "it", "title" => "it", "desc" => "Italian"),
		array ("code" => "pt", "title" => "pt", "desc" => "Portuguese"),
		array ("code" => "jp", "title" => "jp", "desc" => "Japanese"),
		array ("code" => "cn", "title" => "cn", "desc" => "Chinese"),
		array ("code" => "nl", "title" => "nl", "desc" => "Dutch"),
	);
	foreach ($config ["sociallist_languages"] as $l) { ?>
	<option value="<?php echo $l["code"]?>" <?php if ($l["code"] == $lang) echo "selected";?>><?php echo $l["desc"]?></option>
<?php } ?>
</select>
</fieldset>

<fieldset id="sociallist_tagline">
<p>
<?php _e("Change the text displayed in front of the button below. For complete customization, edit <kbd>sociallist.css</kbd> in the sociallist plugin directory.", 'sociallist'); ?>
</p>
<input type="text" name="tagline" value="<?php echo htmlspecialchars($tagline); ?>" />
</fieldset>

<fieldset id="sociallist_button">
<p>
<?php _e("Button Size (width * height)", 'sociallist'); ?>
</p>
<input type="text" name="button_dx" value="<?php echo htmlspecialchars($button_dx); ?>" size="5" />
*
<input type="text" name="button_dy" value="<?php echo htmlspecialchars($button_dy); ?>" size="5" />
<p>
<?php _e("Button Image Url (leave blank for default)", 'sociallist'); ?>
</p>
<input type="text" name="button_img" value="<?php echo htmlspecialchars($button_img); ?>" />
</fieldset>

<fieldset id="sociallist_hint">
<p>
<?php _e("Hint (cols * rows)", 'sociallist'); ?>
</p>
<input type="text" name="cols" value="<?php echo htmlspecialchars($cols); ?>" size="5" />
*
<input type="text" name="rows" value="<?php echo htmlspecialchars($rows); ?>" size="5" />
</fieldset>


<fieldset id="sociallist_conditionals">
<p><?php _e("The button appear at the end of each blog post, and posts may show on many different types of pages. Depending on your theme and audience, it may be tacky to display button on all types of pages.", 'sociallist'); ?></p>

<ul style="list-style-type: none">
	<li><input type="checkbox" name="conditionals[is_home]"<?php echo ($conditionals['is_home']) ? ' checked="checked"' : ''; ?> /> <?php _e("Front page of the blog", 'sociallist'); ?></li>
	<li><input type="checkbox" name="conditionals[is_single]"<?php echo ($conditionals['is_single']) ? ' checked="checked"' : ''; ?> /> <?php _e("Individual blog posts", 'sociallist'); ?></li>
	<li><input type="checkbox" name="conditionals[is_page]"<?php echo ($conditionals['is_page']) ? ' checked="checked"' : ''; ?> /> <?php _e('Individual WordPress "Pages"', 'sociallist'); ?></li>
	<li><input type="checkbox" name="conditionals[is_category]"<?php echo ($conditionals['is_category']) ? ' checked="checked"' : ''; ?> /> <?php _e("Category archives", 'sociallist'); ?></li>
	<li><input type="checkbox" name="conditionals[is_date]"<?php echo ($conditionals['is_date']) ? ' checked="checked"' : ''; ?> /> <?php _e("Date-based archives", 'sociallist'); ?></li>
	<li><input type="checkbox" name="conditionals[is_search]"<?php echo ($conditionals['is_search']) ? ' checked="checked"' : ''; ?> /> <?php _e("Search results", 'sociallist'); ?></li>
</ul>
</fieldset>

<p class="submit"><input name="save" id="save" tabindex="3" value="<?php _e("Save Changes", 'sociallist'); ?>" type="submit" /></p>
<p class="submit"><input name="restore" id="restore" tabindex="3" value="<?php _e("Restore Built-in Defaults", 'sociallist'); ?>" type="submit" style="border: 2px solid #e00;" /></p>

</div>

<div class="wrap">
<p>
<?php _e('<a href="http://sociallist.org/plugin-wp.php">Sociallist</a> is copyright 2008 by <a href="http://ktulhu.net/">Codemaster</a>, released under the GNU GPL version 2 or later. If you like Sociallist, please send a link my way so other folks can find out about it.', 'sociallist'); ?>
</p>
</div>

</form>

<?php
}

?>