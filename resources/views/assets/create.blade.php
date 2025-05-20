@extends('layouts.app')

@section('title', 'Add New Asset')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet">
<style>
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        margin: 10px auto;
        display: block;
        border-radius: 4px;
    }
    .dropzone {
        border: 2px dashed #6c757d;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        margin: 1rem 0;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    .dropzone:hover, .dropzone.dz-drag-hover {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .dropzone .dz-message {
        margin: 2em 0;
        color: #6c757d;
    }
    .dropzone .dz-preview {
        margin: 10px;
    }
    .dropzone .dz-preview .dz-image {
        border-radius: 4px;
    }
    .dz-remove {
        display: block;
        margin-top: 10px;
        color: #dc3545;
        cursor: pointer;
    }
    .dz-remove:hover {
        text-decoration: underline;
    }
    .upload-area {
        position: relative;
    }
    .upload-progress {
        display: none;
        margin-top: 10px;
    }
    .uploaded-files {
        margin-top: 20px;
    }
    .file-preview {
        position: relative;
        display: inline-block;
        margin: 5px;
    }
    .file-preview img {
        max-width: 100px;
        max-height: 100px;
        border-radius: 4px;
    }
    .file-remove {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        text-align: center;
        line-height: 24px;
        cursor: pointer;
        font-size: 12px;
    }
    .file-info {
        font-size: 12px;
        text-align: center;
        margin-top: 5px;
        word-break: break-all;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Asset</h5>
                </div>

                <div class="card-body">
                    <form id="assetForm" action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="serial_number" class="form-label">Serial Number</label>
                                        <input type="text" class="form-control @error('serial_number') is-invalid @enderror" 
                                               id="serial_number" name="serial_number" value="{{ old('serial_number') }}">
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
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                            <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="in_use" {{ old('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                                            <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="retired" {{ old('status') == 'retired' ? 'selected' : '' }}>Retired</option>
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
                                               id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}">
                                        @error('purchase_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="purchase_cost" class="form-label">Purchase Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" class="form-control @error('purchase_cost') is-invalid @enderror" 
                                                   id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost') }}">
                                        </div>
                                        @error('purchase_cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Asset Image</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="upload-area">
                                            <form action="{{ route('assets.upload') }}" class="dropzone" id="assetDropzone">
                                                @csrf
                                                <div class="dz-message" data-dz-message>
                                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                                    <h5>Drag & drop files here or click to upload</h5>
                                                    <p class="text-muted">Supports: JPG, PNG, GIF, PDF, DOC, DOCX (Max: 5MB)</p>
                                                </div>
                                                <div class="fallback">
                                                    <input name="file" type="file" multiple />
                                                </div>
                                            </form>
                                            <div class="upload-progress">
                                                <div class="progress">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                                </div>
                                                <div class="text-center mt-2">
                                                    <span class="upload-status">Uploading: <span class="upload-filename"></span></span>
                                                </div>
                                            </div>
                                            <div id="uploaded-files" class="uploaded-files">
                                                @if(old('uploaded_files'))
                                                    @foreach(json_decode(old('uploaded_files'), true) as $file)
                                                        <div class="file-preview" data-filename="{{ $file['name'] }}">
                                                            @if(in_array(pathinfo($file['name'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                                                                <img src="{{ Storage::url('temp/'.$file['name']) }}" alt="Preview">
                                                            @else
                                                                <div class="file-icon">
                                                                    <i class="fas fa-file fa-3x"></i>
                                                                    <div class="file-extension">{{ strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)) }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="file-remove" title="Remove file">
                                                                <i class="fas fa-times"></i>
                                                            </div>
                                                            <div class="file-info">
                                                                {{ Str::limit($file['name'], 15, '...') }}
                                                            </div>
                                                            <input type="hidden" name="uploaded_files[]" value="{{ json_encode($file) }}">
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <input type="hidden" name="asset_image" id="asset_image" value="{{ old('asset_image') }}">
                                            @error('asset_image')
                                                <div class="text-danger small mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Documents</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="documentContainer">
                                            <div class="document-item mb-2">
                                                <div class="input-group">
                                                    <input type="file" name="documents[]" class="form-control form-control-sm">
                                                    <select name="document_types[]" class="form-select form-select-sm" style="max-width: 120px;">
                                                        <option value="invoice">Invoice</option>
                                                        <option value="manual">Manual</option>
                                                        <option value="warranty">Warranty</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-document">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" id="addDocument" class="btn btn-sm btn-outline-secondary mt-2">
                                            <i class="fas fa-plus"></i> Add Document
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Asset
                                    </button>
                                    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Cancel
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
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Disable auto discover for all elements
    Dropzone.autoDiscover = false;
    
    $(document).ready(function() {
        // Initialize Dropzone
        let myDropzone = new Dropzone("#assetDropzone", {
            url: "{{ route('assets.upload') }}",
            paramName: "file",
            maxFilesize: 5, // MB
            maxFiles: 5,
            acceptedFiles: "image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt",
            addRemoveLinks: true,
            autoProcessQueue: true,
            uploadMultiple: false,
            parallelUploads: 1,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            init: function() {
                this.on("addedfile", function(file) {
                    $('.upload-progress').show();
                    $('.upload-filename').text(file.name);
                    // Only process one file at a time
                    if (this.files.length > 1) {
                        this.removeFile(this.files[0]);
                    }
                });
                
                this.on("sending", function(file, xhr, formData) {
                    $('.upload-status').text('Uploading: ' + file.name);
                    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
                });
                
                this.on("uploadprogress", function(file, progress) {
                    $('.progress-bar').css('width', progress + '%').attr('aria-valuenow', progress);
                });
                
                this.on("success", function(file, response) {
                    // Add the uploaded file to the preview
                    const filePreview = `
                        <div class="file-preview" data-filename="${response.name}">
                            ${response.type === 'image' ? 
                                `<img src="${response.url}" alt="Preview">` : 
                                `<div class="file-icon">
                                    <i class="fas fa-file fa-3x"></i>
                                    <div class="file-extension">${response.extension.toUpperCase()}</div>
                                </div>`
                            }
                            <div class="file-remove" title="Remove file">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="file-info">
                                ${response.original_name}
                            </div>
                            <input type="hidden" name="uploaded_files[]" value='${JSON.stringify(response)}'>
                        </div>
                    `;
                    
                    $('#uploaded-files').append(filePreview);
                    
                    // If it's an image, set it as the main asset image
                    if (response.type === 'image') {
                        $('#asset_image').val(response.name);
                    }
                    
                    // Reset progress
                    $('.upload-progress').hide();
                    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
                    
                    // Remove the file from dropzone
                    this.removeFile(file);
                });
                
                this.on("error", function(file, errorMessage) {
                    alert('Error uploading file: ' + errorMessage);
                    $('.upload-progress').hide();
                    this.removeFile(file);
                });
            }
        });
        
        // Handle file removal
        $(document).on('click', '.file-remove', function() {
            const $filePreview = $(this).closest('.file-preview');
            const filename = $filePreview.data('filename');
            
            // Send AJAX request to delete the file
            $.ajax({
                url: "{{ route('assets.delete-upload') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    filename: filename
                },
                success: function(response) {
                    $filePreview.remove();
                    // If the removed file was the asset image, clear the input
                    if ($('#asset_image').val() === filename) {
                        $('#asset_image').val('');
                    }
                },
                error: function(xhr) {
                    alert('Error deleting file');
                }
            });
        });

        // Initialize Select2
        $('#category_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select a category',
            allowClear: true
        });

        // Image preview
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('asset_image');
        const imagePreview = document.getElementById('imagePreview');

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
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        // Add/remove document fields
        let docCounter = 1;
        
        $('#addDocument').click(function() {
            const newDoc = `
                <div class="document-item mb-2">
                    <div class="input-group">
                        <input type="file" name="documents[]" class="form-control form-control-sm">
                        <select name="document_types[]" class="form-select form-select-sm" style="max-width: 120px;">
                            <option value="invoice">Invoice</option>
                            <option value="manual">Manual</option>
                            <option value="warranty">Warranty</option>
                            <option value="other">Other</option>
                        </select>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-document">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>`;
            $('#documentContainer').append(newDoc);
            docCounter++;
        });

        $(document).on('click', '.remove-document', function() {
            if ($('.document-item').length > 1) {
                $(this).closest('.document-item').remove();
            } else {
                $(this).closest('.document-item').find('input[type="file"]').val('');
            }
        });

        // Form validation
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
            }
        });
    });
</script>
@endpush
