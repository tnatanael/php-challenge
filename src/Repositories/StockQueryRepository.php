<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StockQuery;

class StockQueryRepository
{
    /**
     * Create a new stock query
     *
     * @param array $data
     * @return StockQuery
     */
    public function create(array $data): StockQuery
    {
        return StockQuery::create($data);
    }
    
    /**
     * Get stock query history for a user
     *
     * @param int $userId
     * @return array
     */
    public function getUserHistory(int $userId): array
    {
        return StockQuery::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}