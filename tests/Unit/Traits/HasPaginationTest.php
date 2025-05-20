<?php

namespace Tests\Unit\Traits;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TestController extends Controller
{
    use HasPagination;

    public function testGetPaginationLimit(Request $request)
    {
        return $this->getPaginationLimit($request->input('per_page', 15));
    }

    public function testGetPaginationData(LengthAwarePaginator $paginator)
    {
        return $this->getPaginationData($paginator);
    }

    public function testGetPaginationLinks(LengthAwarePaginator $paginator)
    {
        return $this->getPaginationLinks($paginator);
    }

    public function testPaginatedResponse(LengthAwarePaginator $paginator, $resourceClass = null)
    {
        return $this->paginatedResponse($paginator, $resourceClass);
    }
}

class HasPaginationTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestController;
    }

    /** @test */
    public function it_gets_pagination_limit_from_request()
    {
        $request = new Request;

        // Test default value
        $this->assertEquals(15, $this->controller->testGetPaginationLimit($request));

        // Test custom value
        $request->merge(['per_page' => 25]);
        $this->assertEquals(25, $this->controller->testGetPaginationLimit($request));

        // Test minimum value
        $request->merge(['per_page' => -1]);
        $this->assertEquals(1, $this->controller->testGetPaginationLimit($request));

        // Test maximum value
        $request->merge(['per_page' => 200]);
        $this->assertEquals(100, $this->controller->testGetPaginationLimit($request));
    }

    /** @test */
    public function it_gets_pagination_data()
    {
        $items = collect(range(1, 15));
        $paginator = new LengthAwarePaginator($items, 100, 15, 2);

        $expected = [
            'current_page' => 2,
            'from' => 16,
            'last_page' => 7,
            'per_page' => 15,
            'to' => 30,
            'total' => 100,
        ];

        $this->assertEquals($expected, $this->controller->testGetPaginationData($paginator));
    }

    /** @test */
    public function it_gets_pagination_links()
    {
        $items = collect(range(1, 15));
        $paginator = new LengthAwarePaginator($items, 100, 15, 2);

        $links = $this->controller->testGetPaginationLinks($paginator);

        $this->assertArrayHasKey('first', $links);
        $this->assertArrayHasKey('last', $links);
        $this->assertArrayHasKey('prev', $links);
        $this->assertArrayHasKey('next', $links);

        // First page should be page 1
        $this->assertStringContainsString('page=1', $links['first']);
        // Last page should be page 7 (100 items / 15 per page = 6.67 -> 7 pages)
        $this->assertStringContainsString('page=7', $links['last']);
        // Previous page should be page 1 (since we're on page 2)
        $this->assertStringContainsString('page=1', $links['prev']);
        // Next page should be page 3
        $this->assertStringContainsString('page=3', $links['next']);
    }

    /** @test */
    public function it_creates_paginated_response()
    {
        $items = collect(range(1, 15));
        $paginator = new LengthAwarePaginator($items, 100, 15, 2);

        $response = $this->controller->testPaginatedResponse($paginator);

        $this->assertEquals(200, $response->status());
        $data = $response->getData(true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('links', $data['meta']['pagination']);

        $pagination = $data['meta']['pagination'];
        $this->assertEquals(2, $pagination['current_page']);
        $this->assertEquals(7, $pagination['last_page']);
        $this->assertEquals(15, $pagination['per_page']);
        $this->assertEquals(100, $pagination['total']);
    }
}
