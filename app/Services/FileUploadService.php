<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class FileUploadService
{
    public function uploadContractDocument(UploadedFile $file): string
    {
        return $file->storeAs('contracts', $this->storedName($file, 'contract'), 'local');
    }

    public function uploadApplicationDocument(UploadedFile $file): string
    {
        return $file->storeAs('application_documents', $this->storedName($file, 'application'), 'local');
    }

    public function uploadTicketAttachment(UploadedFile $file): string
    {
        \Illuminate\Support\Facades\Validator::make(['attachment' => $file],
            ['attachment' => \App\Rules\TicketMedia::rules()])->validate();
        $name = 'ticket_'.Str::random(40).'.'.strtolower($file->getClientOriginalExtension());

        return $file->storeAs('ticket_attachments', $name, 'local');
    }

    public function uploadTicketAttachments(array $files): array
    {
        \Illuminate\Support\Facades\Validator::make(['attachments' => $files],
            ['attachments.*' => \App\Rules\TicketMedia::rules()])->validate();
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
        return $file->storeAs('avatars', $this->storedName($file, 'avatar'), 'public');
    }

    public function uploadStallImage(UploadedFile $file): string
    {
        return $file->storeAs('stalls', $this->storedName($file, 'stall'), 'public');
    }

    /**
     * A server-chosen name. The extension comes from the file's detected content type, never
     * from the uploader's file name, so "photo.php" holding a PNG is saved as .png and can never
     * be executed from public storage.
     */
    private function storedName(UploadedFile $file, string $prefix): string
    {
        // Sniff the bytes themselves (finfo), not the client-declared type or name.
        $mimeTypes = MimeTypes::getDefault();
        $mime = $mimeTypes->guessMimeType($file->getRealPath());
        $extension = $mime ? ($mimeTypes->getExtensions($mime)[0] ?? null) : null;

        return $prefix . '_' . Str::random(40) . '.' . ($extension ?: 'bin');
    }
}
