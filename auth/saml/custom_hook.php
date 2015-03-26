<?php
/*
SAML Authentication Plugin Custom Hook


This file acts as a hook for the SAML Authentication plugin. The plugin will
call the functions defined in this file in certain points in the plugin
lifecycle.

Use this sample file as a template. You should copy it and not modify it
in place since you may lost your changes in future updates.

To use this hook you have to go to the config form in the admin interface of
Moodle and set the full path to this file. Please note that the default value
for such a field is this custom_hook.php file itself.

You should not change the name of the funcions since that's the API the plugin
expect to exist and to use.

Read the comments of each function to discover when they are called and what
are they for.
*/


/*
 name: saml_hook_attribute_filter
 arguments:
   - $saml_attributes: array of SAML attributes
 return value:
   - nothing
 purpose: this function allows you to modify the array of SAML attributes.
          You can change the values of them (e.g. removing the non desired
          urn parts) or you can even remove or add attributes on the fly.
*/
function saml_hook_attribute_filter(&$saml_attributes) {

    // Nos quedamos sólamente con el DNI dentro del schacPersonalUniqueID
    if(isset($saml_attributes['schacPersonalUniqueID'])) {
        foreach($saml_attributes['schacPersonalUniqueID'] as $key => $value) {
            $data = array();
            if(preg_match('/urn:mace:terena.org:schac:personalUniqueID:es:(.*):(.*)/', $value, $data)) {
                $saml_attributes['schacPersonalUniqueID'][$key] = $data[2];
                //DNI sin letra
                //$saml_attributes['schacPersonalUniqueID'][$key] = substr($value[2], 0, 8);
            }
            else {
                unset($saml_attributes['schacPersonalUniqueID'][$key]);
            }
        }
    }

    // Pasamos el irisMailMainAddress como mail si no existe
    if(!isset($saml_attributes['mail'])) {
        if(isset($saml_attributes['irisMailMainAddress'])) {
            $saml_attributes['mail'] = $saml_attributes['irisMailMainAddress'];
        }
    }


    // Pasamos el uid como eduPersonPrincipalName o como eduPersonTargetedID
    if(!isset($saml_attributes['eduPersonPrincipalName'])) {
        if(isset($saml_attributes['uid'])) {
            $saml_attributes['eduPersonPrincipalName'] = $saml_attributes['uid'];
        }
        else if (isset($saml_attributes['eduPersonTargetedID'])) {
            $saml_attributes['eduPersonPrincipalName'] = $saml_attributes['eduPersonTargetedID'];
        }
        else if (isset($saml_attributes['mail'])) {
            $saml_attributes['eduPersonPrincipalName'] = $saml_attributes['mail'];
        }
    }


    // Pasamos el uid como eduPersonPrincipalName

    if(!isset($saml_attributes['eduPersonPrincipalName'])) {
        if(isset($saml_attributes['uid'])) {
            $saml_attributes['eduPersonPrincipalName'] = $saml_attributes['uid'];
        }
        else if (isset($saml_attributes['mail'])) {
            $saml_attributes['eduPersonPrincipalName'] = $saml_attributes['mail'];
        }
    }

}

/*
 name: saml_hook_user_exists
 arguments:
   - $username: candidate name of the current user
   - $saml_attributes: array of SAML attributes
   - $user_exists: true if the $username exists in Moodle database
 return value:
   - true if you consider that this username should exist, false otherwise.
 purpose: this function let you change the logic by which the plugin thinks
          the user exists in Moodle. You can even change the username if
          the user exists but you want to recreate with another name.
*/
function saml_hook_user_exists(&$username, $saml_attributes, $user_exists) {
    return true;
}

/*
 name: saml_hook_authorize_user
 arguments:
    - $username: name of the current user
    - $saml_attributes: array of SAML attributes
    - $authorize_user: true if the plugin thinks this user should be allowed
 return value:
    - true if the user should be authorized or an error string explaining
      why the user access should be denied.
 purpose: use this function to deny the access to the current user based on
          the value of its attributes or any other reason you want. It is
	  very important that this function return either true or an error
	  message.
*/
function saml_hook_authorize_user($username, $saml_attributes, $authorize_user) {
    return true;
}

/*
 name: saml_hook_post_user_created
 arguments:
   - $user: object containing the Moodle user
 return value:
   - nothing
 purpose: use this function if you want to make changes to the user object
          or update any external system for statistics or something similar.
*/
function saml_hook_post_user_created($user) {

}
