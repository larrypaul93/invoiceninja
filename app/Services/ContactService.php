<?php

namespace App\Services;

use App\Models\Client;
use App\Ninja\Datatables\ContactDatatable;
use App\Ninja\Repositories\ContactRepository;

/**
 * Class ContactService.
 */
class ContactService extends BaseService
{
    /**
     * @var ContactRepository
     */
    protected $contactRepo;
    protected $datatableService;
    /**
     * ContactService constructor.
     *
     * @param ContactRepository $contactRepo
     */
    public function __construct(ContactRepository $contactRepo,DatatableService $datatableService)
    {
        $this->contactRepo = $contactRepo;
        $this->datatableService = $datatableService;
    }

    /**
     * @return ContactRepository
     */
    protected function getRepo()
    {
        return $this->contactRepo;
    }

    /**
     * @param $data
     * @param null $contact
     *
     * @return mixed|null
     */
    public function save($data, $contact = null)
    {
        if (isset($data['client_id']) && $data['client_id']) {
          //  $data['client_id'] = Client::getPrivateId($data['client_id']);
        }

        return $this->contactRepo->save($data, $contact);
    }

    public function getDatatable($clientID, $search,$userID = false)
    {
        $datatable = new ContactDatatable();
        if($clientID)
            $query = $this->contactRepo->findByClientId($clientID,$search);
        else
           $query = $this->contactRepo->find($search,$userID);  

        return $this->datatableService->createDatatable($datatable, $query);
    }

}
