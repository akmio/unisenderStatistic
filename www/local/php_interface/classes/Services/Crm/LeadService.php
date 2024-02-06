<?php


namespace Yolva\Services\Crm;


use Bitrix\Main\Type\DateTime;
use Yolva\Services\Crm\CrmService;

class LeadService extends CrmService
{
    function __construct()
    {
        $entityTypeId = $entityTypeId ?? \CCrmOwnerType::Lead;
        parent::__construct($entityTypeId);
    }
    function getByStatusID($statusId, $select = array(), $filter = array(), $orderBy = array('DATE_MODIFY' => 'DESC'))
    {
        $result = array();

        array_push($select, 'ID', 'ASSIGNED_BY_ID');
        array_push($filter,  Array(
            'STATUS_ID' => $statusId,
        ));

        $items = $this->getItems(['select' =>  $select, 'filter' =>  $filter, 'order' =>  $orderBy]);
        foreach ($items as &$item) {
            array_push($result,  $item->getData()['ID']);
        }

        return $result;
    }

    function getOldInProgressLeads($select = array(), $filter = array(), $orderBy = array('DATE_MODIFY' => 'DESC'))
    {
        $dateTime = new DateTime();
        $dateTime->add('-90 day');
        $date = $dateTime->format('d.m.Y');

        array_push($filter,  Array(
            '<=UF_LEAD_WIP_DATE' => $date,
        ));
        
        return $this->getByStatusID('WARMING_UP', $select, $filter);

    }
}