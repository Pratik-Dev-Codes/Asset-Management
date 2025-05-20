@extends('api.documentation.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Categories Section -->
            <div id="categories" class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">Categories</h2>
                </div>
                <div class="card-body">
                    <p>Manage asset categories in the system.</p>
                    
                    <!-- List Categories -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-get">GET</span>
                            <code class="endpoint-path">/api/v1/categories</code>
                        </div>
                        <p class="endpoint-description">Get a list of all categories.</p>
                        
                        <h4>Query Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">per_page</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Number of items per page (default: all, no pagination)</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X GET {{ config('app.url') }}/api/v1/categories \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Computers",
      "description": "Desktop and laptop computers",
      "created_at": "2024-05-19T12:00:00.000000Z",
      "updated_at": "2024-05-19T12:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Furniture",
      "description": "Office furniture and fixtures",
      "created_at": "2024-05-19T12:00:00.000000Z",
      "updated_at": "2024-05-19T12:00:00.000000Z"
    }
  ]
}</code></pre>
                    </div>
                    
                    <!-- Create Category -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-post">POST</span>
                            <code class="endpoint-path">/api/v1/categories</code>
                        </div>
                        <p class="endpoint-description">Create a new category.</p>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Content-Type: application/json
Accept: application/json</code></pre>
                        
                        <h4>Request Body</h4>
                        <div class="parameter">
                            <span class="parameter-name">name</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The name of the category</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">description</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">A description of the category</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X POST {{ config('app.url') }}/api/v1/categories \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Monitors",
    "description": "Computer monitors and displays"
  }'</code></pre>
                        
                        <h4>Example Response (201 Created)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "name": "Monitors",
    "description": "Computer monitors and displays",
    "updated_at": "2024-05-19T16:00:00.000000Z",
    "created_at": "2024-05-19T16:00:00.000000Z",
    "id": 3
  }
}</code></pre>
                    </div>
                    
                    <!-- Get Category -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-get">GET</span>
                            <code class="endpoint-path">/api/v1/categories/{id}</code>
                        </div>
                        <p class="endpoint-description">Get a specific category by ID.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the category to retrieve</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X GET {{ config('app.url') }}/api/v1/categories/1 \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Category retrieved successfully",
  "data": {
    "id": 1,
    "name": "Computers",
    "description": "Desktop and laptop computers",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T12:00:00.000000Z"
  }
}</code></pre>
                    </div>
                    
                    <!-- Update Category -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-put">PUT</span>
                            <code class="endpoint-path">/api/v1/categories/{id}</code>
                        </div>
                        <p class="endpoint-description">Update an existing category.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the category to update</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Content-Type: application/json
Accept: application/json</code></pre>
                        
                        <h4>Request Body</h4>
                        <div class="parameter">
                            <span class="parameter-name">name</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The name of the category</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">description</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">A description of the category</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X PUT {{ config('app.url') }}/api/v1/categories/1 \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "description": "Desktop computers, laptops, and workstations"
  }'</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "id": 1,
    "name": "Computers",
    "description": "Desktop computers, laptops, and workstations",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T17:00:00.000000Z"
  }
}</code></pre>
                    </div>
                    
                    <!-- Delete Category -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-delete">DELETE</span>
                            <code class="endpoint-path">/api/v1/categories/{id}</code>
                        </div>
                        <p class="endpoint-description">Delete a category.</p>
                        
                        <h4>URL Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">id</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The ID of the category to delete</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X DELETE {{ config('app.url') }}/api/v1/categories/3 \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (204 No Content)</h4>
                        <pre><code class="language-json">{}</code></pre>
                    </div>
                </div>
            </div>
            
            <!-- Users Section -->
            <div id="users" class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">Users</h2>
                </div>
                <div class="card-body">
                    <p>Manage user accounts and authentication.</p>
                    
                    <!-- Get Current User -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-get">GET</span>
                            <code class="endpoint-path">/api/v1/users/me</code>
                        </div>
                        <p class="endpoint-description">Get the currently authenticated user's profile.</p>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X GET {{ config('app.url') }}/api/v1/users/me \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "email_verified_at": "2024-05-19T12:00:00.000000Z",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T12:00:00.000000Z",
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "guard_name": "api",
        "created_at": "2024-05-19T12:00:00.000000Z",
        "updated_at": "2024-05-19T12:00:00.000000Z",
        "pivot": {
          "model_type": "App\\Models\\User",
          "model_id": 1,
          "role_id": 1
        }
      }
    ]
  }
}</code></pre>
                    </div>
                    
                    <!-- Update Current User -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-put">PUT</span>
                            <code class="endpoint-path">/api/v1/users/me</code>
                        </div>
                        <p class="endpoint-description">Update the currently authenticated user's profile.</p>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Content-Type: application/json
Accept: application/json</code></pre>
                        
                        <h4>Request Body</h4>
                        <div class="parameter">
                            <span class="parameter-name">name</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The user's full name</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">email</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The user's email address (must be unique)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">current_password</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required if changing password</span>
                            <p class="parameter-description">The user's current password (required when changing password)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">password</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">The new password (must be at least 8 characters)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">password_confirmation</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required if changing password</span>
                            <p class="parameter-description">Confirmation of the new password</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X PUT {{ config('app.url') }}/api/v1/users/me \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Updated",
    "email": "john.updated@example.com"
  }'</code></pre>
                        
                        <h4>Example Response (200 OK)</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "John Updated",
    "email": "john.updated@example.com",
    "email_verified_at": "2024-05-19T12:00:00.000000Z",
    "created_at": "2024-05-19T12:00:00.000000Z",
    "updated_at": "2024-05-19T18:00:00.000000Z"
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
