"ID","Name","Username","Email","Employee ID","Department","Position","Status","Last Login","Assets Assigned","Roles"
@foreach($users as $user)"{{ $user->id }}","{{ $user->name }}","{{ $user->username }}","{{ $user->email }}","{{ $user->employee_id ?? 'N/A' }}","{{ $user->department ? $user->department->name : 'N/A' }}","{{ $user->position ?? 'N/A' }}","{{ $user->is_active ? 'Active' : 'Inactive' }}","{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never' }}","{{ $user->assets_count ?? 0 }}","{{ $user->roles->pluck('name')->implode(', ') }}"
@endforeach
