<?php

namespace Phpactor\CodeTransform\Domain\Utils;

class TextUtils
{
    public static function removeIndentation(string $string)
    {
        $indentation = null;
        $lines = explode(PHP_EOL, $string);

        if (empty($lines)) {
            return $string;
        }

        foreach ($lines as $line) {
            preg_match('{^(\s+).*$}', $line, $matches);

            if (false === isset($matches[1])) {
                $indentation = 0;
                break;
            }

            $count = mb_strlen($matches[1]);
            if (null === $indentation || $count < $indentation) {
                $indentation = $count;
            }
        }

        foreach ($lines as &$line) {
            $line = substr($line, $indentation);
        }

        return trim(implode(PHP_EOL, $lines), PHP_EOL);
    }
}
