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

    private View $view;

    /**
     * @param \Cake\View\View $View
     * @param array<string,mixed> $config
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);
        $this->view = $View;

        $service = $config['adConfigurationService'] ?? null;
        if ($service instanceof AdConfigurationService) {
            $this->adConfigurationService = $service;

            return;
        }

        $this->adConfigurationService = new AdConfigurationService();
    }

    /**
     * Resolve the first normalized payload for a placement.
     *
     * @param string $slot
     * @param array<string,mixed> $context
     * @return array{
     *   slot:string,
     *   slot_class:string,
     *   active:bool,
     *   mode:string,
     *   is_google:bool,
     *   html:string,
     *   sizes_desktop:array<int,array{0:int,1:int}>,
     *   sizes_mobile:array<int,array{0:int,1:int}>,
     *   gpt_unit_path:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }
     */
    public function slot(string $slot, array $context = []): array
    {
        $resolvedContext = $context !== [] ? $context : $this->pageContext();
        $configuration = $this->adConfigurationService->getSlotConfiguration($slot, $resolvedContext);

        return $this->formatSlotPayload($configuration);
    }

    /**
     * Resolve all normalized campaign payloads for a placement.
     *
     * @param string $slot Placement key.
     * @param array<string,mixed> $context Optional sport/team context.
     * @return array<int,array{
     *   slot:string,
     *   slot_class:string,
     *   active:bool,
     *   mode:string,
     *   is_google:bool,
     *   html:string,
     *   sizes_desktop:array<int,array{0:int,1:int}>,
     *   sizes_mobile:array<int,array{0:int,1:int}>,
     *   gpt_unit_path:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }>
     */
    public function slots(string $slot, array $context = []): array
    {
        $resolvedContext = $context !== [] ? $context : $this->pageContext();
        $configurations = $this->adConfigurationService->getSlotConfigurations($slot, $resolvedContext);
        $payloads = [];
        foreach ($configurations as $configuration) {
            $payloads[] = $this->formatSlotPayload($configuration);
        }

        return $payloads;
    }

    /**
     * Return the global provider script.
     */
    public function globalScript(): string
    {
        return $this->adConfigurationService->getGlobalScript();
    }

    /**
     * Resolve the current view's sport/team context for content campaigns.
     *
     * @return array{sportKey:string|null,teamId:int|null}
     */
    private function pageContext(): array
    {
        return $this->adConfigurationService->resolvePageContext([
            'advertisingContext' => $this->view->get('advertisingContext'),
            'publicScope' => $this->view->get('publicScope'),
            'currentSport' => $this->view->get('currentSport'),
            'game' => $this->view->get('game'),
            'teamSeason' => $this->view->get('teamSeason'),
            'post' => $this->view->get('post'),
        ]);
    }

    /**
     * Map a service payload to the stable view helper shape.
     *
     * @param array<string,mixed> $configuration Normalized campaign payload.
     * @return array{
     *   slot:string,
     *   slot_class:string,
     *   active:bool,
     *   mode:string,
     *   is_google:bool,
     *   html:string,
     *   sizes_desktop:array<int,array{0:int,1:int}>,
     *   sizes_mobile:array<int,array{0:int,1:int}>,
     *   gpt_unit_path:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }
     */
    private function formatSlotPayload(array $configuration): array
    {
        $slotName = (string)($configuration['slot'] ?? '');
        $mode = (string)($configuration['mode'] ?? 'custom');

        return [
            'slot' => $slotName,
            'slot_class' => str_replace('_', '-', $slotName),
            'active' => (bool)($configuration['active'] ?? false),
            'mode' => $mode,
            'is_google' => $mode === 'google',
            'html' => (string)($configuration['html'] ?? ''),
            'sizes_desktop' => is_array($configuration['sizes_desktop'] ?? null)
                ? $configuration['sizes_desktop']
                : [],
            'sizes_mobile' => is_array($configuration['sizes_mobile'] ?? null)
                ? $configuration['sizes_mobile']
                : [],
            'gpt_unit_path' => (string)($configuration['gpt_unit_path'] ?? ''),
            'google_slot_id' => (string)($configuration['google_slot_id'] ?? ''),
            'google_client' => (string)($configuration['google_client'] ?? ''),
            'google_format' => (string)($configuration['google_format'] ?? ''),
            'google_layout' => (string)($configuration['google_layout'] ?? ''),
            'google_layout_key' => (string)($configuration['google_layout_key'] ?? ''),
            'google_full_width_responsive' => (string)($configuration['google_full_width_responsive'] ?? ''),
        ];
    }
}
