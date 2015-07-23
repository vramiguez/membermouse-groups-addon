<?php
global $wpdb;
if(!isset($wpdb)):
	require_once('../../../../wp-config.php');
    require_once('../../../../wp-includes/wp-db.php');
endif;

if(count($_POST) > 0):
	foreach($_POST as $key => $value):
		$$key = $value;
	endforeach;
	
	$groupSql	= "INSERT INTO ".$wpdb -> prefix."group_sets_members (id,group_id,member_id,createdDate,modifiedDate)VALUES('','".$group_id."','".$member_id."',now(),now())";
	$groupQuery	= $wpdb -> query($groupSql);

	//get the membership associated to the membersgroup
	$sql = "SELECT group_template_id FROM ".$wpdb -> prefix."group_sets WHERE group_leader = ".$leader_id;
	$g_template_id = $wpdb->get_var($sql);

	$sql = "SELECT member_memlevel FROM ".$wpdb -> prefix."group_items WHERE id = ".$g_template_id;
	$mem_level = $wpdb -> get_var($sql);

	$sql = "INSERT INTO ".$wpdb -> prefix."mm_user_data (wp_user_id,membership_level_id,status,first_name,last_name,phone) VALUES('".$userId."','".$mem_level."','1','".$first_name."','".$last_name."','".$phone."'')";
	$query	= $wpdb -> query($sql);

	if($groupQuery):
		$msg["success"]	= "yes";
	else:
		$msg["success"] = "no";
	endif;	
	$return = json_encode($msg);
	echo $return;	
endif;
?>