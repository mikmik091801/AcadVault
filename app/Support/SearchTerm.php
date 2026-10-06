<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Case-insensitive search helpers.
 *
 * PostgreSQL (Render) treats LIKE as case-sensitive, so every search column
 * goes through LOWER(...) to match MySQL's behaviour and the user's
 * expectation that "anna" finds "Anna".
 */
final class SearchTerm
{
    /** First condition: LOWER(column) LIKE term. */
    public static function where(Builder $query, string $column, string $term): Builder
    {
        return $query->whereRaw("LOWER({$column}) LIKE ?", ['%'.mb_strtolower($term).'%']);
    }

    /** Additional condition: ... OR LOWER(column) LIKE term. */
    public static function orWhere(Builder $query, string $column, string $term): Builder
    {
        return $query->orWhereRaw("LOWER({$column}) LIKE ?", ['%'.mb_strtolower($term).'%']);
    }

    /**
     * Every whitespace-separated word of the term must appear in at least one
     * of the given columns — so "anna" matches all Annas and "anna bautista"
     * also matches "Anna L. Bautista".
     *
     * @param  array<int, string>  $columns
     */
    public static function whereAllWords(Builder $query, array $columns, string $term): Builder
    {
        foreach (preg_split('/\s+/u', trim($term)) ?: [] as $word) {
            if ($word === '') {
                continue;
            }

            $query->where(function (Builder $q) use ($columns, $word) {
                foreach ($columns as $i => $column) {
                    $i === 0
                        ? self::where($q, $column, $word)
                        : self::orWhere($q, $column, $word);
                }
            });
        }

        return $query;
    }
}
