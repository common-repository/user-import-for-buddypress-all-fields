<?
/*
Plugin Name: User Import for BuddyPress (All Fields)
Plugin URI: http://manojkumar.org/user-import-for-buddypress/
Description: This plugin will import users with all fields to your Wordpress MU + BuddyPress installation.
Version: 1.0
Author: Manoj Kumar
Author URI: http://manojkumar.org/
*/

/*  Copyright 2008-2009  Manoj Kumar (http://manojkumar.org/) (email : talk2manoj@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/


require_once( 'bp-core.php' );
require_once( 'bp-user-import-adv/bp-user-import-adv-functions.php' );

function user_import_add_admin_menu() {
	global $wpdb, $bp;
	if ( is_site_admin() ) {
		
        add_submenu_page('wpmu-admin.php', 'BuddyPress User Import', 'BuddyPress User Import', 10, 'bp_user_import_adv', 'bp_user_import_adv');

	}
}
add_action( 'admin_menu', 'user_import_add_admin_menu' );



function bp_user_import_adv() {
  // Plugin content here will only be accessible by site admins
 

  $RecordErrors="";
  $ErrorCount=0;
  $complete=0;
  $Result = "";

  if (isset($_POST['data_submit'])) {
     ?><div id="message" class="updated fade"><p>
     <?php echo "Your request has been checked!";?></p></div>
    
     <?php


        //Data Split is based on dd-import-users

		// Get form data into an array temp variables
        
		$user_data_temp = array();
        $user_field_type_temp=array();
        $user_field_ids_temp=array();
        $user_field_count_temp=$_POST["field_count"];
        

		if(trim((string)$_POST["textarea_data"]) != ""){
			$user_data_temp = array_merge($user_data_temp, explodelines(((string) ($_POST["textarea_data"]))));
            $user_field_type_temp =array_merge($user_field_type_temp,explodepipe(((string) ($_POST["field_type"]))));
            $user_field_ids_temp =array_merge($user_field_ids_temp,explodepipe(((string) ($_POST["field_ids"]))));
		}
		else{
			$Result .= "<p>There is no data in field. Please try again!</p>";
            echo "<div class='error'>".$Result."</div>";
            exit;
		}


        //print_r($user_data_temp);
        //print_r($user_field_type_temp);
        //print_r($user_field_ids_temp);
        //print_r($user_field_count_temp);

      

        $user_data = array();
		$j = 0;
		//for ( $i = 0; $i < count($fields); $i++ ) {

		foreach ($user_data_temp as $ut) {

			if (trim($ut) != '') {
                
                $records=explode("|",$ut);
                $record_count=count($records);

                //print_r($user_field_type_temp);
                //print_r($user_field_ids_temp);
                //print_r($record_count);
                //print_r($user_field_count_temp);

                if ($record_count!=$user_field_count_temp+2){
                    $RecordErrors .='<strong>Check number of Delimiters [|]</strong> - ' . $ut .'<br />';
                    $ErrorCount++;
                    //echo "Delimiter are wrong in - " . $ut;
                }else{
                   
                    for ( $i = 2; $i < $record_count; $i++ ) {
                        
                        if ($user_field_type_temp[$i]=='multiselectbox' || $user_field_type_temp[$i]=='checkbox'){
                            if ($records[$i]){
                                $temp_array=array();
                                $temp_array = array_merge($temp_array, explodetilde($records[$i]));
                                $xprofile_meta[$user_field_ids_temp[$i]] = serialize($temp_array);                               
                            }

                        }elseif($user_field_type_temp[$i]=='datebox'){
                            if ($records[$i]){
                                $temp_array = (strtotime ($records[$i]));
                                $xprofile_meta[$user_field_ids_temp[$i]] = $temp_array;                                
                            }
                        }
                        else{
                                $xprofile_meta[$user_field_ids_temp[$i]]=$records[$i];
                                
                        }
                     
                    }
                    $xprofile_meta['xprofile_field_ids']=$_POST['xprofile_ids'];
                    $xprofile_meta['avatar_image_resized']=false;
                    $xprofile_meta['avatar_image_original']=false;

                    //print_r($xprofile_meta);
                    //print_r($i);

                    if (($records[0] != '') && ($records[1] != '')) {

                        $user_data[$j]['username'] = $records[0];
                        $user_data[$j]['email'] = $records[1];
                        $user_data[$j]['xprofile_meta'] = $xprofile_meta;
                    }
                    //print_r($user_data[$j]);
                    
                    $j++;
                }
                //print_r("<hr>");
                
            }

            

			
        }
        //// Close foreach
        //print_r($j);
        //print_r($user_data);
        //exit;

        $errors = array();
		$complete = 0;

		foreach ($user_data as $u_data) {

			// check for errors
            //I am using core Wordpress Mu function to avoid any hacking
         //wpmu_validate_user_signup(username, email-id)
            //It will return error, if there is some issue
            //Also in Buddy Press there is one filter added at 209 to this function for
            //validating all xprofile fileds, so if you want more functionality check following function
            //xprofile_validate_signup_fields
            //add_filter('wpmu_validate_user_signup', 'xprofile_validate_signup_fields', 10, 1 );

			$user_record = $u_data['username'].' - '.$u_data['email'];

            $Result = wpmu_validate_user_signup($u_data['username'],$u_data['email']);

            extract($Result);

            if (!$errors->get_error_code()) {
        
                    $user_data = array( 'user_login' => $u_data['username'], 'user_email' => $u_data['email']);

                    $_SESSION['xprofile_meta']='';
                    $_SESSION['xprofile_meta']=$u_data['xprofile_meta'];

                    if ($_POST['mail_notifications']=='all'){
                        wpmu_signup_user($user_data['user_login'], $user_data['user_email'], apply_filters( "add_signup_meta", array()) );
                    }else{
                        my_wpmu_signup_user($user_data['user_login'], $user_data['user_email'], apply_filters( "add_signup_meta", array()) );
                    }

                    $complete++;

             }else{

                    /*
                     if ( $errors->get_error_message('user_name') && $errors->get_error_message('user_email') && $errors->get_error_message('user_email_used')==null) {
                        $RecordErrors =$RecordErrors.$errors->get_error_message('user_name') .' - '. $errors->get_error_message('user_email') ." In ".$user_record."<br />";
                     }
                     if ( $errors->get_error_message('user_name') && $errors->get_error_message('user_email') && $errors->get_error_message('user_email_used')) {
                        $RecordErrors =$RecordErrors.$errors->get_error_message('user_name') .' - '. $errors->get_error_message('user_email') .' - '. $errors->get_error_message('user_email_used')." In ".$user_record."<br />";
                     }
                     if ( $errors->get_error_message('user_name') && $errors->get_error_message('user_email') == null ) {
                        $RecordErrors =$RecordErrors.$errors->get_error_message('user_name')." In ".$user_record."<br />";
                     }
                     if ( $errors->get_error_message('user_name') == null && $errors->get_error_message('user_email') ) {
                        $RecordErrors =$RecordErrors.$errors->get_error_message('user_email')." In ".$user_record."<br />";
                     }
                     */
             
                     $RecordErrors =$RecordErrors.'<strong>'.$errors->get_error_message().' in</strong>- '.$user_record;
                     $ErrorCount++;

            }
            
         }

        if ($ErrorCount>0){

            echo '<div class="error">'.$ErrorCount.' Error <br />'.$RecordErrors.'</div>';

        }
        if ($complete>0){

            echo '<div class="updated fade">'.$complete.' Record has been Sucsessfully imported <br /></div>';

        }

  }



  ?>
  <div class="wrap">
  <div id="icon-users" class="icon32"><br /></div>
    <h2 id="add-new-user">User Import for BuddyPress Site with all fields</h2><ul>
        <li><?php _e('1. You can import all xprofile fields to your BuddyPress Installation', 'buddypress') ?></li>
        <li><?php _e('2. Delimit your data with "[|]" (pipe sign) like (username|email@domainname.com|fullname)', 'buddypress') ?></li>
        <li><?php _e('3. Delimit your data for multiselectbox and checkbox with "[~]" (tilde sign) like (Option 1~Option 2~Option 3)', 'buddypress') ?></li>
        <li><?php _e('4. Paste your data in the box below', 'buddypress') ?></li>
        <li><?php _e('5. Choose any of the 3 Email notification options', 'buddypress') ?></li>
        <li><?php _e('6. Click on the import user button', 'buddypress') ?></li>
        </ul>
		<p>For any suggestion, help or bug, please visit <a href="http://manojkumar.org/user-import-for-buddypress/">http://manojkumar.org/user-import-for-buddypress/</a></p>

        <?php


	/* Fetch the all fields*/

    $fields = get_all_fields();

    //print_r($fields);

	if ( $fields ) {

        $html='username|email@dmainname.com';
        $field_count=0;

        ?>
        <table class="widefat">
					<thead>
					    <tr class="nodrag">
					    	<th scope="col" colspan="2">You have following fields in your BuddyPress xprofile</th>
						</tr>

					</thead>
					<tbody>
					   <tr class="header nodrag">
                            <td><strong>Field Name</strong></td>
					    	<td width="30%"><strong>Field Type</strong></td>
					    </tr>

        <?php
        
        for ( $i = 0; $i < count($fields); $i++ ) {

            $html  .= "|" ;
            $html  .= $fields[$i]->name;
        ?>
                    <tr class="alternate">
					    	<td><?php echo $fields[$i]->name; ?></td>
					    	<td width="30%"><?php echo $fields[$i]->type; ?></td>
					</tr>
        <?php
            //echo $fields[$i]->name . " - (" . $fields[$i]->type .")<br />";
			$field_ids .= $fields[$i]->id . ",";
            $all_fields .= "field_" . $fields[$i]->id . "|";
            $field_count++;
            $field_type .=$fields[$i]->type. "|";
        }
        ?>
                    </tbody>
        </table><br />

        <table class="widefat">
                            <thead>
                                <tr class="nodrag">
                                    <th scope="col">A formatted example for your BuddyPress xprofile fields is as follows</th>
                                </tr>
                            </thead>
                            <tbody>
                               <tr class="header nodrag"  >
                                    <td><?php echo $html;?></td>
                               </tr>
                           </tbody>
       </table>

        <form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"  >
            <input type="hidden" name="data_submit" id="data_submit" value="true" />

            <div class="form-table"><br />
                    <table class="widefat">
                            <thead>
                                <tr class="nodrag">
                                    <th scope="col" colspan="2"><strong>Copy and Paste your data in box below :-</strong></th>
                                </tr>

                            </thead>
                            <tbody id="the-list">
                               <tr class="header nodrag"  >
                                    <td colspan="2"><textarea name="textarea_data" cols="90" rows="12" style="width:100%"></textarea></td>
                               </tr>
                               <tr valign="top">
                                    <td><?php _e('E-mail Notifications') ?></td>

                                    <td>
                                        <label><input name="mail_notifications" type="radio" id="mail_notifications1" value='all' checked='checked' /> <?php _e('Send Activation Mail.'); ?></label><br />
                                        <label><input name="mail_notifications" type="radio" id="mail_notifications2" value='withpass'  /> <?php _e('Automatically activate account and send mail with user name and password only.'); ?></label><br />
                                        <label><input name="mail_notifications" type="radio" id="mail_notifications3" value='none' /> <?php _e('Automatically activate account and do not send any mail.'); ?></label><br />
                                    </td>
                                </tr>

                            </tbody>
                    </table>
            </div>
            <div>
                <input type="hidden" name="xprofile_ids" value="<?php echo $field_ids; ?>" />
                <input type="hidden" name="field_ids" value="<?php echo "username|email|". $all_fields; ?>" />
                <input type="hidden" name="field_count" value="<?php echo $field_count; ?>" />
                <input type="hidden" name="field_type" value="<?php echo "textbox|textbox|".$field_type; ?>" />
            </div>
            <div class="submit">
                <input type="submit" name="data_submit" value="<?php _e('Import Users'); ?> &raquo;" />
            </div>
        </form>
        </div>

	<?php
	}
?>


        
  

  </div><?php
}

?>
