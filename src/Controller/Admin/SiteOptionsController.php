<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteOptionsService;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Throwable;

/**
 * Admin Site Options Controller
 *
 * Thin HTTP adapter for global site options management.
 */
class SiteOptionsController extends AppController
{
    private SiteOptionsService $siteOptionsService;

    /**
     * Initialization hook method.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->siteOptionsService = new SiteOptionsService();
    }

    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (
            in_array(
                (string)$this->request->getParam('action'),
                ['editSportConfigs'],
                true,
            )
            && $this->components()->has('FormProtection')
        ) {
            $this->FormProtection->setConfig('unlockedActions', ['editSportConfigs']);
        }
    }

    /**
     * Edit global site options.
     *
     * URL: /admin/site-options/edit
     *
     * @return \Cake\Http\Response|null
     */
    public function edit(): ?Response
    {
        if ($this->request->is(['patch', 'post', 'put'])) {
            $saved = $this->siteOptionsService->saveSettings((array)$this->request->getData());

            if ($saved) {
                $this->Flash->success('Site options have been saved.');
            } else {
                $this->Flash->error('Site options could not be saved. Please try again.');
            }

            return $this->redirect(['action' => 'edit']);
        }

        $siteOptionDefinitions = $this->siteOptionsService->getDefinitions();
        $siteOptions = $this->siteOptionsService->getRuntimeSettings();

        $this->set(compact('siteOptionDefinitions', 'siteOptions'));

        return null;
    }

    /**
     * View sport configurations from the SiteOptions settings surface.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response|null
     */
    public function sportsConfigs(?string $sportRef = null): ?Response
    {
        $sportOptions = $this->siteOptionsService->getAvailableSports();
        $sportKey = $this->resolveSportKey($sportRef, $sportOptions);
        if ($sportKey === null) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'edit']);
        }

        $routeSportRef = $this->resolveSportRouteRef($sportRef, $sportKey);
        $sportDisplayName = $this->siteOptionsService->getSportDisplayName($sportKey);
        $configs = $this->siteOptionsService->getFormattedSportConfigs($sportKey);
        $configs = $this->siteOptionsService->normalizeFormattedSportConfigs($configs, $sportKey);
        $configController = 'SiteOptions';

        $this->set(compact(
            'configs',
            'sportKey',
            'sportDisplayName',
            'sportOptions',
            'routeSportRef',
            'configController',
        ));

        return $this->render('/Admin/Sports/configs');
    }

    /**
     * Edit sport configurations from the SiteOptions settings surface.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response|null
     */
    public function editSportConfigs(?string $sportRef = null): ?Response
    {
        $sportOptions = $this->siteOptionsService->getAvailableSports();
        $sportKey = $this->resolveSportKey($sportRef, $sportOptions);
        if ($sportKey === null) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'edit']);
        }

        $routeSportRef = $this->resolveSportRouteRef($sportRef, $sportKey);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $configData = (array)$this->request->getData('configs', []);

            if ($this->siteOptionsService->saveSportConfigs($sportKey, $configData)) {
                $this->Flash->success(__('Sport configurations have been updated.'));

                return $this->redirect(['action' => 'sportsConfigs', $routeSportRef]);
            }

            $this->Flash->error(__('Unable to update sport configurations. Please try again.'));
        }

        $sportDisplayName = $this->siteOptionsService->getSportDisplayName($sportKey);
        $configs = $this->siteOptionsService->getFormattedSportConfigs($sportKey);
        $configs = $this->siteOptionsService->normalizeFormattedSportConfigs($configs, $sportKey);
        $configController = 'SiteOptions';

        $this->set(compact(
            'configs',
            'sportKey',
            'sportDisplayName',
            'sportOptions',
            'routeSportRef',
            'configController',
        ));

        return $this->render('/Admin/Sports/edit_configs');
    }

    /**
     * Add a single sport config key/value from SiteOptions settings.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response
     */
    public function addSportConfig(?string $sportRef = null): Response
    {
        $this->request->allowMethod(['post']);

        $sportKey = $this->resolveSportKey($sportRef, $this->siteOptionsService->getAvailableSports());
        if ($sportKey === null) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'edit']);
        }

        $key = (string)$this->request->getData('config_key', '');
        $value = $this->request->getData('config_value');
        $description = $this->request->getData('description');

        if ($key === '') {
            $this->Flash->error(__('Configuration key is required.'));

            return $this->redirect(['action' => 'editSportConfigs', $this->resolveSportRouteRef($sportRef, $sportKey)]);
        }

        if (is_string($value) && str_contains($value, ',')) {
            $value = array_map('trim', explode(',', $value));
        }

        $saved = $this->siteOptionsService->setSportConfig($sportKey, $key, $value, (string)$description);
        if ($saved) {
            $this->Flash->success(__('Configuration added successfully.'));
        } else {
            $this->Flash->error(__('Unable to add configuration. Please try again.'));
        }

        return $this->redirect(['action' => 'editSportConfigs', $this->resolveSportRouteRef($sportRef, $sportKey)]);
    }

    /**
     * Delete a single sport config key from SiteOptions settings.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @param string $configKey Configuration key
     * @return \Cake\Http\Response
     */
    public function deleteSportConfig(?string $sportRef = null, string $configKey = ''): Response
    {
        $this->request->allowMethod(['delete']);

        $sportKey = $this->resolveSportKey($sportRef, $this->siteOptionsService->getAvailableSports());
        if ($sportKey === null) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'edit']);
        }

        if ($configKey === '') {
            $this->Flash->error(__('Unable to delete configuration.'));

            return $this->redirect(['action' => 'editSportConfigs', $this->resolveSportRouteRef($sportRef, $sportKey)]);
        }

        if ($this->siteOptionsService->deleteSportConfig($sportKey, $configKey)) {
            $this->Flash->success(__('Configuration deleted successfully.'));
        } else {
            $this->Flash->error(__('Unable to delete configuration.'));
        }

        return $this->redirect(['action' => 'editSportConfigs', $this->resolveSportRouteRef($sportRef, $sportKey)]);
    }

    /**
     * Reset sport configurations to defaults from SiteOptions settings.
     *
     * @param string|null $sportRef Sport key or legacy sport ID
     * @return \Cake\Http\Response
     */
    public function resetSportConfigs(?string $sportRef = null): Response
    {
        $this->request->allowMethod(['post']);

        $sportKey = $this->resolveSportKey($sportRef, $this->siteOptionsService->getAvailableSports());
        if ($sportKey === null) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'edit']);
        }

        if ($this->siteOptionsService->resetSportConfigs($sportKey)) {
            $this->Flash->success(__('Sport configurations have been reset to defaults.'));
        } else {
            $this->Flash->error(__('Unable to reset configurations. Please try again.'));
        }

        return $this->redirect(['action' => 'editSportConfigs', $this->resolveSportRouteRef($sportRef, $sportKey)]);
    }

    /**
     * Write a minimal ads.txt file to webroot using the configured publisher ID.
     *
     * POST /admin/site-options/write-ads-txt
     *
     * @return \Cake\Http\Response
     */
    public function writeAdsTxt(): Response
    {
        $this->request->allowMethod(['post']);

        $publisherId = (string)$this->siteOptionsService->getRuntimeSetting('ad_publisher_id', '');
        if (trim($publisherId) === '') {
            $this->Flash->error('No Publisher ID configured.');

            return $this->redirect(['action' => 'edit']);
        }

        $adsFile = WWW_ROOT . 'ads.txt';
        $line = sprintf("google.com, %s, DIRECT, f08c47fec0942fa0\n", $publisherId);

        try {
            file_put_contents($adsFile, $line, LOCK_EX);
            $this->Flash->success('ads.txt has been written to webroot.');
        } catch (Throwable $e) {
            $this->Flash->error('Unable to write ads.txt. Check filesystem permissions.');
        }

        return $this->redirect(['action' => 'edit']);
    }

    /**
     * Resolve a canonical sport key from route/query references.
     *
     * @param string|null $sportRef
     * @param array<string,string> $sportOptions
     * @return string|null
     */
    private function resolveSportKey(?string $sportRef, array $sportOptions): ?string
    {
        if ($sportOptions === []) {
            return null;
        }

        $candidate = $sportRef;
        if ($candidate === null || trim($candidate) === '') {
            $fromQuery = $this->request->getQuery('sport_key');
            if (is_string($fromQuery) && trim($fromQuery) !== '') {
                $candidate = $fromQuery;
            }
        }

        if ($candidate === null || trim($candidate) === '') {
            return (string)array_key_first($sportOptions);
        }

        $candidate = trim($candidate);
        if (ctype_digit($candidate)) {
            return $this->siteOptionsService->getSportKeyForId((int)$candidate);
        }

        $normalized = strtolower($candidate);

        return array_key_exists($normalized, $sportOptions) ? $normalized : null;
    }

    /**
     * Preserve incoming route style (numeric ID or semantic sport key)
     * when generating follow-up links and redirects.
     *
     * @param string|null $sportRef
     * @param string $sportKey
     * @return string
     */
    private function resolveSportRouteRef(?string $sportRef, string $sportKey): string
    {
        if ($sportRef !== null && trim($sportRef) !== '') {
            return trim($sportRef);
        }

        return $sportKey;
    }
}
