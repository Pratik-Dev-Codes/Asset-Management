@extends('layouts.app')

@section('title', 'Assets Management')

@section('content')
<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Assets Management</h1>
    <a href="{{ route('assets.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Asset
    </a>
</div>

<!-- Content Row -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">All Assets</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Export Options:</div>
                        <a class="dropdown-item" href="#">Export as Excel</a>
                        <a class="dropdown-item" href="#">Export as PDF</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Print</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="assetsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Purchase Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Purchase Date</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @forelse($assets as $asset)
                                <tr>
                                    <td>{{ $asset->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($asset->image_path)
                                                <img src="{{ asset('storage/' . $asset->image_path) }}" class="img-profile rounded-circle me-2" width="32" height="32">
                                            @else
                                                <div class="img-profile rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-weight-bold">{{ $asset->name }}</div>
                                                <div class="text-muted small">{{ $asset->serial_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $asset->category->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $asset->status_color }}">
                                            {{ ucfirst($asset->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($asset->assignedTo)
                                            {{ $asset->assignedTo->name }}
                                        @else
                                            <span class="text-muted">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $asset->purchase_date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No assets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($assets->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $assets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Page level plugins -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Page level custom scripts -->
<script>
    $(document).ready(function() {
        $('#assetsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            pageLength: 25,
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on actions column
            ]
        });
    });
</script>
@endpush
