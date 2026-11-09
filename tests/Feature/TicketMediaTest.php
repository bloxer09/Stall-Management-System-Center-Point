<?php

namespace Tests\Feature;

use App\Livewire\Admin\TicketCenter;
use App\Livewire\User\TicketManager;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Rules\TicketMedia;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class TicketMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_roles_reject_unsupported_files_before_preview(): void
    {
        foreach (['user' => TicketManager::class, 'admin' => TicketCenter::class] as $role => $component) {
            $user = User::factory()->create(['role' => $role]);
            Livewire::actingAs($user)->test($component)
                ->set('incomingAttachments', [UploadedFile::fake()->createWithContent('program.exe', 'MZ executable')])
                ->assertHasErrors('incomingAttachments.0')->assertSet('attachments', [])
                ->assertSet('incomingAttachments', []);
        }
    }

    public function test_content_and_extension_must_match_and_size_is_limited(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('photo.png', '<?php echo "not an image";'),
            UploadedFile::fake()->image('script.php'),
            UploadedFile::fake()->image('large.png')->size(10241),
        ];
        foreach ($files as $file) {
            $this->assertTrue(Validator::make(['media' => $file], ['media' => TicketMedia::rules()])->fails());
        }
        $this->assertFalse(Validator::make(['media' => UploadedFile::fake()->image('photo.png')], ['media' => TicketMedia::rules()])->fails());
    }

    public function test_video_content_is_accepted_and_a_mixed_batch_does_not_store_files(): void
    {
        Storage::fake('local');
        $video = UploadedFile::fake()->createWithContent('clip.mp4', hex2bin('000000186674797069736f6d0000000069736f6d69736f32'));
        $this->assertFalse(Validator::make(['media' => $video], ['media' => TicketMedia::rules()])->fails());
        $path = app(FileUploadService::class)->uploadTicketAttachment($video);
        Storage::disk('local')->assertExists($path);
        $before = Storage::disk('local')->allFiles();
        try {
            app(FileUploadService::class)->uploadTicketAttachments([
                UploadedFile::fake()->image('ok.png'), UploadedFile::fake()->createWithContent('bad.pdf', 'not a PDF'),
            ]);
            $this->fail('Mixed invalid uploads must fail.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('attachments.1', $exception->errors());
        }
        $this->assertSame($before, Storage::disk('local')->allFiles());
    }

    public function test_both_roles_accept_documents_spreadsheets_and_audio_before_preview(): void
    {
        foreach (['user' => TicketManager::class, 'admin' => TicketCenter::class] as $role => $component) {
            $files = $this->documentAndAudioFiles();
            Livewire::actingAs(User::factory()->create(['role' => $role]))->test($component)
                ->set('incomingAttachments', $files)->assertHasNoErrors()
                ->assertSet('incomingAttachments', [])
                ->assertSet('attachments', fn ($attachments) => count($attachments) === 5);
            foreach ($files as $file) {
                $this->assertFalse(Validator::make(['file' => $file], ['file' => TicketMedia::rules()])->fails(), $file->getClientOriginalName());
            }
        }
    }

    public function test_office_files_keep_their_extensions_and_reject_disguised_packages(): void
    {
        Storage::fake('local');
        foreach ($this->documentAndAudioFiles() as $file) {
            $path = app(FileUploadService::class)->uploadTicketAttachment($file);
            $this->assertSame($file->getClientOriginalExtension(), pathinfo($path, PATHINFO_EXTENSION));
            Storage::disk('local')->assertExists($path);
        }
        $files = $this->documentAndAudioFiles();
        foreach ([
            UploadedFile::fake()->createWithContent('report.docx', file_get_contents($files[3]->getRealPath())),
            UploadedFile::fake()->createWithContent('report.xlsx', file_get_contents($files[1]->getRealPath())),
            UploadedFile::fake()->createWithContent('report.xls', '%PDF-1.4'),
            UploadedFile::fake()->createWithContent('audio.mp3', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('report.docx', 'PK not an Office package'),
        ] as $file) {
            $this->assertTrue(Validator::make(['file' => $file], ['file' => TicketMedia::rules()])->fails());
        }
    }

    private function documentAndAudioFiles(): array
    {
        $path = tempnam(sys_get_temp_dir(), 'ticket-office-');
        try {
            $zip = new \ZipArchive();
            $zip->open($path, \ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>');
            $zip->close();
            $docx = UploadedFile::fake()->createWithContent('report.docx', file_get_contents($path));
            $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $book->getActiveSheet()->setCellValue('A1', 'Ticket attachment');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xls($book))->save($path);
            $xls = UploadedFile::fake()->createWithContent('report.xls', file_get_contents($path));
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            $xlsx = UploadedFile::fake()->createWithContent('report.xlsx', file_get_contents($path));
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $wav = 'RIFF'.pack('V', 38).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', 2)."\0\0";

        return [UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n%%EOF"), $docx, $xls, $xlsx,
            UploadedFile::fake()->createWithContent('voice.wav', $wav)];
    }

    public function test_media_download_uses_private_disk_and_ticket_ownership(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::create(['user_id' => $tenant->id, 'issue' => 'Media test', 'status' => 'open']);
        $path = app(FileUploadService::class)->uploadTicketAttachment(UploadedFile::fake()->image('photo.png'));
        $message = TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $tenant->id, 'message' => 'Photo', 'attachment_path' => $path]);
        $this->actingAs($tenant)->get(route('file.ticket-attachment', $message->id))->assertOk();
        $this->actingAs($other)->get(route('file.ticket-attachment', $message->id))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('file.ticket-attachment', $message->id))->assertOk();
    }
}
