<?php
namespace Yolva\Services\Unisender;

use Bitrix\Main\Type\DateTime;

class UnisenderStatisticalService extends UnisenderService
{
    function __construct()
    {
        parent::__construct();
    }

    public function create($name){

    }
    public function get(){

    }

    //Метод для получения всех рассылок
    public function getCampaigns($from = ''){
        if (empty($from)){
            $from = date('Y-m-d H:i:s', strtotime("-1 year -1 days"));
        }else{
            $from = date('Y-m-d H:i:s', strtotime($from));
        }

        $filter =['from' => $from, 'to' => date('Y-m-d H:i:s')];
        $result = $this->uniApi->getCampaigns($filter);
        $resultCampaigns = json_decode($result, true);
        return $resultCampaigns['result'];
    }

    // Метод для получения всех писем
    public function getMessages($from = ''){

        if (empty($from)){
            $from = date('Y-m-d H:i', strtotime("-1 year -1 days"));
        }else{
            $from = date('Y-m-d H:i', strtotime($from));
        }

        $filter =['date_from' => $from, 'date_to' => date('Y-m-d H:i'), 'limit' => '100'];
        $result = $this->uniApi->getMessages($filter);
        $resultMessages = json_decode($result, true);
        return $resultMessages['result'];
    }

    // Метод для получения статусов писем
    public function getStatusLetters($campaignId){

        $result = $this->uniApi->taskGetCampaignDeliveryStats([
            'campaign_id'=>$campaignId,
            'notify_url' => getenv('NOTIFY_URL_STATUS').'?compaignId='.$campaignId,
        ]);
        $resultStatusLetters = json_decode($result, true);
        return $resultStatusLetters['result'];
    }
}