<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>User Report - {{ $user->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 8px 15px;
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #2c3e50;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .badge-primary {
            background-color: #cce5ff;
            color: #004085;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>User Report</h1>
        <div class="subtitle">Generated on {{ now()->format('F j, Y') }}</div>
    </div>

    <div class="section">
        <div class="section-title">User Information</div>
        
        <div style="display: flex; margin-bottom: 20px;">
            <div style="flex: 0 0 120px; margin-right: 20px;">
                @if($user->avatar_path)
                    <img src="{{ storage_path('app/public/' . $user->avatar_path) }}" alt="{{ $user->name }}" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f1f1;">
                @else
                    <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #f1f1f1; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #999;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div style="flex: 1;">
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">{{ $user->name }}</div>
                <div style="color: #7f8c8d; margin-bottom: 5px;">{{ $user->email }}</div>
                @if($user->phone)
                    <div style="color: #7f8c8d; margin-bottom: 5px;">{{ $user->phone }}</div>
                @endif
                <div style="margin-top: 10px;">
                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @foreach($user->roles as $role)
                        <span class="badge badge-primary" style="margin-left: 5px;">
                            {{ $role->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-label">Username:</div>
            <div class="info-value">{{ $user->username }}</div>
            
            <div class="info-label">Employee ID:</div>
            <div class="info-value">{{ $user->employee_id ?? 'N/A' }}</div>
            
            <div class="info-label">Department:</div>
            <div class="info-value">{{ $user->department ? $user->department->name : 'N/A' }}</div>
            
            <div class="info-label">Position:</div>
            <div class="info-value">{{ $user->position ?? 'N/A' }}</div>
            
            <div class="info-label">Location:</div>
            <div class="info-value">
                @if($user->location)
                    {{ $user->location->name }}
                @else
                    N/A
                @endif
            </div>
            
            <div class="info-label">Member Since:</div>
            <div class="info-value">{{ $user->created_at->format('F j, Y') }}</div>
            
            <div class="info-label">Last Login:</div>
            <div class="info-value">
                @if($user->last_login_at)
                    {{ $user->last_login_at->format('F j, Y H:i') }}
                    <div style="font-size: 11px; color: #7f8c8d;">
                        ({{ $user->last_login_ip ?? 'No IP recorded' }})
                    </div>
                @else
                    Never logged in
                @endif
            </div>
            
            <div class="info-label">Status:</div>
            <div class="info-value">
                @if($user->is_active)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-secondary">Inactive</span>
                @endif
                
                @if($user->email_verified_at)
                    <span class="badge badge-primary" style="margin-left: 5px;">
                        Email Verified
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    @if($user->address || $user->city || $user->state || $user->postal_code || $user->country)
    <div class="section">
        <div class="section-title">Contact Information</div>
        <div class="info-grid">
            @if($user->address)
                <div class="info-label">Address:</div>
                <div class="info-value">{{ $user->address }}</div>
            @endif
            
            <div class="info-label">Location:</div>
            <div class="info-value">
                @if($user->city && $user->state)
                    {{ $user->city }}, {{ $user->state }}
                    @if($user->postal_code)
                        {{ $user->postal_code }}
                    @endif
                    <br>
                @endif
                {{ $user->country ? \App\Helpers\CountryHelper::getCountryName($user->country) : 'N/A' }}
            </div>
            
            @if($user->phone_work)
                <div class="info-label">Work Phone:</div>
                <div class="info-value">{{ $user->phone_work }}</div>
            @endif
            
            @if($user->phone_mobile)
                <div class="info-label">Mobile:</div>
                <div class="info-value">{{ $user->phone_mobile }}</div>
            @endif
            
            @if($user->website)
                <div class="info-label">Website:</div>
                <div class="info-value">{{ $user->website }}</div>
            @endif
        </div>
    </div>
    @endif
    
    @if($user->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <div style="padding: 10px; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #3498db;">
            {!! nl2br(e($user->notes)) !!}
        </div>
    </div>
    @endif
    
    @if($user->assets->count() > 0)
    <div class="section">
        <div class="section-title">Assigned Assets ({{ $user->assets->count() }})</div>
        <table>
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Assigned Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->assets as $asset)
                <tr>
                    <td>{{ $asset->asset_tag }}</td>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->category ? $asset->category->name : 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $asset->status === 'assigned' ? 'badge-success' : 'badge-secondary' }}">
                            {{ ucfirst($asset->status) }}
                        </span>
                    </td>
                    <td>{{ $asset->assigned_date ? $asset->assigned_date->format('M j, Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    @if($user->roles->count() > 0)
    <div class="section">
        <div class="section-title">Roles & Permissions</div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="margin-bottom: 10px; font-size: 14px; color: #2c3e50;">Roles ({{ $user->roles->count() }})</h4>
            <div>
                @foreach($user->roles as $role)
                    <span class="badge badge-primary" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                        {{ $role->name }}
                    </span>
                @endforeach
            </div>
        </div>
        
        @php
            $permissions = $user->getAllPermissions()->groupBy('group');
        @endphp
        
        @if($permissions->count() > 0)
        <div>
            <h4 style="margin-bottom: 10px; font-size: 14px; color: #2c3e50;">Permissions ({{ $user->getAllPermissions()->count() }})</h4>
            
            @foreach($permissions as $group => $groupPermissions)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 5px; color: #555; text-transform: uppercase; font-size: 12px;">
                        {{ ucfirst($group) }}
                    </div>
                    <div>
                        @foreach($groupPermissions as $permission)
                            <span style="display: inline-block; background-color: #f1f1f1; padding: 3px 8px; margin: 0 5px 5px 0; border-radius: 3px; font-size: 11px;">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif
    
    <div class="footer">
        This report was generated on {{ now()->format('F j, Y \a\t H:i:s') }} by {{ auth()->user()->name ?? 'System' }}
    </div>
</body>
</html>
