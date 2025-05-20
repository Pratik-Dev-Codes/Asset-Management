// resources/views/components/file-upload.blade.php
@props([
    'assetId',
    'maxFiles' => 5,
    'maxFileSize' => 50, // MB
    'acceptedFileTypes' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip',
    'chunkSize' => 5, // MB
    'autoUpload' => true,
])

<div class="file-uploader" x-data="fileUpload({{ json_encode([
    'assetId' => $assetId,
    'maxFiles' => $maxFiles,
    'maxFileSize' => $maxFileSize * 1024 * 1024,
    'chunkSize' => $chunkSize * 1024 * 1024,
    'autoUpload' => $autoUpload,
    'endpoints' => [
        'init' => route('api.assets.attachments.chunked.init', ['asset' => $assetId]),
        'upload' => route('api.assets.attachments.chunked.upload', ['asset' => $assetId, 'uploadId' => 'UPLOAD_ID']),
        'complete' => route('api.assets.attachments.chunked.complete', ['asset' => $assetId, 'uploadId' => 'UPLOAD_ID']),
        'progress' => route('api.assets.attachments.chunked.progress', ['asset' => $assetId, 'uploadId' => 'UPLOAD_ID']),
    ],
    'csrfToken' => csrf_token(),
]) }}">
    <!-- Drop Zone -->
    <div class="drop-zone" 
         @dragover.prevent="isDragging = true" 
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)"
         :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/10': isDragging }">
        <div class="drop-zone-content">
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Drag & drop files here</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">or</p>
            <label class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                <input type="file" 
                       class="hidden" 
                       :accept="acceptedFileTypes"
                       multiple
                       @change="handleFileSelect"
                       :disabled="isUploading">
                <span x-text="isUploading ? 'Uploading...' : 'Select Files'"></span>
            </label>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Max {{ $maxFileSize }}MB per file • {{ $acceptedFileTypes }}
            </p>
        </div>
    </div>

    <!-- File List -->
    <div class="mt-4 space-y-2" x-show="files.length > 0">
        <template x-for="(file, index) in files" :key="file.id">
            <div class="file-item" :class="{
                'border-blue-200 bg-blue-50 dark:bg-blue-900/10': file.status === 'uploading',
                'border-green-200 bg-green-50 dark:bg-green-900/10': file.status === 'completed',
                'border-red-200 bg-red-50 dark:bg-red-900/10': file.status === 'error'
            }">
                <div class="file-icon">
                    <template x-if="file.type && file.type.startsWith('image/')">
                        <img :src="file.preview" class="w-10 h-10 object-cover rounded" :alt="file.name">
                    </template>
                    <template x-if="!file.type || !file.type.startsWith('image/')">
                        <div class="file-icon-default">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>
                <div class="file-details">
                    <div class="file-name" x-text="file.name"></div>
                    <div class="file-meta">
                        <span x-text="formatFileSize(file.size)"></span>
                        <span x-show="file.status === 'uploading'">
                            • <span x-text="`${file.progress}%`"></span>
                            <span x-show="file.speed > 0" x-text="`• ${formatSpeed(file.speed)}/s`"></span>
                            <span x-show="file.timeRemaining > 0" x-text="`• ${formatTime(file.timeRemaining)} left`"></span>
                        </span>
                        <span x-show="file.status === 'completed'" class="text-green-600 dark:text-green-400">
                            • Uploaded
                        </span>
                        <span x-show="file.status === 'error'" class="text-red-600 dark:text-red-400">
                            • <span x-text="file.error || 'Upload failed'"></span>
                        </span>
                    </div>
                    <div class="progress-bar" x-show="file.status === 'uploading'">
                        <div class="progress-bar-fill" :style="`width: ${file.progress}%`"></div>
                    </div>
                </div>
                <div class="file-actions">
                    <button type="button" 
                            @click="removeFile(index)"
                            class="text-gray-400 hover:text-red-500 focus:outline-none"
                            :disabled="file.status === 'uploading'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Upload Controls -->
    <div class="mt-4 flex justify-end space-x-2" x-show="files.length > 0 && !autoUpload">
        <button type="button" 
                @click="clearFiles"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isUploading">
            Cancel
        </button>
        <button type="button" 
                @click="uploadFiles"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isUploading || files.every(f => f.status === 'completed')">
            <span x-show="!isUploading">Upload <span x-text="files.filter(f => f.status !== 'completed').length"></span> files</span>
            <span x-show="isUploading">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
            </span>
        </button>
    </div>

    <!-- Uploaded Files List -->
    <div class="mt-8" x-show="uploadedFiles.length > 0">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Uploaded Files</h4>
        <div class="space-y-2">
            <template x-for="(file, index) in uploadedFiles" :key="file.id">
                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="flex-shrink-0">
                            <template x-if="file.mime_type && file.mime_type.startsWith('image/')">
                                <img :src="file.url" class="w-10 h-10 object-cover rounded" :alt="file.original_name">
                            </template>
                            <template x-if="!file.mime_type || !file.mime_type.startsWith('image/')">
                                <div class="w-10 h-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <a :href="file.url" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 truncate block" x-text="file.original_name"></a>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`${formatFileSize(file.size)} • ${formatDate(file.created_at)}`"></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a :href="file.url" download :title="`Download ${file.original_name}`" class="text-gray-400 hover:text-blue-500 dark:hover:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                        <button type="button" 
                                @click="deleteFile(file.id, index)" 
                                class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 focus:outline-none"
                                :disabled="isDeleting">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 22H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Error Alert -->
    <div x-show="error" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 transform translate-y-2" 
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" @click="error = null" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.file-uploader {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.drop-zone {
    @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center transition-colors duration-200;
}

.drop-zone-content {
    @apply pointer-events-none;
}

.file-item {
    @apply flex items-start p-3 border rounded-lg transition-colors duration-200;
    @apply border-gray-200 dark:border-gray-700;
}

.file-icon {
    @apply flex-shrink-0;
}

.file-icon-default {
    @apply w-10 h-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded;
}

.file-details {
    @apply flex-1 min-w-0 ml-3;
}

.file-name {
    @apply text-sm font-medium text-gray-900 dark:text-gray-100 truncate;
}

.file-meta {
    @apply text-xs text-gray-500 dark:text-gray-400 mt-1;
}

.progress-bar {
    @apply mt-2 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
}

.progress-bar-fill {
    @apply h-full bg-blue-500 transition-all duration-300;
}

.upload-speed {
    @apply text-xs text-gray-500 dark:text-gray-400 mt-1;
}

/* Dark mode adjustments */
.dark .file-item {
    @apply bg-gray-800;
}

.dark .file-name {
    @apply text-gray-100;
}

.dark .file-meta {
    @apply text-gray-400;
}

.dark .progress-bar {
    @apply bg-gray-700;
}
</style>
@endpush

@push('scripts')
<script>
function fileUpload(config) {
    return {
        // Configuration
        assetId: config.assetId,
        maxFiles: config.maxFiles,
        maxFileSize: config.maxFileSize,
        chunkSize: config.chunkSize,
        autoUpload: config.autoUpload,
        endpoints: config.endpoints,
        csrfToken: config.csrfToken,
        
        // State
        files: [],
        uploadedFiles: [],
        isDragging: false,
        isUploading: false,
        isDeleting: false,
        error: null,
        uploadProgress: 0,
        activeUploads: 0,
        maxConcurrentUploads: 3,
        uploadQueue: [],
        
        // Initialize
        init() {
            this.loadUploadedFiles();
            
            // Set up drag and drop
            this.$watch('files', (files) => {
                if (this.autoUpload && files.length > 0) {
                    this.uploadFiles();
                }
            });
        },
        
        // Load previously uploaded files
        async loadUploadedFiles() {
            try {
                const response = await fetch(`/api/assets/${this.assetId}/attachments`);
                if (response.ok) {
                    this.uploadedFiles = await response.json();
                }
            } catch (error) {
                console.error('Failed to load uploaded files:', error);
                this.showError('Failed to load uploaded files');
            }
        },
        
        // Handle file selection
        handleFileSelect(event) {
            const newFiles = Array.from(event.target.files || []);
            this.addFiles(newFiles);
            event.target.value = ''; // Reset input to allow re-uploading the same file
        },
        
        // Handle file drop
        handleDrop(event) {
            this.isDragging = false;
            const newFiles = Array.from(event.dataTransfer.files || []);
            this.addFiles(newFiles);
        },
        
        // Add files to the upload queue
        addFiles(newFiles) {
            // Check if adding these files would exceed the max files limit
            if (this.files.length + newFiles.length > this.maxFiles) {
                this.showError(`You can only upload up to ${this.maxFiles} files at once.`);
                return;
            }
            
            newFiles.forEach(file => {
                // Check file size
                if (file.size > this.maxFileSize) {
                    this.showError(`File "${file.name}" exceeds the maximum allowed size of ${this.formatFileSize(this.maxFileSize)}.`);
                    return;
                }
                
                // Create file object
                const fileObj = {
                    id: this.generateId(),
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    status: 'pending',
                    progress: 0,
                    speed: 0,
                    timeRemaining: 0,
                    startTime: null,
                    lastLoaded: 0,
                    chunks: [],
                    uploadId: null,
                    preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
                };
                
                // Add to files array
                this.files.push(fileObj);
                
                // Prepare chunks for chunked upload
                this.prepareChunks(fileObj);
            });
        },
        
        // Prepare file chunks for chunked upload
        prepareChunks(fileObj) {
            const chunkSize = this.chunkSize;
            const fileSize = fileObj.size;
            let offset = 0;
            let chunkIndex = 0;
            
            fileObj.chunks = [];
            
            while (offset < fileSize) {
                const chunk = fileObj.file.slice(offset, offset + chunkSize);
                fileObj.chunks.push({
                    index: chunkIndex,
                    start: offset,
                    end: Math.min(offset + chunkSize, fileSize),
                    size: Math.min(chunkSize, fileSize - offset),
                    blob: chunk,
                    uploaded: false,
                    progress: 0
                });
                
                offset += chunkSize;
                chunkIndex++;
            }
        },
        
        // Start uploading files
        async uploadFiles() {
            if (this.isUploading || this.files.length === 0) return;
            
            this.isUploading = true;
            this.error = null;
            
            // Filter out already completed files
            const filesToUpload = this.files.filter(f => f.status !== 'completed');
            
            // Process files in the queue
            for (const file of filesToUpload) {
                if (file.status === 'error') {
                    file.status = 'pending';
                    file.progress = 0;
                }
                
                // Add to upload queue
                this.uploadQueue.push(file);
            }
            
            // Start processing the queue
            this.processUploadQueue();
        },
        
        // Process the upload queue with concurrency control
        async processUploadQueue() {
            while (this.uploadQueue.length > 0 && this.activeUploads < this.maxConcurrentUploads) {
                const file = this.uploadQueue.shift();
                if (file && file.status === 'pending') {
                    this.activeUploads++;
                    this.uploadFile(file)
                        .finally(() => {
                            this.activeUploads--;
                            this.processUploadQueue(); // Process next in queue
                        });
                }
            }
            
            // Check if all uploads are done
            if (this.uploadQueue.length === 0 && this.activeUploads === 0) {
                this.isUploading = false;
                
                // Refresh the uploaded files list
                this.loadUploadedFiles();
            }
        },
        
        // Upload a single file
        async uploadFile(file) {
            if (file.status === 'completed') return;
            
            file.status = 'uploading';
            file.startTime = Date.now();
            file.lastLoaded = 0;
            
            try {
                // Initialize chunked upload
                if (!file.uploadId) {
                    const uploadId = await this.initChunkedUpload(file);
                    if (!uploadId) {
                        throw new Error('Failed to initialize upload');
                    }
                    file.uploadId = uploadId;
                }
                
                // Upload chunks
                for (const chunk of file.chunks) {
                    if (chunk.uploaded) continue;
                    
                    await this.uploadChunk(file, chunk);
                    chunk.uploaded = true;
                    chunk.progress = 100;
                    
                    // Update overall file progress
                    const uploadedChunks = file.chunks.filter(c => c.uploaded).length;
                    file.progress = Math.round((uploadedChunks / file.chunks.length) * 100);
                    
                    // Calculate upload speed and time remaining
                    this.calculateUploadStats(file);
                }
                
                // Complete the upload
                await this.completeChunkedUpload(file);
                
                file.status = 'completed';
                file.progress = 100;
                
            } catch (error) {
                console.error('Upload failed:', error);
                file.status = 'error';
                file.error = error.message || 'Upload failed';
                this.showError(`Failed to upload "${file.name}": ${error.message}`);
            }
        },
        
        // Initialize chunked upload
        async initChunkedUpload(file) {
            try {
                const response = await fetch(this.getEndpoint('init', file.uploadId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        filename: file.name,
                        file_size: file.size,
                        mime_type: file.type,
                        total_chunks: file.chunks.length
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to initialize upload');
                }
                
                const data = await response.json();
                return data.upload_id;
                
            } catch (error) {
                console.error('Init chunked upload failed:', error);
                throw new Error('Failed to initialize upload');
            }
        },
        
        // Upload a single chunk
        async uploadChunk(file, chunk) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                
                formData.append('chunk_index', chunk.index);
                formData.append('total_chunks', file.chunks.length);
                formData.append('file', chunk.blob, file.name);
                
                xhr.open('POST', this.getEndpoint('upload', file.uploadId), true);
                
                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        // Update chunk progress
                        chunk.progress = Math.round((event.loaded / event.total) * 100);
                        
                        // Calculate loaded bytes for this chunk
                        const loaded = chunk.start + (event.loaded / event.total) * chunk.size;
                        
                        // Update file progress
                        file.progress = Math.round((loaded / file.size) * 100);
                        
                        // Calculate upload speed and time remaining
                        this.calculateUploadStats(file, loaded);
                    }
                };
                
                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(xhr.response);
                    } else {
                        reject(new Error(`Upload failed with status ${xhr.status}`));
                    }
                };
                
                xhr.onerror = () => {
                    reject(new Error('Network error during upload'));
                };
                
                xhr.send(formData);
            });
        },
        
        // Complete chunked upload
        async completeChunkedUpload(file) {
            try {
                const response = await fetch(this.getEndpoint('complete', file.uploadId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        filename: file.name,
                        file_size: file.size,
                        mime_type: file.type,
                        total_chunks: file.chunks.length
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to complete upload');
                }
                
                return await response.json();
                
            } catch (error) {
                console.error('Complete chunked upload failed:', error);
                throw new Error('Failed to complete upload');
            }
        },
        
        // Calculate upload statistics (speed and time remaining)
        calculateUploadStats(file, loadedBytes) {
            const now = Date.now();
            const timeElapsed = (now - file.startTime) / 1000; // in seconds
            const bytesUploaded = loadedBytes || (file.size * file.progress / 100);
            
            // Calculate speed (bytes per second)
            if (timeElapsed > 0) {
                file.speed = Math.round(bytesUploaded / timeElapsed);
            }
            
            // Calculate time remaining
            if (file.speed > 0) {
                const remainingBytes = file.size - bytesUploaded;
                file.timeRemaining = Math.ceil(remainingBytes / file.speed);
            }
        },
        
        // Remove a file from the upload list
        removeFile(index) {
            const file = this.files[index];
            
            // If upload is in progress, cancel it
            if (file.status === 'uploading') {
                // TODO: Implement cancellation of in-progress uploads
                if (confirm('This file is currently uploading. Are you sure you want to cancel and remove it?')) {
                    this.files.splice(index, 1);
                }
            } else {
                this.files.splice(index, 1);
            }
        },
        
        // Clear all files from the upload list
        clearFiles() {
            if (this.isUploading) {
                if (confirm('There are uploads in progress. Are you sure you want to cancel all uploads?')) {
                    // TODO: Cancel all in-progress uploads
                    this.files = [];
                    this.uploadQueue = [];
                    this.activeUploads = 0;
                    this.isUploading = false;
                }
            } else {
                this.files = [];
            }
        },
        
        // Delete an uploaded file
        async deleteFile(fileId, index) {
            if (!confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                return;
            }
            
            this.isDeleting = true;
            
            try {
                const response = await fetch(`/api/assets/${this.assetId}/attachments/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    // Remove from the UI
                    this.uploadedFiles.splice(index, 1);
                    
                    // Show success message
                    this.showToast('File deleted successfully', 'success');
                } else {
                    throw new Error('Failed to delete file');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                this.showError(`Failed to delete file: ${error.message}`);
            } finally {
                this.isDeleting = false;
            }
        },
        
        // Helper to get endpoint URL with upload ID
        getEndpoint(type, uploadId) {
            return this.endpoints[type].replace('UPLOAD_ID', uploadId || '');
        },
        
        // Format file size
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // Format upload speed
        formatSpeed(bytesPerSecond) {
            if (bytesPerSecond < 1024) {
                return bytesPerSecond + ' B/s';
            } else if (bytesPerSecond < 1024 * 1024) {
                return (bytesPerSecond / 1024).toFixed(1) + ' KB/s';
            } else {
                return (bytesPerSecond / (1024 * 1024)).toFixed(1) + ' MB/s';
            }
        },
        
        // Format time remaining
        formatTime(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
            } else {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                return hours + 'h ' + minutes + 'm';
            }
        },
        
        // Format date
        formatDate(dateString) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        
        // Show error message
        showError(message) {
            this.error = message;
            // Auto-hide error after 5 seconds
            setTimeout(() => {
                this.error = null;
            }, 5000);
        },
        
        // Show toast notification
        showToast(message, type = 'success') {
            const event = new CustomEvent('toast', {
                detail: { message, type }
            });
            window.dispatchEvent(event);
        },
        
        // Generate a unique ID
        generateId() {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        }
    };
}
</script>
@endpush