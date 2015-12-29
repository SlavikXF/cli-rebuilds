<?php
// CLI only
if (PHP_SAPI != 'cli') {
    die('This script may only be run at the command line.');
}

ini_set('display_errors', true);
set_time_limit(0);

// *****************************

$startTime = microtime(true);
$fileDir = realpath(dirname(__FILE__).'/../');
chdir($fileDir);

set_time_limit(0);
ignore_user_abort(true);

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

XenForo_Application::get('db')->setProfiler(false); // this causes lots of memory usage in debug mode, so stop that

$dependencies = new XenForo_Dependencies_Public();
$dependencies->preLoadData();

$cliHelper = new XenForo_Install_CliHelper();

function cliRunDeferredTask($deferred)
{
    global $cliHelper;

    $runner = XenForo_Deferred_Abstract::create($deferred['execute_class']);
    @set_time_limit(0);
    if (!$runner) {
        $cliHelper->printWarning("{$deferred['execute_class']} deferred class not found.");
        return false;
    }

    $cliHelper->printStatus('');

    if(!is_array($deferred['execute_data'])) {
        $data = unserialize($deferred['execute_data']);
    } else {
        $data = $deferred['execute_data'];
    }


    $targetRunTime = XenForo_Application::getConfig()->rebuildMaxExecution;

    while(
            $data = $runner->execute($deferred, $data, $targetRunTime, $status)
            AND is_array($data)
    ) {
        $cliHelper->printStatus($status);
    }

    if(empty($status)) {
        $status=$deferred['execute_class'].' (0)';
    }

    $cliHelper->printStatus($status.' - Done.');
    $cliHelper->printMessage('');
}

function getRebuilderList()
{
    $files=glob(dirname(__FILE__).'/*.php');

    foreach($files AS $index=>$file) {
        $file=basename($file);
        if($file=='init_rebuilder.php') {
            unset($files[$index]);
            continue;
        }
        if($file=='CLIRebuilds.php') {
            unset($files[$index]);
            continue;
        }
        $file=str_replace('.php', '', $file);
        $files[$index]=$file;
    }

    return array_merge($files, array());
}