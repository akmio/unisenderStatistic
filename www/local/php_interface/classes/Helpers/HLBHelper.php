<?php

namespace Yolva\Helpers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;

class HLBHelper
{
    public static array $hl;

    /**
     * Получение сущности HL Блока
     * @param string $hlCode
     * @return mixed
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public static function getEntityFromCode(string $hlCode)
    {
        if (Loader::includeModule("highloadblock")) {
            if (!isset($hl[$hlCode])) {
                $hlblock = HL\HighloadBlockTable::getList(['filter' => ['NAME' => $hlCode]])->fetch();
                $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                self::$hl[$hlCode] = $entity->getDataClass();
            }
            if (self::$hl[$hlCode])
                return self::$hl[$hlCode];
        }

        throw new Exception('Ошибка получения HL блока');
    }

    public static function getFromHlb($hlCode, array $select, array $filter)
    {
        $entityData = self::getEntityFromCode($hlCode);
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