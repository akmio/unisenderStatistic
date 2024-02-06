<?
namespace Yolva\Agents\Crm\Contact;

use Yolva\Agents\Base;
use Yolva\Helpers\HLBHelper;
use Yolva\Services\Crm\ContactService;
use Yolva\Services\Unisender\UnisenderContactService;

class UpdateFieldsContact extends Base
{
    public static function runUpdate(): string
    {
        $helper = new UpdateFieldsContactHelper();
        $helper->run();
        return self::getFunctionString(__METHOD__);
    }


}

class UpdateFieldsContactHelper
{
    private ContactService $contactService;
    private UnisenderContactService $uniContactService;
    function __construct()
    {
        $this->contactService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.contact');
        $this->uniContactService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.contact');
    }

    public function run()
    {
        //Получение списков юнисендора (id)
        $arrIdListsFromUni = $this->contactService->getIdsListsUni();

        //Отправка запроса на получение полей контакта
        $countQuerys = 0;
        foreach ($arrIdListsFromUni as $idList){
            $countQuerys++;
            if ($countQuerys > 20){
                $countQuerys = 1;
                sleep(60);
            }
            $this->uniContactService->exportContacts($idList);
        }
    }

    public function addUniMailingsContacts($arrCampaigns)
    {
        $hlbCampaigns = HLBHelper::getEntityFromCode('YlvMailingContact');

        foreach ($arrCampaigns as $campaign ) {
            if (!empty($campaign['recipient_mail']) && $campaign['UPDATE'] != 'Y') {
                foreach ($campaign['recipient_mail'] as $itemMail) {
                    $hlbCampaigns::add([
                        'UF_MAILING' => $campaign['campaign_id_b24'],
                        'UF_CONTACT' => $itemMail,
                        'UF_ID_MAIL' => $campaign['message_id'],
                        'UF_ID_CAMPAIGNS' => $campaign['id'],
                    ]);
                }
            }
        }
    }

    public function addDateWorkAgentInHlbUnilog()
    {
        $hlbAddUniLog = HLBHelper::getEntityFromCode('YlvUniLog');

        $hlbAddUniLog::add([
            'UF_DATE_AGENT_START' => date('d.m.Y H:i:s'),
            'UF_STATUS_LOG' => 'Успешно',
        ]);
    }

    public function getLastAgentRun()
    {
        $rsDataUniParam = HLBHelper::getFromHlb('YlvUniLog', ['UF_DATE_AGENT_START', 'ID'], []);
        foreach ($rsDataUniParam as $elUniParam){
            if (!empty($elUniParam['UF_DATE_AGENT_START'])) {
                $dateAgent = $elUniParam['UF_DATE_AGENT_START']->format("Y-m-d H:i:s");
            }
        }
        return $dateAgent;
    }
}
?>