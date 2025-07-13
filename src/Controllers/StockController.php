<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Models\StockQuery;
use App\Services\MessageQueue;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Mailer\Mailer;

class StockController
{
    /**
     * @var Mailer|null
     */
    private $mailer;
    
    /**
     * @var MessageQueue
     */
    private $messageQueue;

    /**
     * StockController constructor.
     */
    public function __construct(?Mailer $mailer, MessageQueue $messageQueue)
    {
        $this->mailer = $mailer;
        $this->messageQueue = $messageQueue;
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
        content: new OA\JsonContent(ref: "#/components/schemas/StockQuery")
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
        
        if (!$symbol) {
            $errorResponse = new ErrorResponse('Stock symbol is required', 400);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Get user from request attribute (set by JwtMiddleware)
        $userId = $request->getAttribute('user_id');
        $userEmail = $request->getAttribute('email');
        
        // Fetch stock data from Stooq API
        $stockData = $this->fetchStockData($symbol);
        
        if (!$stockData) {
            $errorResponse = new ErrorResponse('Failed to fetch stock data', 404);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        // Save query to database
        $stockQuery = new StockQuery();
        $stockQuery->user_id = $userId;
        $stockQuery->symbol = $stockData['symbol'];
        $stockQuery->name = $stockData['name'];
        $stockQuery->open = $stockData['open'];
        $stockQuery->high = $stockData['high'];
        $stockQuery->low = $stockData['low'];
        $stockQuery->close = $stockData['close'];
        $stockQuery->save();
        
        // Send email to user asynchronously via RabbitMQ if user email is available
        if ($userEmail) {
            $this->queueStockEmail($userEmail, $stockData);
        }
        
        // In getStock() method:
        $successResponse = new SuccessResponse(
            message: 'Stock quote retrieved successfully',
            data: $stockData  // Pass array directly
        );
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
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
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/StockQuery"))
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized"
    )]
    public function getHistory(Request $request, Response $response): Response
    {
        // Get user from request attribute (set by JwtMiddleware)
        $userId = $request->getAttribute('user_id');
        
        // Get stock queries for user, ordered by latest first
        $stockQueries = StockQuery::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
        
        $successResponse = new SuccessResponse($stockQueries, 'Stock query history retrieved successfully');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    /**
     * Fetch stock data from Stooq API
     *
     * @param string $symbol
     * @return array|null
     */
    private function fetchStockData(string $symbol): ?array
    {
        $url = "https://stooq.com/q/l/?s={$symbol}&f=sd2t2ohlcvn&h&e=csv";
        $csvData = file_get_contents($url);
        
        if (!$csvData) {
            return null;
        }
        
        $lines = explode("\n", $csvData);
        if (count($lines) < 2) {
            return null;
        }
        
        $headers = str_getcsv($lines[0]);
        $values = str_getcsv($lines[1]);
        
        $data = array_combine($headers, $values);

        // Check if we have valid data
        if (!isset($data['Symbol']) || 
            !isset($data['Open']) || $data['Open'] === 'N/D' || 
            !isset($data['High']) || $data['High'] === 'N/D' || 
            !isset($data['Low']) || $data['Low'] === 'N/D' || 
            !isset($data['Close']) || $data['Close'] === 'N/D') {
            return null;
        }
        
        return [
            'symbol' => $data['Symbol'],
            'name' => $data['Name'],
            'open' => (float) $data['Open'],
            'high' => (float) $data['High'],
            'low' => (float) $data['Low'],
            'close' => (float) $data['Close'],
        ];
    }
    
    /**
     * Queue stock data email to be sent asynchronously
     *
     * @param string $email
     * @param array $stockData
     * @return void
     */
    private function queueStockEmail(string $email, array $stockData): void
    {
        // Render email template
        $emailBody = $this->renderEmailTemplate($stockData);
        
        $fromEmail = $_ENV['MAILER_FROM'] ?? 'stock-api@example.com';
        $fromName = $_ENV['MAILER_FROM_NAME'] ?? 'Stock API';
        
        // Prepare email data for the queue
        $emailData = [
            'to' => $email,
            'subject' => 'Stock Quote Information',
            'body' => $emailBody,
            'from_email' => $fromEmail,
            'from_name' => $fromName
        ];
        
        // Publish to the queue
        $this->messageQueue->publish('email_queue', $emailData);
    }
    
    /**
     * Render email template with stock data
     *
     * @param array $stockData
     * @return string
     */
    private function renderEmailTemplate(array $stockData): string
    {
        // Extract variables to be used in the template
        extract(['stockData' => $stockData]);
        
        // Start output buffering
        ob_start();
        
        // Include the template file
        include __DIR__ . '/../../src/Views/emails/stock_quote.php';
        
        // Get the contents of the buffer and clean it
        return ob_get_clean();
    }
}