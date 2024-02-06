<?php
namespace Yolva\Services\Rest;

use Yolva\Model\AlutechUser;
use Yolva\Services\User\IUserService;
use Yolva\Helpers\HLBHelper;

\Bitrix\Main\Loader::includeModule('rest');

class YolvaUsersRestService extends \IRestService
{
    const SCOPE = "yolva.unisender.event";

    public function getDescription()
    {
        return array(
            static::SCOPE => array(
                static::SCOPE . 'add' => array(__CLASS__, 'add'),
                static::SCOPE . 'getFileWithContacts' => array(__CLASS__, 'getFileWithContacts'),
                static::SCOPE . 'updateContactFields' => array(__CLASS__, 'updateContactFields'),
                static::SCOPE . 'updateStatusLetters' => array(__CLASS__, 'updateStatusLetters'),
            )

        );
    }

    public static function OnRestServiceBuildDescription()
    {
        return array(
            static::SCOPE => array(
                static::SCOPE . '.add' => array(
                    'callback' => array(__CLASS__, 'add'),
                    'options' => array(),
                ),
                static::SCOPE . ".ping" => array(
                    'callback' => array(__CLASS__, 'ping'),
                    'options' => array(),
                ),
                static::SCOPE . ".getFileWithContacts" => array(
                    'callback' => array(__CLASS__, 'getFileWithContacts'),
                    'options' => array(),
                ),
                static::SCOPE . ".updateContactFields" => array(
                    'callback' => array(__CLASS__, 'updateContactFields'),
                    'options' => array(),
                ),
                static::SCOPE . ".updateStatusLetters" => array(
                    'callback' => array(__CLASS__, 'updateStatusLetters'),
                    'options' => array(),
                ),
            )
        );
    }

    public static function ping($query, $n = null, \CRestServer $server = null)
    {
        \Bitrix\Main\Diag\Debug::dumpToFile('rest', "ping", "!debug.log");
        if ($query['error']) {
            throw new \Bitrix\Rest\RestException(
                'Message',
                'ERROR_CODE',
                \CRestServer::STATUS_PAYMENT_REQUIRED
            );
        }
        return array('yourquery' => $query, 'myresponse' => 'pong');
    }

    public static function add($query, $nav = null, \CRestServer $server = null)
    {
        $eventData = $query['events_by_user']['0']['events']['0']['event_data'];
        $eventTime = new \DateTime($query['events_by_user']['0']['events']['0']['event_time']);

        // Получаем id записи контакта из hlb "Рассылки-Контакт"
        $hlbMailContact = HLBHelper::getEntityFromCode('YlvMailingContact');

        $rsData = $hlbMailContact::getlist([
            'select' => ['ID'],
            'filter' => ['UF_ID_CAMPAIGNS' => $eventData['campaign_id'], 'UF_CONTACT' => $eventData['email']]
        ]);
        if ($el = $rsData->fetch()){
            $idMailContact = $el;
        }

        if (empty($idMailContact)){
            $contactService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.contact');
            $statisticalService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.statistical');

            //Получаем дату последнего запуска $dateAgent
            $rsDataUniParam = HLBHelper::getFromHlb('YlvUniLog', ['UF_DATE_AGENT_START', 'ID'], []);
            foreach ($rsDataUniParam as $elUniParam){
                if (!empty($elUniParam['UF_DATE_AGENT_START'])) {
                    $dateAgent = $elUniParam['UF_DATE_AGENT_START']->format("Y-m-d H:i:s");
                }
            }

            $arrCampaigns = $statisticalService->getCampaigns($dateAgent);
            foreach ($arrCampaigns as $campaign){
                if ($campaign['id'] == $eventData['campaign_id']){
                    $arrFieldsCampaign[] = $campaign;

                    break;
                }
            }

            if (empty($arrFieldsCampaign)){
                $loggerServices = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.logger');
                $loggerServices->error('Empty Campaign', ['Error'=>'Отсутствует рассылка добавления', 'Method'=>'YolvaUsersRestService::add']);
                return;
            }

            $resRecordingInHlbMailing = $contactService->addUniMailings($arrFieldsCampaign);//добавляем рассылки и получаем id добавления
            $arrSaveCampaignsID = $resRecordingInHlbMailing['CampaignsID']; //id рассылки в битриксе

            //Получаем id значения поля "статус"
            $idValuefield = \CUserFieldEnum::GetList(array(), array(
                "XML_ID" => $eventData['status'],
                "USER_FIELD_NAME" => "UF_STATUS",
            ))->Fetch();

            $hlbCampaigns = HLBHelper::getEntityFromCode('YlvMailingContact');

            $hlbCampaigns::add([
                'UF_MAILING' => $arrSaveCampaignsID[$eventData['campaign_id']],
                'UF_CONTACT' => $eventData['email'],
                'UF_ID_MAIL' => $arrFieldsCampaign[0]['message_id'],
                'UF_ID_CAMPAIGNS' => $eventData['campaign_id'],
                'UF_STATUS' => $idValuefield['ID'],
                'UF_UPDATE_MAIL_STATUS_DATE' => $eventTime->format("d.m.Y H:i:s"),
            ]);

        }else {

            //Получаем id значения поля "статус"
            $idValuefield = \CUserFieldEnum::GetList(array(), array(
                "XML_ID" => $eventData['status'],
                "USER_FIELD_NAME" => "UF_STATUS",
            ))->Fetch();

            // Обнавляем поле "статус" в Письме
            $hlbMailUpd = HLBHelper::getEntityFromCode('YlvMailingContact');

            $hlbMailUpd::update(
                $idMailContact,
                [
                    'UF_STATUS' => $idValuefield['ID'],
                    'UF_UPDATE_MAIL_STATUS_DATE' => $eventTime->format("d.m.Y H:i:s"),
                ]
            );
        }
    }

    public static function getFileWithContacts($query, $nav = null, \CRestServer $server = null)
    {
        if (is_array($query)){
            $eventData = $query['result'];

            $getEmails = file_get_contents($eventData['file_to_download']);
            $getEmails = trim($getEmails);
            $arrEmailsIds = explode("\n", $getEmails);
            array_shift($arrEmailsIds);
            foreach ($arrEmailsIds as $emailListId){
                list($email, $emailListId) = explode(',', $emailListId);
                $arrEmailsUnisender[] = $email;
            }
        }else{
            $emailListId = $query;
        }

        if (!empty($emailListId)){
            $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

            $filter = ['UF_UNI_ID' => $emailListId];
            $rsData = $hlb::getlist([
                'select' => ['UF_B24_ID', 'UF_UNI_ID'],
                'filter' => $filter
            ]);
            if ($el = $rsData->fetch()){
                $segmentId = $el['UF_B24_ID'];
                $listId = $el['UF_UNI_ID'];
            }

            $dataObj = \Bitrix\Sender\SegmentDataTable::getList([
                "select" => ['EMAIL', 'NAME'],
                "filter" => [
                    "GROUP_ID" => $segmentId,
                    "HAS_EMAIL" => "Y"
                ]
            ]);

            while ($segment = $dataObj->Fetch()) {
                $arrEmailSegment[] = $segment['EMAIL'];
                $arrContactSegm[$segment['EMAIL']] = [
                    'EMAIL' => $segment['EMAIL'],
                    'NAME' => $segment['NAME'],
                    'UNI_ID' => $listId,
                ];
            }
        }

        if(empty($arrEmailSegment)){
            return '';
        }

        if (empty($arrEmailsUnisender)){
            $arrAddEmail = $arrEmailSegment;
            $arrDeleteEmail = [];
        }else{
            $arrAddEmail = array_diff($arrEmailSegment, $arrEmailsUnisender);
            $arrDeleteEmail = array_diff($arrEmailsUnisender, $arrEmailSegment);
        }

        $service = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.list');

        // Удаление контактов из списков
        if (!empty($arrDeleteEmail)) {
            foreach ($arrDeleteEmail as $deleteEmail){
                $service->exclideContact($deleteEmail, $listId);
            }
        }

        //Добавление контактов в списки
        if (!empty($arrAddEmail)) {
            $arrAddEmail = array_intersect_key($arrContactSegm, array_flip($arrAddEmail));

            $j = 0;
            $maxCountToSend = 500;
            for ($index = 0; $j < count($arrAddEmail); $index++) {
                $j += $maxCountToSend;
                $arrToSend = array_slice($arrAddEmail, $index * $maxCountToSend, $maxCountToSend);
                $service->importContact($arrToSend);
            }
        }
    }

    public static function updateContactFields($query, $nav = null, \CRestServer $server = null)
    {
        //Получаем контакты (id и email)
        $contactService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.contact');
        $arrContactsFromSegments = $contactService->getContactsFromUniSegment();

        $eventData = $query['result'];

        $getEmails = file_get_contents($eventData['file_to_download']);
        $getEmails = trim($getEmails);
        $arrEmailsIds = explode("\n", $getEmails);
        array_shift($arrEmailsIds);

        foreach ($arrEmailsIds as $emailListId){
            list(
                $email,
                $email_add_time,
                $email_last_read_at,
                $email_status,
                $email_availability,
                $email_last_delivered_at,
                $email_last_clicked_at) = explode(',', $emailListId);

            $arrFieldsEmailsUnisender[$arrContactsFromSegments[$email]['ID']] = [
                'email'=>$email,
                'email_add_time' => trim($email_add_time, '"'),
                'email_last_read_at' => trim($email_last_read_at, '"'),
                'email_status'=> $email_status,
                'email_availability' => $email_availability,
                'email_last_delivered_at' => trim($email_last_delivered_at, '"'),
                'email_last_clicked_at' => trim($email_last_clicked_at, '"')
            ];
        }

        foreach ($arrFieldsEmailsUnisender as $keyIdEmail => $fieldsEmail){
            if (empty($keyIdEmail))
                continue;
            //Получаем id значения поля "Статус рассылки"
            $idValueStatus = \CUserFieldEnum::GetList(array(), array(
                'XML_ID' => $fieldsEmail['email_status'],
                'USER_FIELD_NAME' => 'UF_EMAIL_STATUS',
            ))->Fetch();

            $idValueAvailability = \CUserFieldEnum::GetList(array(), array(
                'XML_ID' => $fieldsEmail['email_availability'],
                'USER_FIELD_NAME' => 'UF_EMAIL_AVAILABILITY',
            ))->Fetch();

            $fieldsEmail['email_add_time'] = (empty($fieldsEmail['email_add_time'])) ? '' : new \DateTime($fieldsEmail['email_add_time']);
            $fieldsEmail['email_last_read_at'] = (empty($fieldsEmail['email_last_read_at'])) ? '' : new \DateTime($fieldsEmail['email_last_read_at']);
            $fieldsEmail['email_last_delivered_at'] = (empty($fieldsEmail['email_last_delivered_at'])) ? '' : new \DateTime($fieldsEmail['email_last_delivered_at']);
            $fieldsEmail['email_last_clicked_at'] = (empty($fieldsEmail['email_last_clicked_at'])) ? '' : new \DateTime($fieldsEmail['email_last_clicked_at']);

            $arrDataContacts[$keyIdEmail] = [
                'UF_CREATE_DATE' => (empty($fieldsEmail['email_add_time'])) ? '' : $fieldsEmail['email_add_time']->format('d.m.Y H:i:s'),
                'UF_LAST_READ' => (empty($fieldsEmail['email_last_read_at'])) ? '' : $fieldsEmail['email_last_read_at']->format('d.m.Y H:i:s'),
                'UF_EMAIL_STATUS' => $idValueStatus['ID'],
                'UF_EMAIL_AVAILABILITY' => $idValueAvailability['ID'],
                'UF_LAST_RECEIVE' => (empty($fieldsEmail['email_last_delivered_at'])) ? '' : $fieldsEmail['email_last_delivered_at']->format('d.m.Y H:i:s'),
                'UF_LAST_CLICK' => (empty($fieldsEmail['email_last_clicked_at'])) ? '' : $fieldsEmail['email_last_clicked_at']->format('d.m.Y H:i:s'),
            ];
        }

        //Обновляем поля контакта
        $contactService->updateEntity($arrDataContacts,true);
    }

    public static function updateStatusLetters($query, $nav = null, \CRestServer $server = null)
    {
        $compaignId = $query['compaignId'];
        $eventData = $query['result'];

        $getEmailsData = file_get_contents($eventData['file_to_download']);
        $getEmailsData = trim($getEmailsData);
        $arrEmailsData = explode("\n", $getEmailsData);
        array_shift($arrEmailsData);

        foreach ($arrEmailsData as $emailData){
            list(
                $email,
                $status,
                $dateUpdate,
                ) = explode(',', $emailData);

            $arrFieldsEmails[] = [
                'email'=>$email,
                'status' => $status,
                'dateUpdate' => trim($dateUpdate, '"')
            ];
        }

        $hlb = HLBHelper::getEntityFromCode('YlvMailingContact');

        $rsData = $hlb::getlist([
            'select' => ['ID', 'UF_CONTACT'],
            'filter' => ['UF_ID_CAMPAIGNS' => $compaignId]
        ]);
        while ($el = $rsData->fetch()){
            $el['UF_CONTACT'] = strtolower($el['UF_CONTACT']);
            $arrHlbMailings[] = ['ID'=> $el['ID'], 'email' => $el['UF_CONTACT']];
        }

        foreach ($arrHlbMailings as $hlbMailing) {
            foreach ($arrFieldsEmails as $emailFields) {

                if ($hlbMailing['email'] == $emailFields['email']) {
                    //Получаем id значения поля "Статус рассылки"
                    $idValueStatus = \CUserFieldEnum::GetList(array(), array(
                        'XML_ID' => $emailFields['status'],
                        'USER_FIELD_NAME' => 'UF_STATUS',
                    ))->Fetch();

                    // Обнавляем поле "статус" в Письме
                    $hlbMailUpd = HLBHelper::getEntityFromCode('YlvMailingContact');
                    $hlbMailUpd::update(
                        $hlbMailing['ID'],
                        [
                            'UF_STATUS' => $idValueStatus,
                        ]
                    );
                }
            }
        }
    }

    /*static function addUser(IUserService $service, AlutechUser $user) : string
    {
        return $service->add($user);
    }

    //TODO: Remove after research
    public static function exampleWithNavData($query, $nav, \CRestServer $server)
    {
        $navData = static::getNavData($nav, true);
        $res = \Bitrix\Main\UserTable::getList(
            [
                'filter' => $query['filter'] ?: [],
                'select' => $query['select'] ?: ['*'],
                'order' => $query['order'] ?: ['ID' => 'ASC'],
                'limit' => $navData['limit'],
                'offset' => $navData['offset'],
                'count_total' => true,
            ]
        );
        $result = array();
        while ($user = $res->fetch()) {
            $result[] = $user;
        }
        return static::setNavData(
            $result,
            array(
                "count" => $res->getCount(),
                "offset" => $navData['offset']
            )
        );
    }*/


}
