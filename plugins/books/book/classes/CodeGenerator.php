<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    /**
     * @param string $table
     * @param int $codeLength
     *
     * @return string
     */
    public static function generateUniqueCode(string $table, int $codeLength = 6): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $charactersNumber = strlen($characters);

        $code = '';

        while (strlen($code) < $codeLength) {
            $position = rand(0, $charactersNumber - 1);
            $character = $characters[$position];
            $code = $code.$character;
        }

        if (DB::table($table)->where('code', $code)->exists()) {
            self::generateUniqueCode($table, $codeLength);
        }

        return $code;
    }
}
