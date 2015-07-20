<script src="../auth/saml/resources/moodle_saml.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="../auth/saml/resources/ui.theme.css" />
<link rel="stylesheet" type="text/css" href="../auth/saml/resources/ui.core.css" />
<link rel="stylesheet" type="text/css" href="../auth/saml/resources/ui.tabs.css" />
<link rel="stylesheet" type="text/css" href="../auth/saml/resources/moodle_saml.css" />

<script type="text/javascript" src="../auth/saml/resources/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../auth/saml/resources/jquery-ui-1.7.2.custom.min.js"></script>


<?php
/**
 * @author Erlend Strømsvik - Ny Media AS
 * @author Piers Harding - made quite a number of changes
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth/saml
 *
 * Authentication Plugin: SAML based SSO Authentication
 *
 * Authentication using SAML2 with SimpleSAMLphp.
 *
 * Based on plugins made by Sergio Gómez (moodle_ssp) and Martin Dougiamas (Shibboleth).
 *
 * 2008-10  Created
 * 2009-07  Added new configuration options
**/

    global $CFG;

    require_once("courses.php");
    require_once("roles.php");

    // Get saml paramters stored in the saml_config.php
    if(file_exists($CFG->dataroot.'/saml_config.php')) {
        $contentfile = file_get_contents($CFG->dataroot.'/saml_config.php');
        $saml_param = json_decode($contentfile);
    } else if (file_exists('saml_config.php')) {
        $contentfile = file_get_contents('saml_config.php');
        $saml_param = json_decode($contentfile);
    } else {
        $saml_param = new stdClass();
    }

    // Set to defaults if undefined
    if (!isset ($saml_param->samllib)) {
        if(isset ($config->samllib)) {
            $saml_param->samllib = $config->samllib;
        }
        else {
            $saml_param->samllib = '/var/www/sp/simplesamlphp/lib';
        }
    }
    if (!isset ($saml_param->sp_source)) {
        if(isset ($config->sp_source)) {
            $saml_param->sp_source = $config->sp_source;
        }
        else {
            $saml_param->sp_source = 'saml';
        }
    }
    if (!isset ($saml_param->dosinglelogout)) {
        if(isset ($config->dosinglelogout)) {
            $saml_param->dosinglelogout = $config->dosinglelogout;
        }
        else {
            $saml_param->dosinglelogout = false;
        }
    }
    if (!isset ($config->username)) {
        $config->username = 'eduPersonPrincipalName';
    }
    if (!isset ($config->notshowusername)) {
        $config->notshowusername = 'none';
    }
    if (!isset ($config->supportcourses)) {
        $config->supportcourses = 'nosupport';
    }
    if (!isset ($config->syncusersfrom)) {
        $config->syncusersfrom = '';
    }
    if (!isset ($config->samlcourses)) {
        $config->samlcourses = 'schacUserStatus';
    }
    if (!isset ($config->samllogoimage)) {
        $config->samllogoimage = 'logo.gif';
    }
    if (!isset ($config->samllogoinfo)) {
        $config->samllogoinfo = 'SAML login';
    }
    if (!isset ($config->autologin)) {
        $config->autologin = false;
    }
    if (!isset ($config->samllogfile)) {
        $config->samllogfile = '';
    }
    if (!isset ($config->samlhookfile)) {
        $config->samlhookfile = $CFG->dirroot . '/auth/saml/custom_hook.php';
    }
    if (!isset ($config->moodlecoursefieldid)) {
        $config->moodlecoursefieldid = 'shortname';
    }
    if (!isset ($config->ignoreinactivecourses)) {
        $config->ignoreinactivecourses = true;
    }
    if (!isset ($config->externalcoursemappingdsn)) { 
        $config->externalcoursemappingdsn = ''; 
    }
    if (!isset ($config->externalrolemappingdsn)) { 
        $config->externalrolemappingdsn = ''; 
    }
    if (!isset ($config->externalcoursemappingsql)) { 
        $config->externalcoursemappingsql = ''; 
    }
    if (!isset ($config->externalrolemappingsql)) { 
        $config->externalrolemappingsql = ''; 
    }

?>

<div align="right">
    <input type="submit" name="auth_saml_db_reset" value="<?php print_string('auth_saml_db_reset_button', 'auth_saml'); ?>" />
</div>

<table cellspacing="0" cellpadding="5" border="0">

<?php
if (isset($err) && !empty($err)) {

    require_once('error.php');
    auth_saml_error($err, false, $config->samllogfile);

    echo '
    <tr>
        <td class="center" colspan="4" style="background-color: red; color: white;text-">
    ';
    if(isset($err['reset'])) {
        echo $err['reset'];
    }
    else {
        print_string("auth_saml_form_error", "auth_saml");
    }
    echo '
        </td>
    </tr>
    ';
}
?>

<tr valign="top" class="required">
    <td class="right"><?php print_string("auth_saml_samllib", "auth_saml"); ?>:</td>
    <td>
        <input name="samllib" type="text" size="30" value="<?php echo $saml_param->samllib; ?>" />
        <?php
        if (isset($err['samllib'])) {
            formerr($err['samllib']);
        }
        ?>
    </td>
    <td><?php print_string("auth_saml_samllib_description", "auth_saml"); ?></td>
</tr>

<tr valign="top" class="required">
    <td class="right"><?php print_string("auth_saml_sp_source", "auth_saml"); ?>:</td>
    <td>
        <input name="sp_source" type="text" size="10" value="<?php echo $saml_param->sp_source; ?>" />
        <?php
        if (isset($err['sp_source'])) {
            formerr($err['sp_source']);
        }
        ?>
    </td>
    <td><?php print_string("auth_saml_sp_source_description", "auth_saml"); ?></td>
</tr>

<tr valign="top" class="required">
    <td class="right"><?php print_string("auth_saml_username", "auth_saml"); ?>:</td>
    <td>
        <input name="username" type="text" size="30" value="<?php echo $config->username; ?>" />
    </td>
    <td><?php print_string("auth_saml_username_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_dosinglelogout", "auth_saml"); ?>:</td>
    <td>
        <input name="dosinglelogout" type="checkbox" <?php if($saml_param->dosinglelogout) echo 'checked="CHECKED"'; ?> />
    </td>
    <td><?php print_string("auth_saml_dosinglelogout_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_logo_path", "auth_saml"); ?>:</td>
    <td>
       <input name="samllogoimage" type="text" size="30" value="<?php echo $config->samllogoimage; ?>" />
    </td>
    <td><?php print_string("auth_saml_logo_path_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_logo_info", "auth_saml"); ?>:</td>
    <td>
       <textarea name="samllogoinfo" type="text" size="30" rows="5" cols="30"><?php echo $config->samllogoinfo; ?></textarea>
    </td>
    <td><?php print_string("auth_saml_logo_info_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_autologin", "auth_saml"); ?>:</td>
    <td>
        <input name="autologin" type="checkbox" <?php if($config->autologin) echo 'checked="CHECKED"'; ?> />
    </td>
    <td><?php print_string("auth_saml_autologin_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_logfile", "auth_saml"); ?>:</td>
    <td>
       <input name="samllogfile" type="text" size="30" value="<?php echo $config->samllogfile; ?>" />
    </td>
    <td><?php print_string("auth_saml_logfile_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_samlhookfile", "auth_saml"); ?>:</td>
    <td>
       <input name="samlhookfile" type="text" size="30" value="<?php echo $config->samlhookfile; ?>" />
       <?php
            if (isset($err['samlhookerror'])) {
                formerr('<p>' . $err['samlhookerror'] . '</p>');
            }
       ?>

    </td>
    <td><?php print_string("auth_saml_samlhookfile_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string("auth_saml_supportcourses", "auth_saml"); ?>:</td>
    <td>
       <select name="supportcourses" onchange="javascript:{
    document.getElementById('coursemapping_li').style.display = (this.value == 'internal') ? 'inline' : 'none';
    document.getElementById('rolemapping_li').style.display = (this.value == 'internal') ? 'inline' : 'none';
    document.getElementById('externalmapping_li').style.display = (this.value == 'external') ? 'inline' : 'none';
    document.getElementById('samlcourses_tr').style.display = (this.value == 'nosupport') ? 'none' : '';
    document.getElementById('moodlecoursefieldid_tr').style.display = (this.value == 'nosupport') ? 'none' : '';
    document.getElementById('ignoreinactivecourses_tr').style.display = (this.value == 'nosupport') ? 'none' : '';

    $('#tabdiv').tabs('select', 0);}" >
            <option name="nosupport" value="nosupport" <?php if($config->supportcourses == 'nosupport') echo 'selected="selected"'; ?> >No Support</option>
            <option name="internal" value="internal" <?php if($config->supportcourses == 'internal') echo 'selected="selected"'; ?> >Internal</option>
            <option name="external" value="external" <?php if($config->supportcourses == 'external') echo 'selected="selected"'; ?> >External</option>
        </select>
    </td>
    <td><?php print_string("auth_saml_supportcourses_description", "auth_saml"); ?></td>
</tr>

<tr valign="top">
    <td class="right"><?php print_string('auth_saml_syncusersfrom', 'auth_saml'); ?>:</td>
    <td>
        <select name="syncusersfrom">
        <option name="none" value="">Disabled</option>
        <?php
            foreach (get_enabled_auth_plugins() as $name) {
                $plugin = get_auth_plugin($name);
                if (method_exists($plugin, 'sync_users')) {
                    print '<option name="' . $name . '" value ="' . $name . '" ' . (($config->syncusersfrom == $name) ? 'selected="selected"' : '') . '>' . $name . '</option>';
                }
            }
        ?>
        </select>
    </td>
    <td><?php print_string("auth_saml_syncusersfrom_description", "auth_saml"); ?></td>
</tr>

<tr valign="top" class="required" id="samlcourses_tr" <?php echo ($config->supportcourses == 'nosupport'? 'style="display:none;"' : '') ?> >
    <td class="right"><?php print_string("auth_saml_courses", "auth_saml"); ?>:</td>
    <td>
       <input name="samlcourses" type="text" size="30" value="<?php echo $config->samlcourses; ?>" />
    </td>
    <td><?php print_string("auth_saml_courses_description", "auth_saml"); ?></td>
</tr>

<tr valign="top" id="moodlecoursefieldid_tr" <?php echo ($config->supportcourses == 'nosupport' ? 'style="display:none;"' : '') ; ?> >
    <td class="right"><?php print_string("auth_saml_course_field_id", "auth_saml"); ?>:</td>
    <td>
       <select name="moodlecoursefieldid">
            <option name="shortname" value="shortname" <?php if($config->moodlecoursefieldid == 'shortname') echo 'selected="selected"'; ?> >Short Name</option>
            <option name="idnumber" value="idnumber" <?php if($config->moodlecoursefieldid == 'idnumber') echo 'selected="selected"'; ?> >Number ID</option>
       </select>
    </td>
    <td><?php print_string("auth_saml_course_field_id_description", "auth_saml"); ?></td>
</tr>

<tr valign="top" id="ignoreinactivecourses_tr" <?php echo ($config->supportcourses == 'nosupport' ? 'style="display:none;"' : '') ; ?> >
    <td class="right"><?php print_string("auth_saml_ignoreinactivecourses", "auth_saml"); ?>:</td>
    <td>
        <input name="ignoreinactivecourses" type="checkbox" <?php if($config->ignoreinactivecourses) echo 'checked="checked"'; ?>/>
    </td>
    <td><?php print_string("auth_saml_ignoreinactivecourses_description", "auth_saml"); ?></td>
</tr>

</table>

<script type="text/javascript">

$(document).ready(function() {
    $("#tabdiv").tabs();
});


</script>

<div id="mapping_container">
<div id="tabdiv">
<ul>
    <li><a href="#datamapping">User Data Mapping</a></li>
    <li id="coursemapping_li" <?php if($config->supportcourses != 'internal') echo 'style="display:none;"'; ?> ><a <?php echo (isset($err['course_mapping'])  || isset($err['missed_course_mapping']) || isset($err['course_mapping_db']) ? 'style="color:red;"' : ''); ?> href="#coursemapping">Course Mapping</a></li>
    <li id="rolemapping_li" <?php if($config->supportcourses  != 'internal') echo 'style="display:none;"' ?> ><a <?php echo (isset($err['role_mapping'])  || isset($err['missed_role_mapping']) || isset($err['role_mapping_db']) ? 'style="color:red;"' : ''); ?> href="#rolemapping">Role Mapping</a></li>
    <li id="externalmapping_li" <?php if($config->supportcourses  != 'external') echo 'style="display:none;"'; ?> ><a <?php echo ($config->supportcourses  == 'external' && isset($err['samlexternal']) ? 'style="color:red;"' : ''); ?> href="#externalmapping">External Mapping Info</a></li>
</ul>

<div id="datamapping">
    <table class="center">
    <?php
    print_auth_lock_options('saml', $user_fields, '<!-- empty help -->', true, false);
    ?>
    </table>
</div>

<div id="coursemapping">

<?php 

if(isset($err['course_mapping_db']) && in_array("error_creating_course_mapping", $err['course_mapping_db'])) {
    echo '<span class="error">';
    print_string("auth_saml_error_creating_course_mapping", "auth_saml");
    echo '</span>';
}
else {
    echo '<table id="newcourses" class="center">';
        print_course_mapping_options($course_mapping, $config, $err);
    echo '
        </table>
        <div style="padding-left: 44%;"><input type="submit" name="deletecourses" value="Delete selected" /></div>
         ';
}
?>
</div>

<div id="rolemapping">
<?php
if(isset($err['role_mapping_db']) && in_array("error_creating_role_mapping", $err['role_mapping_db'])) {
    echo '<span class="error">';
    print_string("auth_saml_error_creating_role_mapping", "auth_saml");
    echo '</span>';
}
else {
    echo '<table id="newroles" class="center">';
          print_role_mapping_options($role_mapping, $config, $err);
    echo '</table>
          <div style="padding-top: 10px; padding-left: 44%;"><input type="submit" name="deleteroles" value="Delete selected" /></div>';

    if($config->supportcourses == 'internal') {
        echo  '<div align="left">
                <input type="submit" name="initialize_roles" value="';
                print_string('auth_saml_initialize_roles', 'auth_saml');
        echo '" />
              </div>
             ';
    }
}
?>
</div>

<div id="externalmapping">
    <?php
        if (isset($err['samlexternal'])) {
            formerr($err['samlexternal']);
        }
    ?>
    <table id="externalmappinginfo" class="center">
        <tr valign="top">
            <td colspan="2"><?php print_string("auth_saml_mapping_dsn_description", "auth_saml"); ?></td>
        </tr>
        <tr valign="top" class="required">
            <td class="right"><?php print_string("auth_saml_course_mapping_dsn", "auth_saml"); ?>:</td>
            <td>
               <input name="externalcoursemappingdsn" type="text" size="55" value="<?php echo $config->externalcoursemappingdsn; ?>" />
            </td>
        </tr>
        <tr class="required">    
            <td class="right" valign="top"><?php print_string("auth_saml_course_mapping_sql", "auth_saml"); ?>:</td>
            <td>
               <textarea name="externalcoursemappingsql" type="text" size="55" rows="3" cols="55"><?php echo $config->externalcoursemappingsql; ?></textarea>            
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2"></td>
        </tr>
        <tr class="required">    
            <td class="right"><?php print_string("auth_saml_role_mapping_dsn", "auth_saml"); ?>:</td>
            <td>
               <input name="externalrolemappingdsn" type="text" size="55" value="<?php echo $config->externalrolemappingdsn; ?>" />
            </td>
        </tr>
        <tr class="required">    
            <td class="right" valign="top"><?php print_string("auth_saml_role_mapping_sql", "auth_saml"); ?>:</td>
            <td>
               <textarea name="externalrolemappingsql" type="text" size="55" rows="3" cols="55"><?php echo htmlspecialchars($config->externalrolemappingsql); ?></textarea>
            </td>
        </tr>
    </table>
    <p>DSN and SQL examples:</p> 
<?php
    echo "<p>" . htmlspecialchars(get_string("auth_saml_mapping_dsn_examples", "auth_saml")) . "</p>";
    echo "<p>" . htmlspecialchars(get_string("auth_saml_mapping_sql_examples", "auth_saml")) . "</p>";
    echo "<p>" . htmlspecialchars(get_string("auth_saml_mapping_external_warning", "auth_saml")) . "</p>";
?>
</div>

</div>
</div>
