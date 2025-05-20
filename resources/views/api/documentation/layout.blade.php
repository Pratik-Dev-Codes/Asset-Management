<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'API Documentation' }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.1/styles/atom-one-dark.min.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #5a5c69;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #5a5c69;
            background-color: #f8f9fc;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .nav-link {
            font-weight: 600;
            color: #333;
            padding: 0.5rem 1rem;
        }
        
        .nav-link.active {
            color: var(--primary-color);
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .card {
            border: none;
            border-radius: .35rem;
            box-shadow: 0 .15rem 1.75rem 0 rgba(58, 59, 69, .15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 700;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .endpoint {
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
        }
        
        .endpoint-method {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: white;
            margin-right: 0.5rem;
        }
        
        .method-get { background-color: var(--info-color); }
        .method-post { background-color: var(--success-color); }
        .method-put { background-color: var(--warning-color); }
        .method-patch { background-color: var(--warning-color); }
        .method-delete { background-color: var(--danger-color); }
        
        .endpoint-path {
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }
        
        .endpoint-description {
            margin: 0.5rem 0 1rem;
            color: #6c757d;
        }
        
        .parameter {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .parameter:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .parameter-name {
            font-weight: 700;
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
            color: #e83e8c;
        }
        
        .parameter-type {
            color: #6c757d;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
        
        .parameter-required {
            color: var(--danger-color);
            font-size: 0.75rem;
            margin-left: 0.5rem;
            text-transform: uppercase;
        }
        
        .parameter-description {
            margin: 0.25rem 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        pre {
            background-color: #f8f9fc;
            border-radius: 0.25rem;
            padding: 1rem;
            margin: 1rem 0;
            overflow-x: auto;
        }
        
        code {
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
            font-size: 0.85rem;
            color: #e83e8c;
            word-break: break-word;
        }
        
        .response-code {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            margin-right: 0.5rem;
        }
        
        .code-2xx { background-color: #d4edda; color: #155724; }
        .code-4xx { background-color: #fff3cd; color: #856404; }
        .code-5xx { background-color: #f8d7da; color: #721c24; }
        
        .example-request,
        .example-response {
            margin: 1rem 0;
        }
        
        .example-header {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .version-badge {
            font-size: 0.7rem;
            vertical-align: middle;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                height: auto;
                padding-top: 0;
            }
            
            .sidebar-sticky {
                height: auto;
                padding-bottom: 1rem;
            }
            
            .content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                {{ config('app.name') }} API
                <span class="badge bg-secondary version-badge">v{{ $version ?? '1.0' }}</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('api.documentation') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#authentication">Authentication</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#assets">Assets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#categories">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#users">Users</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="{{ route('api.specification', ['version' => $version ?? '1.0']) }}" class="btn btn-outline-primary" target="_blank">
                        View OpenAPI Spec
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>API Reference</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="#getting-started">Getting Started</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#authentication">Authentication</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#rate-limiting">Rate Limiting</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#errors">Errors</a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Endpoints</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="#assets">Assets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#categories">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#users">Users</a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Resources</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="#data-types">Data Types</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#enums">Enums</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="margin-top: 60px;">
                <div class="content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.1/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
    
    <script>
        // Add active class to current nav item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.hash || '#getting-started';
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
                
                link.addEventListener('click', function() {
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
