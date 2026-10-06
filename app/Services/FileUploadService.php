<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function uploadContractDocument(UploadedFile $file): string
    {
        $filename = 'contract_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('contracts', $filename, 'local');
    }

    public function uploadApplicationDocument(UploadedFile $file): string
    {
        $filename = 'application_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('application_documents', $filename, 'local');
    }

    public function uploadTicketAttachment(UploadedFile $file): string
    {
        $filename = 'ticket_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('ticket_attachments', $filename, 'local');
    }

    public function uploadTicketAttachments(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->uploadTicketAttachment($file);
            }
        }
        return $paths;
    }

    public function uploadProfilePicture(UploadedFile $file): string
    {
        $filename = 'avatar_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('avatars', $filename, 'public');
    }

    public function uploadStallImage(UploadedFile $file): string
    {
        $filename = 'stall_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('stalls', $filename, 'public');
    }
}
