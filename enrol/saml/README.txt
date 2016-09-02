SAML Enrollment for Moodle
-------------------------------------------------------------------------------
license: http://www.gnu.org/copyleft/gpl.html GNU Public License

Changes:
- 2010-11    : Created by Yaco Sistemas.

Requirements:
- SimpleSAML (http://rnd.feide.no/simplesamlphp). Tested with version > 1.7
- SAML Authentication for Moodle module

This plugin require a simplesamlphp instance configured as SP
(http://simplesamlphp.org/docs/trunk/simplesamlphp-sp)

Tested in moodle2.0.3, 2.1.2, 2.2.3

Install instructions:

Check moodle_enrol_saml.txt


Important for enrollment!!
==========================

This plugin suppose that the IdP send the courses data of the user in a attribute that
can be configured but the pattern of the expected data is always defined per the RFC:
https://tools.ietf.org/html/rfc6338
e.g.,
urn:mace:terena.org:schac:userStatus:(.+):(.+):(.+):(.+):(.+):(.+)
You can change this pattern editing the file auth/saml/course_mapping.php
