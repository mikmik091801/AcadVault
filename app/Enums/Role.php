<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Registrar = 'registrar';
    case Faculty = 'faculty';
    case Student = 'student';

    /**
     * Human label for badges, tables and the top bar.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Registrar => 'Registrar',
            self::Faculty => 'Faculty',
            self::Student => 'Student',
        };
    }

    /**
     * Bootstrap Icons name used beside the role.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'bi-shield-lock',
            self::Registrar => 'bi-folder-check',
            self::Faculty => 'bi-person-workspace',
            self::Student => 'bi-mortarboard',
        };
    }

    /**
     * All role values — handy for validation rules and seeders.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
