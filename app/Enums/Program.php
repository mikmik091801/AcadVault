<?php

namespace App\Enums;

/**
 * Undergraduate degree programs of the University of Mindanao's College of
 * Computing Education — the only college AcadVault serves.
 *
 * Values are the official program names as published by the university, so the
 * string stored on a student profile reads correctly on transcripts and
 * certificates without any further mapping.
 *
 * @see https://umindanao.edu.ph/colleges/main
 */
enum Program: string
{
    public const COLLEGE = 'College of Computing Education';

    public const UNIVERSITY = 'University of Mindanao';

    case ComputerScience = 'Bachelor of Science in Computer Science';
    case GameDevelopment = 'Bachelor of Science in Entertainment and Multimedia Computing Major in Game Development';
    case InformationTechnology = 'Bachelor of Science in Information Technology';
    case LibraryAndInformationScience = 'Bachelor of Library and Information Science';
    case MultimediaArts = 'Bachelor of Multimedia Arts';

    /**
     * The abbreviation used around the college, for tables and filters where
     * the full name would wrap.
     */
    public function shortName(): string
    {
        return match ($this) {
            self::ComputerScience => 'BSCS',
            self::GameDevelopment => 'BSEMC-GD',
            self::InformationTechnology => 'BSIT',
            self::LibraryAndInformationScience => 'BLIS',
            self::MultimediaArts => 'BMMA',
        };
    }

    /**
     * Short name for a stored program string, or the string itself when it is
     * not a current program (a profile saved before the college focus).
     */
    public static function shortNameFor(?string $value): ?string
    {
        return self::tryFrom((string) $value)?->shortName() ?? $value;
    }

    /**
     * The college that offers this program, used to group the picker.
     */
    public function college(): string
    {
        return self::COLLEGE;
    }

    /**
     * Every program grouped by the college that offers it, for the picker.
     *
     * @return array<string, array<int, self>>
     */
    public static function groupedByCollege(): array
    {
        $grouped = [];

        foreach (self::cases() as $program) {
            $grouped[$program->college()][] = $program;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
