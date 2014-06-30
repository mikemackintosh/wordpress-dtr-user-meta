<?php

defined('ABSPATH') or die("Direct access to script has been disabled");

/*
  Plugin Name: [dTR] User Meta
  Plugin URI: https://github.com/mikemackintosh/wordpress-dtr-user-meta
  Description: A custom user meta plugin for wordpress
  Version: 1.0
  Author: Mike Mackintosh
  Author Twitter: @mikemackintosh
  Author URI: http://github.com/mikemackintosh
  License: GPL V2
 */

/*
[dTR] User Meta (Wordpress Plugin)
Copyright (C) 2014 Mike Mackintosh
Contact me at http://twitter.com/mikemackintosh

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Create Field
$field_types = 
    array('Text', 'Paragraph', 'Date', 'Checkbox', 'Multiple', 'Dropdown', 'OnlyOne');

/**
 * Check if admin
 */
if( is_admin() ){
    add_action('admin_init', 'admin_init');
    add_action('admin_menu', 'admin_menu');
}

/**
 * Add callback for new user registration form
 */
add_action( 'register_form', 'dtr_registration_fields' );
add_filter( 'registration_errors', 'dtr_registration_errors', 10, 3);
add_action( 'user_register', 'dtr_registration_save');

/**
 * Add callback for existing user edit form
 */
add_action( 'show_user_profile', 'get_dtr_profile_fields');
add_action( 'edit_user_profile', 'get_dtr_profile_fields');

/**
 * Add callback for user edit form
 */
add_action( 'personal_options_update', 'save_dtr_profile_fields' );
add_action( 'edit_user_profile_update', 'save_dtr_profile_fields' );


/**
 * Stuff to exec on admin load callback
 * 
 * @since 1.0
 * 
 * @return
 */
function admin_init() {
    // Load jQuery Sortable
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_register_style( 'dtrud_stylesheet', plugins_url('stylesheet.css', __FILE__) );

}

/**
 * Displays admin menu and submenu
 * 
 * @since 1.0
 *  
 * @return
 */
function admin_menu() {
    $parent_page = add_menu_page('User Details', 'User Details', 'manage_options', 'user-details', 'manage_user_details', 'dashicons-nametag', 27);
    $meta_page = add_submenu_page( 'user-details', 'Meta', 'Meta', 'manage_options', 'user-details-meta', 'manage_user_meta');
}

/**
 * Edits Plugin Meta from Admin
 * 
 * @since  1.0
 * 
 * @return
 */
function manage_user_meta(){
    // If it's a post
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        save_user_meta();
    }

    $meta = stripslashes(get_option('dtrud_meta'));
?>    
    <style type="text/css">
    textarea {
        width: 100%;
    }
    </style>
    <div id="wpbody-content" aria-label="Main content" tabindex="0">
        <div class="wrap">
            <h2>User Detail Meta</h2>
            <div class="welcome-panel" style="padding-top:0px;">
                <p>Enter any Javascript required for your user attributes such as validation.</p>
            </div>

            <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
                <input name="dtr_user_meta" type="hidden" value="<?php echo wp_create_nonce('dtr_user_meta'); ?>" />
                <textarea name="meta" rows="30"><?=$meta;?></textarea>
                <button class="button" type="submit">Submit</button>
            </form>
        </div>
    </div>
<?php
}

/**
 * Saves Plugin Meta from Admin
 *
 * @since  1.0
 * 
 * @return
 */
function save_user_meta() {
    status_header(200);
    if (!isset($_POST['dtr_user_meta'])) die("<br><br>Hmm .. looks like you didn't send any credentials.. No CSRF for you! ");
    if (!wp_verify_nonce($_POST['dtr_user_meta'],'dtr_user_meta')) die("<br><br>Hmm .. looks like you didn't send any credentials.. No CSRF for you! ");
        

    update_option('dtrud_meta', $_REQUEST['meta']);
?>
    <div class="updated">
        <p>Updated! User Detail meta has been saved!</p>
    </div>
<?php
}

/**
 * This method allows you to add and 
 * remove attributes which serve as 
 * user meta
 * 
 * @since  1.0
 * 
 * @return [type] [description]
 */
function manage_user_details() {
    global $field_types;
    
    // If it's a post
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        save_user_details();
    }

    // Get Attributes from Settings
    $attributes = get_option('dtrud_attributes');

    ?>
    <style>
        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
    </style>
    <div id="wpbody-content" aria-label="Main content" tabindex="0">
        <div class="wrap">
            <h2>User Detail Manager</h2>
            <div class="welcome-panel" style="padding-top:0px;">
                <p>The <strong>[dTR]</strong> User Detail Manager provides the ability to choose additional user meta data, such as patient information, social network details and other create user specific details.</p>
            </div>
            <p>To add custom details, click the <kbd class="button">Add New</kbd> button below.</p>
            <p><b><em>Note:</em></b> To delete an attribute, use check the box next to the attribute</p>
            <button class="button add_new">Add New</button>
            <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
                <input name="dtr_user_attrib" type="hidden" value="<?php echo wp_create_nonce('dtr_user_attrib'); ?>" />

                <div class="attrib_block">
                    <table class="wp-list-table widefat fixed posts">
                        <thead>
                            <tr>
                                <th scope='col' id='cb' class='manage-column column-cb check-column'  style="">
                                    <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                 <input id="cb-select-all-1" type="checkbox" />
                                </th>
                                <th scope='col' id='attrib_name' class='manage-column column-name sortable desc'  style="padding-left:10px;">
                                    <span>Attribute Name</span>
                                </th>                                
                                <th scope='col' id='attrib_display' class='manage-column column-display sortable desc'  style="padding-left:10px;">
                                    <span>Display</span>
                                </th>  
                                <th scope='col' id='attrib_description' class='manage-column column-name sortable desc'  style="padding-left:10px;">
                                    <span>Description</span>
                                </th>   
                                <th scope='col' id='attrib_type' class='manage-column column-type sortable desc'  style="padding-left:10px;">
                                    <span>Field Type</span>
                                </th>
                                <th scope='col' id='attrib_values' class='manage-column column-values sortable desc'  style="padding-left:10px;">
                                    <span>Values</span>
                                </th>                                
                                 <th scope='col' id='attrib_reqd' class='manage-column column-required sortable desc'  style="padding-left:10px;">
                                    <span>Required</span>
                                </th>  
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th scope='col' id='cb' class='manage-column column-cb check-column'  style="">
                                    <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                 <input id="cb-select-all-1" type="checkbox" />
                                </th>
                                <th scope='col' id='attrib_name' class='manage-column column-name sortable desc'  style="padding-left:10px;">
                                    <span>Attribute Name</span>
                                </th>                                
                                <th scope='col' id='attrib_display' class='manage-column column-name sortable desc'  style="padding-left:10px;">
                                    <span>Display</span>
                                </th>   
                                <th scope='col' id='attrib_description' class='manage-column column-name sortable desc'  style="padding-left:10px;">
                                    <span>Description</span>
                                </th>   
                                <th scope='col' id='attrib_type' class='manage-column column-type sortable desc'  style="padding-left:10px;">
                                    <span>Field Type</span>
                                </th>
                                <th scope='col' id='attrib_values' class='manage-column column-values sortable desc'  style="padding-left:10px;">
                                    <span>Values</span>
                                </th>   
                                <th scope='col' id='attrib_reqd' class='manage-column column-required sortable desc'  style="padding-left:10px;">
                                    <span>Required</span>
                                </th>                          
                            </tr>
                        </tfoot>
                        <!-- Body Table -->
                        <tbody id="the-list">
                            <?php if( empty($attributes) ){ ?>
                            <tr>
                                <td colspan="7">No attributes exist</td>
                            </tr>
                            <? } else { ?>
                            <?php $i = 0; foreach( $attributes as $attr_name => $attr_details ){ print_r($attribute);
                                $attr_name = strtolower(preg_replace('/[^\da-z_]/i', '_', $attr_name));
                            ?>

                            <tr attrid="<?=++$i;?>">
                                <td>
                                    <input id="cb-select-1" type="checkbox" name="attrib_delete[<?=$i;?>]" value="<?=$attr_name;?>" />
                                </td>
                                <td>
                                    <input type="text" name="attrib_name[<?=$i;?>]" value="<?=$attr_name;?>" />
                                    <div></div>
                                </td>
                                <td>
                                    <input type="text" name="attrib_details[<?=$i;?>]" value="<?=$attr_details['details'];?>"  />
                                </td>
                                <td>
                                    <input type="text" name="attrib_description[<?=$i;?>]" value="<?=$attr_details['description'];?>"  />
                                </td>
                                <td>
                                    <select style="margin-top:-2px;" name="attrib_type[<?=$i;?>]">
                                        <?php foreach($field_types as $type ){
                                            $selected = '';
                                            if( $attr_details['type'] == $type){
                                                $selected='selected="selected" ';
                                            }?>
                                            <option <?=$selected;?>><?=$type;?></option>
                                        <?php }?>
                                    </select>
                                </td>
                                <td>
                                    <a class="add_new">Add New</a><br />
                                    <?php if(!empty($attr_details['values'])){ 
                                            foreach($attr_details['values'] as $v){ ?>
                                            <input type="text" name="attrib_values[<?=$i;?>][]" value="<?=$v;?>" />
                                    <?php }} ?>
                                </td>                                
                                <td>
                                    <input type="checkbox" name="attrib_required[<?=$i;?>]" <?=($attr_details['required'] == 'on' ? 'checked="checked" ' : '');?>/>
                                </td>
                            </tr>
                            <?php }} ?>
                        </tbody>
                        <!-- End Body Table -->
                    </table>
                </div>
                <button class="attrib_submit button" type="submit">Submit</button>
            </form>
            <!--<button class="button add_new">Add New</button>-->
        </div>
    </div>
    <script>
        (function($) {
            $(document).ready(function(e){
                var click = false
                var items = $('tbody tr[attrid]').length
                // Add new row
                $('button.add_new').live('click',function(e){
                    $('td[colspan="7"]').parent().remove();
                    item_id = items+1;
                    $('table tbody').append('<tr attrid="'+ item_id +'"><td><input id="cb-select-'+ item_id +'" type="checkbox" name="attribute['+ item_id +']" value="'+ item_id +'" /></td><td><input type="text" name="attrib_name['+ item_id +']" /></td><td><input type="text" name="attrib_details['+ item_id +']" /><span></span></td><td><input type="text" name="attrib_description['+ item_id +']" /></td><td><select style="margin-top:-2px;" name="attrib_type['+ item_id +']"><?='"<option">'.implode("</option><option>", $field_types).'</option>';?></select></td><td><a class="add_new">Add New</a></td><td><input type="checkbox" name="attrib_required['+ item_id +']" /></td></tr>');
                    items = $('tbody tr[attrid]').length
                });

                $('a.add_new').live('click',function(e){
                    var attr_id = $(this).parent().parent().attr('attrid')
                    //console.log('adding to id: '+ item_id)
                    $(this).parent().append('<input type="text" name="attrib_values['+attr_id+'][]" />')

                    $( "tbody" ).sortable().disableSelection();

                });

                $( "tbody" ).sortable().disableSelection();
            });
        })(jQuery);
    </script>
    <?php
}

/**
 * This handles post action callback 
 * for saving attribute fields
 * 
 * @since  1.0
 * 
 * @return [type] [description]
 */
function save_user_details() {
    status_header(200);
    if (!isset($_POST['dtr_user_attrib'])) die("<br><br>Hmm .. looks like you didn't send any credentials.. No CSRF for you! ");
    if (!wp_verify_nonce($_POST['dtr_user_attrib'],'dtr_user_attrib')) die("<br><br>Hmm .. looks like you didn't send any credentials.. No CSRF for you! ");
    
    //request handlers should die() when they complete their task
    // update_option('dtrud_attributes', $_REQUEST);
    $master_attribute_list = array();
    if( !empty($_POST['attrib_name']) ){
        
        // Loop through attributes
        foreach($_POST['attrib_name'] as $id => $attribute_name){
            $_POST['attrib_name'] = strtolower(preg_replace('/[^\da-z_]/i', '_', $attribute_name ));
            // If delete checked, don't add to array
            if(isset($_POST['attrib_delete'][$id])){
                continue;
            }

            $master_attribute_list[$attribute_name]['details'] = $_POST['attrib_details'][$id];
            $master_attribute_list[$attribute_name]['description'] = $_POST['attrib_description'][$id];
            $master_attribute_list[$attribute_name]['type'] = $_POST['attrib_type'][$id];
            $master_attribute_list[$attribute_name]['values'] = array_filter($_POST['attrib_values'][$id], function($b){ if(strlen($b) > 0 ) return $b; });
            $master_attribute_list[$attribute_name]['required'] = $_POST['attrib_required'][$id];

        }

    }

    update_option('dtrud_attributes', $master_attribute_list);
?>
    <div class="updated">
        <p>Updated! Custom User Attributes have been saved!</p>
    </div>
<?php
}


/**
 * Saves the user data into database
 * 
 * @since  1.0
 * 
 * @param  [type] $user_id [description]
 * @return [type]          [description]
 */
function save_dtr_profile_fields( $user_id ) {
    global $wpdb;
    
    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    /* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
    #update_usermeta( $user_id, 'twitter', $_POST['twitter'] );
}

/**
 * Displays custom user fields in
 * dashboard
 * 
 * @since  1.0
 * 
 * @param  [type] $user [description]
 * @return [type]       [description]
 */
function get_dtr_profile_fields( $user ) { 
    global $wpdb;

    $meta = stripslashes(get_option('dtrud_meta'));
    $attributes = get_option('dtrud_attributes');

    ?>

    <h3>Extra User Details</h3>

    <table class="form-table">
    <?php
    //Get and set any values already sent
    #$user_extra = ( isset( $_POST['user_extra'] ) ) ? $_POST['user_extra'] : '';
    foreach($attributes as $attribute => $attr_details){
        $meta_value = get_the_author_meta( $attribute, $user->ID );
    ?>
    <tr>
        <th><label for="<?=$attribute;?>"><?php _e( $attr_details['details'], 'dtrud' ) ?></label></th>
        <td>
        <?php
        switch($attr_details['type']){
            case 'Multiple':
                echo '<div class="multiple">'.PHP_EOL;

                foreach($attr_details['values'] as $value){
                    $selected = '';
                
                    if( $meta_value[$value] == 'on' ){
                        $selected='checked="checked" ';
                    }
                
                    echo '<div class="multiple_row"><div class="multiple_left">'.$value.'</div><div class="multiple_right"><input type="checkbox" name="'.$attribute.'['.$value.']'.'" id="'.$attribute.'['.$value.']" '.$selected.' /></div></div>'.PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
                break;     

            case 'OnlyOne':
                echo '<div class="multiple">'.PHP_EOL;

                foreach($attr_details['values'] as $value){
                    $checked = '';
                
                    if( $meta_value[$value] == 'on' ){
                        $selected='checked="checked" ';
                    }
                    
                    echo '<div class="multiple_row"><div class="multiple_left">'.$value.'</div><div class="multiple_right"><input type="radio" name="'.$attribute.'" id="'.$attribute.'" '.$selected.'/></div></div>'.PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
                break;   

            case 'Paragraph':
                echo '<textarea name="'.$attribute.'" id="'.$attribute.'" class="regular-text" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'">'.esc_attr( stripslashes( $meta_value ) ).'</textarea><br />'.PHP_EOL;
                break;   

            case 'Dropdown':
                echo '<select name="'.$attribute.'" id="'.$attribute.'" class="regular-text" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'" />'.PHP_EOL;
                foreach($attr_details['values'] as $value){
                    $selected = '';
                
                    if( $meta_value == $value ){
                        $selected='selected="selected" ';
                    }                    

                    echo "<option {$selected}>{$value}</option>".PHP_EOL;
                }

                echo '</select>'.PHP_EOL;
                break;                
            
            case 'Checkbox':
                $checked = '';
                
                if( $meta_value == 'on' ){
                    $checked = 'checked="checked" ';
                }
                echo '<input type="checkbox" name="'.$attribute.'" id="'.$attribute.'" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'" '.$checked.'/><br />'.PHP_EOL;
                break;           
            
            case 'Date':
                echo '<input type="date" name="'.$attribute.'" id="'.$attribute.'" class="regular-text"  value="'.esc_attr( stripslashes( $meta_value ) ).'" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'" />'.PHP_EOL;
                break;
            default:
                echo '<input type="text" name="'.$attribute.'" id="'.$attribute.'" class="regular-text"  value="'.esc_attr( stripslashes( $meta_value ) ).'"  required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'"/>'.PHP_EOL;
                break;
        }
        ?>
            <span class="description"><?=$attr_details['description'];?></span>
        </td>
    <?php } ?>
    </table> 

    <script>
    (function($){
        $(document).ready(function(e){

            // Set datepicker to class datepicker
            //$( ".datepicker" ).datepicker();

            // Custom meta 
            <?=$meta;?>
        });
    })(jQuery);
    </script>
    <?php 
}


/**
 * Sets fields to be displayed on user registration
 * 
 * @return
 */
function dtr_registration_fields() {
    global $wpdb;

    // Load meta data
    $meta = stripslashes(get_option('dtrud_meta'));
    $attributes = get_option('dtrud_attributes');

    ?>
    <style type="text/css">
    .multiple_left{
        width:73%;
        display:inline-block;
        padding-left:2%;
        font-weight: bold;
    }
    .multiple_right{
        width:25%;
        display:inline-block;
    }
    div.multiple_row:hover {
        background-color: #f9f9f9;
    }
    input[type="checkbox"] {
        margin-top: 3px;
    }
    div.multiple, label input[type="checkbox"]{
        margin-bottom: 12px !important;
    }
    </style>
    <?php
    //Get and set any values already sent
    foreach($attributes as $attribute => $attr_details){

        if( $attr_details['type'] == 'Checkbox' && sizeof($attr_details['values']) > 0){
            $attr_details['type'] = 'Multiple';
        }
    ?>
    <p>
        <label for="<?=$attribute;?>"><?php _e( $attr_details['details'], 'dtrud' ) ?><br />
        <?php
        
        switch($attr_details['type']){
            case 'Multiple':
                echo '<div class="multiple">'.PHP_EOL;
                foreach($attr_details['values'] as $value){
                    $checked = '';
                    
                    if( $_POST[$attribute][$value] == 'on' ){
                        $checked = 'checked="checked" ';
                    }

                    echo '<div class="multiple_row"><div class="multiple_left">'.$value.'</div><div class="multiple_right"><input type="checkbox" name="'.$attribute.'['.$value.']" id="'.$attribute.'['.$attr_details[$value].']" '.$checked.'/></div></div>'.PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
                break;     

            case 'OnlyOne':
                echo '<div class="multiple">'.PHP_EOL;
                foreach($attr_details['values'] as $value){
                    $checked = '';
                
                    if( $_POST[$attribute][$value] == 'on' ){
                        $selected='checked="checked" ';
                    }
                
                    echo '<div class="multiple_row"><div class="multiple_left">'.$value.'</div><div class="multiple_right"><input type="radio" name="'.$attribute.'['.$value.']'.'" id="'.$attribute.'['.$value.']" '.$selected.' /></div></div>'.PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
                break;     

            case 'Dropdown':
                echo '<select name="'.$attribute.'" id="'.$attribute.'" class="input" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'" />'.PHP_EOL;
                foreach($attr_details['values'] as $value){
                    
                    $selected = '';
                    if( $_POST[$attribute] == $value){
                        $selected='selected="selected" ';
                    }

                    echo "<option {$selected}>{$value}</option>".PHP_EOL;
                }
                echo '</select>'.PHP_EOL;
                break;                
            
            case 'Checkbox':
                $checked = '';
                
                if( $_POST[$attribute] == 'on' ){
                    $checked = 'checked="checked" ';
                }

                echo '<input type="checkbox" name="'.$attribute.'" id="'.$attribute.'" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'"  '.$checked.'/><br />'.PHP_EOL;
                break;           
            
            case 'Date':
                echo '<input type="date" name="'.$attribute.'" id="'.$attribute.'" class="input"  value="'.esc_attr( stripslashes( $_POST[$attribute] ) ).'" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'" />'.PHP_EOL;
                break;
                
            case 'Paragraph':
                echo '<textarea name="'.$attribute.'" id="'.$attribute.'" class="input" required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'">'.esc_attr( stripslashes( $_POST[$attribute] ) ).'</textarea>'.PHP_EOL;
                break; 

            default:
                echo '<input type="text" name="'.$attribute.'" id="'.$attribute.'" class="input"  value="'.esc_attr( stripslashes( $_POST[$attribute] ) ).'"  required="'.($attr_details['required'] == 'on' ? 'true' : 'false' ).'"/>'.PHP_EOL;
                break;
        }

        ?>
        </label>
    </p>


    <?php } ?>
    <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js?ver=1.8.3'></script>
    <script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
    <script>
    (function($){
        $(document).ready(function(e){

            // Hack to hide username and set username to email
            $('#user_login').parent().hide();
            $('#user_email').focusout(function(e){
                $('#user_login').val($(this).val());
            });

            // Set datepicker to class datepicker
            $( ".datepicker" ).datepicker();

            // Custom meta 
            <?=$meta;?>
        });
    })(jQuery);
    </script>

    <?php
}


/**
 * Validates user registration form submissions
 * 
 * @since  1.0
 * 
 * @param  [type] $errors               [description]
 * @param  [type] $sanitized_user_login [description]
 * @param  [type] $user_email           [description]
 * @return [type]                       [description]
 */
function dtr_registration_errors ($errors, $sanitized_user_login, $user_email) {

    $attributes = get_option('dtrud_attributes');

    foreach($attributes as $attribute => $attr_details){
        if( $attrib_details['required'] ==  'on' && empty( $_POST[$attribute] )) {
            $errors->add( $attribute.'_error', __('<strong>ERROR</strong>: You must include \''.$attr_details['details'].'\'','dtrud') );

        }
    }
    
    return $errors;
}

/**
 * Saves and stores user registration 
 * form submission
 * 
 * @since  1.0
 * 
 * 
 * @return [type] [description]
 */
function dtr_registration_save( $user_id ){
    $attributes = get_option('dtrud_attributes');

    foreach($attributes as $attribute => $attr_details){
        update_user_meta($user_id, $attribute, $_POST[$attribute]);
    }

}
