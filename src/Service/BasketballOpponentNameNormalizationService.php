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

    /**
     * @param \App\Model\Table\StatBasketGameOpponentTable|null $statsTable Opponent game stats table.
     */
    public function __construct(?StatBasketGameOpponentTable $statsTable = null)
    {
        if ($statsTable === null) {
            /** @var \App\Model\Table\StatBasketGameOpponentTable $statsTable */
            $statsTable = TableRegistry::getTableLocator()->get('StatBasketGameOpponent');
        }

        $this->statsTable = $statsTable;
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
            $normalized = $this->normalizeName($original);
            if ($normalized === null || $normalized === $original) {
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

    /**
     * Convert a supported comma-separated name to title-cased First Last form.
     *
     * @param string $name Existing name value.
     * @return string|null Normalized name, or null when the format is ambiguous.
     */
    private function normalizeName(string $name): ?string
    {
        $parts = array_map('trim', explode(',', trim($name)));
        $parts = array_values(array_filter(
            $parts,
            static fn(string $part): bool => $part !== '' && preg_match('/^jr\.?$/i', $part) !== 1,
        ));

        if (count($parts) !== 2) {
            return null;
        }

        [$last, $first] = $parts;
        $last = preg_replace('/\s+jr\.?$/i', '', $last) ?? $last;
        $first = preg_replace('/\s+jr\.?$/i', '', $first) ?? $first;
        $last = trim($last);
        $first = trim($first);

        if ($last === '' || $first === '') {
            return null;
        }

        return ucwords(strtolower($first . ' ' . $last));
    }
}
