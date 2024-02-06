<?php

namespace Yolva\Handlers;

use Yolva\Handlers\EpilogHandler;
use Yolva\Handlers\TabsInitialized;
use Yolva\Handlers\Rest\RestServiceEventHandler;

class EventHandlers
{
    public static function addEventHandlers ()
    {
        new EpilogHandler();
        new TabsInitialized();
        new RestServiceEventHandler();
    }
}



