<?
namespace Yolva\Agents\Crm\Contact;

use Yolva\Agents\Base;
use Yolva\Helpers\HLBHelper;
use Yolva\Services\Crm\ContactService;
use Yolva\Services\Unisender\UnisenderStatisticalService;

class UpdateCampaignsContact extends Base
{
    public static function runUpdate(): string
    {
        $helper = new UpdateCampaignsContactHelper();
        $helper->run();
        return self::getFunctionString(__METHOD__);
    }


}

class UpdateCampaignsContactHelper
{
    private ContactService $contactService;
    private UnisenderStatisticalService $statisticalService;
    function __construct()
    {
        $this->contactService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.contact');
        $this->statisticalService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.statistical');
    }

    public function run()
    {
        $dateAgent = $this->getLastAgentRun();

        $arrCampaigns = $this->statisticalService->getCampaigns();

        $arrCampaigns = $this->contactService->updateStatusUniMailings($arrCampaigns);

        $this->addDateWorkAgentInHlbUnilog();

        $resRecordingInHlbMailing = $this->contactService->addUniMailings($arrCampaigns);
        $arrSaveCampaignsID = $resRecordingInHlbMailing['CampaignsID'];
        $arrListId = $resRecordingInHlbMailing['ListId'];

        //Получаем id сегментов
        $arrIdSegment = $this->contactService->getIdSegments($arrListId);

        //Получаем email из сегмента
        $arrSegmentEmail = $this->contactService->getEmailsFromSegments($arrIdSegment);

        //Соединяем списки с email
        foreach ($arrIdSegment as $keyIdList => $idSegment) {
            foreach ($arrSegmentEmail as $keyIdSegment => $email) {
                if ($keyIdSegment == $idSegment) {
                    $arrEmailList[$keyIdList] = $email;
                }
            }
        }

        //Заполняем поля для рассылок из смарт процессов
        foreach ($arrCampaigns as $campaign){
            $arrSmartCampaignsId[] = $campaign['id'];
            $arrSmartCampaigns[$campaign['id']] = $campaign;
        }

        if (!empty($arrSmartCampaignsId)){
            $arrSmartCampaignsHlbMailingContact = $this->getSmartCampaignsFromMailingContact($arrSmartCampaignsId);

            if (!empty($arrSmartCampaignsHlbMailingContact)){
                foreach ($arrSmartCampaignsHlbMailingContact as $keySmartCampaign => $itemSmartCampaign){
                    foreach ($arrSaveCampaignsID as $keyCampaignSave => $idCampaignSave){
                        if ($itemSmartCampaign['UF_ID_CAMPAIGNS'] == $keyCampaignSave){
                            $arrSmartCampaignsHlbMailingContact[$keySmartCampaign]['campaign_id_b24'] = $idCampaignSave;
                            $arrSmartCampaignsHlbMailingContact[$keySmartCampaign]['message_id'] = $arrSmartCampaigns[$itemSmartCampaign['UF_ID_CAMPAIGNS']]['message_id'];
                        }
                    }
                }

                $this->updateSmartCampaignsInMailingContact($arrSmartCampaignsHlbMailingContact);
            }
        }

        //Соединяем рассылки с email и добавляем id рассылки из Hlb "Рассылок"
        foreach ($arrCampaigns as $keyCampaign => $itemCampaign) {
            foreach ($arrEmailList as $keyList => $arrEmail) {
                if ($itemCampaign['list_id'] == $keyList) {
                    $arrCampaigns[$keyCampaign]['recipient_mail'] = $arrEmail;
                }
            }

            foreach ($arrSaveCampaignsID as $keyCampaignSave => $idCampaignSave){
                if ($itemCampaign['id'] == $keyCampaignSave){
                    $arrCampaigns[$keyCampaign]['campaign_id_b24'] = $idCampaignSave;
                }
            }
        }

        $this->addUniMailingsContacts($arrCampaigns);
        $arrMessFromUni = $this->statisticalService->getMessages($dateAgent);
        $this->contactService->addUniMessages($arrMessFromUni);


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

    public function getSmartCampaignsFromMailingContact($arrSmartCampaignsId)
    {
        $rsDataHlbMailContact = HLBHelper::getFromHlb(
            'YlvMailingContact',
            ['ID','UF_CONTACT', 'UF_ID_CAMPAIGNS'],
            [
                'UF_ID_CAMPAIGNS' =>$arrSmartCampaignsId,
                'UF_MAILING' => false,
                'UF_ID_MAIL' => false,
                '!UF_CONTACT' => false,
            ]);
        return $rsDataHlbMailContact;
    }

    public function updateSmartCampaignsInMailingContact($arrSmartCampaignsHlbMailingContact){
        foreach ($arrSmartCampaignsHlbMailingContact as $smartCampaigns){

            $hlbMailUpd = HLBHelper::getEntityFromCode('YlvMailingContact');
            $hlbMailUpd::update(
                $smartCampaigns['ID'],
                [
                    'UF_MAILING' => (!empty($smartCampaigns['campaign_id_b24'])) ? $smartCampaigns['campaign_id_b24']: '',
                    'UF_ID_MAIL' => (!empty($smartCampaigns['message_id'])) ? $smartCampaigns['message_id'] : '',
                ]
            );
        }
    }
}
?>