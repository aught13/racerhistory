<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\AdConfigurationService;
use Cake\View\Helper;
use Cake\View\View;

/**
 * View helper for ad slot rendering payloads.
 */
class AdHelper extends Helper
{
    private AdConfigurationService $adConfigurationService;

    /**
     * @param \Cake\View\View $View
     * @param array<string,mixed> $config
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $service = $config['adConfigurationService'] ?? null;
        if ($service instanceof AdConfigurationService) {
            $this->adConfigurationService = $service;

            return;
        }

        $this->adConfigurationService = new AdConfigurationService();
    }

    /**
     * Resolve normalized payload for a slot name.
     *
     * @param string $slot
     * @return array{
     *   slot:string,
     *   slot_class:string,
     *   active:bool,
     *   mode:string,
     *   is_google:bool,
     *   html:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }
     */
    public function slot(string $slot): array
    {
        $configuration = $this->adConfigurationService->getSlotConfiguration($slot);
        $slotName = (string)($configuration['slot'] ?? '');
        $mode = (string)($configuration['mode'] ?? 'custom');

        return [
            'slot' => $slotName,
            'slot_class' => str_replace('_', '-', $slotName),
            'active' => (bool)($configuration['active'] ?? false),
            'mode' => $mode,
            'is_google' => $mode === 'google',
            'html' => (string)($configuration['html'] ?? ''),
            'google_slot_id' => (string)($configuration['google_slot_id'] ?? ''),
            'google_client' => (string)($configuration['google_client'] ?? ''),
            'google_format' => (string)($configuration['google_format'] ?? ''),
            'google_layout' => (string)($configuration['google_layout'] ?? ''),
            'google_layout_key' => (string)($configuration['google_layout_key'] ?? ''),
            'google_full_width_responsive' => (string)($configuration['google_full_width_responsive'] ?? ''),
        ];
    }
}
