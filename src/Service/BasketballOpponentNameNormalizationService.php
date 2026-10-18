<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\StatBasketGameOpponentTable;
use Cake\ORM\TableRegistry;

/**
 * Normalizes legacy comma-separated basketball opponent player names.
 */
class BasketballOpponentNameNormalizationService
{
    private StatBasketGameOpponentTable $statsTable;

    private BasketballPlayerNameNormalizer $nameNormalizer;

    /**
     * @param \App\Model\Table\StatBasketGameOpponentTable|null $statsTable Opponent game stats table.
     * @param \App\Service\BasketballPlayerNameNormalizer|null $nameNormalizer Player-name normalizer.
     */
    public function __construct(
        ?StatBasketGameOpponentTable $statsTable = null,
        ?BasketballPlayerNameNormalizer $nameNormalizer = null,
    ) {
        if ($statsTable === null) {
            /** @var \App\Model\Table\StatBasketGameOpponentTable $statsTable */
            $statsTable = TableRegistry::getTableLocator()->get('StatBasketGameOpponent');
        }

        $this->statsTable = $statsTable;
        $this->nameNormalizer = $nameNormalizer ?? new BasketballPlayerNameNormalizer();
    }

    /**
     * Preview names that would be changed, showing at most 25 examples.
     *
     * @return array{total: int, rows: list<array{id: int, original: string, normalized: string}>}
     */
    public function preview(): array
    {
        $total = 0;
        $rows = [];

        foreach ($this->getCandidates() as $candidate) {
            $total++;
            if (count($rows) < 25) {
                $rows[] = $candidate;
            }
        }

        return compact('total', 'rows');
    }

    /**
     * Apply the normalization to every eligible row atomically.
     *
     * @return int Number of updated records.
     */
    public function normalize(): int
    {
        return $this->statsTable->getConnection()->transactional(function (): int {
            $updated = 0;
            foreach ($this->getCandidates() as $candidate) {
                $updated += $this->statsTable->updateAll(
                    ['name' => $candidate['normalized']],
                    ['id' => $candidate['id']],
                );
            }

            return $updated;
        });
    }

    /**
     * @return list<array{id: int, original: string, normalized: string}>
     */
    private function getCandidates(): array
    {
        $candidates = [];
        $stats = $this->statsTable->find()
            ->select(['id', 'name'])
            ->where(['name LIKE' => '%,%'])
            ->orderBy(['id' => 'ASC']);

        foreach ($stats as $stat) {
            $original = (string)$stat->get('name');
            $normalized = $this->nameNormalizer->normalize($original);
            if ($normalized === $original) {
                continue;
            }

            $candidates[] = [
                'id' => (int)$stat->get('id'),
                'original' => $original,
                'normalized' => $normalized,
            ];
        }

        return $candidates;
    }
}
