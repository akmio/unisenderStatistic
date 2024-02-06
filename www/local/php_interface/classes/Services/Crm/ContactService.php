<?php


namespace Yolva\Services\Crm;


use Yolva\Services\Crm\CrmService;
use Yolva\Services\Unisender\UnisenderContactService;
use Yolva\Helpers\HLBHelper;

class ContactService extends CrmService
{
    private UnisenderContactService $uniContactServices;
    function __construct()
    {
        $entityTypeId = $entityTypeId ?? \CCrmOwnerType::Contact;
        parent::__construct($entityTypeId);
        $this->uniContactServices = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.contact');
    }

    //Получаем контакты (id и email)
    public function getContactsFromUniSegment()
    {

        $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

        $rsData = $hlb::getlist([
            'select' => ['UF_B24_ID'],
            'filter' => []
        ]);
        while ($el = $rsData->fetch()){
            $arrSegmentIdHlb[] = $el['UF_B24_ID'];
        }

        $dataObj = \Bitrix\Sender\SegmentDataTable::getList([
            "select" => ['*'],
            "filter" => [
                "GROUP_ID" => $arrSegmentIdHlb,
                "HAS_EMAIL" => "Y",
                "CRM_ENTITY_TYPE" => "CONTACT"
            ]
        ]);

        while ($segment = $dataObj->Fetch()) {
            if (isset($arrDataContacts[$segment['EMAIL']]))
                continue;
            $arrDataContacts[$segment['EMAIL']] = ['ID' => $segment['CRM_ENTITY_ID'], 'EMAIL' => $segment['EMAIL']];
        }

        return $arrDataContacts;
    }

    public function getIdsListsUni()
    {

        $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

        $rsData = $hlb::getlist([
            'select' => ['UF_UNI_ID'],
            'filter' => []
        ]);
        while ($el = $rsData->fetch()){
            $arrIdsListsHlb[] = $el['UF_UNI_ID'];
        }
        return $arrIdsListsHlb;
    }

    public function updateUniContact(array $arrContacts)
    {
        foreach ($arrContacts as $contact){
            $arrDataContact = $this->uniContactServices->get($contact['EMAIL']);

            //Получаем id значения поля "Статус рассылки"
            $idValueStatus = \CUserFieldEnum::GetList(array(), array(
                'XML_ID' => $arrDataContact['status'],
                'USER_FIELD_NAME' => 'UF_EMAIL_STATUS',
            ))->Fetch();

            $idValueAvailability = \CUserFieldEnum::GetList(array(), array(
                'XML_ID' => $arrDataContact['availability'],
                'USER_FIELD_NAME' => 'UF_EMAIL_AVAILABILITY',
            ))->Fetch();

            $arrDataContacts[$contact['ID']] = [
                'UF_CREATE_DATE' => $this->dateConvert($arrDataContact['added_at']),
                'UF_LAST_READ' => $this->dateConvert($arrDataContact['last_read_datetime']),
                'UF_EMAIL_STATUS' => $idValueStatus['ID'],
                'UF_EMAIL_AVAILABILITY' => $idValueAvailability['ID'],
                'UF_LAST_SEND' => $this->dateConvert($arrDataContact['last_send_datetime']),
                'UF_LAST_RECEIVE' => $this->dateConvert($arrDataContact['last_delivery_datetime']),
                'UF_LAST_CLICK' => $this->dateConvert($arrDataContact['last_click_datetime']),
            ];
        }

        //Обновляем поля контакта
        $this->updateEntity($arrDataContacts,true);
    }

    public function addUniMailings(array $arrCampaigns)
    {
        $hlbCampaigns = HLBHelper::getEntityFromCode('YlvUniMailing');
        foreach ($arrCampaigns as $keyCampaign => $campaign) {
            if ($campaign['UPDATE'] == 'Y') continue;

            $arrListId[] = $campaign['list_id'];

            //Получаем id значения поля "Статус рассылки"
            $idValueField = \CUserFieldEnum::GetList(array(), array(
                'XML_ID' => $campaign['status'],
                'USER_FIELD_NAME' => 'UF_MAILING_STATUS',
            ))->Fetch();

            //Записываем рассылки в Hlb "Рассылки"
            $saveCampaigns = $hlbCampaigns::add([
                'UF_TITLE' => $campaign['subject'],
                'UF_CREATE_DATE' => $this->dateConvert($campaign['start_time']),
                'UF_MAILING_STATUS' => $idValueField['ID'],
                'UF_CAMPAIGNS_ID' => $campaign['id'],
            ]);
            $arrSaveCampaignsID[$campaign['id']] = $saveCampaigns->getId();
        }

        $arrResult = ['ListId' => $arrListId, 'CampaignsID' => $arrSaveCampaignsID];

        return $arrResult;
    }

    public function updateStatusUniMailings($arrCampaigns)
    {
        $hlbCampaigns = HLBHelper::getEntityFromCode('YlvUniMailing');
        $rsCampaigns = $hlbCampaigns::getlist([
            'select' => ['ID', 'UF_CAMPAIGNS_ID'],
            'filter' => ['>UF_CREATE_DATE' => date('d.m.Y H:i:s', strtotime("-1 year -1 days"))]
        ]);
        while ($hlbCampaign = $rsCampaigns->fetch()){
            foreach ($arrCampaigns as $keyCampaigns => $campaign) {
                if ($campaign['id'] == $hlbCampaign['UF_CAMPAIGNS_ID']) {

                    $idValueField = \CUserFieldEnum::GetList(array(), array(
                        'XML_ID' => $campaign['status'],
                        'USER_FIELD_NAME' => 'UF_MAILING_STATUS',
                    ))->Fetch();

                    $hlbCampaigns::update(
                        $hlbCampaign['ID'],
                        [
                            'UF_MAILING_STATUS' => $idValueField['ID'],
                        ]
                    );

                    $arrCampaigns[$keyCampaigns]['UPDATE'] = 'Y';
                }
            }
        }
        return $arrCampaigns;
    }

    public function getIdSegments($arrListId)
    {
        $rsDataSegment = HLBHelper::getFromHlb('YlvUniSegment', ['UF_UNI_ID','UF_B24_ID'], ['UF_UNI_ID' => $arrListId]);
        foreach ($rsDataSegment as $elSegment){
            $arrIdSegment[$elSegment['UF_UNI_ID']] = $elSegment['UF_B24_ID'];
        }
        return $arrIdSegment;
    }

    /**
     * Получаем email из сегмента
     * @param $arrIdSegment
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getEmailsFromSegments($arrIdSegment)
    {
        $dataObj = \Bitrix\Sender\SegmentDataTable::getList([
            "select" => ['*'],
            "filter" => [
                "GROUP_ID" => $arrIdSegment,
                "HAS_EMAIL" => "Y"
            ]
        ]);

        while ($segment = $dataObj->Fetch()) {
            $arrSegmentEmail[$segment['GROUP_ID']][] = $segment['EMAIL'];
        }

        return $arrSegmentEmail;
    }

    public function addUniMessages($arrMessFromUni)
    {
        $hlbCampaigns = HLBHelper::getEntityFromCode('YlvUniMail');

        foreach ($arrMessFromUni as $itemMessage){
            $from = '';
            if (!empty($itemMessage['sender_name']) && !empty($itemMessage['sender_email'])){
                $from = $itemMessage['sender_name'].' ('.$itemMessage['sender_email'].')';
            }
            $hlbCampaigns::add([
                'UF_UNI_ID' => $itemMessage['id'],
                'UF_FROM' => $from,
                'UF_SUBJECT' => $itemMessage['subject'],
                'UF_CREATE_DATE' => $this->dateConvert($itemMessage['created']),
            ]);
        }
    }

    private function dateConvert($date){
        if (empty($date)){
            return '';
        }else{
            $date = new \DateTime($date);
            return $date->format('d.m.Y H:i:s');
        }
    }
}