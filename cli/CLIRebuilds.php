<?php
include_once dirname(__FILE__) . '/init_rebuilder.php';

$rebuilders=getRebuilderList();

$message="Please select caches to to rebuild:\n";
$message.="All [0]\n";
foreach($rebuilders AS $index=>$rebuilder) {
    $message.="$rebuilder [".($index+1)."]\n";
}
$message.="Cache to rebuild: ";
$rebuilderIndex=intval($cliHelper->askQuestion($message));

if(
    !is_numeric($rebuilderIndex)
    OR ($rebuilderIndex>0 AND array_key_exists($rebuilderIndex-1, $rebuilders)===false)
    OR $rebuilderIndex<0
) {
    $cliHelper->printWarning("Number not recognised.");
    exit(1);
}

if($rebuilderIndex==0) {
  foreach($rebuilders AS $rebuilder) {
      include dirname(__FILE__).'/'.$rebuilder.'.php';
  }
} else {
    include dirname(__FILE__) . '/' . $rebuilders[$rebuilderIndex-1] . '.php';
}