<?php

namespace Yolva\Handlers;

use Bitrix\Main\EventManager;
use Bitrix\Main\Page\Asset;
use Yolva\Helpers\HLBHelper;
use CModule;


class EpilogHandler
{
    public function __construct()
    {
        CModule::IncludeModule('main');
        $eventManager = EventManager::getInstance();
        $eventManager->addEventHandler(
            'main',
            'OnEpilog',
            [$this, 'MyOnEpilogHandler'],
            100
        );
    }

    
    function MyOnEpilogHandler()
    {
        global $APPLICATION;
        $asset = Asset::getInstance();
        $curPage = $APPLICATION->GetCurPage();

        if (str_contains($curPage, "/marketing/segment/edit/")) {
            \CJSCore::RegisterExt("UnisenderExport", array(
                "js" =>    [
                    "/local/js/segment/detail/UnisenderExport.js"
                ],
                "css" => "",
                "lang" =>   "",
                "rel" =>   array("main", "jquery")
            ));
            \CJSCore::Init(array("UnisenderExport"));

            $arrPropsHlb = $this->getFieldsHLblock();

            $asset->addString('<script>BX.ready(function () {
                BX.UnisenderExport.createSaveButton('.$arrPropsHlb['UF_B24_ID'].');
            })</script>');
            $asset->addString('<script>BX.ready(function () {
                BX.UnisenderExport.createCheckbox(' .$arrPropsHlb['UF_AUTO_UPDATE'] . ');
            })</script>');
            $asset->addString('<script>BX.ready(function () {
                BX.UnisenderExport.submitCheckbox('.$arrPropsHlb['UF_AUTO_UPDATE'].');
            })</script>');
        }

        if (str_contains($curPage, "/mail/")) {
            \CJSCore::RegisterExt("AddButtonContact", array(
                "js" =>    [
                    "/local/js/mail/AddContact.js"
                ],
                "css" => "",
                "lang" =>   "",
                "rel" =>   array("main", "jquery")
            ));
            \CJSCore::Init(array("AddButtonContact"));

            $asset->addString('<script>BX.ready(function () {
                BX.AddButtonContact.init();
            })</script>');
        }
        if (str_contains($curPage, "services/lists/32/element/0/")) {
            \CJSCore::RegisterExt("filterPrevStep", array(
                "js" =>    [
                    "/local/js/iblock/MailingSteps.js"
                ],
                "css" => "",
                "lang" =>   "",
                "rel" =>   array("main", "jquery")
            ));
            \CJSCore::Init(array("filterPrevStep"));
        }

        if (str_contains($curPage, "/mail/")) {
            \CJSCore::RegisterExt("AddButtonCrm", array(
                "js" =>    [
                    "/local/js/mail/AddCrm.js"
                ],
                "css" => "",
                "lang" =>   "",
                "rel" =>   array("main", "jquery")
            ));
            \CJSCore::Init(array("AddButtonCrm"));

            $asset->addString('<script>BX.ready(function () {
                BX.AddButtonCrm.init();
            })</script>');
        }
    }

    function getFieldsHLblock()
    {
        global $APPLICATION;
        $curPage = substr($APPLICATION->GetCurPage(),0, -1);
        $segmentId = str_replace('/marketing/segment/edit/','', $curPage);

        $hlb = HLBHelper::getEntityFromCode('YlvUniSegment');

        $rsData = $hlb::getlist([
            'select' => ['UF_AUTO_UPDATE', 'UF_B24_ID'],
            'filter' => ['UF_B24_ID' => $segmentId]
        ]);
        
        while ($el = $rsData->fetch()){

            $arrProps = $el;
        }
        return $arrProps;

    }
}
