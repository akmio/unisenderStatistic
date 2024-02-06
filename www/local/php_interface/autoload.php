<?php
Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    #region SERVICES
    'Yolva\Services\Unisender\UnisenderListService' =>'/local/php_interface/classes/Services/UnisenderListService.php',
    'Yolva\Services\Unisender\UnisenderService' =>'/local/php_interface/classes/Services/UnisenderService.php',

    #endregion

    #region HANDLERS
    'Handlers\EventHandlers' => '/local/php_interface/classes/Handlers/EventHandlers.php',
    'Yolva\Handlers\EpilogHandler' => '/local/php_interface/classes/Handlers/EpilogHandler.php',
    #endregion

    #region HELPERS
    'Yolva\Helpers\HLBHelper' => '/local/php_interface/classes/Helpers/HLBHelper.php',
    #endregion

    #region AGENT
    'Agents\Base' =>'/local/php_interface/classes/Agents/Base.php',
    'Agents\Marketing\Segment\ActualizeSegment' =>'/local/php_interface/classes/Agents/Marketing/Segment/ActualizeSegment.php',
    #endregion

    #regions TRAITS
    #endregion
    #region MODEL

    #endregion

    #region ETC

    #socserv
    #endsocserv
]);
