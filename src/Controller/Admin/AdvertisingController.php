<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Advertisement;
use App\Service\AdvertisingService;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Admin controller for provider settings and advertising campaigns.
 */
class AdvertisingController extends AppController
{
    private AdvertisingService $advertisingService;

    /**
     * Create the controller with its advertising service.
     *
     * @param \Cake\Http\ServerRequest $request Current request.
     * @param \App\Service\AdvertisingService $advertisingService Advertising service.
     * @param \Cake\Controller\ComponentRegistry|null $components Component registry.
     */
    public function __construct(
        ServerRequest $request,
        AdvertisingService $advertisingService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
        $this->advertisingService = $advertisingService;
    }

    /**
     * Show provider settings and the campaign list.
     */
    public function index(): ?Response
    {
        $this->request->allowMethod(['get']);

        $advertisements = $this->advertisingService->getAdvertisements();
        $settings = $this->advertisingService->getSettings();
        $sports = $this->advertisingService->getAvailableSports();
        $teams = $this->advertisingService->getTeamOptions();
        $placementOptions = $this->advertisingService->getPlacementOptions();

        $this->set(compact(
            'advertisements',
            'settings',
            'sports',
            'teams',
            'placementOptions',
        ));

        return null;
    }

    /**
     * Add an advertising campaign.
     */
    public function add(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $advertisement = $this->advertisingService->newAdvertisement();

        if ($this->request->is('post')) {
            $advertisement = $this->advertisingService->patchAdvertisement(
                $advertisement,
                (array)$this->request->getData(),
            );
            if ($this->advertisingService->saveAdvertisement($advertisement)) {
                $this->Flash->success('The advertisement has been saved.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The advertisement could not be saved. Please correct the errors and try again.');
        }

        $this->setFormViewData($advertisement);

        return null;
    }

    /**
     * Edit an advertising campaign.
     *
     * @param int $id Campaign id.
     */
    public function edit(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post', 'put', 'patch']);
        $advertisement = $this->advertisingService->getAdvertisement($id);
        if (!$advertisement instanceof Advertisement) {
            throw new NotFoundException('Advertisement not found.');
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $advertisement = $this->advertisingService->patchAdvertisement(
                $advertisement,
                (array)$this->request->getData(),
            );
            if ($this->advertisingService->saveAdvertisement($advertisement)) {
                $this->Flash->success('The advertisement has been saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }

            $this->Flash->error('The advertisement could not be saved. Please correct the errors and try again.');
        }

        $this->setFormViewData($advertisement);

        return null;
    }

    /**
     * Delete an advertising campaign.
     *
     * @param int $id Campaign id.
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $advertisement = $this->advertisingService->getAdvertisement($id);
        if (!$advertisement instanceof Advertisement) {
            throw new NotFoundException('Advertisement not found.');
        }

        if ($this->advertisingService->deleteAdvertisement($advertisement)) {
            $this->Flash->success('The advertisement has been deleted.');
        } else {
            $this->Flash->error('The advertisement could not be deleted. Please try again.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Save the shared provider settings.
     */
    public function saveSettings(): Response
    {
        $this->request->allowMethod(['post']);

        if ($this->advertisingService->saveSettings((array)$this->request->getData())) {
            $this->Flash->success('Advertising provider settings have been saved.');
        } else {
            $this->Flash->error(
                'Advertising provider settings could not be saved. '
                . 'Please check the values and try again.',
            );
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Write the configured publisher ID to webroot/ads.txt.
     */
    public function writeAdsTxt(): Response
    {
        $this->request->allowMethod(['post']);

        $settings = $this->advertisingService->getSettings();
        if ($settings['publisher_id'] === '') {
            $this->Flash->error('No Publisher ID configured.');
        } elseif ($this->advertisingService->writeAdsTxt(WWW_ROOT)) {
            $this->Flash->success('ads.txt has been written to webroot.');
        } else {
            $this->Flash->error('Unable to write ads.txt. Check the Publisher ID and filesystem permissions.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Add the options needed by the campaign form.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Campaign entity.
     */
    private function setFormViewData(Advertisement $advertisement): void
    {
        $sports = $this->advertisingService->getAvailableSports();
        $teams = $this->advertisingService->getTeamOptions();
        $placementOptions = $this->advertisingService->getPlacementOptions();

        $this->set(compact('advertisement', 'sports', 'teams', 'placementOptions'));
    }
}
