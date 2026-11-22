<?php

$config = require base_path('vendor/darkaonline/l5-swagger/config/l5-swagger.php');

$config['documentations']['default']['paths']['annotations'] = [app_path('OpenApi')];
$config['defaults']['generate_yaml_copy'] = true;
$config['defaults']['scanOptions']['open_api_spec_version'] = '3.0.3';

return $config;
