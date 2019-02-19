<?php

namespace Wuyj\Escpos\Actions;

class AlignSideAction
{
    protected $text = [];

    protected $cols = [];

    protected $totalCol = 0;

    protected $fontWidth = 12;

    protected $stack = [];

    public function __construct(array $text, array $cols)
    {
        $this->text = $text;
        $this->cols = $cols;
        foreach ($this->cols as $col) {
            $this->totalCol += abs($col);
        }
    }

    public function setFontWidth($fontWidth)
    {
        $this->fontWidth = $fontWidth;
        if ($fontWidth === 1.5) {
            $this->fontWidth = 18;
        } else {
            $this->fontWidth = $fontWidth * 12;
        }
    }

    public function parse()
    {
        $this->alignText($this->text);

        return [implode('', $this->stack)];
    }

    public function alignText(array $text)
    {
        $loop = false;
        foreach ($text as $item) {
            if (is_array($item)) {
                $loop = true;
                $this->alignText($item);
            }
        }
        if ($loop !== true) {
            $this->stack[] = $this->resolveText($text);
        }
    }

    private function resolveText(array $text)
    {
        $row = [];
        foreach ($text as $colIndex => $item) {
            $colSize = isset($this->cols[$colIndex]) ? $this->cols[$colIndex] : 1;
            foreach ($this->handleStr($item, $colSize) as $rowIndex => $str) {
                $row[$rowIndex][$colIndex] = $str;
            }
        }
        $result = [];
        foreach ($row as $rowIndex => $item) {
            foreach ($this->cols as $colIndex => $colSize) {
                if ( ! isset($row[$rowIndex][$colIndex])) {
                    $multiplier = $this->getBlankGridNum('', $colSize);
                    $row[$rowIndex][$colIndex] = $this->paddingStr('', $multiplier);
                }
            }
            ksort($row[$rowIndex]);
            $result[] = implode('', $row[$rowIndex]);
        }

        return implode('', $result);
    }

    private function handleStr($str, $col)
    {
        $rowIndex = 0;
        $col = + $col;
        if ($col < 0) {
            $col = - $col;
            $pad_type = STR_PAD_LEFT;
        } else {
            $pad_type = STR_PAD_RIGHT;
        }
        $multiplier = $this->getBlankGridNum($str, $col);
        if ($multiplier >= 0) {
            yield $rowIndex => $this->paddingStr($str, $multiplier, $pad_type);
        } else {
            $words = $this->splitStrIntoWords($str, $col);
            $lines = $this->groupWordByLine($words, $col);
            foreach ($lines as $rowIndex => $row) {
                $multiplier = $this->getBlankGridNum($row, $col);
                yield $rowIndex => $this->paddingStr($row, $multiplier, $pad_type);
            }
        }
    }

    private function paddingStr($str, $multiplier, $pad_type = STR_PAD_RIGHT)
    {
        if ($multiplier < 0) {
            return $str;
        }
        $repeat_str = str_repeat(' ', $multiplier);
        if ($pad_type === STR_PAD_RIGHT) {
            $str = $str . $repeat_str;
        } else {
            $str = $repeat_str . $str;
        }

        return $str;
    }

    private function getBlankGridNum($str, $col)
    {
        $font_width = $this->fontWidth;
        $str_len = strlen($str);
        $mb_len = mb_strlen($str);
        $mb_total = ($str_len - $mb_len) / 2;
        $str_total = $mb_len - $mb_total;
        if ($font_width === 18) {
            $str_width = $mb_total * 48 + $str_total * 18;
        } else {
            $str_width = $str_total * $font_width + 2 * $mb_total * $font_width;
        }
        $total_width = 576 * $col / $this->totalCol;
        $blank_gridNum = ($total_width - $str_width) / $font_width;

        return floor($blank_gridNum);
    }

    private function groupWordByLine($words, $col)
    {
        $group = [];
        $rowIndex = 0;
        $group[$rowIndex] = '';
        foreach ($words as $word) {
            $text = $group[$rowIndex];
            $multiplier = $this->getBlankGridNum($text . $word, $col);
            if ($multiplier <= 0) {
                $rowIndex ++;
                $group[$rowIndex] = '';
            }
            $group[$rowIndex] .= $word;
        }

        return $group;
    }

    private function splitStrIntoWords($str, $col)
    {
        $words = [];
        $current = '';
        $len = 576 * $col / $this->totalCol / $this->fontWidth;
        $len = intval($len);
        $str = preg_split('/(?<!^)(?!$)/u', $str);
        foreach ($str as $char) {
            if (strlen($char) > 1) {
                if (!empty($current)) {
                    $words[] = $current;
                    $current = '';
                }
                $words[] = $char;
                continue;
            }
            $current .= $char;
            if ($char === ' ') {
                $words[] = $current;
                $current = '';
            } elseif (strlen($current) >= $len) {
                $words = array_merge($words, str_split($current, $len));
                $current = '';
                continue;
            }
        }
        if ( ! empty($current)) {
            $words[] = $current;
        }

        return $words;
    }
}