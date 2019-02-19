<?php

namespace Wuyj\Escpos\Compiler;

class TLine
{
    /**
     * @var array
     */
    public $styles = [];

    /**
     * @var array
     */
    public $option = [];

    /**
     * @var array
     */
    public $text = [];

    /**
     * TLine constructor.
     *
     * @param $templateLine
     */
    public function __construct($templateLine)
    {
        $styles = $templateLine['styles'] ?? [];
        $inheritedStyles = $templateLine['inheritedStyles'] ?? [];
        $this->text = $templateLine['text'] ?? [];
        $this->initStyles($styles, $inheritedStyles);
    }

    /**
     *
     * @param array $data
     * @param array $text
     *
     * @return array
     */
    public function parsingText(array $data, array $text = [])
    {
        $preg = '/\${([^}]+)}/';
        foreach ($text as $key => $item) {
            if (is_array($item)) {
                $text[$key] = $this->parsingText($data, $item);
            } else {
                $text[$key] = preg_replace_callback($preg, function ($matches) use ($data) {
                    return $data[$matches[1]] ?? '';
                }, $item);
            }
        }

        return $text;
    }

    /**
     * @param array $text
     */
    public function setText(array $text)
    {
        $this->text = $text;
    }

    /**
     * @param $styles
     * @param $inheritedStyles
     */
    public function initStyles($styles, $inheritedStyles)
    {
        foreach ($this->resolveType($styles) as $style => $option) {
            $this->setOption($option, $style);
        }
        foreach ($this->resolveType($inheritedStyles) as $style => $option) {
            if (in_array($style, $this->styles)) {
                continue;
            }
            $this->setOption($option, $style);
        }
    }

    /**
     * @param $styles
     *
     * @return \Generator
     */
    private function resolveType($styles)
    {
        foreach ($styles as $style) {
            $exploded = explode(':', $style);
            $style = @$exploded[0];
            $option = @$exploded[1];
            yield $style => $option;
        }
    }

    /**
     * @param $option
     * @param $style
     */
    private function setOption($option, $style)
    {
        $this->styles[] = $style;
        if ( ! empty($option)) {
            $option = str_replace(' ', '', $option);
            $option = explode(',', $option);
            $this->option[$style] = $option;
        } else {
            $this->option[$style] = [];
        }
    }

    /**
     * @param $style
     *
     * @return array|mixed
     */
    public function getOption($style)
    {
        return $this->option[$style] ?? [];
    }
}