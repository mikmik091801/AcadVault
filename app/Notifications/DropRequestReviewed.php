<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DropRequestReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public Enrollment $enrollment,
        public bool $approved,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->enrollment->course?->code ?? 'the course';

        $mail = (new MailMessage)
            ->subject($this->approved ? "Drop request approved — {$course}" : "Drop request declined — {$course}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->approved
                ? "Your request to drop **{$course}** has been approved. You are no longer enrolled in this class."
                : "Your request to drop **{$course}** was declined. You remain enrolled in this class.");

        if (filled($this->enrollment->review_note)) {
            $mail->line("Registrar note: {$this->enrollment->review_note}");
        }

        return $mail->action('View your enrollments', route('enrollments.index'));
    }
}
