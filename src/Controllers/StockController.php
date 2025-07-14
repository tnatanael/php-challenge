<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Services\StockService;
use App\Services\Interfaces\NotificationServiceInterface;
use App\Validators\StockValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class StockController
{
    private StockService $stockService;
    private NotificationServiceInterface $notificationService;
    private StockValidator $validator;
    
    public function __construct(
        StockService $stockService,
        NotificationServiceInterface $notificationService,
        StockValidator $validator
    ) {
        $this->stockService = $stockService;
        $this->notificationService = $notificationService;
        $this->validator = $validator;
    }
    
    #[OA\Get(
        path: "/stock",
        summary: "Get a stock quote",
        tags: ["Stock"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "q",
        in: "query",
        required: true,
        description: "Stock symbol",
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns stock information",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        ref: "#/components/schemas/StockQuery"
                    ),
                    new OA\Property(
                        property: "message",
                        example: "Stock quote retrieved successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Bad request"
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized"
    )]
    #[OA\Response(
        response: 404,
        description: "Stock not found"
    )]
    public function getStock(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $symbol = $params['q'] ?? null;
        
        // Validate input
        if (!$this->validator->validateSymbol($symbol)) {
            return $this->jsonResponse(
                $response,
                new ErrorResponse('Stock symbol is required', 400),
                400
            );
        }
        
        // Get user from request attribute (set by JwtMiddleware)
        $userId = $request->getAttribute('user_id');
        $userEmail = $request->getAttribute('email');
        
        // Get stock data
        $stockData = $this->stockService->getStockData($symbol);
        
        if (!$stockData) {
            return $this->jsonResponse(
                $response,
                new ErrorResponse('Failed to fetch stock data', 404),
                404
            );
        }
        
        // Save query to database
        $this->stockService->saveStockQuery($userId, $stockData);
        
        // Send email notification if email is available
        if ($userEmail) {
            $this->notificationService->send(
                $userEmail,
                'Stock Quote Information',
                $stockData
            );
        }
        
        return $this->jsonResponse(
            $response,
            new SuccessResponse(
                $stockData,
                'Stock quote retrieved successfully'
            ),
            200
        );
    }
    
    #[OA\Get(
        path: "/history",
        summary: "Get stock query history",
        tags: ["Stock"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Returns stock query history",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/StockQuery")
                    ),
                    new OA\Property(
                        property: "message",
                        example: "Stock query history retrieved successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized"
    )]
    public function getHistory(Request $request, Response $response): Response
    {
        // Get user from request attribute (set by JwtMiddleware)
        $userId = $request->getAttribute('user_id');
        
        // Get stock queries for user
        $stockQueries = $this->stockService->getUserStockHistory($userId);
        
        return $this->jsonResponse(
            $response,
            new SuccessResponse(
                $stockQueries,
                'Stock query history retrieved successfully'
            ),
            200
        );
    }
    
    /**
     * Create JSON response
     *
     * @param Response $response
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    private function jsonResponse(Response $response, $data, int $status): Response
    {
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}