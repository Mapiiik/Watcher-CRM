<?php
declare(strict_types=1);

namespace Ruian\Controller;

/**
 * Addresses Controller
 *
 * @property \Ruian\Model\Table\AddressesTable $Addresses
 * @method \Ruian\Model\Entity\Address[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AddressesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Addresses.kod_adm::text ILIKE' => '%' . trim($search) . '%',
                    'Addresses.obec_nazev ILIKE' => '%' . trim($search) . '%',
                    'Addresses.momc_nazev ILIKE' => '%' . trim($search) . '%',
                    'Addresses.mop_nazev ILIKE' => '%' . trim($search) . '%',
                    'Addresses.cast_obce_nazev ILIKE' => '%' . trim($search) . '%',
                    'Addresses.ulice_nazev ILIKE' => '%' . trim($search) . '%',
                    'Addresses.cislo_domovni::text ILIKE' => '%' . trim($search) . '%',
                    'Addresses.psc::text ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'Addresses.id' => 'DESC',
            ],
        ];

        $addresses = $this->paginate($this->Addresses->find(
            'all',
            contain: [],
            conditions: $conditions
        ));

        $this->set(compact('addresses'));
    }

    /**
     * View method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $address = $this->Addresses->get($id, contain: []);

        $this->set(compact('address'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $address = $this->Addresses->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());
            if ($this->Addresses->save($address)) {
                $this->Flash->success(__d('ruian', 'The address has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $address->id]);
            }
            $this->Flash->error(__d('ruian', 'The address could not be saved. Please, try again.'));
        }
        $this->set(compact('address'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $address = $this->Addresses->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());
            if ($this->Addresses->save($address)) {
                $this->Flash->success(__d('ruian', 'The address has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $address->id]);
            }
            $this->Flash->error(__d('ruian', 'The address could not be saved. Please, try again.'));
        }
        $this->set(compact('address'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $address = $this->Addresses->get($id);
        if ($this->Addresses->delete($address)) {
            $this->Flash->success(__d('ruian', 'The address has been deleted.'));
        } else {
            $this->Flash->error(__d('ruian', 'The address could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
