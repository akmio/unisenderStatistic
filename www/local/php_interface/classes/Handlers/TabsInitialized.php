<?php

namespace Yolva\Handlers;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use CModule;
use Yolva\ServiceContainer\SmartProcessHelper;

class TabsInitialized
{
    public function __construct()
    {
        CModule::IncludeModule('crm');

        $eventManager = EventManager::getInstance();

        $eventManager->addEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            [$this, 'onEntityDetailsTabsInitialized']
        );
    }

    public static function onEntityDetailsTabsInitialized(Event $event)
    {
        $entityTypeID = $event->getParameter('entityTypeID');
        $entityID = $event->getParameter('entityID');
        $tabs = $event->getParameter('tabs');

        foreach ($tabs as $tab) {
            $new_tabs[] = $tab;
        }

            $new_tabs[] = [
                'id' => 'tab_statistical_data',
                'name' => 'Unisender',
                'loader' => [
                    'serviceUrl' => '/local/components/yolva/statistical.data/lazyload.ajax.php?&site='.\SITE_ID.'&'.\bitrix_sessid_get(),
                    'componentData' => [
                        'entityTypeID' => $entityTypeID,
                        'entityID' => $entityID,
                        'template' => '',
                        'params' => [
                            'ID_CONTACT' => $entityID,
                        ]
                    ]
                ]
            ];

        return new EventResult(EventResult::SUCCESS, [
            'tabs' => $new_tabs,
        ]);
    }
}
