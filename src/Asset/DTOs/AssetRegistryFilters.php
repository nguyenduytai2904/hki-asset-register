<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\DTOs;

final readonly class AssetRegistryFilters
{
    /**
     * @param  list<string>  $statuses
     * @param  list<int>  $typeIds
     * @param  list<int>  $locationIds
     * @param  list<string>  $warranties
     */
    public function __construct(
        public string $search,
        public array $statuses,
        public array $typeIds,
        public array $locationIds,
        public array $warranties,
        public string $sort,
        public string $direction,
        public int $perPage,
    ) {}

    /**
     * @return array{q: string, status: list<string>, type: list<string>, location: list<string>, warranty: list<string>, sort: string, direction: string, per_page: int}
     */
    public function toArray(): array
    {
        return [
            'q' => $this->search,
            'status' => $this->statuses,
            'type' => array_map('strval', $this->typeIds),
            'location' => array_map('strval', $this->locationIds),
            'warranty' => $this->warranties,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ];
    }
}
