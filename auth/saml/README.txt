SAML Authentication for Moodle
-------------------------------------------------------------------------------
license: http://www.gnu.org/copyleft/gpl.html GNU Public License

Changes:
- 2008-10    : Created by Ny Media AS
- 2008-11-03 : Updated by Ny Media AS
- 2009-07-29 : added configuration options for sslib path and config path
               tightened up the session switching between ss and moodle
               Piers Harding <piers@catalyst.net.nz>
- 2010-11    : Rewrited by Yaco Sistemas.

Requirements:
- SimpleSAML (http://rnd.feide.no/simplesamlphp). Tested with version > 1.7
- SAML Enrollment for Moodle module


Notes: 
- Uses IdP attribute "eduPersonPrincipalName" as username by default

Install instructions:

Check moodle_auth_saml.txt


Important for enrollment!!
==========================

This plugin suppose that the IdP send the courses data of the user in a attribute that
can be configured but the pattern of the expected data is always: 
<course_id>:<period>:<role>:<status>
You can change this pattern editing the file auth/saml/course_mapping.php
