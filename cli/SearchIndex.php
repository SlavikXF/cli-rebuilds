<?php
/** Manual configuration variables */
$specialBuildRange='1-2'; // if entered the IDs in this range will be committed to the source one by one, not in batch
/** END Manual configuration variables */

include_once dirname(__FILE__) . '/init_rebuilder.php';

/* @var $searchModel XenForo_Model_Search */
$searchModel = XenForo_Model::create('XenForo_Model_Search');
$searchContentTypes = $searchModel->getSearchContentTypes();

$input = array('delete_index' => 0, 'content_type' => '');

$contentTypeQuestion=new XenForo_Phrase('build_content_type').PHP_EOL;
$contentTypeQuestion.="0 - ".(new XenForo_Phrase('all')).PHP_EOL;
$typeIndex=1;
foreach($searchContentTypes AS $typeId=>$typeHandler) {
    $contentTypeQuestion .= $typeIndex." - " . (new XenForo_Phrase($typeId)).PHP_EOL;
    $typeIndex++;
}
$contentTypeQuestion.="Please enter the number:";

$contentTypeIndex = $cliHelper->askQuestion($contentTypeQuestion);

$searchContentTypeKeys = array_keys($searchContentTypes);

if(!is_numeric($contentTypeIndex) OR $contentTypeIndex>0 AND !array_key_exists($contentTypeIndex-1, array_keys($searchContentTypes))) {
    $cliHelper->triggerFatalError("Invalid content type index is entered");
}

if($contentTypeIndex) {
    $input['content_type'] = $searchContentTypeKeys[$contentTypeIndex - 1];
}

$input['delete_index']=$cliHelper->askQuestion((new XenForo_Phrase('delete_index_before_rebuilding')).' [y/n]');

if ($cliHelper->validateYesNoAnswer($input['delete_index'], $continue)) {
    if($continue) {
        $input['delete_index']=1;
    } else {
        $input['delete_index']=0;
    }
}

if($specialBuildRange) {
    $specialBuildRange=explode('-', $specialBuildRange);
    $specialBuildRange=array_map('intval', $specialBuildRange);
} else {
    $specialBuildRange=array(0, 0);
}

if ($input['delete_index']) {
    $source = XenForo_Search_SourceHandler_Abstract::getDefaultSourceHandler();
    $source->deleteIndex($input['content_type'] ? $input['content_type'] : null);
}

if($input['content_type']) {
    $searchContentList=array($input['content_type']);
} else {
    $searchContentList=array_keys($searchContentTypes);
}

// ******************

$start = 0;
$batch = 100;
$currentContentType=array_shift($searchContentList);

// ******************


$position = $start;

$lastStart=0;


while (true)
{
    $searchHandler = $searchContentTypes[$currentContentType];
    if (class_exists($searchHandler)) {
        $dataHandler = XenForo_Search_DataHandler_Abstract::create($searchHandler);
        $indexer = new XenForo_Search_Indexer();

        if($start AND $start>$specialBuildRange[0] AND $start<$specialBuildRange[1]) {
            $start = $dataHandler->rebuildIndex($indexer, $start, $batch);
        } else {
            $indexer->setIsRebuild(true);

            $start = $dataHandler->rebuildIndex($indexer, $start, $batch);

            $indexer->finalizeRebuildSet();
        }
    } else {
        $start=false;
    }

    if($start===false) {

        if (empty($searchContentList)) {
            break;
        }

        $currentContentType = array_shift($searchContentList);
        $start=0;

        echo PHP_EOL;

        continue;
    }

    $actionPhrase = new XenForo_Phrase('rebuilding');
    $typePhrase = new XenForo_Phrase('search_index');
    $text = new XenForo_Phrase($currentContentType);
    $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, "$text " . XenForo_Locale::numberFormat($start));

    echo str_pad($status, 120, ' ', STR_PAD_RIGHT) . "\r";
}

echo PHP_EOL;
echo "Finished." . PHP_EOL;