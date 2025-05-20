@extends('api.documentation.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h1 class="h3 mb-0">API Documentation v1.0</h1>
                </div>
                <div class="card-body">
                    <p class="lead">Welcome to the {{ config('app.name') }} API v1.0 documentation.</p>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This is version 1.0 of the API. Please refer to the <a href="{{ route('api.documentation') }}">latest version</a> for the most up-to-date documentation.
                    </div>
                    
                    <h2 id="getting-started" class="h4 mt-4">Getting Started</h2>
                    <p>To get started with the v1.0 API, you'll need to:</p>
                    <ol>
                        <li>Obtain an API key (contact support if you don't have one)</li>
                        <li>Authenticate using your credentials</li>
                        <li>Start making requests to the API endpoints</li>
                    </ol>
                    
                    <h2 id="base-url" class="h4 mt-4">Base URL</h2>
                    <p>All API requests should be made to the following base URL:</p>
                    <div class="alert alert-light">
                        <code>{{ config('app.url') }}/api/v1</code>
                    </div>
                    
                    <h2 id="authentication" class="h4 mt-4">Authentication</h2>
                    <p>This API uses JWT (JSON Web Tokens) for authentication. Include the token in the <code>Authorization</code> header of your requests:</p>
                    <pre><code>Authorization: Bearer your_token_here</code></pre>
                    
                    <h3>Obtaining a Token</h3>
                    <p>To obtain a token, make a POST request to the authentication endpoint:</p>
                    
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-post">POST</span>
                            <code class="endpoint-path">/api/v1/auth/login</code>
                        </div>
                        <p class="endpoint-description">Authenticate a user and receive an access token.</p>
                        
                        <h4>Request Body</h4>
                        <div class="parameter">
                            <span class="parameter-name">email</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The user's email address</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">password</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">required</span>
                            <p class="parameter-description">The user's password</p>
                        </div>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X POST {{ config('app.url') }}/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"yourpassword"}'</code></pre>
                        
                        <h4>Example Response</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}</code></pre>
                    </div>
                    
                    <h2 id="rate-limiting" class="h4 mt-4">Rate Limiting</h2>
                    <p>API requests are rate limited to prevent abuse. The current rate limits are:</p>
                    <ul>
                        <li><strong>60 requests per minute</strong> for authenticated users</li>
                        <li><strong>20 requests per minute</strong> for unauthenticated requests</li>
                    </ul>
                    <p>Rate limit headers are included in all responses:</p>
                    <ul>
                        <li><code>X-RateLimit-Limit</code>: The maximum number of requests allowed</li>
                        <li><code>X-RateLimit-Remaining</code>: The number of requests remaining in the current window</li>
                        <li><code>X-RateLimit-Reset</code>: The time at which the current rate limit window resets (UTC epoch seconds)</li>
                    </ul>
                    
                    <h2 id="errors" class="h4 mt-4">Error Handling</h2>
                    <p>Error responses follow a consistent format:</p>
                    <pre><code class="language-json">{
  "success": false,
  "message": "Error message describing the issue",
  "errors": {
    // Validation errors (if any)
  }
}</code></pre>
                    
                    <h3>Common HTTP Status Codes</h3>
                    <ul>
                        <li><code>200 OK</code> - The request was successful</li>
                        <li><code>201 Created</code> - Resource created successfully</li>
                        <li><code>204 No Content</code> - Resource deleted successfully</li>
                        <li><code>400 Bad Request</code> - Invalid request (e.g., missing required fields)</li>
                        <li><code>401 Unauthorized</code> - Authentication failed or not provided</li>
                        <li><code>403 Forbidden</code> - Not authorized to perform this action</li>
                        <li><code>404 Not Found</code> - Resource not found</li>
                        <li><code>422 Unprocessable Entity</code> - Validation errors</li>
                        <li><code>429 Too Many Requests</code> - Rate limit exceeded</li>
                        <li><code>500 Internal Server Error</code> - Server error</li>
                    </ul>
                </div>
            </div>
            
            <!-- Assets Section -->
            <div id="assets" class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">Assets</h2>
                </div>
                <div class="card-body">
                    <p>Manage assets in the system.</p>
                    
                    <!-- List Assets -->
                    <div class="endpoint">
                        <div class="d-flex align-items-center mb-2">
                            <span class="endpoint-method method-get">GET</span>
                            <code class="endpoint-path">/api/v1/assets</code>
                        </div>
                        <p class="endpoint-description">Get a paginated list of assets.</p>
                        
                        <h4>Query Parameters</h4>
                        <div class="parameter">
                            <span class="parameter-name">page</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Page number to return (default: 1)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">per_page</span>
                            <span class="parameter-type">integer</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Number of items per page (default: 15, max: 100)</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">search</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Search term to filter assets by name or description</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">status</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Filter assets by status (e.g., 'available', 'in_use', 'maintenance')</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">sort_by</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Field to sort by (e.g., 'name', 'created_at')</p>
                        </div>
                        <div class="parameter">
                            <span class="parameter-name">sort_dir</span>
                            <span class="parameter-type">string</span>
                            <span class="parameter-required">optional</span>
                            <p class="parameter-description">Sort direction ('asc' or 'desc')</p>
                        </div>
                        
                        <h4>Headers</h4>
                        <pre><code>Authorization: Bearer your_token_here
Accept: application/json</code></pre>
                        
                        <h4>Example Request</h4>
                        <pre><code class="language-bash">curl -X GET {{ config('app.url') }}/api/v1/assets \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"</code></pre>
                        
                        <h4>Example Response</h4>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Assets retrieved successfully",
  "data": [
    {
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
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "{{ config('app.url') }}/api/v1/assets",
    "per_page": 15,
    "to": 1,
    "total": 1
  },
  "links": {
    "first": "{{ config('app.url') }}/api/v1/assets?page=1",
    "last": "{{ config('app.url') }}/api/v1/assets?page=1",
    "prev": null,
    "next": null
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
