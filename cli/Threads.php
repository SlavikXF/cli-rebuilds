<?php
include_once dirname(__FILE__) . '/init_rebuilder.php';
// ******************

$start = 0;
$batch = 100;

// ******************

$position = $start;

while (true)
{
	$data = array_merge(array(
		'batch' => 100,
		'position' => 0,
		'positionRebuild' => false
	));

	/* @var $threadModel XenForo_Model_Thread */
	$threadModel = XenForo_Model::create('XenForo_Model_Thread');

	$threadIds = $threadModel->getThreadIdsInRange($position, $batch);
	if (sizeof($threadIds) == 0)
	{
		break;
	}

	$forums = XenForo_Model::create('XenForo_Model_Forum')->getForumsByThreadIds($threadIds);

	foreach ($threadIds AS $threadId)
	{
		$position = $threadId;

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($threadId))
		{
			$dw->setOption(XenForo_DataWriter_Discussion::OPTION_UPDATE_CONTAINER, false);

			if (isset($forums[$dw->get('node_id')]))
			{
				$dw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forums[$dw->get('node_id')]);
			}

			if (false)
			{
				$dw->rebuildDiscussion();
			}
			else
			{
				$dw->rebuildDiscussionCounters();
			}
			$dw->save();
		}
	}

	$actionPhrase = new XenForo_Phrase('rebuilding');
	$typePhrase = new XenForo_Phrase('threads');
	$status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($position));
	echo str_pad($status, 80, ' ', STR_PAD_RIGHT) . "\r";
}

echo PHP_EOL;
echo "Finished." . PHP_EOL;