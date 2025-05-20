@extends('api.documentation.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h1 class="h3 mb-0">API Documentation v2.0</h1>
                </div>
                <div class="card-body">
                    <p class="lead">Welcome to the {{ config('app.name') }} API v2.0 documentation.</p>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This is version 2.0 of the API. Please refer to the <a href="{{ route('api.documentation') }}">latest version</a> for the most up-to-date documentation.
                    </div>
                    
                    <h2 id="getting-started" class="h4 mt-4">Getting Started</h2>
                    <p>To get started with the v2.0 API, you'll need to:</p>
                    <ol>
                        <li>Obtain an API key (contact support if you don't have one)</li>
                        <li>Authenticate using your credentials</li>
                        <li>Start making requests to the API endpoints</li>
                    </ol>
                    
                    <h2 id="base-url" class="h4 mt-4">Base URL</h2>
                    <p>All API endpoints are relative to the following base URL:</p>
                    <pre><code class="language-http">{{ config('app.url') }}/api/v2</code></pre>
                    
                    <h2 id="authentication" class="h4 mt-4">Authentication</h2>
                    <p>Most API endpoints require authentication. Include your API token in the <code>Authorization</code> header:</p>
                    <pre><code class="language-http">Authorization: Bearer your_api_token_here</code></pre>
                    
                    <h2 id="rate-limiting" class="h4 mt-4">Rate Limiting</h2>
                    <p>API requests are rate limited to prevent abuse. The current rate limits are:</p>
                    <ul>
                        <li><strong>60 requests per minute</strong> for authenticated users</li>
                        <li><strong>20 requests per minute</strong> for unauthenticated users</li>
                    </ul>
                    
                    <h2 id="response-format" class="h4 mt-4">Response Format</h2>
                    <p>All API responses are in JSON format and include the following structure:</p>
                    <pre><code class="language-json">{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation completed successfully"
}</code></pre>
                    
                    <h2 id="error-handling" class="h4 mt-4">Error Handling</h2>
                    <p>Error responses follow a consistent format:</p>
                    <pre><code class="language-json">{
    "success": false,
    "message": "Error message",
    "errors": {
        // Validation errors (if any)
    }
}</code></pre>
                    
                    <h2 id="http-status-codes" class="h4 mt-4">HTTP Status Codes</h2>
                    <p>The API uses standard HTTP status codes to indicate the success or failure of a request:</p>
                    
                    <h3>Success Codes</h3>
                    <ul>
                        <li><code>200 OK</code> - The request was successful</li>
                        <li><code>201 Created</code> - Resource created successfully</li>
                        <li><code>204 No Content</code> - Request successful, no content to return</li>
                    </ul>
                    
                    <h3>Error Codes</h3>
                    <ul>
                        <li><code>400 Bad Request</code> - Invalid request parameters</li>
                        <li><code>401 Unauthorized</code> - Authentication required</li>
                        <li><code>403 Forbidden</code> - Insufficient permissions</li>
                        <li><code>404 Not Found</code> - Resource not found</li>
                        <li><code>422 Unprocessable Entity</code> - Validation failed</li>
                        <li><code>429 Too Many Requests</code> - Rate limit exceeded</li>
                        <li><code>500 Internal Server Error</code> - Server error</li>
                    </ul>
                    
                    <h2 id="example-requests" class="h4 mt-4">Example Requests</h2>
                    
                    <h3>Authentication</h3>
                    <pre><code class="language-bash"># Login
curl -X POST {{ config('app.url') }}/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"yourpassword"}'

# Response
{
    "access_token": "your_access_token_here",
    "token_type": "bearer",
    "expires_in": 3600
}

# Using the token
curl -X GET {{ config('app.url') }}/api/v2/user \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Accept: application/json"</code></pre>
                    
                    <h3>Fetching Assets</h3>
                    <pre><code class="language-bash"># Get all assets
curl -X GET {{ config('app.url') }}/api/v2/assets \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Accept: application/json"

# Get a specific asset
curl -X GET {{ config('app.url') }}/api/v2/assets/1 \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Accept: application/json"</code></pre>
                    
                    <h3>Creating an Asset</h3>
                    <pre><code class="language-bash">curl -X POST {{ config('app.url') }}/api/v2/assets \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "New Asset",
    "serial_number": "SN12345",
    "status": "available"
  }'</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
