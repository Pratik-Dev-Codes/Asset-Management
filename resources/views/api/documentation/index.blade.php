@extends('api.documentation.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h1 class="h3 mb-0">API Documentation</h1>
                </div>
                <div class="card-body">
                    <p class="lead">Welcome to the {{ config('app.name') }} API documentation.</p>
                    <p>This documentation provides information about the available API endpoints, request/response formats, and authentication methods.</p>
                    
                    <h2 class="h4 mt-4">Getting Started</h2>
                    <p>To get started with the API, you'll need to:</p>
                    <ol>
                        <li>Obtain an API key (contact support if you don't have one)</li>
                        <li>Authenticate using your credentials</li>
                        <li>Start making requests to the API endpoints</li>
                    </ol>
                    
                    <h2 class="h4 mt-4">Base URL</h2>
                    <p>All API requests should be made to the following base URL:</p>
                    <div class="alert alert-light">
                        <code>{{ config('app.url') }}/api/v1</code>
                    </div>
                    
                    <h2 class="h4 mt-4">Authentication</h2>
                    <p>This API uses JWT (JSON Web Tokens) for authentication. Include the token in the <code>Authorization</code> header of your requests:</p>
                    <pre><code>Authorization: Bearer your_token_here</code></pre>
                    
                    <h2 class="h4 mt-4">Response Format</h2>
                    <p>All API responses are returned in JSON format with the following structure:</p>
                    <pre><code class="language-json">{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data
    },
    "meta": {
        // Pagination or other metadata
    }
}</code></pre>
                    
                    <h2 class="h4 mt-4">Error Handling</h2>
                    <p>Error responses will include an error message and an appropriate HTTP status code:</p>
                    <pre><code class="language-json">{
    "success": false,
    "message": "Error message describing the issue",
    "errors": {
        // Validation errors (if any)
    }
}</code></pre>
                    
                    <h2 class="h4 mt-4">Rate Limiting</h2>
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
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">API Endpoints</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Endpoint</th>
                                    <th>Description</th>
                                    <th>Authentication</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Authentication -->
                                <tr>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td><code>/api/v1/auth/login</code></td>
                                    <td>Authenticate user and get access token</td>
                                    <td>No</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td><code>/api/v1/auth/refresh</code></td>
                                    <td>Refresh access token</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">POST</span></td>
                                    <td><code>/api/v1/auth/logout</code></td>
                                    <td>Revoke the current access token</td>
                                    <td>Yes</td>
                                </tr>
                                
                                <!-- Assets -->
                                <tr>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td><code>/api/v1/assets</code></td>
                                    <td>List all assets</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td><code>/api/v1/assets</code></td>
                                    <td>Create a new asset</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td><code>/api/v1/assets/{id}</code></td>
                                    <td>Get a specific asset</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning">PUT/PATCH</span></td>
                                    <td><code>/api/v1/assets/{id}</code></td>
                                    <td>Update an asset</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">DELETE</span></td>
                                    <td><code>/api/v1/assets/{id}</code></td>
                                    <td>Delete an asset</td>
                                    <td>Yes</td>
                                </tr>
                                
                                <!-- Categories -->
                                <tr>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td><code>/api/v1/categories</code></td>
                                    <td>List all categories</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td><code>/api/v1/categories</code></td>
                                    <td>Create a new category</td>
                                    <td>Yes</td>
                                </tr>
                                
                                <!-- Users -->
                                <tr>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td><code>/api/v1/users/me</code></td>
                                    <td>Get current user profile</td>
                                    <td>Yes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">Code Examples</h2>
                </div>
                <div class="card-body">
                    <h3 class="h5">Example: Authentication</h3>
                    <pre><code class="language-bash"># Request
curl -X POST {{ config('app.url') }}/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"yourpassword"}'

# Response
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}</code></pre>
                    
                    <h3 class="h5 mt-4">Example: Get All Assets</h3>
                    <pre><code class="language-bash"># Request
curl -X GET {{ config('app.url') }}/api/v1/assets \
  -H "Authorization: Bearer your_token_here"

# Response
{
  "success": true,
  "message": "Assets retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Laptop",
      "description": "Dell XPS 15",
      "status": "available",
      "created_at": "2024-05-19T12:00:00.000000Z",
      "updated_at": "2024-05-19T12:00:00.000000Z"
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
            
            <div class="card">
                <div class="card-header">
                    <h2 class="h4 mb-0">Support</h2>
                </div>
                <div class="card-body">
                    <p>If you have any questions or need assistance, please contact our support team:</p>
                    <ul>
                        <li>Email: <a href="mailto:support@example.com">support@example.com</a></li>
                        <li>Phone: +1 (555) 123-4567</li>
                        <li>Hours: Monday - Friday, 9:00 AM - 5:00 PM (EST)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
