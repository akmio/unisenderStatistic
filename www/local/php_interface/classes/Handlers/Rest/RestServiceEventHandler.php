<?
namespace Yolva\Handlers\Rest;

use Bitrix\Main\EventManager;

class RestServiceEventHandler{
    public function __construct()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->addEventHandler(
            'rest',
            'OnRestServiceBuildDescription',
            array('\Yolva\Services\Rest\YolvaUsersRestService', 'OnRestServiceBuildDescription')
        );

        /*$eventManager->addEventHandler(
            'rest',
            'OnRestServiceBuildDescription',
            array('\Yolva\Services\Rest\AlutechQuoteRestService', 'OnRestServiceBuildDescription')
        );*/
    }
}
?>