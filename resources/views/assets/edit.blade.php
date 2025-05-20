@extends('layouts.app')

@section('title', 'Edit Asset: ' . $asset->name)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
        display: block;
    }
    .dropzone {
        border: 2px dashed #ccc;
        border-radius: 4px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        margin-bottom: 20px;
    }
    .dropzone:hover {
        border-color: #999;
    }
    .dropzone.dragover {
        background-color: #f8f9fa;
    }
    .document-attachment {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Asset: {{ $asset->name }}</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="darkModeToggle">
                        <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
                    </div>
                </div>

                <div class="card-body">
                    <form id="assetForm" action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name', $asset->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="serial_number" class="form-label">Serial Number</label>
                                        <input type="text" class="form-control @error('serial_number') is-invalid @enderror" 
                                               id="serial_number" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}">
                                        @error('serial_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                                id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ (old('category_id', $asset->category_id) == $category->id) ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror" 
                                                id="status" name="status" required>
                                            <option value="available" {{ old('status', $asset->status) == 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="in_use" {{ old('status', $asset->status) == 'in_use' ? 'selected' : '' }}>In Use</option>
                                            <option value="maintenance" {{ old('status', $asset->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="retired" {{ old('status', $asset->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="purchase_date" class="form-label">Purchase Date</label>
                                        <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" 
                                               id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '') }}">
                                        @error('purchase_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="purchase_cost" class="form-label">Purchase Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" class="form-control @error('purchase_cost') is-invalid @enderror" 
                                                   id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost', $asset->purchase_cost) }}">
                                        </div>
                                        @error('purchase_cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Asset Image</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div id="dropzone" class="dropzone">
                                            @if($asset->image_path)
                                                <img id="imagePreview" src="{{ asset('storage/' . $asset->image_path) }}" alt="Current Image" class="img-thumbnail mb-2" style="max-width: 100%;">
                                            @else
                                                <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                                <p>Drag & drop an image here, or click to select</p>
                                                <small class="text-muted">Supports: JPG, PNG, GIF (Max: 2MB)</small>
                                            @endif
                                            <input type="file" id="asset_image" name="asset_image" 
                                                   class="d-none" accept="image/*">
                                        </div>
                                        @if($asset->image_path)
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image">
                                                <label class="form-check-label" for="remove_image">
                                                    Remove current image
                                                </label>
                                            </div>
                                        @endif
                                        @error('asset_image')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Documents</h6>
                                        <button type="button" id="addDocument" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="documentContainer">
                                            @foreach($asset->documents as $document)
                                                <div class="document-attachment">
                                                    <div>
                                                        <i class="fas fa-file me-2"></i>
                                                        <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank">
                                                            {{ $document->file_name }}
                                                        </a>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-document" 
                                                                data-document-id="{{ $document->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            <div id="newDocuments">
                                                <!-- New document fields will be added here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="saveButton">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        <i class="fas fa-save me-1"></i> Update Asset
                                    </button>
                                    <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Document Confirmation Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this document? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteDocumentForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Document</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs5-dark-mode@1.1.1/dist/darkmode-utils.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs5-dark-mode@1.1.1/dist/darkmode.min.js"></script>
<script>
    $(document).ready(function() {
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkMode = new Darkmode({
            time: '0.5s',
            label: '🌓',
            saveInCookies: true,
            autoMatchOsTheme: true
        });

        // Set initial state
        if (darkMode.isActivated()) {
            darkModeToggle.checked = true;
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('change', () => {
            darkMode.toggle();
        });

        // Initialize Select2
        $('#category_id').select2({
            placeholder: 'Select a category',
            allowClear: true
        });

        // Image preview
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('asset_image');
        const imagePreview = document.querySelector('#imagePreview') || document.createElement('img');
        
        if (!imagePreview.id) {
            imagePreview.id = 'imagePreview';
            imagePreview.className = 'img-thumbnail image-preview';
            dropzone.insertBefore(imagePreview, fileInput);
        }

        dropzone.addEventListener('click', () => fileInput.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateImagePreview(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                updateImagePreview(e.target.files[0]);
            }
        });

        function updateImagePreview(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (!imagePreview.parentNode) {
                        dropzone.innerHTML = '';
                        dropzone.appendChild(imagePreview);
                    }
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    
                    // Remove any existing upload prompt
                    const uploadPrompt = dropzone.querySelector('i.fa-cloud-upload-alt, p, small');
                    if (uploadPrompt) {
                        uploadPrompt.remove();
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        // Add document field
        $('#addDocument').click(function() {
            const docId = Date.now();
            const newDoc = `
                <div class="document-item mb-2" id="doc-${docId}">
                    <div class="input-group">
                        <input type="file" name="documents[]" class="form-control form-control-sm" required>
                        <select name="document_types[]" class="form-select form-select-sm" style="max-width: 120px;">
                            <option value="invoice">Invoice</option>
                            <option value="manual">Manual</option>
                            <option value="warranty">Warranty</option>
                            <option value="other">Other</option>
                        </select>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-document" data-doc-id="${docId}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>`;
            $('#newDocuments').append(newDoc);
        });

        // Remove document field
        $(document).on('click', '.remove-document', function() {
            const docId = $(this).data('doc-id');
            if (docId) {
                $(`#doc-${docId}`).remove();
            } else {
                $(this).closest('.document-item').remove();
            }
        });

        // Delete document confirmation
        $(document).on('click', '.delete-document', function() {
            const documentId = $(this).data('document-id');
            const formAction = '{{ route("documents.destroy", ":id") }}'.replace(':id', documentId);
            $('#deleteDocumentForm').attr('action', formAction);
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'));
            deleteModal.show();
        });

        // Form submission
        $('#assetForm').on('submit', function(e) {
            let isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            
            // Required fields validation
            $('#assetForm [required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 500);
                
                // Show toast message
                const toast = new bootstrap.Toast(document.getElementById('validationToast'));
                toast.show();
            } else {
                // Show loading state
                const saveButton = $('#saveButton');
                saveButton.prop('disabled', true);
                saveButton.find('.spinner-border').removeClass('d-none');
                saveButton.find('i').addClass('d-none');
            }
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl+S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#saveButton').click();
            }
            // Esc to cancel
            if (e.key === 'Escape') {
                window.location.href = '{{ route("assets.show", $asset->id) }}';
            }
        });
    });
</script>
@endpush
