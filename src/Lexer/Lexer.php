<?php

/**
 * Rix Lang
 * 
 * @author Tianle Xu <xtl@xtlsoft.top>
 */

namespace Fly50w\Lexer;

use Fly50w\Lexer\Token;
use Fly50w\Utils\IgnoreUtil;

class Lexer
{
    protected ?IgnoreUtil $ignoreUtil = null;

    public function __construct()
    {
        $this->ignoreUtil = new IgnoreUtil();
    }

    public function tokenize(string $code): array
    {
        $last_in_quote = false;
        $tokens = [''];
        $curr = 0;
        $this->ignoreUtil->initialize();
        $this->ignoreUtil->ignoreBrackets(false);
        $code_c_str = str_split($code);
        $pos = 0;
        while ($pos < strlen($code)) {
            $curr_pos = $pos;
            $pos++;
            $curr_chr = $code_c_str[$curr_pos];
            $this->ignoreUtil->feed($curr_chr);
            if ($this->ignoreUtil->isInComment()) {
                continue;
            }
            if ($this->ignoreUtil->isInQuote()) {
                if (!$last_in_quote) {
                    $last_in_quote = true;
                    if ($tokens[$curr] != '') {
                        $curr++;
                    }
                    $tokens[$curr] = $curr_chr;
                } else {
                    $tokens[$curr] .= $curr_chr;
                }
                continue;
            } else {
                if ($last_in_quote) {
                    $last_in_quote = false;
                    // TODO: escape string: $tokens[$curr]
                    $tokens[$curr] .= $curr_chr;
                    $this->escapeString(str: $tokens[$curr], mutate: true);
                    $tokens[++$curr] = '';
                    continue;
                }
            }
            $shouldCut = $this->shouldCut(
                current: $tokens[$curr],
                next_char: $curr_chr,
                code_c_str: $code_c_str,
                pos: $pos,
                last_token: ($curr > 0) ? $tokens[$curr - 1] : null
            );
            if (!$shouldCut) {
                $tokens[$curr] .= $curr_chr;
            } else {
                if ($this->isEmptyCharacter($curr_chr) && $tokens[$curr] === '') continue;
                $tokens[++$curr] = $curr_chr;
            }
        }
        if ($tokens[$curr] === '') {
            unset($tokens[$curr]);
        }
        return $tokens;
    }

    public function standardize(array $tokens): array
    {
        return array_map([$this, 'standardizeSingle'], $tokens);
    }

    public function standardizeSingle(string $token): Token
    {
        if ($this->isKeyword($token)) {
            return new Token(
                type: Token::T_KEYWORD,
                value: $token
            );
        }
        if (
            substr($token, 0, 1) === '@'
            && $this->isIdentify(substr($token, 1))
        ) {
            return new Token(
                type: Token::T_ANNOTATION,
                value: substr($token, 1)
            );
        }
        if (
            substr($token, 0, 1) === '"'
            || substr($token, 0, 1) === "'"
        ) {
            return new Token(
                type: Token::T_SCALAR,
                value: $token,
                scalar: new Scalar(
                    type: Scalar::T_STR,
                    value: substr($token, 1)
                )
            );
        }
        if (is_numeric($token)) {
            return new Token(
                type: Token::T_SCALAR,
                value: $token,
                scalar: new Scalar(
                    type: Scalar::T_NUMERIC,
                    value: $token
                )
            );
        }
        if (in_array(
            needle: $token,
            haystack: ['true', 'false']
        )) {
            return new Token(
                type: Token::T_SCALAR,
                value: $token,
                scalar: new Scalar(
                    type: Scalar::T_BOOL,
                    value: $token === 'true'
                )
            );
        }
        if ($token === 'null') {
            return new Token(
                type: Token::T_SCALAR,
                value: $token,
                scalar: new Scalar(
                    type: Scalar::T_NULL,
                    value: null
                )
            );
        }
        if ($this->isIdentify($token)) {
            return new Token(
                type: Token::T_IDENTIFY,
                value: $token
            );
        }
        return new Token(
            type: Token::T_SYMBOL,
            value: $token
        );
    }

    public static function shouldCut(
        string $current,
        string &$next_char,
        ?array $code_c_str = null,
        int $pos = 0,
        ?string $last_token = null
    ): bool {
        // Note that this is order-sensitive
        static $chars_delimiters = [
            '::', '>=', '<=', '!=', '==', '->', '<-', '~>', '=>',
            '++', '--', '**', '..', '+=', '-=', '*=', '/=', '%=',
            ':=', '$$', '||', '&&'
        ];
        static $char_delimiters = '~(){}[]=,;.<>+-/|*^%!:?$';

        $is_empty_char = self::isEmptyCharacter(
            char: $next_char
        );

        if (
            $current !== ''
            && $next_char === '@'
        ) {
            return true;
        }

        if ($is_empty_char) {
            $next_char = '';
            return true;
        }

        if ($code_c_str !== null) {
            if ($next_char === '.') {
                if (
                    array_key_exists($pos - 2, $code_c_str) &&
                    is_numeric($code_c_str[$pos - 2])
                ) {
                    return false;
                }
            }
            if (is_numeric($next_char)) {
                if (
                    array_key_exists($pos - 2, $code_c_str) &&
                    $code_c_str[$pos - 2] === '.'
                ) {
                    return false;
                }
            }
        }

        if (
            $current === '-' &&
            is_numeric($next_char) &&
            (
                ($last_token !== null &&
                    strpos(
                        haystack: $char_delimiters,
                        needle: $last_token
                    ) !== false) ||
                ($last_token === null))
        ) {
            return false;
        }

        if (in_array($current . $next_char, $chars_delimiters)) {
            return false;
        }

        if (in_array($current, str_split($char_delimiters))) {
            return true;
        }

        if (in_array($current, $chars_delimiters)) {
            return true;
        }

        if (
            strlen($current) != 0
            && strpos(
                haystack: $char_delimiters,
                needle: $next_char
            ) !== false
        ) {
            return true;
        }

        return false;
    }

    public static function isEmptyCharacter(string $char): bool
    {
        static $empty_chars = " \t\n\r";
        if (
            strpos(
                haystack: $empty_chars,
                needle: $char
            ) !== false
        ) {
            return true;
        }
        if ($char === '') return true;
        return false;
    }

    public static function isKeyword(string $current): bool
    {
        // TODO: add types
        static $keywords = [
            'fn',
            // 'type',
            // 'struct',
            // 'namespace',
            'import',
            // 'as',
            'return',
            'for',
            'break',
            // 'while',
            // 'if',
            // 'match',
            'let',
            'try',
            'except',
            // 'stream',
            'throw',
            // 'static',
            // 'use',
            // 'null' // we consider null as a scalar
        ];
        return in_array($current, $keywords);
    }

    public static function escapeString(string &$str, bool $mutate = false): string
    {
        $rslt = substr($str, 0, 1);
        if (substr($str, 0, 1) == '"') {
            $rslt .= self::escapeHandler($str);
        } else if (substr($str, 0, 1) == "'") {
            $rslt .= substr($str, 1, -1);
        }
        if ($mutate) {
            $str = $rslt;
        }
        return $rslt;
    }

    protected static function escapeHandler(string $str): string
    {
        // TODO: escape unicode characters
        static $rules = [
            '\r' => "\r",
            '\n' => "\n",
            '\t' => "\t",
            '\\\\' => '\\',
            '\\"' => '"',
            '\f' => "\f"
        ];
        return substr(
            str_replace(
                search: array_keys($rules),
                replace: array_values($rules),
                subject: $str
            ),
            1,
            -1
        );
    }

    protected static function isIdentify(string $str): bool
    {
        return (bool) preg_match(
            '/^([A-Za-z_])([A-Za-z0-9_])*$/',
            $str
        );
    }
}
