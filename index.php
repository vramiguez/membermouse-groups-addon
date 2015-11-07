<?php
/****************************************************************************************************************************
 * Plugin Name: MemberMouse Groups Extension
 * Description: Adds group support to MemberMouse. You can define different types of groups allowing a single customer to pay for multiple seats and members to join existing groups for free or for a price based on how you configure the group type.
 * Version: 1.0.2
 * Author: MemberMouse, LLC
 * Plugin URI:  http://membermouse.com
 * Author URI:  http://membermouse.com
 *
 ****************************************************************************************************************************/
if(!(DEFINED( 'MGROUP_DIR' ))) DEFINE('MGROUP_DIR', WP_PLUGIN_URL."/MemberMouseGroupAddon/");
if(!(DEFINED( 'MGROUP_IMG' ))) DEFINE('MGROUP_IMG', WP_PLUGIN_URL."/MemberMouseGroupAddon/images/");

if(!class_exists('MemberMouseGroupAddon')){
	class MemberMouseGroupAddon{
		
		function __construct(){
			$this -> plugin_name = basename(dirname(__FILE__)).'/'.basename(__FILE__);
			include_once(ABSPATH .'wp-admin/includes/plugin.php');
			$plugin = 'membermouse/index.php';
			if(is_plugin_active($plugin)):
				register_activation_hook($this -> plugin_name, array(&$this, 'MemberMouseGroupAddonActivate'));
				register_deactivation_hook($this -> plugin_name, array(&$this, 'MemberMouseGroupAddonDeactivate'));
				add_action('admin_menu',array(&$this, 'MemberMouseGroupAddonAdminMenu'));
				add_action('admin_head', array(&$this, 'MemberMouseGroupAddonAdminResources'));
				add_action('mm_member_add', array(&$this, 'MemberMouseGroupMemberAdded'));
				add_action('mm_member_status_change', array(&$this, 'MemberMouseGroupLeaderStatus'));
				add_action('admin_head', array(&$this, 'MemberMouseGroupOptionUpdate'));
				add_action('admin_notices', array(&$this, 'MemberMouseGroupAdminNotice'));
				add_action('admin_init', array(&$this, 'MemberMouseGroupAdminNoticeIgnore'));
				add_shortcode('MM_Group_SignUp_Link', array(&$this, 'MemberMouseGroupPurchaseLinkShortcode'));
			endif;
		}
		
		function MemberMouseGroupAddonActivate(){
			$this -> MemberMouseGroupAddGroup();
			$this -> MemberMouseGroupAddonCreateTables();
			$this -> MemberMouseGroupAddCap();
			$this -> MemberMouseGroupAddRoll();
		}
		
		function MemberMouseGroupAddonDeactivate(){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			global $wpdb, $current_user;
			$user_id = $current_user -> ID;
					
			/* CODE TO CANCEL MEMBERSHIP OF GROUP LEADERS AND MEMBERS 	
			$leaderSql		= "SELECT id,group_leader FROM ".$wpdb -> prefix."group_sets WHERE 1";
			$leaderResults	= $wpdb -> get_results($leaderSql);
			$leaderCount	= count($leaderResults);
			if($leaderCount > 0):
				foreach($leaderResults as $leaderResult):
					$group_leader	= $leaderResult -> group_leader;
					if(!empty($group_leader)):
						$leader 		= new MM_User($group_leader);
						$leaderStatus	= MM_AccessControlEngine::changeMembershipStatus($leader, MM_Status::$CANCELED);
					endif;
					$memberSql		= "SELECT member_id FROM ".$wpdb -> prefix."group_sets_members WHERE group_id = '".$leaderResult -> id."'";
					$memberResults	= $wpdb -> get_results($memberSql);
					$memberCount	= count($memberResults);
					if($memberCount > 0):
						foreach($memberResults as $memberResult):
							$member_id	= $memberResult -> member_id;
							if(!empty($member_id)):
								$member 		= new MM_User($member_id);
								$memberStatus	= MM_AccessControlEngine::changeMembershipStatus($member, MM_Status::$CANCELED);
							endif;
						endforeach;
					endif;
				endforeach;
			endif;
			*/
		}
		
		function MemberMouseGroupAddonAdminResources(){
			/* Scripts */
			wp_register_script('MemberMouseGroupAddOnAdminJs', MGROUP_DIR.'js/admin.js');
			wp_enqueue_script('MemberMouseGroupAddOnAdminJs', plugins_url('js/admin.js', __FILE__), array('jquery', 'MemberMouseGroupAddOnAdminJs'));
			
			wp_enqueue_script( 'import-group', MGROUP_DIR . 'js/mm-group-import_wizard.js', array('jquery', 'MemberMouseGroupAddOnAdminJs'), '1.0.0', true );

			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'createGroup', array('ajaxurl' => plugins_url('includes/create_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'addGroup', array('ajaxurl' => plugins_url('includes/add_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'deleteGroup', array('ajaxurl' => plugins_url('includes/delete_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'purchaseLink', array('ajaxurl' => plugins_url('includes/purchase_links.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'editGroup', array('ajaxurl' => plugins_url('includes/edit_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'updateGroup', array('ajaxurl' => plugins_url('includes/update_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'editGroupName', array('ajaxurl' => plugins_url('includes/edit_groupname_form.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'updateGroupName', array('ajaxurl' => plugins_url('includes/edit_groupname.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'showPurchaseLink', array('ajaxurl' => plugins_url('includes/show_purchase_link.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'checkUsername', array('ajaxurl' => plugins_url('includes/check_username.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'addGroupUser', array('ajaxurl' => plugins_url('includes/add_group_user.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'deleteGroupMember', array('ajaxurl' => plugins_url('includes/delete_group_member.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'GroupLeaderForm', array('ajaxurl' => plugins_url('includes/group_leader_form.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'checkGroupUser', array('ajaxurl' => plugins_url('includes/check_user.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'createGroupLeader', array('ajaxurl' => plugins_url('includes/create_group_leader.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'changeGroupCost', array('ajaxurl' => plugins_url('includes/change_group_cost.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'showHelpWindow', array('ajaxurl' => plugins_url('includes/help.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'cancelGroup', array('ajaxurl' => plugins_url('includes/cancel_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'activateGroup', array('ajaxurl' => plugins_url('includes/activate_group.php', __FILE__)));
			wp_localize_script('MemberMouseGroupAddOnAdminJs', 'deletegroupData', array('ajaxurl' => plugins_url('includes/delete_group_data.php', __FILE__)));
			//Styles
			wp_enqueue_style( 'MemberMouseGroupAddOnAdminCss', plugins_url('/css/admin.css', __FILE__));
		}
		
		function MemberMouseGroupAddonAdminMenu(){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			add_menu_page("MemberMouse Groups","MM Groups",'list_users',"membermousegroupaddon",array(&$this,'MemberMouseGroupAddonAdminManagement'), MM_Utils::getImageUrl('mm-logo-svg-white'), '3.21');
			add_submenu_page("membermousegroupaddon","Group Management Dashboard","Group Management Dashboard",'Group Leader',"membermousemanagegroup",array(&$this,"MemberMouseManageGroup"));
			add_submenu_page("membermouseviewgroup","List Group Members","List Group Members",'Administrator',"membermouseviewgroup",array(&$this,"MemberMouseManageGroup"));
		}
		
		function MemberMouseGroupPurchaseLinkShortcode(){
			global $wpdb, $current_user;
			if(is_user_logged_in() && in_array('Group Leader', $current_user -> roles)):
				$leaderSql		= "SELECT id,group_template_id,group_name FROM ".$wpdb -> prefix."group_sets WHERE group_leader = '".$current_user -> ID."'";
				$leaderResult	= $wpdb -> get_row($leaderSql);
				if(count($leaderResult) > 0):
					$group_id 		= $leaderResult -> id;
					$template_id	= $leaderResult -> group_template_id;
					$groupName		= $leaderResult -> group_name;
					$itemSql		= "SELECT member_memlevel,group_member_cost FROM ".$wpdb -> prefix."group_items WHERE id = '".$template_id."'";
					$itemResult		= $wpdb -> get_row($itemSql);
	
					if(!empty($itemResult -> group_member_cost)):
						$itemCost		= $itemResult -> group_member_cost;
						$purchaseUrl 	= MM_CorePageEngine::getCheckoutPageStaticLink($itemCost);
					else:
						$itemCost		= $itemResult -> member_memlevel;
						$purchaseUrl 	= MM_CorePageEngine::getCheckoutPageStaticLink($itemCost, true);
					endif;	
					$custom_field	= get_option("mm_custom_field_group_id");	
					$purchaseUrl 	.= '&cf_'.$custom_field.'=g'.$group_id;
					return $purchaseUrl;
				endif;	
			endif;
			return '';
		}
		
		function MemberMouseGroupAdminNotice(){
			global $current_user;
	        $user_id 	= $current_user -> ID;
			$userRole	= $current_user -> roles;
			if(in_array('administrator', $userRole)):
				$group_id 	= get_option("mm_custom_field_group_id");
				if(!get_user_meta($user_id, 'MemberMouseGroupIgnoreNotice')):
					echo '<div class="updated"><p>';
						printf(__('<strong>Please add the following to your checkout page within the [MM_Form type="checkout"] and [/MM_Form] tags:</strong> [MM_Form_Field type="custom-hidden" id="'.$group_id.'"] | <a href="%1$s">Hide Notice</a>'), '?MemberMouseGroupIgnoreNotice=0');
					echo "</p></div>";
				endif;
				
				if(!get_user_meta($user_id, 'MemberMouseGroupShortcodeIgnoreNotice')):
					echo '<div class="updated"><p>';
						printf(__('<strong>Place this shortcode on your Group Leader\'s confirmation page to show their member signup link.</strong> [MM_Group_SignUp_Link]. | <a href="%1$s">Hide Notice</a>'), '?MemberMouseGroupShortcodeIgnoreNotice=0');
					echo "</p></div>";	
					
				endif;
			endif;	
		}
		
		function MemberMouseGroupAdminNoticeIgnore(){
			global $current_user;
	        $user_id = $current_user -> ID;
	        if(isset($_GET['MemberMouseGroupIgnoreNotice']) && '0' == $_GET['MemberMouseGroupIgnoreNotice']):
	             add_user_meta($user_id, 'MemberMouseGroupIgnoreNotice', 'true', true);
			endif;
			
			if(isset($_GET['MemberMouseGroupShortcodeIgnoreNotice']) && '0' == $_GET['MemberMouseGroupShortcodeIgnoreNotice']):
	             add_user_meta($user_id, 'MemberMouseGroupShortcodeIgnoreNotice', 'true', true);
			endif;
		}
		
		function MemberMouseGroupAddCap(){
			$custom_cap	= "membermouse_group_capability";
			$grant      = true; 
			foreach($GLOBALS['wp_roles'] -> role_objects as $role => $name):
			//	if($role == "Group Leader"):
			if(!$name -> has_cap($custom_cap)):
				$name -> add_cap($custom_cap, $grant);
			endif;
			//	endif;	
			endforeach;
		}
		
		function MemberMouseGroupRemoveCap(){
			$custom_cap	= "membermouse_group_capability";
			foreach($GLOBALS['wp_roles'] -> role_objects as $role => $name):
			//	if($role == "Group Leader"):
					if(!$name -> has_cap($custom_cap)):
						$name -> remove_cap($custom_cap);
					endif;
			//	endif;	
			endforeach;			
		} 
		
		function MemberMouseGroupAddonCreateTables(){
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$table_name 		= $wpdb -> prefix.'group_sets';
			$table_name1 		= $wpdb -> prefix.'group_sets_members';
			$table_group_item 	= $wpdb -> prefix.'group_items';
			$table_group_notice	= $wpdb -> prefix.'group_notices';
			
            if($wpdb -> get_var( "show tables like $table_name" ) != $table_name):
				$sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    group_template_id INT(11) NOT NULL DEFAULT '0',
                    group_name VARCHAR(255) NOT NULL,
					group_size INT(11) NOT NULL DEFAULT '0',
					group_leader INT(11) NOT NULL DEFAULT '0',
                    group_status INT(11) NOT NULL DEFAULT '0',
					createdDate DATETIME NOT NULL,
					modifiedDate DATETIME NOT NULL,
                    PRIMARY KEY (id)
                );";
				dbDelta($sql);
			endif;
			
			if($wpdb -> get_var( "show tables like $table_name1" ) != $table_name1):
				$sql = "CREATE TABLE IF NOT EXISTS $table_name1 (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    group_id INT(11) NOT NULL DEFAULT '0',
                    member_id VARCHAR(255) NOT NULL,
					createdDate DATETIME NOT NULL,
					modifiedDate DATETIME NOT NULL,
                    PRIMARY KEY (id)
                );";
				dbDelta($sql);
			endif;
			
			if($wpdb -> get_var( "show tables like $table_group_item" ) != $table_group_item):
				$sql = "CREATE TABLE IF NOT EXISTS $table_group_item (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    leader_memlevel INT(11) NOT NULL DEFAULT '0',
                    member_memlevel INT(11) NOT NULL DEFAULT '0',
                    group_leader_cost INT(11) NOT NULL DEFAULT '0',
                    group_member_cost INT(11) NOT NULL DEFAULT '0',
                    group_size INT(11) NOT NULL DEFAULT '0',
					description LONGTEXT NOT NULL,
					createdDate DATETIME NOT NULL,
					modifiedDate DATETIME NOT NULL,
                    PRIMARY KEY (id)
                );";
				dbDelta($sql);
			endif;
			
			if($wpdb -> get_var( "show tables like $table_group_notice" ) != $table_group_notice):
				$sql = "CREATE TABLE IF NOT EXISTS $table_group_notice(
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    group_id INT(11) NOT NULL DEFAULT '0',
                    user_id INT(11) NOT NULL DEFAULT '0',
                    leader_id INT(11) NOT NULL DEFAULT '0',
                    msg_type INT(11) NOT NULL DEFAULT '0',
					createdDate DATETIME NOT NULL,
					modifiedDate DATETIME NOT NULL,
                    PRIMARY KEY (id)
                );";
				dbDelta($sql);
			endif;
		}
		
		function MemberMouseGroupAddGroup(){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			$customFieldList = MM_CustomField::getCustomFieldsList();
			if(count($customFieldList) > 0):
				$customFieldId	= 0;
				foreach($customFieldList as $id=>$displayName):
					if($displayName == "group_id"):
						$customFieldId = $id;
						break;
					endif;
				endforeach;
				if(empty($customFieldId)):
					$customField = new MM_CustomField();
					$displayName = "group_id";
					$customField -> setDisplayName($displayName);
					$customField -> setShowOnMyAccount("0");
					$customField -> setHiddenFlag("1");
					$customField -> commitData();
				else:
					update_option("mm_custom_field_group_id", $customFieldId);
				endif;
			else:
				$customField = new MM_CustomField();
				$displayName = "group_id";
				$customField -> setDisplayName($displayName);
				$customField -> setShowOnMyAccount("0");
				$customField -> setHiddenFlag("1");
				$customField -> commitData();
			endif;	
		}
		
		function MemberMouseGroupOptionUpdate(){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			$customFieldList = MM_CustomField::getCustomFieldsList();
			foreach($customFieldList as $id=>$displayName):
				if($displayName == "group_id"):
					update_option("mm_custom_field_group_id", $id);
					break;
				endif;	
			endforeach;
		}
		
		function MemberMouseGroupAddRoll(){
			$role 			= "Group Leader";
			$display_name 	= "Group Leader";
			$capabilities	= array("read" => true,"membermouse_group_capability" => true);
			add_role($role, $display_name, $capabilities);
			
			// gets the administrator role
			$admin = get_role( 'administrator');
			$admin->add_cap($capabilities);
			
		}
		
		function MemberMouseGroupRemoveRoll(){
			remove_role("Group Leader");
		}
				
		function MemberMouseGroupAddonAdminManagement(){
			global $wpdb;
			if(isset($_GET["type"]) && !empty($_GET["type"])):
				$type	= $_GET["type"];
			else:
				$type	= '';
			endif;
			?>
			<div class="wrap" style="margin-top:20px;">
				<div id="create_group_background" style="display:none;">
					<div id="create_group_loading" style="display:none;"></div>
					<div id="create_group_content" style="display:none;"></div>
				</div>
				<div class="membermousegroupaddon">
					<div class="membermousegrouptabs">
						<?php include_once(dirname(__FILE__)."/includes/tabs.php");?>
					</div>
					<div class="membermousegroupcontent">
			<?php		if($type == "manage"):?>			
							<div class="membermousegroupmanage">
								<?php include_once(dirname(__FILE__)."/includes/manage.php");?>
							</div>
			<?php		// NEW block - begin
						elseif($type == "import"):?>			
							<div class="membermousegroupmanage">
								<?php include_once(dirname(__FILE__)."/includes/import.php");?>
							</div>							
			<?php		// NEW block - end
						else:?>
							<div class="membermousegroupconfig">
								<?php include_once(dirname(__FILE__)."/includes/config.php");?>
							</div>
			<?php		endif;?>	
					</div>	
				</div>	
			</div>
<?php	}

		function MemberMouseManageGroup(){
			include_once(dirname(__FILE__)."/includes/manage_groups.php");
			include_once(dirname(__FILE__)."/includes/list_group_members.php");
		}
		
		function MemberMouseGroupPagination($limit = 10, $count, $page, $start, $targetpage, $type="groups"){
			$prev 		= $page - 1;
			$next 		= $page + 1;
			$lastpage 	= ceil($count/$limit);
			$pagination = "";
			$pagination .= "<div class=\"group_pagination\">";
				$pagination .= '<span class="group_prev_next">';
					$pagination .= 'Page'; 
					if($page > 1):
						$pagination .= '<a href="'.$targetpage.'&p='.$prev.'" class="prev" title="Previous" style="margin-left:4px; margin-right:4px;">';
							$pagination .= MM_Utils::getIcon('chevron-circle-left', 'light-blue', '1.4em', '1px');
						$pagination .= '</a>';
					else:
						$pagination .= '<a href="javascript:void(0);" class="prev" title="Previous" style="margin-left:4px; margin-right:4px;">';
							$pagination .= MM_Utils::getIcon('chevron-circle-left', 'light-blue', '1.4em', '1px');
						$pagination .= '</a>';
					endif;
					$pagination .= $page;
					if($page < $lastpage):
						$pagination .= '<a href="'.$targetpage.'&p='.$next.'" class="next" title="Next" style="margin-left:4px; margin-right:4px;">';
							$pagination .= MM_Utils::getIcon('chevron-circle-right', 'light-blue', '1.4em', '1px');
						$pagination .= '</a>';
					else:
						$pagination .= '<a href="javascript:void(0);" title="Next" style="margin-left:4px; margin-right:4px;">';
							$pagination .= MM_Utils::getIcon('chevron-circle-right', 'light-blue', '1.4em', '1px');
						$pagination .= '</a>';
					endif;	
					$pagination .= 'of	'.$lastpage.' pages';
				$pagination .= '</span>';
				$pagination .= '<span class="group_show">';
					$pagination .= 'Show '; 
					$pagination .= "<select name=\"show_record\" id=\"show_record\" onchange=\"javascript:MGROUP.changeRecordVal(this.value,'".$targetpage."');\">";
						$pagination .= '<option value="10"';
							if($limit == 10):
								$pagination .= ' selected="selected"';
							endif;	
						$pagination .= '>10</option>';
						$pagination .= '<option value="20"';
							if($limit == 20):
								$pagination .= ' selected="selected"';
							endif;
						$pagination .= '>20</option>';
						$pagination .= '<option value="50"';
							if($limit == 50):
								$pagination .= ' selected="selected"';
							endif;
						$pagination .= '>50</option>';
						$pagination .= '<option value="100"';
							if($limit == 100):
								$pagination .= ' selected="selected"';
							endif;
						$pagination .= '>100</option>';
						$pagination .= '<option value="500"';
							if($limit == 500):
								$pagination .= ' selected="selected"';
							endif;
						$pagination .= '>500</option>';
						$pagination .= '<option value="1000"';
							if($limit == 1000):
								$pagination .= ' selected="selected"';
							endif;
						$pagination .= '>1000</option>';
					$pagination .= '</select> '; 
					$pagination .= 'per page';
				$pagination .= '</span>';
				$pagination .= '<span class="group_found">'.$count.' '.$type.' found</span>';
			$pagination.= "</div>";
			
			return $pagination;
		}

		// hook over mm_member_add
		
		function MemberMouseGroupMemberAdded($data){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			global $wpdb;
			$groupId 	= get_option("mm_custom_field_group_id");
			if(isset($data["cf_".$groupId]) && !empty($data["cf_".$groupId])):
				$cf	= $data["cf_".$groupId];
				$memberId	= $data["member_id"];
				$groupName = (!empty($data["cf_3"])) ? $data["cf_3"] : 'Group';
				if(is_numeric($cf)):
					$templateSql	= "SELECT id,group_size FROM ".$wpdb -> prefix."group_items WHERE id = '".$cf."'";
					$templateResult	= $wpdb -> get_row($templateSql);
					if(count($templateResult) > 0):
						$template_id	= $templateResult -> id;
						$groupSize		= $templateResult -> group_size;
						$sql			= "INSERT INTO ".$wpdb -> prefix."group_sets (id,group_template_id,group_name,group_size,group_leader,group_status,createdDate,modifiedDate)VALUES('','".$template_id."','".$groupName."','".$groupSize."','".$memberId."','1',now(),now())";
						$query			= $wpdb -> query($sql);
						$updateUser 	= wp_update_user(array('ID' => $memberId, 'role' => 'Group Leader'));	
					endif;
				else:
				//	$gID	= substr($cf, -1);
					$gID	= substr($cf, 1);
					$sql	= "SELECT * FROM ".$wpdb -> prefix."group_sets WHERE id = '".$gID."'";
					$result	= $wpdb -> get_row($sql);
					if(count($result) > 0):
						$groupSize	= $result -> group_size;
						$groupId	= $result -> id;
						$sSql		= "SELECT COUNT(id) AS count FROM ".$wpdb -> prefix."group_sets_members WHERE group_id = '".$gID."'";
						$sRes		= $wpdb -> get_row($sSql);
						$gCount		= $sRes -> count;
						if($gCount < $groupSize):
							$sql	= "INSERT INTO ".$wpdb -> prefix."group_sets_members (id,group_id,member_id,createdDate,modifiedDate)VALUES('','".$groupId."','".$memberId."',now(),now())";
							$query	= $wpdb -> query($sql);
						else:
							$groupSql		= "SELECT group_leader FROM ".$wpdb -> prefix."group_sets WHERE id = '".$groupId."'";
							$groupResult	= $wpdb -> get_row($groupSql);
							$group_leader	= $groupResult -> group_leader;
			
							$adminSql	= "INSERT INTO ".$wpdb -> prefix."group_notices (id,group_id,user_id,leader_id,msg_type,createdDate,modifiedDate)VALUES('','".$groupId."','".$memberId."','".$group_leader."','0',now(),now())";
							$adminQuery	= $wpdb -> query($adminSql);
							
							$leaderSql		= "INSERT INTO ".$wpdb -> prefix."group_notices (id,group_id,user_id,leader_id,msg_type,createdDate,modifiedDate)VALUES('','".$groupId."','".$memberId."','".$group_leader."','1',now(),now())";
							$leaderQuery	= $wpdb -> query($leaderSql);
							
							$user 		= new MM_User($memberId);
							$userStatus	= MM_AccessControlEngine::changeMembershipStatus($user, MM_Status::$CANCELED);
						endif;
					endif;	
				endif;		
			endif;	
		}
		
		function MemberMouseGroupLeaderStatus($data){
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/mm-constants.php");
			include_once(ABSPATH."wp-content/plugins/membermouse/includes/init.php");
			global $wpdb;
			$memberId		= $data["member_id"];
			$status			= $data["status"];
			$leaderSql		= "SELECT id FROM ".$wpdb -> prefix."group_sets WHERE group_leader = '".$memberId."'";
			$leaderResult	= $wpdb -> get_row($leaderSql);
			if(count($leaderResult) > 0):
				$groupId	= $leaderResult -> id;
			else:
				$groupId	= 0;
			endif;

			if(($status == 2) && !empty($groupId)):
				$sql		= "SELECT member_id FROM ".$wpdb -> prefix."group_sets_members WHERE group_id = '".$groupId."'";
				$results	= $wpdb -> get_results($sql);
				if(count($results) > 0):
					foreach($results as $result):
						$user 		= new MM_User($result -> member_id);
						$userStatus	= MM_AccessControlEngine::changeMembershipStatus($user, MM_Status::$CANCELED);
					endforeach;
				endif;
			endif;	
		}
	}
}
if(class_exists('MemberMouseGroupAddon')):
    global $MemberMouseGroupAddon;
    $MemberMouseGroupAddon = new MemberMouseGroupAddon();
endif;
?>