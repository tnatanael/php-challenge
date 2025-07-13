<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    title: "User",
    description: "User model",
    required: ["email", "password"]
)]
class User extends Model
{
    #[OA\Property(
        property: "id",
        type: "integer",
        format: "int64",
        description: "User ID",
        example: 1
    )]
    #[OA\Property(
        property: "email",
        type: "string",
        format: "email",
        description: "User email",
        example: "user@example.com"
    )]
    #[OA\Property(
        property: "password",
        type: "string",
        format: "password",
        description: "User password",
        example: "password123"
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
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the stock queries for the user.
     */
    public function stockQueries(): HasMany
    {
        return $this->hasMany(StockQuery::class);
    }
}