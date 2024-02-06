<?php

namespace Yolva\Services\Crm;

use Bitrix\Crm\Item;
use Error;
use Bitrix\Crm\Service;
use Bitrix\Main\Diag\Debug;
use CBPDocument;
use CBPWorkflowTemplateLoader;
use Tools\Crm\Smarts\Helpers as CrmHelpers;
use Yolva\Helper\DepartmentHelper;

class CrmService implements ICrmService
{
    private $entityTypeId;
    private $factory;

    function __construct($entityTypeId = null)
    {
        $this->entityTypeId = $this->validateEntityType($entityTypeId);
        $this->factory = $this->getFactory();
    }

    function getByDepartmentId($departmentId, $select = array(), $filter = array(), $orderBy = array('DATE_MODIFY' => 'DESC'))
    {
        $result = array();
        // список пользователей
        $userResult = \CUser::GetList('id','asc',
            array(
                'UF_DEPARTMENT' => $departmentId
            ),
            array(
                'SELECT' => ['ID', 'UF_DEPARTMENT']
            ));

        while($ar = $userResult->Fetch())
        {
            $users[] = $ar;
        }
        $usersIds = [];
        foreach ($users as $user) {
            $usersIds[] = $user['ID'];
        }

        array_push($select, 'ID', 'ASSIGNED_BY_ID');
        array_push($filter,  Array(
            'ASSIGNED_BY_ID' => $usersIds,
        ));

        $items = $this->getItems(['select' =>  $select, 'filter' =>  $filter, 'order' =>  $orderBy]);
        foreach ($items as &$item) {
            array_push($result,  $item->getData()['ID']);
        }

        return $result;
    }
    function getForConsultingDepartment($departmentId, $select = array(), $filter = array(), $orderBy = array('DATE_MODIFY' => 'DESC'))
    {
        $consultingDepartmentId = "consulting";
        \Kint::dump(DepartmentHelper::findDepartment());

        return $this->getByDepartmentId($consultingDepartmentId, $select = array(), $filter = array(), $orderBy);
    }

    private function validateEntityType($entityTypeId)
    {
        $sourceEntityTypeId = $entityTypeId;
        if (!isset($entityTypeId) || $entityTypeId == null || $entityTypeId == "")
            throw new Error('Тип сущности не может принимать значение null');
        $entityTypeId = trim($entityTypeId);
        if (is_numeric($entityTypeId)) {
            $entityTypeId = intval($entityTypeId);
            if ($entityTypeId == 0)
                throw new Error('Тип сущности не может быть равен 0');
        } else
            $entityTypeId = \CCrmOwnerType::ResolveID($entityTypeId);
        if ($entityTypeId === \CCrmOwnerType::Undefined)
            throw new Error('Тип сущности не определен - \"' . $sourceEntityTypeId . "\"");
        return $entityTypeId;
    }
    public function getFactory(): \Bitrix\Crm\Service\Factory
    {
        return Service\Container::getInstance()->getFactory($this->entityTypeId);
    }
    public function getEntityTypeId()
    {
        return $this->factory->getEntityTypeId();
    }
    public function getItemsFilteredByPermissions(array $parameters, $userId = null, string $operation = \Bitrix\Crm\Service\UserPermissions::OPERATION_READ)
    {
        return $this->factory->getItemsFilteredByPermissions(
            $parameters,
            $userId,
            $operation
        );
    }
    public function getStages(int $categoryId = null)
    {
        return $this->factory->getStages($categoryId);
    }
    public function getUpdateOperation(Item $item)
    {
        return $this->factory->getUpdateOperation($item);
    }
    public function getAddOperation(\Bitrix\Crm\Item $item)
    {
        return $this->factory->getAddOperation($item);
    }
    public function getItem($id): ?Item
    {
        if (!ctype_digit($id) && !is_numeric($id)) return null;
        return $this->factory->getItem($id);
    }
    public function getItems(array $parameters): array
    {
        return $this->factory->getItems($parameters);
    }
    /**
     * @var array $fieldsRows [ entityId_1 -> $fields, entityId_2 -> $fields,... ]
     * @var array $fields [ name1 -> value1, name2 -> value2,... ]
     */
    public function updateEntity(array $fieldsRows, $withoutLaunch = false)
    {
        $result = array();
        foreach ($fieldsRows as $entityId => $fieldList) {

            $item = $this->factory->getItem($entityId);
            $item->setFromCompatibleData($fieldList);
            if ($withoutLaunch) {
                $item->save();
            } else {
                $updateResult = $this->getUpdateOperation($item)->launch();
                if (!$updateResult)
                    throw new Error($updateResult->getErrorMessages());
            }
            array_push($result, $item->getData());
        }
        return $result;
    }

    /**
     * @var array $fieldsRows [ entityId_1 -> $fields, entityId_2 -> $fields,... ]
     * @var array $fields [ name1 -> value1, name2 -> value2,... ]
     */
    public function addEntity(array $fieldsRows, $withoutLaunch = false)
    {
        $result = array();
        foreach ($fieldsRows as $entityId => $fieldList) {
            $item = $this->factory->createItem();
            $item->setFromCompatibleData($fieldList);
            if ($withoutLaunch) {
                $item->save();
            } else {
                $operations = $this->getAddOperation($item);
                $updateResult = $operations->launch();
                if (!$updateResult)
                    throw new Error($updateResult->getErrorMessages());
            }
            array_push($result, $item->getData());
        }
        return $result;
    }

    /**
     * @var array $fieldsRows [ entityId_1 -> $fields, entityId_2 -> $fields,... ]
     * @var array $fields [ name1 -> value1, name2 -> value2,... ]
     */
    public function deleteEntity(array $fieldsRows, $withoutLaunch = false)
    {
        $result = array();
        foreach ($fieldsRows as $entityId => $fieldList) {

            $item = $this->factory->getItem($entityId);
            $item->setFromCompatibleData($fieldList);
            if ($withoutLaunch) {
                $item->delete();
            } else {
                $deleteResult = $this->factory->getDeleteOperation($item)->launch();
                if (!$deleteResult)
                    throw new Error($deleteResult->getErrorMessages());
            }
            array_push($result, $item->getData());
        }
        return $result;
    }

    public function saveItem(\Item $item)
    {
        return $item->save();
    }
    /**
     * @var string $postfix IN_PROGRESS (full: DT145_7:IN_PROGRESS)
     */
    public function getStageId(\Item $item, string $postfix)
    {
        $categoryId = $item->getCategoryId();
        return "DT" . $this->entityTypeId . "_" . $categoryId . ":" . $postfix;
    }
    public function getCategoryId()
    {
        $result = \Bitrix\Crm\StatusTable::getList([
            "select" => ["CATEGORY_ID"],
            "filter" => ["ENTITY_ID" => "%" . $this->entityTypeId . "%"]
        ])->fetch();
        return $result["CATEGORY_ID"];
    }
    /**
     * Преобразования фильтра в массив для getList
     */
    public function convertFilterToList(\Bitrix\Main\UI\Filter\Options $filterOption): array
    {
        $filterData = $filterOption->getFilter();
        $instance = \Bitrix\Crm\Service\Container::getInstance();
        $filterFactory = $instance->getFilterFactory();
        $type = $instance->getTypeByEntityTypeId($this->factory->getEntityTypeId());

        $filterParams = [
            'categoryId' => $this->getCategoryId(),
            'type' => $type,
        ];

        $settings = $filterFactory->getSettings(
            $this->factory->getEntityTypeId(),
            $filterOption->getId(),
            $filterParams
        );

        $filterFields = $filterFactory->getFilter($settings)->getFieldArrays();
        $ufProvider = new \Bitrix\Crm\Filter\UserFieldDataProvider($settings);
        $filterParams['ID'] = $filterOption->getId();
        $itemSettings = new \Bitrix\Crm\Filter\ItemSettings($filterParams, $type);
        $provider = new \Bitrix\Crm\Filter\ItemDataProvider($itemSettings, $this->factory);

        $list = [];

        $provider->prepareListFilter($list, $filterData);
        $ufProvider->prepareListFilter($list, $filterFields, $filterData);

        return $list;
    }
    private function getUserFieldId($fieldName)
    {
        $rsUserFields = \Bitrix\Main\UserFieldTable::GetList(
            [
                'select' => ['ID', 'FIELD_NAME'],
                'filter' => [
                    'FIELD_NAME' => [$fieldName],
                ]
            ]
        );
        return $rsUserFields->fetch()['ID'];
    }
    public function getUserFieldValue($fieldName, $str_value, $returnAsList = false)
    {
        $filter = [
            'USER_FIELD_ID' => $this->getUserFieldId($fieldName),
        ];
        if (!$returnAsList)
            $filter['VALUE'] = $str_value;
        $rsUserFieldEnums = \CUserFieldEnum::GetList(
            array(),
            $filter
        );
        if ($returnAsList) {
            $result = [];
            while ($rs = $rsUserFieldEnums->GetNext())
                $result[] = $rs;
            return $result;
        }
        return $rsUserFieldEnums->Fetch()['ID'];
    }
    public function getUserFieldStrValueById($fieldName, $id)
    {
        $filter = [
            'USER_FIELD_ID' => $this->getUserFieldId($fieldName),
            'ID'=>$id,
        ];
        $rsUserFieldEnums = \CUserFieldEnum::GetList(
            array(),
            $filter
        );
        return $rsUserFieldEnums->Fetch()['VALUE'];
    }

    public static function startWorkflow(string $nameWorkFlow, \Bitrix\Crm\Item $item, $parameters = [], array &$arErrorsTmp = null): bool
    {
        $arErrorsTmp = [];
        $db = CBPWorkflowTemplateLoader::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['NAME' => $nameWorkFlow],
            false,
            array("ID")
        )->fetch();
        if (empty($db['ID'])) {
            $arErrorsTmp[] = "Не удалось найти БП '$nameWorkFlow'";
        } else {
            CBPDocument::StartWorkflow(
                $db['ID'],
                array(
                    "crm",
                    "Bitrix\Crm\Integration\BizProc\Document\Dynamic",
                    "DYNAMIC_{$item->getEntityTypeId()}_{$item->getId()}"
                ),
                $parameters,
                $arErrorsTmp
            );
        }
        return count($arErrorsTmp) == 0 ? true : false;
    }
    public function createNewItem($params)
    {
        return $this->factory->CreateItem($params);
    }
}
