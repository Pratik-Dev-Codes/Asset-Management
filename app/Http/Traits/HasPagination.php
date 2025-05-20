<?php

namespace App\Http\Traits;

trait HasPagination
{
    /**
     * Get the pagination limit from the request or use the default.
     *
     * @param  int  $default
     * @return int
     */
    protected function getPaginationLimit($default = 15)
    {
        $perPage = request()->input('per_page', $default);
        
        // Ensure the per_page value is within allowed limits
        $perPage = min(max(1, (int) $perPage), 100);
        
        return $perPage;
    }
    
    /**
     * Get the pagination data for the response.
     *
     * @param  mixed  $paginator
     * @return array
     */
    protected function getPaginationData($paginator)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }
    
    /**
     * Get the pagination links for the response.
     *
     * @param  mixed  $paginator
     * @return array
     */
    protected function getPaginationLinks($paginator)
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
    
    /**
     * Get the paginated response.
     *
     * @param  mixed  $paginator
     * @param  string  $resourceClass
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($paginator, $resourceClass = null)
    {
        $response = [
            'data' => $resourceClass ? $resourceClass::collection($paginator->items()) : $paginator->items(),
            'meta' => [
                'pagination' => array_merge(
                    $this->getPaginationData($paginator),
                    ['links' => $this->getPaginationLinks($paginator)]
                ),
            ],
        ];
        
        return response()->json($response);
    }
}
