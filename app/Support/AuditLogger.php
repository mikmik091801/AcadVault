<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Writes the security audit trail.
 *
 * Logging must never break the request that triggered it, so every write is
 * wrapped — a failed audit insert is reported to the application log instead
 * of bubbling up as a 500.
 */
class AuditLogger
{
    // Authentication
    public const LOGIN_SUCCESS = 'login.success';

    public const LOGIN_FAILED = 'login.failed';

    public const LOGOUT = 'logout';

    // Records
    public const RECORD_VIEWED = 'record.viewed';

    public const RECORD_DECRYPTED = 'record.decrypted';

    public const RECORD_CREATED = 'record.created';

    public const RECORD_UPDATED = 'record.updated';

    public const RECORD_DELETED = 'record.deleted';

    public const RECORD_EXPORTED = 'record.exported';

    // Verification
    public const EXPORT_VERIFIED = 'export.verified';

    // Enrollment
    public const ENROLLED = 'enrollment.created';

    public const DROP_REQUESTED = 'enrollment.drop_requested';

    public const DROP_APPROVED = 'enrollment.drop_approved';

    public const DROP_DECLINED = 'enrollment.drop_declined';

    public const CERTIFICATE_ISSUED = 'enrollment.certificate_issued';

    public const CERTIFICATE_VERIFIED = 'enrollment.certificate_verified';

    // Accounts
    public const USER_CREATED = 'user.created';

    public const USER_UPDATED = 'user.updated';

    public const USER_ROLE_CHANGED = 'user.role_changed';

    public const USER_DELETED = 'user.deleted';

    // Access control
    public const ACCESS_DENIED = 'access.denied';

    public const LOGIN_LOCKOUT = 'login.lockout';

    /**
     * Presentation metadata for every action, used by the audit log screen.
     *
     * @return array<string, array{label: string, icon: string, variant: string}>
     */
    public static function catalogue(): array
    {
        return [
            self::LOGIN_SUCCESS => ['label' => 'Login succeeded', 'icon' => 'bi-box-arrow-in-right', 'variant' => 'success'],
            self::LOGIN_FAILED => ['label' => 'Login failed', 'icon' => 'bi-exclamation-triangle', 'variant' => 'danger'],
            self::LOGIN_LOCKOUT => ['label' => 'Login lockout', 'icon' => 'bi-lock', 'variant' => 'danger'],
            self::LOGOUT => ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'variant' => 'neutral'],
            self::RECORD_VIEWED => ['label' => 'Record viewed', 'icon' => 'bi-eye', 'variant' => 'neutral'],
            self::RECORD_DECRYPTED => ['label' => 'Record decrypted', 'icon' => 'bi-unlock', 'variant' => 'warning'],
            self::RECORD_CREATED => ['label' => 'Record created', 'icon' => 'bi-plus-circle', 'variant' => 'success'],
            self::RECORD_UPDATED => ['label' => 'Record updated', 'icon' => 'bi-pencil', 'variant' => 'warning'],
            self::RECORD_DELETED => ['label' => 'Record deleted', 'icon' => 'bi-trash3', 'variant' => 'danger'],
            self::RECORD_EXPORTED => ['label' => 'Record exported', 'icon' => 'bi-file-earmark-pdf', 'variant' => 'success'],
            self::EXPORT_VERIFIED => ['label' => 'Export verified', 'icon' => 'bi-qr-code-scan', 'variant' => 'neutral'],
            self::ENROLLED => ['label' => 'Enrolled in class', 'icon' => 'bi-journal-plus', 'variant' => 'success'],
            self::DROP_REQUESTED => ['label' => 'Drop requested', 'icon' => 'bi-hourglass-split', 'variant' => 'warning'],
            self::DROP_APPROVED => ['label' => 'Drop approved', 'icon' => 'bi-journal-minus', 'variant' => 'warning'],
            self::DROP_DECLINED => ['label' => 'Drop declined', 'icon' => 'bi-x-circle', 'variant' => 'danger'],
            self::CERTIFICATE_ISSUED => ['label' => 'Certificate issued', 'icon' => 'bi-file-earmark-check', 'variant' => 'success'],
            self::CERTIFICATE_VERIFIED => ['label' => 'Certificate verified', 'icon' => 'bi-qr-code-scan', 'variant' => 'neutral'],
            self::USER_CREATED => ['label' => 'User created', 'icon' => 'bi-person-plus', 'variant' => 'success'],
            self::USER_UPDATED => ['label' => 'User updated', 'icon' => 'bi-person-gear', 'variant' => 'warning'],
            self::USER_ROLE_CHANGED => ['label' => 'Role changed', 'icon' => 'bi-shield-check', 'variant' => 'warning'],
            self::USER_DELETED => ['label' => 'User deleted', 'icon' => 'bi-person-x', 'variant' => 'danger'],
            self::ACCESS_DENIED => ['label' => 'Access denied', 'icon' => 'bi-shield-exclamation', 'variant' => 'danger'],
        ];
    }

    /**
     * Metadata for one action, falling back gracefully for unknown values.
     *
     * @return array{label: string, icon: string, variant: string}
     */
    public static function describe(string $action): array
    {
        return self::catalogue()[$action] ?? [
            'label' => Str::headline(str_replace('.', ' ', $action)),
            'icon' => 'bi-dot',
            'variant' => 'neutral',
        ];
    }

    /**
     * Actions that represent a security concern worth surfacing.
     *
     * @return array<int, string>
     */
    public static function securityActions(): array
    {
        return [self::LOGIN_FAILED, self::LOGIN_LOCKOUT, self::ACCESS_DENIED];
    }

    /**
     * Record an auditable action.
     *
     * @param  Model|string|null  $target  A model instance, or a plain string
     *                                     such as a URL for non-model events.
     */
    public static function log(
        string $action,
        Model|string|null $target = null,
        ?User $user = null,
        ?string $ipAddress = null,
    ): ?AuditLog {
        try {
            $targetType = null;
            $targetId = null;

            if ($target instanceof Model) {
                $targetType = $target::class;
                $targetId = $target->getKey();
            } elseif (is_string($target)) {
                $targetType = mb_substr($target, 0, 255);
            }

            return AuditLog::create([
                'user_id' => $user?->getKey() ?? Auth::id(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'ip_address' => $ipAddress ?? Request::ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write audit log entry', [
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Log a blocked (403) attempt.
     */
    public static function denied(string $path, ?User $user = null): ?AuditLog
    {
        return self::log(self::ACCESS_DENIED, $path, $user);
    }
}
