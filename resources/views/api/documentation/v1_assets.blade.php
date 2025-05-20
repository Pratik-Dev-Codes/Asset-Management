@extends('api.documentation.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Assets Section (continued) -->
            <div id="assets" class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">Assets (continued)</h2>
                </div>
                <div class="card-body">
                    <!-- Create Asset -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-post">POST</span>
                            <code class="endpoint-path">/api/v1/assets</code>
                        </div>
                        <p class="endpoint-description">Create a new asset.</p>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Content-Type: application/json
Accept: application/json</code></pre>
                        
                        <h4>Request Body</h4>
                        <div class="parameter">
                            <span class="parameter-name">name</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The name of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">description</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">A description of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">status</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The status of the asset (e.g., 'available', 'in_use', 'maintenance')</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">category_id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the category this asset belongs to</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">location_id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The ID of the location where this asset is stored</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">purchase_date</span>
                            <span class="parameter-type">date</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The date when the asset was purchased (YYYY-MM-DD)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">purchase_cost</span>
                            <span class="parameter-type">number</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The purchase cost of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">warranty_expiry</span>
                            <span class="parameter-type">date</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The date when the warranty expires (YYYY-MM-DD)</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X POST {{ config('app.url') }}/api/v1/assets \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "New Laptop",
    "description": "Dell XPS 15",
    "status": "available",
    "category_id": 1,
    "location_id": 1,
    "purchase_date": "2024-05-19",
    "purchase_cost": 1599.99,
    "warranty_expiry": "2026-05-19"
  }'</code></pre>
                        
                        <h4>Example Response (201 Created)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Asset created successfully",
  "data": {
    "id": 2,
    "name": "New Laptop",
    "description": "Dell XPS 15",
    "status": "available",
    "category_id": 1,
    "location_id": 1,
    "purchase_date": "2024-05-19",
    "purchase_cost": "1599.99",
    "warranty_expiry": "2026-05-19",
    "created_at": "2024-05-19T14:30:00.000000Z",
    "updated_at": "2024-05-19T14:30:00.000000Z"
  }
}</code></pre>
                    </div>
                    
                    <!-- Get Asset -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-get">GET</span>
                            <code class="endpoint-path">/api/v1/assets/{id}</code>
                        </div>
                        <p class="endpoint-description">Get a specific asset by ID.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the asset to retrieve</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X GET {{ config('app.url') }}/api/v1/assets/1 \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Asset retrieved successfully",
  "data": {
    "id": 1,
    "name": "Laptop",
    "description": "Dell XPS 15",
    "status": "available",
    "category_id": 1,
    "location_id": 1,
    "purchase_date": "2024-01-15",
    "purchase_cost": "1499.99",
    "warranty_expiry": "2026-01-15",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T12:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Computers",
      "description": "Desktop and laptop computers"
    },
    "location": {
      "id": 1,
      "name": "Head Office",
      "address": "123 Main St, City, Country"
    }
  }
}</code></pre>
                    </div>
                    
                    <!-- Update Asset -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-put">PUT</span>
                            <code class="endpoint-path">/api/v1/assets/{id}</code>
                        </div>
                        <p class="endpoint-description">Update an existing asset.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the asset to update</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Content-Type: application/json
Accept: application/json</code></pre>
                        
                        <h4>Request Body</h4>
                        <p>Include only the fields you want to update.</p>
                        <div class="parameter">
                            <span class="parameter-name">name</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The name of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">description</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">A description of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">status</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The status of the asset (e.g., 'available', 'in_use', 'maintenance')</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">category_id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The ID of the category this asset belongs to</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">location_id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The ID of the location where this asset is stored</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">purchase_date</span>
                            <span class="parameter-type">date</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The date when the asset was purchased (YYYY-MM-DD)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">purchase_cost</span>
                            <span class="parameter-type">number</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The purchase cost of the asset</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">warranty_expiry</span>
                            <span class="parameter-type">date</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The date when the warranty expires (YYYY-MM-DD)</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X PUT {{ config('app.url') }}/api/v1/assets/1 \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "in_use",
    "location_id": 2
  }'</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Asset updated successfully",
  "data": {
    "id": 1,
    "name": "Laptop",
    "description": "Dell XPS 15",
    "status": "in_use",
    "category_id": 1,
    "location_id": 2,
    "purchase_date": "2024-01-15",
    "purchase_cost": "1499.99",
    "warranty_expiry": "2026-01-15",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T15:00:00.000000Z"
  }
}</code></pre>
                    </div>
                    
                    <!-- Delete Asset -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-delete">DELETE</span>
                            <code class="endpoint-path">/api/v1/assets/{id}</code>
                        </div>
                        <p class="endpoint-description">Delete an asset.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the asset to delete</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X DELETE {{ config('app.url') }}/api/v1/assets/1 \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (204 No Content)</h4>
                        <pre><code class="language-json">{}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
