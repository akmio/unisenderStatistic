<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$service = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.contact');

$contacts = $service->getItems(['select'=> ['ID'], 'filter'=> ['EMAIL' => $_POST['email']]]);
foreach ($contacts as $contact){
    $idContact = $contact['ID'];
}
echo $idContact;

