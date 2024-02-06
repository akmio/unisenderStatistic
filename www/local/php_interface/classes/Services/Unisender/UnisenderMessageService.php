<?
namespace Yolva\Services\Unisender;

class UnisenderMessageService extends UnisenderService
{
    function __construct()
    {
        parent::__construct();
    }

    public function create($name)
    {

    }

    public function get($email = '')
    {

    }

    public function createEmailMessage($data) {
        $result = $this->uniApi->createEmailMessage([
            'sender_name' => $data['sender_name'],
            'sender_email' => $data['sender_email'],
            'subject' => $data['subject'],
            'template_id' => $data['template_id'],
            'list_id' =>$data['list_id'],
        ]);
        return json_decode($result, true);
    }

    public function createCampaign($data) {
        $result = $this->uniApi->createCampaign([
            'message_id' => $data['message_id'],
            'track_read' => $data['track_read'],
            'track_links' => $data['track_links'],
        ]);
        return json_decode($result, true);
    }

}
?>