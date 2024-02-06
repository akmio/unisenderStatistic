<?php


namespace Yolva\Services\Crm;
use \Bitrix\Crm\Item;

interface ICrmService
{
    public function getFactory(): \Bitrix\Crm\Service\Factory;
    public function getEntityTypeId();
    public function getItemsFilteredByPermissions(array $parameters, $userId = null, string $operation = \Bitrix\Crm\Service\UserPermissions::OPERATION_READ);
    public function getUpdateOperation(Item $item);
    public function getAddOperation(Item $item);
    public function getStages(int $categoryId = null);
    public function getItem($id): ?Item;
    public function getItems(array $parameters): array;
    /**
     * @var array $fieldsRows [ entityId_1 -> $fields, entityId_2 -> $fields,... ]
     * @var array $fields [ name1 -> value1, name2 -> value2,... ]
     */
    public function updateEntity(array $fieldsRows, $withoutLaunch = false);
    /**
     * @var string $postfix IN_PROGRESS (full: DT145_7:IN_PROGRESS)
     */
    public function getStageId(\Item $item, string $postfix);
    public function getCategoryId();
    /**
     * Преобразования фильтра в массив для getList
     */
    public function convertFilterToList(\Bitrix\Main\UI\Filter\Options $filterOption): array;
    public function saveItem(\Item $item);
    public function getUserFieldValue($fieldName, $str_value, $returnAsList = false);
    public function getUserFieldStrValueById($fieldName, $id);
}