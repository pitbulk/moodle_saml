<?php

$string['auth_samltitle']  = 'Autenticación SAML';
$string['auth_samldescription'] = 'Autenticación SSO usando SimpleSAML';

$string['auth_saml_samllib'] = 'Ruta de librería SimpleSAMLPHP';
$string['auth_saml_samllib_description'] = 'Ruta de acceso a la librería para el entorno SimpleSAMLPHP que deseas, por ejemplo: /var/www/sp/simplesamlphp/lib';
$string['auth_saml_errorbadlib'] = "El directorio de la librería SimpleSAMLPHP {\$a} no es correcta";

$string['auth_saml_sp_source'] = 'Fuente SimpleSAMLPHP SP';
$string['auth_saml_sp_source_description'] = 'Selecciona la fuente SP que quieres conectar a moodle. (Las fuentes están en /config/authsources.php).';
$string['auth_saml_errorsp_source'] = "La fuente SimpleSAMLPHP {\$a} no es correcta";

$string['auth_saml_db_reset_button'] = 'Pulse para restablecer los valores iniciales';
$string['auth_saml_db_reset_error'] = 'Error al restablecer los valores del plugin saml';

$string['auth_saml_form_error'] = 'Parece que hay algunos errores en el formulario. Por favor, reviselo para corregirlos';

$string['auth_saml_dosinglelogout'] = 'Deslogue global. Single Log out';
$string['auth_saml_dosinglelogout_description'] = 'Selecciona para habilitar el deslogueo global (single log out). Al desloguear de Moodle lo harás también del proveedor de identidad y de todos los proveedores de servicios conectados en los que estés logueado';

$string['auth_saml_username'] = 'Correspondencia del nombre de usuario SAML';
$string['auth_saml_username_description'] = 'Atributo SAML que se asigna como username de Moodle - el valor predeterminado es el eduPersonPrincipalName';
$string['auth_saml_username_not_found'] = "El IdP ha devuelto un conjunto de datos del usuario que no contiene el campo ({\$a}) que se estableció en la configuración que se usase como username de Moodle. Este campo es obligatorio para loguearte";

$string['auth_saml_supportcourses'] = 'Soportar matriculación SAML';
$string['auth_saml_supportcourses_description'] = 'Selecciona Interna o Externa para que Moodle a través del módulo automatricule al usuario (Usa Externa si tu asignación de cursos y roles está en una base de datos externa';

$string['auth_saml_courses'] = 'Correspondencia de cursos SAML';
$string['auth_saml_courses_description'] = 'Atributo SAML que contiene los datos de los cursos (por defecto es schacUserStatus)';
$string['auth_saml_courses_not_found'] = "El IdP ha devuelto un conjunto de datos que no contiene el campo donde Moodle espera encontrar los cursos ({\$a}). Este campo es obligatorio para automatricular al usuario.";

$string['auth_saml_course_field_id'] = 'Campo utilizado para identificar un curso en Moodle';
$string['auth_saml_course_field_id_description'] = 'Podemos asociar el curso SAML con el curso Moodle utilizando el nombre corto (shortname) o el  número ID (idnumber)';


$string['auth_saml_logo_path'] = 'Imagen SAML';
$string['auth_saml_logo_path_description'] = 'Ruta de la imagen para el botón de inicio de sesión de SAML';

$string['auth_saml_logo_info'] = 'Descripción del login de SAML';
$string['auth_saml_logo_info_description'] = 'Descripción que se muestra a continuación del botón de inicio de sesión del SAML';

$string['auth_saml_autologin'] = 'SAML automatic login';
$string['auth_saml_autologin_description'] = 'Automatically redirect to SAML idP without showing a login form';

$string['auth_saml_ignoreinactivecourses'] = 'Ignorar Cursos Inactivos';
$string['auth_saml_ignoreinactivecourses_description'] = "Si no está activado el plugin dará de baja a los cursos 'inactivos'";

$string['auth_saml_not_authorize'] = "{\$a} no tiene activo ningún curso del Campus Andaluz Virtual";

$string['auth_saml_error_executing'] = "Error al ejecutar ";

$string['auth_saml_mapping_dsn_description'] = 'Cadena del Nombre del Origen de Datos (dsn) para conectar con la base de datos de la asignación de cursos/roles.
(el dsn debe ser una ruta absoluta en caso de estar usando SQLite)'; 

$string['auth_saml_course_mapping_dsn'] = 'Curso dsn'; 
$string['auth_saml_role_mapping_dsn'] = 'Rol dsn'; 

$string['auth_saml_course_mapping_sql'] = 'Curso sql'; 
$string['auth_saml_role_mapping_sql'] = 'Rol sql'; 

$string['auth_saml_mapping_dsn_examples'] = 'mysql://moodleuser:moodlepass@localhost/saml_course_mapping
sqlite:/<path-to-db>/mapping.sqlite3
oci8://user:pwd@host/sid
postgresql7://user:pwd@host/sid
';

$string['auth_saml_mapping_sql_examples'] = 'SELECT field1 as lms_course_id, field2 as saml_course_id, field3 as saml_course_period FROM course_mapping
SELECT field1 as lms_role, field2 as saml_role from role_mapping';


$string['auth_saml_mapping_external_warning'] = 'Nota: Cuando la base de datos de las correspondencias y de Moodle son del mismo tipo, la conexión falla. Así que en este caso lo mejor es usar una correspondencia de cursos interno y previamente volcar todos los datos dentro de una base de datos manualmente';

$string['auth_saml_errorsamlexternal'] = 'Establece que la asignación de fuente de las correspondencias para el curso y el rol debería ser externo y así podrás específicar todos los parámetros de consultas DSN y SQL.';

$string['auth_saml_sucess_creating_course_mapping'] = 'Tabla de correspondencias de cursos creada en la basa de datos de moodle';
$string['auth_saml_error_creating_course_mapping'] = 'Error creando correspondencias de cursos en la base de datos de moodle';

$string['auth_saml_sucess_creating_role_mapping'] = 'Tabla de cursos de roles creada en la base de datos de moodle';
$string['auth_saml_error_creating_role_mapping'] = 'Error creando correspondencias de roles en la base de datos de moodle';

$string['auth_saml_error_executing_course_mapping_query'] ='Error ejecutando la consulta de las correspondencias de los cursos';
$string['auth_saml_error_attribute_course_mapping'] = 'Error en los nombres de atributo (índices) de la tabla de correspondencias de los cursos. Comprueba la sintaxis de externalcoursemappingsql';

$string['auth_saml_error_executing_role_mapping_query'] ='Error ejecutando la consulta de correspondencias de los roles';
$string['auth_saml_error_attribute_role_mapping'] = 'Error en los nombres de atributos (índices) de la tabla de correspondencias de rol. Comprueba la sintaxis externalrolemappingsql';


$string['auth_saml_error_role_not_found'] = "Error al inscribirse. El rol {\$a} no existe en Moodle";

$string['auth_saml_initialize_roles'] = 'Inicializar roles';

$string['auth_saml_missed_data'] = 'A los siguientes datos les faltan atributos: ';

$string['auth_saml_duplicated_saml_data'] = 'El siguiente dato saml está duplicado: ';
$string['auth_saml_duplicated_lms_data'] = 'El siguiente dato lms está duplicado: ';

$string['auth_saml_course_not_found'] = "El curso saml2 {\$a->course} no fué encontrado para el usuario {\$a->user}\n";

$string['auth_saml_disable_debugdisplay'] = ' * Desabilita debugdisplay para no mostrar errores del proceso de login/matriculación';
$string['auth_saml_error_authentication_process'] = "Error en el proceso de autenticación {\$a}";
$string['auth_saml_error_complete_user_data'] = "Error al completar los datos del usuario {\$a}";
$string['auth_saml_error_complete_user_login'] = "Error al completar el login del usuario {\$a}";

$string['auth_saml_logfile'] = 'Ruta del fichero de log del plugin SAML';
$string['auth_saml_logfile_description'] = 'Establece un nombre de fichero si tu quieres loggear los errores del plugin saml en un fichero diferente que el syslog (Establece una ruta absoluta o Moodle guardará este fichero dentro de la carpeta moodledata)';

$string['auth_saml_samlhookfile'] = 'Ruta del fichero del hook del plugin SAML';
$string['auth_saml_samlhookfile_description'] = 'Establece la ruta si quieres usar un fichero hook que contiene tus funciones específicas';
$string['auth_saml_errorbadhook'] = "Incorrect SAML plugin hook file: {\$a}";

$string['pluginname'] = 'Autenticación SAML';
