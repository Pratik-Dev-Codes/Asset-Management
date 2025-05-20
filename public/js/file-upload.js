// public/js/file-upload.js
class FileUploader {
    constructor(options) {
        this.dropZone = options.dropZone;
        this.fileInput = options.fileInput;
        this.progressBar = options.progressBar;
        this.progressText = options.progressText;
        this.fileList = options.fileList;
        this.uploadBtn = options.uploadBtn;
        this.clearBtn = options.clearBtn;
        this.errorAlert = options.errorAlert;
        this.assetId = options.assetId;
        this.maxFileSize = options.maxFileSize;
        this.chunkSize = options.chunkSize || 5 * 1024 * 1024; // 5MB default chunk size
        this.maxConcurrentUploads = options.maxConcurrentUploads || 3;
        this.autoUpload = options.autoUpload !== false;
        this.files = [];
        this.uploading = false;
        this.uploadQueue = [];
        this.activeUploads = 0;
        this.uploadedCount = 0;
        this.totalFiles = 0;
        
        this.initialize();
    }
    
    initialize() {
        // Set up drag and drop
        if (this.dropZone) {
            this.dropZone.addEventListener('dragover', this.handleDragOver.bind(this));
            this.dropZone.addEventListener('dragleave', this.handleDragLeave.bind(this));
            this.dropZone.addEventListener('drop', this.handleDrop.bind(this));
        }
        
        // Set up file input change event
        if (this.fileInput) {
            this.fileInput.addEventListener('change', this.handleFileSelect.bind(this));
        }
        
        // Set up upload button click
        if (this.uploadBtn) {
            this.uploadBtn.addEventListener('click', this.uploadFiles.bind(this));
        }
        
        // Set up clear button click
        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', this.clearFiles.bind(this));
        }
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.add('drag-over');
    }
    
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');
    }
    
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        this.processFiles(files);
    }
    
    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        this.processFiles(files);
        
        // Reset the file input to allow selecting the same file again
        this.fileInput.value = '';
    }
    
    processFiles(files) {
        if (!files || files.length === 0) return;
        
        // Check if adding these files would exceed the max files limit
        if (this.files.length + files.length > this.maxFiles) {
            this.showError(`You can only upload a maximum of ${this.maxFileSize} files at once.`);
            return;
        }
        
        files.forEach(file => {
            // Check file size
            if (file.size > this.maxFileSize) {
                this.showError(`File "${file.name}" exceeds the maximum allowed size of ${this.formatFileSize(this.maxFileSize)}.`);
                return;
            }
            
            // Add file to the queue
            this.addFileToQueue(file);
        });
        
        // Update UI
        this.updateFileList();
        
        // Auto-upload if enabled
        if (this.autoUpload && !this.uploading) {
            this.uploadFiles();
        }
    }
    
    addFileToQueue(file) {
        const fileItem = {
            id: this.generateFileId(),
            file: file,
            name: file.name,
            size: file.size,
            type: file.type,
            status: 'pending',
            progress: 0,
            error: null,
            chunkIndex: 0,
            chunks: Math.ceil(file.size / this.chunkSize),
            uploadedChunks: 0,
            uploadId: null
        };
        
        this.files.push(fileItem);
        this.uploadQueue.push(fileItem);
    }
    
    generateFileId() {
        return 'file-' + Math.random().toString(36).substr(2, 9);
    }
    
    updateFileList() {
        if (!this.fileList) return;
        
        if (this.files.length === 0) {
            this.fileList.innerHTML = '<div class="no-files">No files selected</div>';
            return;
        }
        
        let html = '';
        
        this.files.forEach(fileItem => {
            const icon = this.getFileIcon(fileItem.type);
            const size = this.formatFileSize(fileItem.size);
            let statusHtml = '';
            
            if (fileItem.status === 'uploading') {
                statusHtml = `
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${fileItem.progress}%"></div>
                    </div>
                    <span class="file-status uploading">${fileItem.progress}%</span>
                `;
            } else if (fileItem.status === 'completed') {
                statusHtml = '<span class="file-status completed"><i class="fas fa-check"></i> Uploaded</span>';
            } else if (fileItem.status === 'error') {
                statusHtml = `<span class="file-status error" title="${fileItem.error || 'Upload failed'}"><i class="fas fa-exclamation-circle"></i> Error</span>`;
            } else {
                statusHtml = '<span class="file-status pending">Pending</span>';
            }
            
            html += `
                <div class="file-item" data-file-id="${fileItem.id}">
                    <div class="file-icon">${icon}</div>
                    <div class="file-details">
                        <div class="file-name" title="${fileItem.name}">${fileItem.name}</div>
                        <div class="file-meta">
                            <span class="file-size">${size}</span>
                            ${statusHtml}
                        </div>
                    </div>
                    <div class="file-actions">
                        ${fileItem.status === 'completed' ? `
                            <a href="#" class="btn btn-sm btn-outline-secondary" title="Download" disabled>
                                <i class="fas fa-download"></i>
                            </a>
                        ` : ''}
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="event.stopPropagation(); this.closest('.file-uploader').fileUploader.removeFile('${fileItem.id}')" 
                                title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        this.fileList.innerHTML = html;
    }
    
    async uploadFiles() {
        if (this.uploading || this.uploadQueue.length === 0) return;
        
        this.uploading = true;
        this.totalFiles = this.uploadQueue.length;
        this.uploadedCount = 0;
        
        // Show progress bar
        if (this.progressBar && this.progressText) {
            this.progressBar.parentElement.style.display = 'block';
            this.progressBar.style.width = '0%';
            this.progressText.textContent = `Uploading 0 of ${this.totalFiles} file(s)`;
        }
        
        // Enable/disable buttons
        if (this.uploadBtn) {
            this.uploadBtn.disabled = true;
            this.uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        }
        
        if (this.clearBtn) {
            this.clearBtn.disabled = true;
        }
        
        // Process upload queue
        while (this.uploadQueue.length > 0 && this.activeUploads < this.maxConcurrentUploads) {
            this.processNextFile();
        }
    }
    
    async processNextFile() {
        if (this.uploadQueue.length === 0 || this.activeUploads >= this.maxConcurrentUploads) {
            return;
        }
        
        const fileItem = this.uploadQueue.shift();
        this.activeUploads++;
        
        try {
            // Initialize chunked upload
            if (!fileItem.uploadId) {
                const response = await this.initChunkedUpload(fileItem);
                fileItem.uploadId = response.uploadId;
            }
            
            // Upload chunks
            while (fileItem.chunkIndex < fileItem.chunks) {
                await this.uploadChunk(fileItem);
                fileItem.chunkIndex++;
                fileItem.uploadedChunks++;
                
                // Update progress
                const progress = Math.round((fileItem.uploadedChunks / fileItem.chunks) * 100);
                this.updateFileProgress(fileItem.id, progress);
                
                // Update overall progress
                this.updateOverallProgress();
            }
            
            // Complete the upload
            await this.completeUpload(fileItem);
            
            // Update status
            this.updateFileStatus(fileItem.id, 'completed');
            this.uploadedCount++;
            
        } catch (error) {
            console.error('Upload error:', error);
            this.updateFileStatus(fileItem.id, 'error', error.message || 'Upload failed');
            this.showError(`Failed to upload ${fileItem.name}: ${error.message || 'Unknown error'}`);
        } finally {
            this.activeUploads--;
            
            // Process next file in queue
            this.processNextFile();
            
            // Check if all uploads are complete
            if (this.activeUploads === 0) {
                this.uploadComplete();
            }
        }
    }
    
    async initChunkedUpload(fileItem) {
        const formData = new FormData();
        formData.append('filename', fileItem.name);
        formData.append('fileSize', fileItem.size);
        formData.append('chunkSize', this.chunkSize);
        formData.append('totalChunks', fileItem.chunks);
        formData.append('mimeType', fileItem.type);
        
        const response = await fetch(`/api/assets/${this.assetId}/attachments/chunked/init`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to initialize upload');
        }
        
        return response.json();
    }
    
    async uploadChunk(fileItem) {
        const start = fileItem.chunkIndex * this.chunkSize;
        const end = Math.min(fileItem.file.size, start + this.chunkSize);
        const chunk = fileItem.file.slice(start, end);
        
        const formData = new FormData();
        formData.append('file', chunk, fileItem.name);
        formData.append('chunkIndex', fileItem.chunkIndex);
        formData.append('totalChunks', fileItem.chunks);
        formData.append('uploadId', fileItem.uploadId);
        
        const response = await fetch(`/api/assets/${this.assetId}/attachments/chunked/${fileItem.uploadId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Chunk upload failed');
        }
        
        return response.json();
    }
    
    async completeUpload(fileItem) {
        const response = await fetch(`/api/assets/${this.assetId}/attachments/chunked/${fileItem.uploadId}/complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                filename: fileItem.name,
                originalName: fileItem.file.name,
                mimeType: fileItem.type,
                size: fileItem.size
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to complete upload');
        }
        
        return response.json();
    }
    
    updateFileProgress(fileId, progress) {
        const fileItem = this.files.find(f => f.id === fileId);
        if (fileItem) {
            fileItem.progress = progress;
            fileItem.status = progress < 100 ? 'uploading' : 'completed';
            this.updateFileList();
        }
    }
    
    updateFileStatus(fileId, status, error = null) {
        const fileItem = this.files.find(f => f.id === fileId);
        if (fileItem) {
            fileItem.status = status;
            if (error) {
                fileItem.error = error;
            }
            this.updateFileList();
        }
    }
    
    updateOverallProgress() {
        if (this.files.length === 0) return;
        
        // Calculate overall progress
        const totalProgress = this.files.reduce((sum, file) => sum + (file.progress || 0), 0);
        const overallProgress = Math.round(totalProgress / this.files.length);
        
        // Update progress bar
        if (this.progressBar) {
            this.progressBar.style.width = `${overallProgress}%`;
        }
        
        // Update progress text
        if (this.progressText) {
            const uploadedCount = this.files.filter(f => f.status === 'completed').length;
            this.progressText.textContent = `Uploading ${uploadedCount} of ${this.totalFiles} file(s)`;
        }
    }
    
    uploadComplete() {
        this.uploading = false;
        
        // Reset progress bar
        if (this.progressBar) {
            setTimeout(() => {
                this.progressBar.style.width = '0%';
                this.progressBar.parentElement.style.display = 'none';
            }, 1000);
        }
        
        // Reset buttons
        if (this.uploadBtn) {
            this.uploadBtn.disabled = false;
            this.uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Files';
        }
        
        if (this.clearBtn) {
            this.clearBtn.disabled = false;
        }
        
        // Show completion message
        if (this.uploadedCount > 0) {
            this.showToast(`Successfully uploaded ${this.uploadedCount} file(s)`, 'success');
            
            // Clear completed files
            this.files = this.files.filter(f => f.status !== 'completed');
            this.updateFileList();
            
            // Refresh attachments list
            if (typeof loadAttachments === 'function') {
                loadAttachments(this.assetId);
            }
        }
    }
    
    removeFile(fileId) {
        if (this.uploading) {
            if (!confirm('Uploads are in progress. Are you sure you want to remove this file?')) {
                return;
            }
        }
        
        const fileIndex = this.files.findIndex(f => f.id === fileId);
        if (fileIndex !== -1) {
            // Remove from upload queue if it's there
            this.uploadQueue = this.uploadQueue.filter(f => f.id !== fileId);
            
            // Remove from files array
            this.files.splice(fileIndex, 1);
            
            // Update UI
            this.updateFileList();
            this.updateOverallProgress();
        }
    }
    
    clearFiles() {
        if (this.uploading) {
            if (!confirm('Uploads are in progress. Are you sure you want to cancel all uploads?')) {
                return;
            }
        }
        
        // Abort any active uploads
        // Note: This is a simplified example. In a real app, you'd want to use AbortController
        // to properly cancel fetch requests.
        
        // Clear all files
        this.files = [];
        this.uploadQueue = [];
        this.activeUploads = 0;
        this.uploadedCount = 0;
        
        // Reset UI
        this.updateFileList();
        
        if (this.progressBar) {
            this.progressBar.style.width = '0%';
            this.progressBar.parentElement.style.display = 'none';
        }
        
        if (this.uploadBtn) {
            this.uploadBtn.disabled = false;
            this.uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Files';
        }
        
        if (this.clearBtn) {
            this.clearBtn.disabled = false;
        }
    }
    
    showError(message) {
        if (this.errorAlert) {
            const errorMessage = this.errorAlert.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.textContent = message;
            }
            this.errorAlert.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.errorAlert.style.display = 'none';
            }, 5000);
        } else {
            console.error('Upload error:', message);
        }
    }
    
    showToast(message, type = 'info') {
        // Implementation depends on your toast library
        // This is a simplified example
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    getFileIcon(mimeType) {
        const icons = {
            // Images
            'image/': 'fa-file-image',
            // Documents
            'application/pdf': 'fa-file-pdf',
            'application/msword': 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fa-file-word',
            'application/vnd.ms-excel': 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fa-file-excel',
            'application/vnd.ms-powerpoint': 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'fa-file-powerpoint',
            'text/plain': 'fa-file-alt',
            'text/csv': 'fa-file-csv',
            // Archives
            'application/zip': 'fa-file-archive',
            'application/x-rar-compressed': 'fa-file-archive',
            'application/x-7z-compressed': 'fa-file-archive',
            // Default
            'default': 'fa-file'
        };
        
        for (const [key, icon] of Object.entries(icons)) {
            if (mimeType && mimeType.startsWith(key)) {
                return `<i class="fas ${icon}"></i>`;
            }
        }
        
        return `<i class="fas ${icons['default']}"></i>`;
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Make FileUploader available globally
window.FileUploader = FileUploader;