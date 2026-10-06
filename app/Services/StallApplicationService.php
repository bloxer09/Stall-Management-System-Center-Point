<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StallApplicationService
{
    public function __construct(
        protected MailService $mailer,
        protected FileUploadService $uploader,
    ) {}

    /**
     * Record a public stall application, then confirm to the applicant and alert every admin.
     *
     * @throws ValidationException when the stall is no longer open, the applicant already has a
     *                             pending application for it, or the IP has hit the hourly limit.
     */
    public function submit(array $data, ?UploadedFile $document, string $ip): StallApplication
    {
        $data['applicant_email'] = Str::lower(trim($data['applicant_email']));
        $throttleKey = 'stall-apply:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);
            throw ValidationException::withMessages([
                'applicant_email' => "Too many applications from your connection. Please try again in {$minutes} minutes.",
            ]);
        }

        $stall = Stall::find($data['preferred_stall_id'] ?? null);
        if (!$stall || $stall->status !== 'available') {
            throw ValidationException::withMessages([
                'applicant_email' => 'Sorry, this stall is no longer open for applications.',
            ]);
        }

        $duplicate = StallApplication::where('applicant_email', $data['applicant_email'])
            ->where('preferred_stall_id', $stall->id)
            ->where('status', 'pending')
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'applicant_email' => 'You already have a pending application for this stall.',
            ]);
        }

        $documentPath = $document ? $this->uploader->uploadApplicationDocument($document) : null;

        $application = DB::transaction(fn () => StallApplication::create($data + [
            'document' => $documentPath,
            'status' => 'pending',
        ]));

        RateLimiter::hit($throttleKey, 3600);

        $this->mailer->sendEmail(
            $application->applicant_email,
            $application->applicant_name,
            'We received your stall application',
            '<p>Hello ' . e($application->applicant_name) . ',</p>'
                . '<p>Thank you for applying for <strong>Stall ' . e($stall->stall_number) . '</strong>. '
                . 'Our office will review your application and email you the result.</p>',
            "Hello {$application->applicant_name}, thank you for applying for Stall {$stall->stall_number}. Our office will review your application and email you the result."
        );

        foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
            Notification::send(
                $adminId,
                'application',
                'New stall application',
                "{$application->applicant_name} applied for Stall {$stall->stall_number}",
                url('/admin/applications')
            );
        }

        return $application;
    }
}
