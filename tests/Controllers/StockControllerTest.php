<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\StockController;
use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Models\StockQuery;
use App\Services\StockService;
use App\Services\Interfaces\NotificationServiceInterface;
use App\Validators\StockValidator;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class StockControllerTest extends TestCase
{
    /** @var StockService&\PHPUnit\Framework\MockObject\MockObject */
    private StockService $stockService;
    
    /** @var NotificationServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private NotificationServiceInterface $notificationService;
    
    /** @var StockValidator&\PHPUnit\Framework\MockObject\MockObject */
    private StockValidator $validator;
    
    private StockController $controller;
    
    /** @var \Faker\Generator */
    private $faker;
    
    // Define a constant for invalid symbol
    private const INVALID_SYMBOL = 'INVALID';
    
    protected function setUp(): void
    {
        /** @var StockService&\PHPUnit\Framework\MockObject\MockObject $stockServiceMock */
        $stockServiceMock = $this->createMock(StockService::class);
        $this->stockService = $stockServiceMock;
        
        /** @var NotificationServiceInterface&\PHPUnit\Framework\MockObject\MockObject $notificationServiceMock */
        $notificationServiceMock = $this->createMock(NotificationServiceInterface::class);
        $this->notificationService = $notificationServiceMock;
        
        /** @var StockValidator&\PHPUnit\Framework\MockObject\MockObject $validatorMock */
        $validatorMock = $this->createMock(StockValidator::class);
        $this->validator = $validatorMock;
        
        $this->controller = new StockController(
            $this->stockService,
            $this->notificationService,
            $this->validator
        );
        
        // Initialize Faker
        $this->faker = Factory::create();
    }
    
    /**
     * Generate stock data for testing
     * 
     * @param string|null $symbol Override the default symbol
     * @return array
     */
    private function generateStockData(string $symbol = null): array
    {
        return [
            'id' => $this->faker->randomNumber(2),
            'user_id' => $this->faker->randomNumber(2),
            'symbol' => $symbol ?? strtoupper($this->faker->lexify('????')),
            'name' => $this->faker->company(),
            'open' => $this->faker->randomFloat(2, 10, 1000),
            'high' => $this->faker->randomFloat(2, 10, 1000),
            'low' => $this->faker->randomFloat(2, 10, 1000),
            'close' => $this->faker->randomFloat(2, 10, 1000),
            'created_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
            'updated_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate user data for testing
     * 
     * @return array
     */
    private function generateUserData(): array
    {
        return [
            'id' => $this->faker->randomNumber(2),
            'email' => $this->faker->email(),
            'password' => $this->faker->password(),
            'created_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
            'updated_at' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate Microsoft stock data for testing
     * 
     * @return array
     */
    private function generateMicrosoftStockData(): array
    {
        $data = $this->generateStockData('MSFT.US');
        $data['name'] = 'MICROSOFT';
        return $data;
    }
    
    public function testGetStockWithValidSymbol(): void
    {
        // Arrange
        $stockData = $this->generateStockData();
        $symbol = $stockData['symbol'];
        $userData = $this->generateUserData();
        $userId = $userData['id'];
        $userEmail = $userData['email'];
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['q' => $symbol]);
        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnMap([
                ['user_id', null, $userId],
                ['email', null, $userEmail]
            ]);
        
        $this->validator->expects($this->once())
            ->method('validateSymbol')
            ->with($symbol)
            ->willReturn(true);
        
        $this->stockService->expects($this->once())
            ->method('getStockData')
            ->with($symbol)
            ->willReturn($stockData);
        
        $this->stockService->expects($this->once())
            ->method('saveStockQuery')
            ->with($userId, $stockData)
            ->willReturn(new StockQuery());
        
        $this->notificationService->expects($this->once())
            ->method('send')
            ->with($userEmail, 'Stock Quote Information', $stockData)
            ->willReturn(true);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'Stock quote retrieved successfully';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getStock($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testGetStockWithMissingSymbol(): void
    {
        // Arrange
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([]);
        
        $this->validator->expects($this->once())
            ->method('validateSymbol')
            ->with(null)
            ->willReturn(false);
        
        $this->stockService->expects($this->never())
            ->method('getStockData');
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'Stock symbol is required';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getStock($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testGetStockWithInvalidSymbol(): void
    {
        // Arrange
        $symbol = self::INVALID_SYMBOL;
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['q' => $symbol]);
        
        $this->validator->expects($this->once())
            ->method('validateSymbol')
            ->with($symbol)
            ->willReturn(true);
        
        $this->stockService->expects($this->once())
            ->method('getStockData')
            ->with($symbol)
            ->willReturn(null);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'Failed to fetch stock data';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getStock($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testGetHistory(): void
    {
        // Arrange
        $userData = $this->generateUserData();
        $userId = $userData['id'];
        $stockQueries = [
            $this->generateStockData(),
            $this->generateMicrosoftStockData()
        ];
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with('user_id')
            ->willReturn($userId);
        
        $this->stockService->expects($this->once())
            ->method('getUserStockHistory')
            ->with($userId)
            ->willReturn($stockQueries);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) use ($stockQueries) {
                $data = json_decode($json, true);
                
                // Check basic structure
                if ($data['success'] !== true || 
                    $data['message'] !== 'Stock query history retrieved successfully' ||
                    !is_array($data['data'])) {
                    return false;
                }
                
                // Check that we have the same number of items
                if (count($data['data']) !== count($stockQueries)) {
                    return false;
                }
                
                // Check that each stock query has the required fields
                foreach ($data['data'] as $index => $stockQuery) {
                    if (!isset($stockQuery['id']) || 
                        !isset($stockQuery['symbol']) || 
                        !isset($stockQuery['name'])) {
                        return false;
                    }
                }
                
                return true;
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getHistory($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
}