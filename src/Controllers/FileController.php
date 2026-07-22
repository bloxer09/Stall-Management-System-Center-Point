<?php

namespace App\Controllers;

use App\Config\App;
use App\Core\Auth;
use App\Models\Mapping;
use App\Models\Ticket;

class FileController
{
    public function ticketAttachment($id)
    {
        Auth::requireLogin();

        $ticketModel = new Ticket();
        $message = $ticketModel->findMessageById((int)$id);

        if (!$message || empty($message['attachment_path'])) {
            http_response_code(404);
            echo 'File not found.';
            return;
        }

        $user = Auth::user();
        $isStaffOrAdmin = in_array($user['role'], ['admin', 'staff'], true);
        $isOwner = (int)$message['ticket_owner_id'] === (int)$user['id'];

        if (!$isStaffOrAdmin && !$isOwner) {
            http_response_code(403);
            echo 'Access denied.';
            return;
        }

        $this->serveFile($message['attachment_path']);
    }

    public function contractDocument($id)
    {
        Auth::requireLogin();

        $mappingModel = new Mapping();
        $mapping = $mappingModel->findById((int)$id);

        if (!$mapping || empty($mapping['contract_document'])) {
            http_response_code(404);
            echo 'File not found.';
            return;
        }

        $user = Auth::user();
        $isStaffOrAdmin = in_array($user['role'], ['admin', 'staff'], true);
        $isOwner = (int)$mapping['user_id'] === (int)$user['id'];

        if (!$isStaffOrAdmin && !$isOwner) {
            http_response_code(403);
            echo 'Access denied.';
            return;
        }

        $this->serveFile($mapping['contract_document']);
    }

    private function serveFile($relativePath)
    {
        $cleanPath = ltrim(str_replace('\\', '/', (string)$relativePath), '/');
        $basePrefix = trim(App::basePath(), '/');
        if ($basePrefix !== '' && str_starts_with($cleanPath, $basePrefix . '/')) {
            $cleanPath = substr($cleanPath, strlen($basePrefix) + 1);
        }

        if (strpos($cleanPath, '..') !== false) {
            http_response_code(400);
            echo 'Invalid file path.';
            return;
        }

        $absolutePath = App::projectRoot($cleanPath);
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo 'File not found.';
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($absolutePath) ?: 'application/octet-stream';
        $name = basename($absolutePath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($absolutePath));
        header('X-Content-Type-Options: nosniff');

        $disposition = (str_starts_with($mime, 'image/') || $mime === 'application/pdf') ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($name) . '"');

        readfile($absolutePath);
        exit();
    }
}
