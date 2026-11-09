<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class TicketMedia implements ValidationRule
{
    public const ACCEPT = '.pdf,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.ogg,.m4a,.aac,.flac,.mp4,.webm,.mov';
    public const DESCRIPTION = 'Documents, Images, Audio and Videos. Up to 10 MB per file.';
    private const TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'],
        'image/gif' => ['gif'], 'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'audio/mpeg' => ['mp3'], 'audio/x-wav' => ['wav'], 'audio/wav' => ['wav'],
        'audio/ogg' => ['ogg'], 'application/ogg' => ['ogg'],
        'audio/mp4' => ['m4a'], 'audio/x-m4a' => ['m4a'],
        'audio/aac' => ['aac'], 'audio/x-hx-aac-adts' => ['aac'],
        'audio/flac' => ['flac'], 'audio/x-flac' => ['flac'],
        'video/mp4' => ['mp4', 'm4a'], 'video/webm' => ['webm'], 'video/quicktime' => ['mov'],
    ];

    public static function rules(): array
    {
        return ['required', 'file', 'max:10240', new self];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid() || !$this->matchesContent($value)) {
            $fail('Unsupported file or file contents do not match its extension. '.self::DESCRIPTION);
        }
    }

    private function matchesContent(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            return $this->matchesOfficePackage($path, $extension);
        }
        if ($extension === 'xls') {
            try {
                return (new \PhpOffice\PhpSpreadsheet\Reader\Xls())->canRead($path);
            } catch (\Throwable) {
                return false;
            }
        }
        $mime = \Symfony\Component\Mime\MimeTypes::getDefault()->guessMimeType($path);

        return in_array($extension, self::TYPES[$mime ?? ''] ?? [], true);
    }

    private function matchesOfficePackage(string $path, string $extension): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        try {
            $part = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            $type = $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
            $contents = $zip->getFromName('[Content_Types].xml', 65536);
            if ($contents === false || stripos($contents, '<!DOCTYPE') !== false || $zip->locateName($part) === false) {
                return false;
            }
            $xml = new \DOMDocument();
            if (!@$xml->loadXML($contents, LIBXML_NONET)) {
                return false;
            }
            foreach ($xml->getElementsByTagNameNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Override') as $override) {
                if ($override->getAttribute('PartName') === '/'.$part && $override->getAttribute('ContentType') === $type) {
                    return true;
                }
            }

            return false;
        } finally {
            $zip->close();
        }
    }
}
