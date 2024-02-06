<?php

namespace Yolva\Agents\Marketing\Segment;

use Bitrix\Crm\Item;
use Yolva\Agents\Base;
use Yolva\Helpers\HLBHelper;
use Bitrix\Sender\Posting\SegmentDataBuilder;
use Bitrix\Sender\Internals\Model\GroupCounterTable;
use \Bitrix\Sender\SegmentDataTable;
use Yolva\Services\Crm\Smarts\MailingService;
use Yolva\Services\Crm\Smarts\MailingSettingsService;
use Yolva\Services\IBlock\MailingStepsService;
use Yolva\Services\Rest\YolvaUsersRestService;


class ActualizeSegment extends Base
{
    /**
     * Актуализирует контакты сегмента
     * @return string
     */
    public static function actualizeSegments(): string
    {
        $helper = new CreateMailingForNewContacts();

        $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');
        $rsData = $hlb::getlist([
            'select' => ['*'],
            'filter' => ['UF_AUTO_UPDATE' => '1']
        ]);

        while ($el = $rsData->fetch()) {
            $segmentId = $el['UF_B24_ID'];
            SegmentDataBuilder::actualize($segmentId, true);
            $cnt = SegmentDataTable::getCount(['=GROUP_ID' => $segmentId, 'HAS_EMAIL' => 'Y']);
            GroupCounterTable::update(['GROUP_ID' => $segmentId, "TYPE_ID" => 1], array("CNT" => $cnt));
            $helper->run($segmentId);
        }
        return self::getFunctionString(__METHOD__);
    }

    /**
     * Добавляет контакты в список Unisender
     * @return string
     */
    public static function addContacts($segmentId = ''): string
    {
        $serviceContact = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.contact');

        $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

        if (empty($segmentId)){
            $filter = ['UF_AUTO_UPDATE' => '1'];
        }else{
            $filter = ['UF_B24_ID' => $segmentId];
        }

        $rsData = $hlb::getlist([
            'select' => ['UF_UNI_ID'],
            'filter' => $filter
        ]);
        while ($uniListId = $rsData->fetch()){
            if (!empty($segmentId)) {
                YolvaUsersRestService::getFileWithContacts($uniListId['UF_UNI_ID']);
            }else {
                $serviceContact->getContactsFromUnisender($uniListId['UF_UNI_ID']);
            }
        }

        if (empty($segmentId)){
            return self::getFunctionString(__METHOD__);
        }else{
            return '';
        }
    }

    public static function addContactsBySegment($segmentId): string
    {
       self::addContacts($segmentId);
       return '';
    }
    public function getStageId(Item $item, string $postfix)
    {
        $categoryId = $item->getCategoryId();
        return "DT" . $this->entityTypeId . "_" . $categoryId . ":" . $postfix;
    }
}

class CreateMailingForNewContacts
{
    private MailingService $mailingService;
    private MailingSettingsService $mailingSettingsService;
    private MailingStepsService $mailingStepsService;

    function __construct()
    {
        $this->mailingService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.mailing');
        $this->mailingSettingsService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.crm.mailing.settings');
        $this->mailingStepsService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.iblock.mailing.steps');
    }

    public function run($segmentId)
    {
        $iblockId = $this->mailingStepsService->getIBlockId();
        $entityMailingSettingsId = $this->mailingSettingsService->getEntityTypeId();
        $mailing = $this->mailingService->getItems([]);
        $mailingSettings = $this->mailingSettingsService->getItems([]);

        $dbRes = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], false, false, ['ID', 'PROPERTY_MAIL_STATUS']);

        while ($aItem = $dbRes->Fetch()) {
            if ($aItem['PROPERTY_MAIL_STATUS_VALUE'] == 'первый шаг') {
                $mailingCurrentStep = $aItem['ID'];
            }
        }

        $contactIds = [];

        foreach ($mailing as $value) {
            $contactIds[] = $value['CONTACT_ID'];
        }
        $params = [
            'mailingSettings' => $mailingSettings,
            'mailingCurrentStep' => $mailingCurrentStep,
            'entityMailingSettingsId' => $entityMailingSettingsId,
        ];

        SegmentDataBuilder::actualize($segmentId, true);
        $cnt = SegmentDataTable::getList([
            "select" => ['CONTACT_ID'],
            "filter" => [
                "GROUP_ID" => $segmentId,
            ]
        ]);

        while ($segment = $cnt->Fetch()) {
            foreach ($params['mailingSettings'] as $mailingSetting) {
                if ($mailingSetting['UF_MAILING_SETTINGS_SEGMENT'] == $segmentId) {
                    $fieldsMailing = [
                        'CONTACT_ID' => $segment['CONTACT_ID'],
                        "PARENT_ID_{$params['entityMailingSettingsId']}" => $mailingSetting['ID'],
                        'UF_MAILING_CURRENT_STEP' => $params['mailingCurrentStep'],
                    ];
                    $this->mailingService->addElementMailing($contactIds, $segment['CONTACT_ID'], $fieldsMailing);
                }
            }
        }
    }
}