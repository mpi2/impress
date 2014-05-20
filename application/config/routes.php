<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'impress';
$route['ontologysearch'] = $route['default_controller'] . '/ontologies';
$route['pipelines'] = $route['default_controller'] . '/pipelines';
$route['about'] = $route['default_controller'] . '/about';
$route['glossary'] = $route['default_controller'] . '/glossary';
$route['contact'] = $route['default_controller'] . '/contact';
$route['procedures/(:num)'] = $route['default_controller'] . '/listProcedures/$1';
$route['parameters/(:num)'] = $route['default_controller'] . '/listParameters/$1';
$route['parameters/(:num)/(:num)'] = $route['default_controller'] . '/listParameters/$1/$2';
$route['procedureontologies/(:num)'] = $route['default_controller'] . '/displayProcedureOntologies/$1';
$route['procedureontologies/(:num)/(:num)'] = $route['default_controller'] . '/displayProcedureOntologies/$1/$2';
$route['parameterontologies/(:num)'] = $route['default_controller'] . '/listOntologies/$1';
$route['parameterontologies/(:num)/(:num)'] = $route['default_controller'] . '/listOntologies/$1/$2';
$route['ontologyoptions/(:num)'] = $route['default_controller'] . '/ontologyOptions/$1';
$route['ontologyoptions/(:num)/(:num)'] = $route['default_controller'] . '/ontologyOptions/$1/$2';
$route['ontologyoptions/(:num)/(:num)/(:num)'] = $route['default_controller'] . '/ontologyOptions/$1/$2/$3';
$route['procedurexml/(:num)'] = $route['default_controller'] . '/procedureXML/$1';
$route['procedurexml/(:num)/(:num)'] = $route['default_controller'] . '/procedureXML/$1/$2';
$route['history'] = $route['default_controller'] . '/displayChangeHistory';
$route['history/(:num)'] = $route['default_controller'] . '/displayChangeHistory/$1';
$route['history/(:num)/(:num)'] = $route['default_controller'] . '/displayChangeHistory/$1/$2';
$route['protocol/([A-Z\-]{3,8}_[A-Z]{3}_\d{3})'] = $route['default_controller'] . '/displaySOP/$1';
$route['protocol/(:num)'] = $route['default_controller'] . '/displaySOP/$1';
$route['protocol/([A-Z\-]{3,8}_[A-Z]{3}_\d{3})/([A-Z\-]{3,8}_\d{3})'] = $route['default_controller'] . '/displaySOP/$1/$2';
$route['protocol/(:num)/(:num)'] = $route['default_controller'] . '/displaySOP/$1/$2';
$route['protocol/([A-Z\-]{3,8}_[A-Z]{3}_\d{3})/([A-Z\-]{3,8}_\d{3})/(:any)'] = $route['default_controller'] . '/displaySOP/$1/$2/$3';
$route['protocol/(:num)/(:num)/(:any)'] = $route['default_controller'] . '/displaySOP/$1/$2/$3';
$route['admin/iu/model/(:any)'] = 'iu/model/$1';
/* /Deprecated */
$route['404_override'] = '';


/* End of file routes.php */
/* Location: ./application/config/routes.php */