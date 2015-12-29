<?php
include_once dirname(__FILE__).'/init_rebuilder.php';

$deferred=array(
    'deferred_id'=>0,
    'unique_key'=>'UserCaches',
    'execute_class'=>'User',
    'execute_data'=> array(
            'batch' => 70,
            'position' => 0
    ),
    'manual_execute'=>1,
    'trigger_date'=>time(),
);

cliRunDeferredTask($deferred);