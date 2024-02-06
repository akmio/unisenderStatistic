<?
namespace Yolva\Services\Unisender\Decorator;

use Yolva\Services\Logger\LoggerService;
use Yolva\Base\BaseDecorator;

/**
 * API UniSender.
 *
 * @link https://www.unisender.com/en/support/category/api/
 * @link https://www.unisender.com/ru/support/category/api/
 *
 * @method string sendSms(array $params) It is a method for easy sending the one SMS to one or several recipients.
 * @method string sendEmail(array $params) It is a method to send a single individual email without personalization and
 * with limited possibilities to obtain statistics. To send transactional letters, use the
 * UniOne — the transactional letter service from UniSender. https://www.unisender.com/en/features/unione/
 * @method string getLists() It is a method to get the list of all available campaign lists.
 * @method string createList(array $params) It is a method to create a new contact list.
 * @method string updateList(array $params) It is a method to change campaign list properties.
 * @method string deleteList(array $params) It is a method to delete a list.
 * @method string exclude(array $params) The method excludes the contact’s email or phone number from one or several lists.
 * @method string unsubscribe(array $params) The method unsubscribes the contact email or phone number from one or several
 * lists.
 * @method string importContacts(array $params) It is a method of bulk import of contacts.
 * @method string getTotalContactsCount(array $params) The method returns the contacts database size by the user login.
 * @method string getContactCount(array $params) Get contact count in list.
 * @method string createEmailMessage(array $params) It is a method to create an email without sending it.
 * @method string createSmsMessage(array $params) It is a method to create SMS messages without sending them.
 * @method string createCampaign(array $params) This method is used to schedule or immediately start sending email
 * or SMS messages.
 * @method string getActualMessageVersion(array $params) The method returns the id of the relevant version of
 * the specified letter.
 * @method string checkSms(array $params) It returns a string — the SMS sending status.
 * @method string sendTestEmail(array $params) It is a method to send a test email message.
 * @method string checkEmail(array $params) The method allows you to check the delivery status of emails sent
 * using the sendEmail method.
 * @method string updateOptInEmail(array $params) Each campaign list has the attached text of the invitation
 * to subscribe and confirm the email that is sent to the contact to confirm the campaign. The text of the letter
 * can be changed using the updateOptInEmail method.
 * @method string getWebVersion(array $params) It is a method to get the link to the web version of the letter.
 * @method string deleteMessage(array $params) It is a method to delete a message.
 * @method string createEmailTemplate(array $params) It is a method to create an email template for a mass campaign.
 * @method string updateEmailTemplate(array $params) It is a method to edit email templates for a mass campaign.
 * @method string deleteTemplate(array $params) It is a method to delete a template.
 * @method string getTemplate(array $params) The method returns information about the specified template.
 * @method string getTemplates(array $params = []) This method is used to get the list of templates created
 * both through the UniSender personal account and through the API.
 * @method string listTemplates(array $params = []) This method is used to get the list of templates created both
 * through the UniSender personal account and through the API.
 * @method string getCampaignCommonStats(array $params) The method returns statistics similar to «Campaigns».
 * @method string getVisitedLinks(array $params) Get a report on the links visited by users in the specified email campaign.
 * @method string getCampaigns(array $params = array()) It is a method to get the list of all available campaigns.
 * @method string getCampaignStatus(array $params) Find out the status of the campaign created using the createCampaign method.
 * @method string getMessages(array $params = []) This method is used to get the list of letters created both
 * through the UniSender personal account and through the API.
 * @method string getMessage(array $params) It is a method to get information about SMS or email message.
 * @method string listMessages(array $params) This method is used to get the list of messages created both through
 * the UniSender personal account and through the API. The method works like getMessages, the difference of
 * listMessages is that the letter body and attachments are not returned, while the user login is returned. To get the
 * body and attachments, use the getMessage method.
 * @method string getFields() It is a method to get the list of user fields.
 * @method string createField(array $params) It is a method to create a new user field, the value of which can be set for
 * each recipient, and then it can be substituted in the letter.
 * @method string updateField(array $params) It is a method to change user field parameters.
 * @method string deleteField(array $params) It is a method to delete a user field.
 * @method string getTags() It is a method to get list of all tags.
 * @method string deleteTag(array $params) It is a method to delete a user tag.
 * @method string isContactInLists(array $params) Checks whether contact is in list.
 * @method string getContactFieldValues(array $params) Get addinitioan fields values for a contact.
 */

class UnisenderLoggingDecorator extends BaseDecorator
{
    private LoggerService $loggerServices;
    function __construct($object)
    {
        parent::__construct($object);
        $this->loggerServices = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('yolva.services.logger');
    }

    private const RETRY_COUNT = 3;
    protected function beforeMethodCalled($methodName, array $arguments)
    {
        /*$message = "Method '{$methodName}' of class '"
            . get_class($this->object)
            . "' called with parameters "
            . var_export($arguments, true);
        Logger::log($message);*/
    }

    protected function afterMethodCalled($methodName, array $arguments, $result)
    {
        $resultDecod = json_decode($result, true);

        if (!isset($resultDecod) || isset($resultDecod['error'])){
            if (isset($resultDecod) && ($resultDecod['code'] == 'api_call_limit_exceeded_for_api_key' || $resultDecod['code'] == 'api_call_limit_exceeded_for_ip')){

                $this->loggerServices->error($resultDecod['code'], ['Error'=>$resultDecod['error'],'Method'=>$methodName]);

                $countRepeats = 1;
                for ($retry = self::RETRY_COUNT; $retry > 0; $retry--){
                    sleep(60);

                    $resultRetry = call_user_func_array(array($this->object, $methodName), $arguments);

                    $resultRetry = json_decode($resultRetry, true);
                    if (isset($resultRetry['error']) && ($resultRetry['code'] == 'api_call_limit_exceeded_for_api_key' || $resultRetry['code'] == 'api_call_limit_exceeded_for_ip')){
                        $this->loggerServices->error($resultRetry['code'], ['Error'=>$resultRetry['error'],'Method'=>$methodName,'Repeat'=> $countRepeats++]);
                    }elseif(!isset($resultRetry) || isset($resultRetry['error'])){
                        $this->loggerServices->error($resultRetry['code'], ['Error'=>$resultRetry['error'],'Method'=>$methodName,'Repeat'=>$countRepeats]);
                        $resultRetryEncode = json_encode($resultRetry);
                        return $resultRetryEncode;
                    }else{
                        $resultRetryEncode = json_encode($resultRetry);
                        return $resultRetryEncode;
                    }
                }

            }else {
                $this->loggerServices->error($resultDecod['code'] ?? 'Неизвестная ошибка.', ['Error'=>$resultDecod['error'] ?? 'Неизвестная ошибка.','Method'=>$methodName]);
                return $result;
            }
        }else{
            return $result;
        }
    }

}
?>