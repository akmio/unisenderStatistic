<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Application;
use Yolva\Helpers\HLBHelper;

\Bitrix\Main\Loader::requireModule('crm');

class ElemList extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);
        return $params;
    }

    public function executeComponent()
    {
        //Получаем данные контакта
        $contactResult = \CCrmContact::GetListEx(
            [],
            [
                'ID' => $this->arParams['ID_CONTACT'],
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'LAST_NAME',
                'UF_CREATE_DATE',
                'UF_EMAIL_STATUS',
                'UF_EMAIL_AVAILABILITY',
                'UF_LAST_SEND',
                'UF_LAST_RECEIVE',
                'UF_LAST_CLICK',
                'UF_LAST_READ',
            ]
        );

        while( $contact = $contactResult->fetch() )
        {
            /**
             * [ 'ID' => ..., 'TITLE' => ... ]
             * @var array
             */
            $contact['UF_CREATE_DATE'] = empty($contact['UF_CREATE_DATE']) ? '' : date('Y-m-d H:i:s', strtotime($contact['UF_CREATE_DATE'].'+3 hour'));
            $contact['UF_LAST_SEND'] = empty($contact['UF_LAST_SEND']) ? '' : date('Y-m-d H:i:s', strtotime($contact['UF_LAST_SEND'].'+3 hour'));
            $contact['UF_LAST_RECEIVE'] = empty($contact['UF_LAST_RECEIVE']) ? '' : date('Y-m-d H:i:s', strtotime($contact['UF_LAST_RECEIVE'].'+3 hour'));
            $contact['UF_LAST_CLICK'] = empty($contact['UF_LAST_CLICK']) ? '' : date('Y-m-d H:i:s', strtotime($contact['UF_LAST_CLICK'].'+3 hour'));
            $contact['UF_LAST_READ'] = empty($contact['UF_LAST_READ']) ? '' : date('Y-m-d H:i:s', strtotime($contact['UF_LAST_READ'].'+3 hour'));

            $rsEnum = CUserFieldEnum::GetList(
                [],
                [
                    'ID' => $contact['UF_EMAIL_AVAILABILITY'],
                    'USER_FIELD_NAME' => 'UF_EMAIL_AVAILABILITY',
                ]
            );

            if ($arEnum = $rsEnum->Fetch()){
                $contact['UF_EMAIL_AVAILABILITY'] = $arEnum['VALUE'];
            }

            $rsEnumEmailStatus = \CUserFieldEnum::GetList(
                [],
                [
                    'ID' => $contact['UF_EMAIL_STATUS'],
                    'USER_FIELD_NAME' => 'UF_EMAIL_STATUS',
                ]
            )->Fetch();
            $contact['UF_EMAIL_STATUS'] = $rsEnumEmailStatus['VALUE'];

            $this->arResult['CONTACT'] = $contact;
        }

        //Получаем почту контакта
        $contactResultEmail = CCrmFieldMulti::GetListEx(
            [],
            [
                'ENTITY_ID' => 'CONTACT',
                'ELEMENT_ID' => $this->arParams['ID_CONTACT'],
                'TYPE_ID' => "EMAIL"
            ]
        );

        while ($contactEmail = $contactResultEmail->Fetch()){
            $this->arResult['CONTACT']['EMAIL'] = $contactEmail['VALUE'];
        }

        // Получаем Письма
        $arrContactMails = $this->getHlb('YlvMailingContact', ['*'], ['UF_CONTACT' => $this->arResult['CONTACT']['EMAIL']]);
        foreach ($arrContactMails as $keyitemContactMail => $itemContactMail){
            $arrIdsMails[] = $itemContactMail['UF_ID_MAIL'];
            if ($itemContactMail['UF_STATUS'] != '0'){
                $arrIdsStatusMail[] = $itemContactMail['UF_STATUS'];
            }else{
                $itemContactMail['UF_STATUS'] = '';
            }
            $this->arResult['MAIL_ITEMS'][] = $itemContactMail;
        }

        $arrMails = $this->getHlb('YlvUniMail', ['*'], ['UF_UNI_ID' => $arrIdsMails]);
        foreach ($this->arResult['MAIL_ITEMS'] as $keyContactMail => $itemContactMail){
            foreach ($arrMails as $itemMail){
                if ($itemContactMail['UF_ID_MAIL'] == $itemMail['UF_UNI_ID']){
                    $this->arResult['MAIL_ITEMS'][$keyContactMail]['UF_FROM'] = $itemMail['UF_FROM'];
                    $this->arResult['MAIL_ITEMS'][$keyContactMail]['UF_SUBJECT'] = $itemMail['UF_SUBJECT'];
                    $this->arResult['MAIL_ITEMS'][$keyContactMail]['UF_CREATE_DATE'] = $itemMail['UF_CREATE_DATE']->add("3 hours");
                }
            }
        }

        if (!empty($arrIdsStatusMail)) {
            // Получаем значение статуса Писем
            $rsEnumMailStatus = CUserFieldEnum::GetList(
                [],
                [
                    'ID' => $arrIdsStatusMail,
                    'USER_FIELD_NAME' => 'UF_STATUS',
                ]
            );
            while ($arEnumMailStatus = $rsEnumMailStatus->Fetch()){
                foreach ($this->arResult['MAIL_ITEMS'] as $key => $mailItem) {
                    if ($mailItem['UF_STATUS'] == $arEnumMailStatus['ID']){
                        $this->arResult['MAIL_ITEMS'][$key]['UF_STATUS'] = $arEnumMailStatus['VALUE'];
                    }
                }
            }
        }

        // Получаем записи из Hlb "Рассылки"
        $elContMail = $this->getHlb('YlvMailingContact', ['UF_MAILING'], ['UF_CONTACT' => $this->arResult['CONTACT']['EMAIL']]);
        foreach ($elContMail as $elContMailItem){
            $arIdMailing[] = $elContMailItem['UF_MAILING'];
        }

        if (!empty($arIdMailing)) {
            $elMailing = $this->getHlb('YlvUniMailing', ['*'], ['ID' => $arIdMailing]);

            foreach ($elMailing as $elMailingItem){
                $elMailingItem['UF_CREATE_DATE'] = $elMailingItem['UF_CREATE_DATE']->add("3 hours");
                $elMailingItem['UF_CREATE_DATE'] = $elMailingItem['UF_CREATE_DATE']->format("d.m.Y H:i:s");
                $arrMailingStatusId[] = $elMailingItem['UF_MAILING_STATUS'];
                $this->arResult['EMAILING_ITEMS'][] = $elMailingItem;
            }

            if ($arrMailingStatusId) {

                // Получение значений статусов рассылок
                $rsEnumMailingStatus = CUserFieldEnum::GetList(
                    [],
                    [
                        'ID' => $arrMailingStatusId,
                        'USER_FIELD_NAME' => 'UF_MAILING_STATUS',
                    ]
                );
                while ($arEnumMailingStatus = $rsEnumMailingStatus->Fetch()) {
                    foreach ($this->arResult['EMAILING_ITEMS'] as $key => $emailing) {
                        if ($emailing['UF_MAILING_STATUS'] == $arEnumMailingStatus['ID']) {
                            $this->arResult['EMAILING_ITEMS'][$key]['UF_MAILING_STATUS'] = $arEnumMailingStatus['VALUE'];
                        }
                    }
                }
            }
        }

        $this->IncludeComponentTemplate();
    }

    public function getHlb($hlCode, array $select, array $filter)
    {
        $entityData = HLBHelper::getEntityFromCode($hlCode);
        $rsData = $entityData::getlist([
            'select' => $select,
            'filter' => $filter
        ]);
        while ($arData = $rsData->fetch()){
            $arDataHlb[] = $arData;
        }
        return $arDataHlb;
    }

}