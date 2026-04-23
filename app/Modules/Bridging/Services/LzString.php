<?php

namespace App\Modules\Bridging\Services;

/**
 * Pure PHP implementation of LZ-string compression/decompression.
 * Port dari JavaScript library lz-string (https://pieroxy.net/blog/pages/lz-string/index.html)
 * Compatible dengan BPJS web service response decompression.
 *
 * Referensi PHP: https://github.com/nullpunkt/lz-string-php
 */
class LzString
{
    /**
     * @var string
     */
    private static $keyStrUriSafe = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-$';

    /**
     * Decompress an LZ-string from an encoded URI component.
     *
     * @param string|null $input
     * @return string|null
     */
    public static function decompressFromEncodedURIComponent(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return '';
        }

        $input = str_replace(' ', '+', $input);

        return self::_decompress(strlen($input), 32, function ($index) use ($input) {
            return self::getBaseValue(self::$keyStrUriSafe, $input[$index]);
        });
    }

    /**
     * Decompress from a base64 encoded string.
     *
     * @param string|null $input
     * @return string|null
     */
    public static function decompressFromBase64(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return '';
        }

        $keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

        return self::_decompress(strlen($input), 32, function ($index) use ($input, $keyStr) {
            return self::getBaseValue($keyStr, $input[$index]);
        });
    }

    /**
     * Get the base value of a character in the given alphabet.
     *
     * @param string $alphabet
     * @param string $character
     * @return int
     */
    private static function getBaseValue(string $alphabet, string $character): int
    {
        static $baseReverseDic = [];

        if (!isset($baseReverseDic[$alphabet])) {
            $baseReverseDic[$alphabet] = [];
            for ($i = 0; $i < strlen($alphabet); $i++) {
                $baseReverseDic[$alphabet][$alphabet[$i]] = $i;
            }
        }

        return $baseReverseDic[$alphabet][$character] ?? 0;
    }

    /**
     * Core decompression algorithm
     *
     * @param int $length
     * @param int $resetValue
     * @param callable $getNextValue
     * @return string|null
     */
    private static function _decompress(int $length, int $resetValue, callable $getNextValue): ?string
    {
        $dictionary = [];
        $enlargeIn = 4;
        $dictSize = 4;
        $numBits = 3;
        $result = [];
        $entry = '';
        $w = '';
        $c = '';

        $data = [
            'val'      => $getNextValue(0),
            'position' => $resetValue,
            'index'    => 1,
        ];

        $bits = 0;
        $maxpower = pow(2, 2);
        $power = 1;

        while ($power != $maxpower) {
            $resb = $data['val'] & $data['position'];
            $data['position'] >>= 1;

            if ($data['position'] == 0) {
                $data['position'] = $resetValue;
                if ($data['index'] < $length) {
                    $data['val'] = $getNextValue($data['index']);
                    $data['index']++;
                }
            }

            $bits |= ($resb > 0 ? 1 : 0) * $power;
            $power <<= 1;
        }

        switch ($bits) {
            case 0:
                $bits = 0;
                $maxpower = pow(2, 8);
                $power = 1;

                while ($power != $maxpower) {
                    $resb = $data['val'] & $data['position'];
                    $data['position'] >>= 1;

                    if ($data['position'] == 0) {
                        $data['position'] = $resetValue;
                        if ($data['index'] < $length) {
                            $data['val'] = $getNextValue($data['index']);
                            $data['index']++;
                        }
                    }

                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }

                $c = self::fromCharCode($bits);
                break;

            case 1:
                $bits = 0;
                $maxpower = pow(2, 16);
                $power = 1;

                while ($power != $maxpower) {
                    $resb = $data['val'] & $data['position'];
                    $data['position'] >>= 1;

                    if ($data['position'] == 0) {
                        $data['position'] = $resetValue;
                        if ($data['index'] < $length) {
                            $data['val'] = $getNextValue($data['index']);
                            $data['index']++;
                        }
                    }

                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }

                $c = self::fromCharCode($bits);
                break;

            case 2:
                return '';
        }

        $dictionary[3] = $c;
        $w = $c;
        $result[] = $c;

        while (true) {
            if ($data['index'] > $length) {
                return '';
            }

            $bits = 0;
            $maxpower = pow(2, $numBits);
            $power = 1;

            while ($power != $maxpower) {
                $resb = $data['val'] & $data['position'];
                $data['position'] >>= 1;

                if ($data['position'] == 0) {
                    $data['position'] = $resetValue;
                    if ($data['index'] < $length) {
                        $data['val'] = $getNextValue($data['index']);
                        $data['index']++;
                    }
                }

                $bits |= ($resb > 0 ? 1 : 0) * $power;
                $power <<= 1;
            }

            $c_val = $bits;
            switch ($c_val) {
                case 0:
                    $bits = 0;
                    $maxpower = pow(2, 8);
                    $power = 1;

                    while ($power != $maxpower) {
                        $resb = $data['val'] & $data['position'];
                        $data['position'] >>= 1;

                        if ($data['position'] == 0) {
                            $data['position'] = $resetValue;
                            if ($data['index'] < $length) {
                                $data['val'] = $getNextValue($data['index']);
                                $data['index']++;
                            }
                        }

                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }

                    $dictionary[$dictSize++] = self::fromCharCode($bits);
                    $c_val = $dictSize - 1;
                    $enlargeIn--;
                    break;

                case 1:
                    $bits = 0;
                    $maxpower = pow(2, 16);
                    $power = 1;

                    while ($power != $maxpower) {
                        $resb = $data['val'] & $data['position'];
                        $data['position'] >>= 1;

                        if ($data['position'] == 0) {
                            $data['position'] = $resetValue;
                            if ($data['index'] < $length) {
                                $data['val'] = $getNextValue($data['index']);
                                $data['index']++;
                            }
                        }

                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }

                    $dictionary[$dictSize++] = self::fromCharCode($bits);
                    $c_val = $dictSize - 1;
                    $enlargeIn--;
                    break;

                case 2:
                    return implode('', $result);
            }

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }

            if (isset($dictionary[$c_val])) {
                $entry = $dictionary[$c_val];
            } else {
                if ($c_val === $dictSize) {
                    $entry = $w . self::charAt($w, 0);
                } else {
                    return null;
                }
            }

            $result[] = $entry;

            // Add w + entry[0] to the dictionary
            $dictionary[$dictSize++] = $w . self::charAt($entry, 0);
            $enlargeIn--;

            $w = $entry;

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }
        }
    }

    /**
     * Convert a Unicode code point to a character (UTF-8 safe).
     *
     * @param int $code
     * @return string
     */
    private static function fromCharCode(int $code): string
    {
        if ($code <= 0x7F) {
            return chr($code);
        }
        return mb_chr($code, 'UTF-8') ?: chr($code);
    }

    /**
     * Get character at a specific position (multibyte safe).
     *
     * @param string $str
     * @param int $pos
     * @return string
     */
    private static function charAt(string $str, int $pos): string
    {
        return mb_substr($str, $pos, 1, 'UTF-8');
    }
}
