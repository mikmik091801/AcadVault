<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    /** Student is enrolled and the class counts towards their certificate. */
    case Enrolled = 'enrolled';

    /** Student asked to drop; still enrolled until a registrar approves. */
    case DropPending = 'drop_pending';

    /** Drop approved by a registrar. Kept as history, not deleted. */
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Enrolled',
            self::DropPending => 'Drop pending',
            self::Dropped => 'Dropped',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Enrolled => 'bi-check-circle',
            self::DropPending => 'bi-hourglass-split',
            self::Dropped => 'bi-x-circle',
        };
    }

    /**
     * Maps onto the badge classes already used elsewhere in the app.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Enrolled => 'badge-authentic',
            self::DropPending => 'badge-warning-soft',
            self::Dropped => 'badge-tampered',
        };
    }

    /**
     * Does this status put the class on the student's certificate?
     *
     * A pending drop still counts — the class is only off the record once a
     * registrar has approved it.
     */
    public function isActive(): bool
    {
        return $this !== self::Dropped;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
