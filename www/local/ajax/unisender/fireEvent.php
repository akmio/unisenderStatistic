<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);
define("NOT_CHECK_PERMISSIONS", true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
use Yolva\Services\Rest\YolvaUsersRestService;

$service = new YolvaUsersRestService();

use Bitrix\Main\Application;
$request = Application::getInstance()->getContext()->getRequest();

return $service::add($request->getJsonList());
