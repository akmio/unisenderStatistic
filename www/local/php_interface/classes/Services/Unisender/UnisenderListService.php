<?php

declare(strict_types=1);

namespace Yolva\Services\Unisender;

class UnisenderListService extends UnisenderService
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Метод для создания нового списка контактов
     */
    public function create($name){
        $result = $this->uniApi->createList(['title' => $name]);
        return json_decode($result, true);
    }

    /**
     * Метод для получения перечня всех имеющихся списков рассылок
     */
    public function get(){
        $result = $this->uniApi->getLists();
        return json_decode($result, true);
    }

    /**
     * Метод для удаления списка в Unisender
     */
    public function delete($list_id){
        $result = $this->uniApi->deleteList($list_id);
        return json_decode($result, true);
    }

    /**
     * Импорт контактов в список Unisender
     */
    public function importContact(array $contacts)
    {
        $data = [];
        foreach ($contacts as $contact) {
            $email = $contact['EMAIL'];

            if (isset($data[$email])) {
                $data[$email][1] .= ',' . $contact['UNI_ID'];
            } else {
                $data[$email] = [$email, $contact['UNI_ID'], $contact['NAME']];
            }
        }

        $result = $this->uniApi->importContacts([
            'field_names' => ['email', 'email_list_ids', 'Name'],
            'data' => array_values($data)
        ]);

        return json_decode($result, true);
    }



    /**
     * Удаление контактов из списка Unisender
     */
    public function exclideContact ($emailCont, $idLists){

        $result = $this->uniApi->exclude([
                'contact_type' => 'email',
                'contact' => $emailCont,
                'list_ids' => $idLists
            ]

        );
        return json_decode($result, true);
    }
}