<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StallApplicationService
{
    /**
     * Whether the applicant email of the last reject() call went out. A failed send never
     * undoes the review; the caller warns the admin instead.
     */
    public bool $lastNotificationSent = true;

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
                route('admin.applications')
            );
        }

        return $application;
    }

    /**
     * Approve an application: link (or create) the applicant's tenant account.
     * The admin assigns the stall afterwards in Lease Mappings.
     *
     * @return array{application: StallApplication, user: User, created: bool, emailSent: bool, phoneDropped: bool}
     *
     * @throws DomainException when the application was already reviewed, or its email belongs to an admin.
     */
    public function approve(StallApplication $application, User $admin): array
    {
        [$application, $user, $created, $phoneDropped] = DB::transaction(function () use ($application, $admin) {
            $locked = $this->lockPending($application);
            $email = Str::lower(trim($locked->applicant_email));

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            $created = false;
            $phoneDropped = false;

            if ($user && $user->role !== 'user') {
                throw new DomainException('This email belongs to an admin account and cannot be used for a tenant application.');
            }

            if (!$user) {
                // users.phone is unique: if another account already uses this number, create the tenant without it.
                $phone = trim((string) $locked->applicant_phone);
                $phoneDropped = $phone !== '' && User::where('phone', $phone)->exists();

                $user = User::create([
                    'name' => $locked->applicant_name,
                    'email' => $email,
                    'phone' => $phoneDropped || $phone === '' ? null : $phone,
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
                $created = true;
            }

            $locked->update([
                'status' => 'approved',
                'user_id' => $user->id,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::record(
                "Approved stall application from {$locked->applicant_name}" . ($created ? ' (tenant account created)' : ' (linked to existing account)'),
                'StallApplication',
                $locked->id,
                null,
                $admin->id
            );

            return [$locked, $user, $created, $phoneDropped];
        });

        $stallNumber = $application->preferredStall?->stall_number;
        $stallText = $stallNumber ? ' for Stall ' . $stallNumber : '';

        if ($created) {
            $link = $this->setPasswordUrl($user);
            $emailSent = $this->mailer->sendEmail(
                $user->email,
                $user->name,
                'Your stall application was approved — set your password',
                '<p>Hello ' . e($user->name) . ',</p>'
                    . '<p>Good news! Your application' . e($stallText) . ' was approved and your tenant account is ready.</p>'
                    . '<p><a href="' . e($link) . '">Set your password</a> to sign in. This link expires in 72 hours.</p>',
                "Hello {$user->name}, your application{$stallText} was approved. Set your password here (expires in 72 hours): {$link}"
            );
        } else {
            $emailSent = $this->mailer->sendEmail(
                $user->email,
                $user->name,
                'Your stall application was approved',
                '<p>Hello ' . e($user->name) . ',</p>'
                    . '<p>Good news! Your application' . e($stallText) . ' was approved. Sign in to your account to continue.</p>',
                "Hello {$user->name}, your application{$stallText} was approved. Sign in to your account to continue."
            );
            Notification::send($user->id, 'application', 'Application approved', "Your application{$stallText} was approved.", route('user.dashboard'));
        }

        return ['application' => $application, 'user' => $user, 'created' => $created, 'emailSent' => $emailSent, 'phoneDropped' => $phoneDropped];
    }

    /**
     * Signed 72-hour link. The "v" fingerprint changes when the password changes, so a used link stops working.
     */
    public function setPasswordUrl(User $user): string
    {
        return URL::temporarySignedRoute('account.set-password', now()->addHours(72), [
            'user' => $user->id,
            'v' => self::passwordFingerprint($user),
        ]);
    }

    public static function passwordFingerprint(User $user): string
    {
        return substr(sha1((string) $user->password), 0, 12);
    }

    /**
     * @throws DomainException when the application was already reviewed.
     */
    public function reject(StallApplication $application, User $admin, string $reason): StallApplication
    {
        $application = DB::transaction(function () use ($application, $admin, $reason) {
            $locked = $this->lockPending($application);

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::record("Rejected stall application from {$locked->applicant_name}", 'StallApplication', $locked->id, $reason, $admin->id);

            return $locked;
        });

        $stallNumber = $application->preferredStall?->stall_number;
        $this->lastNotificationSent = $this->mailer->sendEmail(
            $application->applicant_email,
            $application->applicant_name,
            'Update on your stall application',
            '<p>Hello ' . e($application->applicant_name) . ',</p>'
                . '<p>We are sorry, but your application' . ($stallNumber ? ' for <strong>Stall ' . e($stallNumber) . '</strong>' : '') . ' was not approved.</p>'
                . '<p><strong>Reason:</strong> ' . e($reason) . '</p>'
                . '<p>You are welcome to apply for another available stall.</p>',
            "Hello {$application->applicant_name}, your stall application was not approved. Reason: {$reason}"
        );

        return $application;
    }

    /**
     * Re-read the application under a row lock so two admins cannot review it at once.
     */
    protected function lockPending(StallApplication $application): StallApplication
    {
        $locked = StallApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== 'pending') {
            throw new DomainException('Application has already been reviewed.');
        }

        return $locked;
    }
}
