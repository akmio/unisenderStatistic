<?
namespace Yolva\Agents\Crm\Contact;

use Yolva\Agents\Base;
use Yolva\Helpers\HLBHelper;
use Yolva\Services\Unisender\UnisenderStatisticalService;

class UpdateStatusMailContact extends Base
{
    public static function runUpdate(): string
    {
        $helper = new UpdateStatusMailContactHelper();
        $helper->run();
        return self::getFunctionString(__METHOD__);
    }


}

class UpdateStatusMailContactHelper
{
    private UnisenderStatisticalService $uniStatisticalService;
    function __construct()
    {
        $this->uniStatisticalService = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.unisender.statistical');
    }

    public function run()
    {
        $hlb = HLBHelper::getEntityFromCode('YlvUniMailing');

        $rsData = $hlb::getlist([
            'select' => ['UF_CAMPAIGNS_ID'],
            'filter' => ['>=UF_CREATE_DATE' => date('d.m.Y H:i:s', strtotime("-180 days"))]
        ]);
        
        while ($el = $rsData->fetch()){
            $this->uniStatisticalService->getStatusLetters($el['UF_CAMPAIGNS_ID']);
        }
    }
}
?>