<?php

/*
Plugin Name: Widget Changer
Plugin URI: http://maketecheasier.com/
Description: This plugin allows you to set a different widget for different pages 
Version: 1.2.5
Author: Damien Oh
Author URI: http://maketecheasier.com/about

Copyright 2007  Damien Oh  (email : damien@maketecheasier.com)

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define(WIDGETC_DB_VER, "1.2");

$WC_config = array ('allow_user_role' => "8",
'appear_on' => "post",
'show_cat_option' => "0",
'table_name'=>"widgetC",
'db_ver' => "1.0",
'wp_version' => "2.5");


add_option('widget_changer', $WC_config, 'Settings for WP Widget Changer');
$WidgetC = get_option('widget_changer');

if(isset($_POST['check_widget_submit'])){
	$WidgetC['allow_user_role'] = $_POST['wc_user_level'];
	$WidgetC['appear_on'] = $_POST['appear_on'];
	if($WidgetC['appear_on']=="category" && $WidgetC['show_cat_option']=="1")
	{
		$cat_widget = array();
		$i=0;
		while(isset($_POST['cat'.$i]))
		{	
			$cat_id = $_POST['cat'.$i];
			$cat_title = $_POST['cat_title_'.$cat_id];
			$cat_content = $_POST['cat_content_'.$cat_id];
			if($cat_title=="" && $cat_content=="")
			{	$cat_title="%blank%";
				$cat_content="%blank%";
				//$i++;
				//continue;
			}
			$cat_widget[$i] = $cat_id."+++ ".$cat_title."+++ ".$cat_content;
			$i++;
		}
		insert_cat_widget($cat_widget);
	}	
	if($WidgetC['appear_on']=="category")
		$WidgetC['show_cat_option'] = "1";
	else
		$WidgetC['show_cat_option'] = "0";
	update_option('widget_changer', $WidgetC);
}

register_activation_hook(__FILE__,'widget_db_install');
add_action('plugins_loaded', 'widget_changer_init');
add_action('admin_menu', 'WC_add_option_page');
add_filter('the_content', 'wc_set_ID');
if($WidgetC['appear_on']=="post")
{
add_action('edit_post', 'add_widget_content');
add_action('publish_post', 'add_widget_content');
add_action('save_post', 'add_widget_content');
add_action('edit_page_form', 'add_widget_content');
add_action('dbx_post_advanced', 'widget_input_form');

$WidgetC = get_option('widget_changer');
if($WidgetC['wp_version']>=2.5)
	add_action('edit_page_form', 'widget_input_form');
else
	add_action('dbx_page_advanced', 'widget_input_form');
}

function widget_db_install () 
{	global $wpdb,$wp_version;
   	$WidgetC = get_option('widget_changer');	
   
  	$table_name_old = $wpdb->prefix . "widgetC";
  	$table_name = $wpdb->prefix . "widget_changer";
	$WidgetC['table_name'] = $table_name;
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {    
		$sql = "CREATE TABLE " . $table_name . " (
	  		id mediumint(9) NOT NULL AUTO_INCREMENT,
			Post_ID mediumint(9) NOT NULL,
	  		Post_Type TEXT NOT NULL,
	  		Widget_Title TEXT NOT NULL,
	  		Widget_Content LONGTEXT NOT NULL,
			UNIQUE KEY id (id)
		);";

      	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      	dbDelta($sql);
   	}
	if($wpdb->get_var("show tables like '$table_name_old'") == $table_name_old)
	{
		$old_database = $wpdb->get_results("SELECT * FROM $table_name_old");
		foreach($old_database as $old_db)
		{	$postid = $old_db->Post_ID;
			$type = $old_db->Post_Type;
			if($type=="") $type="post";
			$widget_title = $old_db->Widget_Title;
			$widget_content = $old_db->Widget_Content;
			$wpdb->query("INSERT INTO $table_name (Post_ID, Post_Type, Widget_Title, Widget_Content) VALUES ('$postid', '$type','$widget_title', '$widget_content')");
		}
		$wpdb->query("DROP TABLE $table_name_old");
	}
/*	else{   
		//if(($WidgetC_db_version=get_option('WidgetC_db_version'))!="")
		$installed_ver = $WidgetC['db_ver'];	
		if( WIDGETC_DB_VER != $installed_ver) {
			$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				Post_ID mediumint(9) NOT NULL,
				Post_Type TEXT NOT NULL,
				Widget_Title TEXT NOT NULL,
			  	Widget_Content LONGTEXT NOT NULL,
				UNIQUE KEY id (id)
			);";
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
      		dbDelta($sql);
	  		$wpdb->query("UPDATE $table_name SET Post_Type='post' WHERE Post_Type=''");
	  		//$WidgetC['db_ver'] = WIDGETC_DB_VER;
      		
  		}
 	}*/
	$WidgetC['db_ver'] = WIDGETC_DB_VER;
	$WidgetC['wp_version'] = $wp_version;
	update_option('widget_changer',$WidgetC);
}

function WC_add_option_page ()
{
	add_options_page('Widget Changer', 'Widget Changer', 8, basename(__FILE__), 'WC_option_page');
}

function WC_option_page ()
{	global $wpdb;
	$WidgetC = get_option("widget_changer");
	$table_name = $WidgetC['table_name'];
?>	
	<div class="wrap">
	<h2>Widget Changer Options</h2>
		<p><a href="http://maketecheasier.com/wordpress-plugins/wordpress-widget-changer">How to use it?</a></p>
		<p><a href="http://maketecheasier.com/wordpress-plugins/wordpress-widget-changer#comment">Comments and suggestions</a></p>
		<p>&nbsp;</p>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=widget_changer.php">
	<input type="hidden" name="check_widget_submit" value="1" />
	<table class="form-table"><tr><td>
	Allow users with <select name="wc_user_level">
							<option value="8" <?php if($WidgetC['allow_user_role'] == 8) echo 'selected="selected"' ?>>Administrator</option>
							<option value="5" <?php if($WidgetC['allow_user_role'] == 5) echo 'selected="selected"' ?>>Editor</option>
							<option value="2" <?php if($WidgetC['allow_user_role'] == 2) echo 'selected="selected"' ?>>Author</option>
							<option value="1" <?php if($WidgetC['allow_user_role'] == 1) echo 'selected="selected"' ?>>Contributor</option>
							</select> privilege to add/edit widget 
	</td></tr>
	<tr><td>
	<h3>Select where your widget will appear</h3>
	<input type="radio" name="appear_on" value="post" <?php if($WidgetC['appear_on']=="post") echo 'checked="checked"'; ?>/> specific posts/pages. This will allow you to set which widget to appear on each post/page.<br/>
	   <input type="radio" name="appear_on" value="category" <?php if($WidgetC['appear_on']=="category") echo 'checked="checked"'; ?> /> specific category. This will allow you to set which widget to appear on each category.
	   <p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p></td></tr>
	  <?php if($WidgetC['appear_on']=="category" && $WidgetC['show_cat_option']=="1") { ?>
	 <tr><td>
	  <h3> Manage Categories Widget</h3>
	   <table>
	   <?php 
	  	 	$categories = get_categories('orderby=ID&hide_empty=0'); 
		 	for($i=0;$i<count($categories);$i++){
				$cat = $categories[$i];
				$cat_name = $cat->cat_name;
				$cat_id = $cat->cat_ID;
				$widget = $wpdb->get_row("SELECT * FROM $table_name WHERE Post_ID='$cat_id' AND Post_Type='category'");
				if(is_object($widget))
				{	$widget_title = $widget->Widget_Title;
					$widget_content = $widget->Widget_Content;
				}
				else
				{	$widget_title="";
					$widget_content="";
				} 
		 		?><tr><td valign="top"><a onmouseover="this.style.cursor='pointer'" onclick="document.getElementById('div_<?php echo $cat_id;?>').style.display='block'"><?php echo $cat_name;?></a><input type="hidden" name="cat<?php echo $i;?>" value="<?php echo $cat_id;?>" /></td><td valign="top" align="right"><div id="div_<?php echo $cat_id;?>" style="display:none"><p>Widget Title:&nbsp;&nbsp;&nbsp;<input name="cat_title_<?php echo $cat_id; ?>" value="<?php echo $widget_title;?>" size="50"/></p>
				<p>Widget Content: <textarea name="cat_content_<?php echo $cat_id;?>" cols="48" rows="8"><?php echo $widget_content;?></textarea></p>
				<p align="right"><a onmouseover="this.style.cursor='pointer'" onclick="document.getElementById('div_<?php echo $cat_id;?>').style.display='none'">Close</a></p></div></td></tr>
		
	<?php	}?>
	   </table>
	   <p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p>
	   </td></tr>
	   <?php } ?>
	   
	</table>						
	
	</form>
	<p>&nbsp;</p>
	If you like this plugin, you can show your support by giving donation to aid in the development cost.

<form action="https://www.paypal.com/cgi-bin/webscr" method="post"> <input name="cmd" value="_s-xclick" type="hidden" /> <input src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" border="0" type="image" /><img src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" height="1" width="1" />
<input name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" type="hidden" /></form>
	</div>
<?php 
}
function wc_set_ID($content='') {
	global $wc_ID,$post;
	
	$wc_ID = $post->ID;
	return $content;
}

function widget_input_form()
{	global $post,$wpdb,$userdata;

	$WidgetC = get_option("widget_changer");
    $table_name = $WidgetC['table_name'];
	
	get_currentuserinfo();
	$wc_user_level = $userdata->user_level;
	
	if($wc_user_level >= $WidgetC['allow_user_role']){	
	$type=$WidgetC['appear_on'];
	if($type=="post")
	{
		$post_id = $post;
		if (is_object($post_id)) {
		   	$post_id = $post_id->ID;
		}
	}
	else
	{	$post_id = $_GET['cat_ID']; 
	}
	$widget = $wpdb->get_row("SELECT * FROM $table_name WHERE Post_ID='$post_id' AND Post_Type='$type'");
	if(is_object($widget))
	{	$widget_title = $widget->Widget_Title;
		$widget_content = $widget->Widget_Content;
	}
	else
	{	$widget_title="";
		$widget_content="";
	} 
	?>	
			<SCRIPT LANGUAGE="JavaScript">
		<!-- Begin
		function countChars(field,cntfield) {
		cntfield.value = field.value.length;
		}
		//  End -->
		</script>
		<?php if($WidgetC['wp_version']>=2.5) { ?>
		<div id="postwidgetc" class="postbox closed">
		<h3><?php _e('Widget Changer') ?></h3>
		<div class="inside">
		<div id="postwidgetc">
		<?php } else { ?>
	<div class="dbx-b-ox-wrapper">
		<fieldset id="widgetchangerdiv" class="dbx-box">
			<div class="dbx-h-andle-wrapper">
		<h3 class="dbx-handle"><a style="color:white" target="__blank" href="http://maketecheasier.com/wordpress-plugins/wordpress-widget-changer">Widget Changer</a></h3>
			</div>
			<div class="dbx-c-ontent-wrapper">
		<div class="dbx-content">
	<?php } ?>
	<input value="is_add_widget" type="hidden" name="is_add_widget" />
		<table>
		<tr>
		<td valign="top" align="right" width="30%"><strong>Widget Title</strong></td>
		<td><input type="text" value="<?php echo $widget_title; ?>" name="widget_title" size="60" /></td>
		</tr>
		</tr>
		<tr>
		<td valign="top" align="right"><strong>Widget Code</strong></td>
		<td><textarea id="add_widget" name="add_widget" rows="10" cols="58"><?php echo stripslashes($widget_content); ?></textarea></td>
		</tr>
		</table>
		<br/>
		<?php if($WidgetC['wp_version']>=2.5) { ?>
				</div></div></div>
		<?php } else { ?>			
				</div>
			</div>
		</fieldset>
	</div><br/>
		<?php
		}
		}
}

function add_widget_content($ID)
{	global $wpdb;
	$WidgetC = get_option('widget_changer');
	$table_name = $WidgetC['table_name'];
	
	if(isset($_POST['is_add_widget']))
	{	$widget_title = $_POST['widget_title'];
		$widget_content = $_POST['add_widget'];
		$type = "post";
		$result = $wpdb->get_row("SELECT * FROM $table_name WHERE Post_ID='$ID' AND Post_Type='$type'",ARRAY_N);
		if(count($result)==0)
			$result = $wpdb->query("INSERT INTO $table_name (Post_ID, Post_Type, Widget_Title, Widget_Content) VALUES ('$ID', '$type','$widget_title', '$widget_content')");
		else if(count($result) >0)
			$result = $wpdb->query("UPDATE $table_name SET Widget_Title='$widget_title', Widget_Content = '$widget_content' WHERE Post_ID = '$ID' AND Post_Type='$type'");
	}
	$wpdb->query("DELETE FROM $table_name WHERE Widget_Title='' AND Widget_Content=''");
	return $ID;
}

function insert_cat_widget($cat_widget)
{	global $wpdb;
	$WidgetC = get_option('widget_changer');
	$table_name = $WidgetC['table_name'];
	for($i=0;$i<count($cat_widget);$i++)
	{	//if($cat_widget[$i]!="")
		//{
		$widget = explode("+++",$cat_widget[$i]);
		$id = $widget[0];
		$widget_title = trim($widget[1]);
		$widget_content = trim($widget[2]);
		$result = $wpdb->get_row("SELECT * FROM $table_name WHERE Post_ID='$id' AND Post_Type='category'",ARRAY_N);
		if(count($result)==0 && $widget_title!="%blank%" && $widget_content!="%blank%")
		{	$result = $wpdb->query("INSERT INTO $table_name (Post_ID, Post_Type, Widget_Title, Widget_Content) VALUES ('$id', 'category','$widget_title', '$widget_content')");
		}
		else if(count($result) >0)
		{	if($widget_title=="%blank%" && $widget_content=="%blank%") 
				$wpdb->query("DELETE FROM $table_name WHERE Post_ID='$id' AND Post_Type='category'");
			else	
				$result = $wpdb->query("UPDATE $table_name SET Widget_Title='$widget_title', Widget_Content = '$widget_content' WHERE Post_ID = '$id' AND Post_Type='category'");
		}
	}
	$wpdb->query("DELETE FROM $table_name WHERE Widget_Title='' AND Widget_Content=''");
}

function widget_changer_init()
{		
	if ( !function_exists('register_sidebar_widget'))
		return; 
		
	function widget_changer($args)
	{	global $wpdb,$post,$wc_ID;
		$WidgetC = get_option('widget_changer');	
		$table_name = $WidgetC['table_name'];
		$widget_title = "";
		$widget_content = "";
		$cat = "";
		extract($args);
		$type = $WidgetC['appear_on'];
		if(is_single() || is_page() || (is_category() && $type=="category")){
			if($type=="post")
			{	$sql = "SELECT * FROM $table_name WHERE Post_ID='$wc_ID' AND Post_Type='$type'";
				//$sql = "SELECT * FROM $table_name WHERE Post_ID='$post->ID' AND Post_Type='$type'";
				$widget = $wpdb->get_row($sql);
			}
			elseif($type=="category")
			{	foreach(get_the_category() as $category)
				{	
				$cat = $category->cat_ID;
				$sql = "SELECT * FROM $table_name WHERE Post_ID='$cat' AND Post_Type='$type'";
				$widget = $wpdb->get_row($sql);
				if($widget !=null) break;
				}				
			}
			if($widget != null){
				$widget_title = $widget->Widget_Title;
				$widget_content = $widget->Widget_Content;
				if($widget_content != ""){
					echo $before_widget;
					if($widget_title != "")
					{	echo $before_title . $widget_title . $after_title; 
					}
					echo $widget_content;
					echo $after_widget;	
				}
			}
		}
		
	}

	// This registers the widget. About time.
	register_sidebar_widget('Widget Changer', 'widget_changer');

}


?>