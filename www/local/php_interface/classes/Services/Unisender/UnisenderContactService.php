<?
namespace Yolva\Services\Unisender;

class UnisenderContactService extends UnisenderService
{
    function __construct()
    {
        parent::__construct();
    }

    public function create($name)
    {

    }

    /**
     * Получаем информацию о контакте
     */
    public function get($email = '')
    {
        if (!empty($email)){
            $params =['email' => $email, 'include_details' => '1'];
            $result = $this->uniApi->getContact($params);
            $result = json_decode($result, true);
            return $result['result']['email'];
        }else{
            return false;
        }
    }

    public function getContactsFromUnisender ($listId){
        $result = $this->uniApi->taskExportContacts([
                'notify_url' => getenv('NOTIFY_URL'),
                'list_id' => $listId,
                'field_names' => ['email', 'email_list_ids'],
            ]

        );
        return json_decode($result, true);
    }

    public function exportContacts($listId){
        $result = $this->uniApi->taskExportContacts([
                'notify_url' => getenv('NOTIFY_URL_FIELDS'),
                'list_id' => $listId,
                'field_names' => [
                    'email',
                    'email_add_time',
                    'email_last_read_at',
                    'email_status',
                    'email_availability',
                    'email_last_delivered_at',
                    'email_last_clicked_at',
                ],
            ]
        );
        return json_decode($result, true);
    }

}
?>