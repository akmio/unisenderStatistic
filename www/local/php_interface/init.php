<?
use Yolva\Handlers\EventHandlers;

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_deal_events.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_deal_events.php";

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_lead_events.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_lead_events.php";	

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_timeline_events.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_timeline_events.php";	

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_contact_events.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_contact_events.php";	
	
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_licenses_events.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/ylv_licenses_events.php";	

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/const.php"))
	include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/const.php";




/**
 * Подключаем composer
 */
$composerPath = realpath(dirname(__FILE__, 4) . '/composer/vendor/autoload.php');
$issetComposer = file_exists($composerPath);

if ($issetComposer)
    require_once $composerPath;

if ($issetComposer) {

    //Include env file
    (new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(__DIR__, strtolower('.env')))
        ->bootstrap();

    Services::addInstancesLazy();
    EventHandlers::addEventHandlers();
}

class Services
{
    public static function addInstancesLazy()
    {
        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.logger',
            ['constructor' => static function () {
                return new Yolva\Services\Logger\LoggerService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.crm.contact',
            ['constructor' => static function () {
                return new Yolva\Services\Crm\ContactService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.iblock.mailing.steps',
            ['constructor' => static function () {
                return new Yolva\Services\IBlock\MailingStepsService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.unisender.list',
            ['constructor' => static function () {
                return new Yolva\Services\Unisender\UnisenderListService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.unisender.statistical',
            ['constructor' => static function () {
                return new Yolva\Services\Unisender\UnisenderStatisticalService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.unisender.contact',
            ['constructor' => static function () {
                return new Yolva\Services\Unisender\UnisenderContactService();
            }]
        );
        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.crm.smarts.licenses',
            ['constructor' => static function () {
                return new Yolva\Services\Crm\Smarts\LicensesService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.unisender.message',
            ['constructor' => static function () {
                return new Yolva\Services\Unisender\UnisenderMessageService();
            }]
        );

        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.crm.mailing',
            ['constructor' => static function () {
                return new Yolva\Services\Crm\Smarts\MailingService();
            }]
        );
        \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy(
            'yolva.services.crm.mailing.settings',
            ['constructor' => static function () {
                return new Yolva\Services\Crm\Smarts\MailingSettingsService();
            }]
        );
    }
}