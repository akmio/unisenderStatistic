<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
use Yolva\Helpers\HLBHelper;

if ($_POST['clickSaveBtn'] == 'Y'){
    $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');
    $rsData = $hlb::getlist([
        'select' => ['ID'],
        'filter' => ['UF_B24_ID' => $_POST['segmentId']]
    ]);
    if ($el = $rsData->fetch()){
        $idRecHlb = $el['ID'];
    }

    $hlb::update(
        $idRecHlb,
        [
            'UF_AUTO_UPDATE' => $_POST['isAuto'],
        ]
    );
}else{
    $service = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.list');
    $list = $service->create($_POST['segmentName']);


    $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

    $resultAdd = $hlb::add([
        'UF_UNI_ID' => $list['result']['id'],
        'UF_B24_ID' => $_POST['segmentId'],
        'UF_AUTO_UPDATE' => $_POST['isAuto'],
    ]);

    \CAgent::AddAgent(
        "Yolva\Agents\Marketing\Segment\ActualizeSegment::addContactsBySegment({$_POST['segmentId']});",
        'main',
        'N',
        '120',
        '',
        'Y',
        ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 120,"FULL"),
    );
}
