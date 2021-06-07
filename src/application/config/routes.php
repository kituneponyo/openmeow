<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
| example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
| https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
| $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
| $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
| $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes with
| underscores in the controller and method URI segments.
|
| Examples: my-controller/index -> my_controller/index
|   my-controller/my-method -> my_controller/my_method
*/
//$route['default_controller'] = 'welcome';
$route['default_controller'] = 'home';
$route['404_override'] = '/home/notfound';
$route['translate_uri_dashes'] = FALSE;

$route['@(:any)'] = 'user/index/$1';
$route['u/(:any)'] = 'user/index/$1';
$route['@(:any)/(:num)'] = 'post/detail/$2/$1';
$route['u/(:any)/(:any)'] = 'user/index/$1/$2';
$route['p/(:num)'] = 'post/detail/$2';

$route['p/(:any)/(:num)'] = 'post/detail/$2/$1';
$route['p/(:any)/(:num)/activity'] = 'post/detail/$2/$1/activity';

$route['search'] = 'search/index';

$route['album/(:any)/(:num)'] = 'album/index/$1/$2';

$route['invite/(:any)/(:any)'] = 'register/invite/$1/$2';

$route['paint/8bit'] = 'paint/eightbit';

$route['my/album'] = 'myalbum';

$route['.well-known/webfinger'] = 'ap/webfinger';
$route['.well-known/host-meta'] = 'ap/hostMeta';

$route['inbox'] = 'ap/inbox';
$route['ap/u/(:any)/inbox'] = 'ap/user_inbox/$1';
$route['ap/u/(:any)/outbox'] = 'ap/user_outbox/$1';

$route['api/v1/instance'] = 'about/api_v1_instance';
$route['api/nodeinfo/2.0'] = 'about/nodeinfo_2_0';
$route['api/nodeinfo/2.0.json'] = 'about/nodeinfo_2_0';
$route['nodeinfo/2.0'] = 'about/nodeinfo_2_0';
$route['nodeinfo/2.0.json'] = 'about/nodeinfo_2_0';
$route['manifest.json'] = 'about/manifest';
$route['statistics.json'] = 'about/statistics';

$route['about/more'] = 'about/blank'; // 利用規約とか
$route['about/(:any)'] = 'about/index/$1'; // about 管理者生成

$route['static/terms-of-service.html'] = 'about/blank'; // プライバシーポリシー
$route['terms'] = 'about/blank'; // プライバシーポリシー

$route['api/statusnet/config.json'] = 'about/blankJson';
$route['atom'] = 'about/blankJson';
$route['feed'] = 'about/blankJson';

$route['rss'] = 'about/blankRss';