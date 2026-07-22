<?php

namespace App\Services;

use App\Config\App;

class FileUploadService
{
    public function store(array $file, string $category, array $options = []): array
    {
        $maxSize = (int)($options['max_size'] ?? (5 * 1024 * 1024));
        $allowedExtensions = array_map('strtolower', $options['allowed_extensions'] ?? []);
        $allowedMimes = array_map('strtolower', $options['allowed_mimes'] ?? []);
        $visibility = strtolower((string)($options['visibility'] ?? 'private'));

        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \RuntimeException('Invalid file upload payload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed with error code: ' . $file['error']);
        }

        if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxSize) {
            throw new \RuntimeException('Uploaded file size is invalid.');
        }

        $originalName = (string)($file['name'] ?? 'file');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('File type is not allowed.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = strtolower((string)$finfo->file($file['tmp_name']));

        if (!empty($allowedMimes) && !in_array($mimeType, $allowedMimes, true)) {
            throw new \RuntimeException('File MIME type is not allowed.');
        }

        $safeCategory = trim(preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $category), '/');
        if ($safeCategory === '') {
            $safeCategory = 'misc';
        }

        $year = date('Y');
        $month = date('m');
        $randomName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');

        if ($visibility === 'public') {
            $relativePath = "public/uploads/{$safeCategory}/{$year}/{$month}/{$randomName}";
            $absolutePath = App::projectRoot($relativePath);
            $publicUrl = App::path('/' . $relativePath);
        } else {
            $relativePath = "storage/uploads/{$safeCategory}/{$year}/{$month}/{$randomName}";
            $absolutePath = App::projectRoot($relativePath);
            $publicUrl = null;
        }

        $targetDir = dirname($absolutePath);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create upload directory.');
        }

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return [
            'path' => str_replace('\\', '/', $relativePath),
            'public_url' => $publicUrl,
            'mime_type' => $mimeType,
            'size' => (int)$file['size'],
            'original_name' => $originalName,
            'extension' => $extension,
        ];
    }
}

