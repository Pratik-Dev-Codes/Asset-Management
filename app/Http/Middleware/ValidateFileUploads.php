<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ValidateFileUploads
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasFile('*')) {
            $this->validateFiles($request);
        }
        
        return $next($request);
    }
    
    /**
     * Validate all uploaded files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function validateFiles(Request $request)
    {
        $files = $request->allFiles();
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $this->validateFile($f);
                }
            } else {
                $this->validateFile($file);
            }
        }
    }
    
    /**
     * Validate a single file.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function validateFile(UploadedFile $file)
    {
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            abort(422, 'File size must be less than 10MB');
        }
        
        // Get file extension
        $extension = strtolower($file->getClientOriginalExtension());
        
        // List of allowed extensions
        $allowedExtensions = [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            // Archives
            'zip', 'rar', '7z',
            // Audio/Video
            'mp3', 'wav', 'mp4', 'mov', 'avi', 'mkv'
        ];
        
        if (!in_array($extension, $allowedExtensions)) {
            abort(422, 'File type not allowed');
        }
        
        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/svg+xml', 'image/webp',
            // Documents
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            // Archives
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            // Audio/Video
            'audio/mpeg', 'audio/wav', 'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            abort(422, 'File type not allowed');
        }
        
        // Check for PHP files disguised as other file types
        if ($this->isFileMalicious($file)) {
            abort(422, 'Malicious file detected');
        }
    }
    
    /**
     * Check if a file might be malicious.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return bool
     */
    protected function isFileMalicious(UploadedFile $file)
    {
        $content = file_get_contents($file->getRealPath());
        
        // Check for PHP tags
        if (preg_match('/<\?php|\<\?\=/i', $content)) {
            return true;
        }
        
        // Check for common PHP functions
        $dangerousFunctions = [
            'system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open',
            'eval', 'assert', 'create_function', 'include', 'include_once',
            'require', 'require_once', 'base64_decode', 'gzinflate', 'gzuncompress',
            'str_rot13', 'get_defined_vars', 'get_defined_functions', 'get_defined_constants',
            'get_included_files', 'get_required_files', 'get_loaded_extensions', 'get_extension_funcs',
            'dl', 'ini_set', 'ini_restore', 'ini_get_all', 'set_time_limit', 'ignore_user_abort',
            'highlight_file', 'show_source', 'phpinfo', 'phpversion', 'get_cfg_var', 'disk_free_space',
            'disk_total_space', 'diskfreespace', 'getlastmo', 'getrusage', 'getmyuid', 'getmygid',
            'getmyinode', 'getmyuid', 'getmygid', 'getmyinode', 'get_current_user', 'getcwd',
            'getenv', 'getopt', 'getrandmax', 'getrusage', 'getservbyname', 'getservbyport',
            'getprotobyname', 'getprotobynumber', 'getmyinode', 'getlastmod', 'getmyinode',
            'getmypid', 'getmyuid', 'getmygid', 'get_current_user', 'get_include_path',
            'get_required_files', 'get_included_files', 'get_loaded_extensions', 'get_extension_funcs',
            'get_defined_functions', 'get_defined_vars', 'get_defined_constants', 'get_cfg_var',
            'magic_quotes_runtime', 'set_magic_quotes_runtime', 'import_request_variables',
            'extract', 'parse_str', 'putenv', 'ini_set', 'ini_restore', 'ini_get_all',
            'dl', 'pfsockopen', 'fsockopen', 'popen', 'proc_open', 'stream_socket_server',
            'stream_socket_client', 'stream_socket_accept', 'stream_socket_pair', 'stream_socket_sendto',
            'stream_socket_recvfrom', 'stream_socket_get_name', 'stream_socket_enable_crypto',
            'stream_socket_shutdown', 'stream_socket_client', 'stream_socket_server',
            'stream_socket_accept', 'stream_socket_pair', 'stream_socket_sendto', 'stream_socket_recvfrom',
            'stream_socket_get_name', 'stream_socket_enable_crypto', 'stream_socket_shutdown'
        ];
        
        foreach ($dangerousFunctions as $function) {
            if (stripos($content, $function . '(') !== false) {
                return true;
            }
        }
        
        return false;
    }
}
