<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StockQuery",
    title: "Stock Query",
    description: "Stock query model",
    required: ["user_id", "symbol"]
)]
class StockQuery extends Model
{
    #[OA\Property(
        property: "id",
        type: "integer",
        format: "int64",
        description: "Stock query ID",
        example: 1
    )]
    #[OA\Property(
        property: "user_id",
        type: "integer",
        format: "int64",
        description: "User ID",
        example: 1
    )]
    #[OA\Property(
        property: "symbol",
        type: "string",
        description: "Stock symbol",
        example: "AAPL.US"
    )]
    #[OA\Property(
        property: "name",
        type: "string",
        description: "Stock name",
        example: "APPLE"
    )]
    #[OA\Property(
        property: "open",
        type: "number",
        format: "float",
        description: "Opening price",
        example: 123.66
    )]
    #[OA\Property(
        property: "high",
        type: "number",
        format: "float",
        description: "Highest price",
        example: 123.66
    )]
    #[OA\Property(
        property: "low",
        type: "number",
        format: "float",
        description: "Lowest price",
        example: 122.49
    )]
    #[OA\Property(
        property: "close",
        type: "number",
        format: "float",
        description: "Closing price",
        example: 123.00
    )]
    #[OA\Property(
        property: "created_at",
        type: "string",
        format: "date-time",
        description: "Creation date"
    )]
    #[OA\Property(
        property: "updated_at",
        type: "string",
        format: "date-time",
        description: "Last update date"
    )]
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'name',
        'open',
        'high',
        'low',
        'close',
    ];

    /**
     * Get the user that owns the stock query.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}