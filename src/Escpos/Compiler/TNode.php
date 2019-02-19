<?php

namespace Wuyj\Escpos\Compiler;

class TNode
{
    /**
     * @var array
     */
    public $styles = [];

    /**
     * @var array
     */
    public $inheritedStyles = [];

    /**
     * @var array
     */
    public $text = [];

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var bool
     */
    protected $hasSubNode = false;

    /**
     * TNode constructor.
     *
     * @param array $line
     */
    public function __construct(array $line)
    {
        $this->setStyles($line['styles'] ?? '');
        $this->setText($line['text'] ?? []);
    }

    /**
     * @param $styles
     *
     * @return array
     */
    private function transformStylesToArray($styles)
    {
        $styles = explode(';', $styles);
        $styles = array_filter($styles, function ($styles) {
            return ! empty($styles);
        });

        return $styles;
    }

    /**
     * @param string $styles
     */
    private function setStyles(string $styles)
    {
        $this->styles = $this->transformStylesToArray($styles);
    }

    /**
     * @param array $text
     */
    private function setText(array $text)
    {
        if (count($text) > 0 && isset($text[0]['styles'])) {
            $this->hasSubNode = true;
            $this->text = array_map(function ($line) {
                return new TNode($line);
            }, $text);
        } else {
            $this->hasSubNode = false;
            $this->text = $text;
        }
    }

    /**
     * @return TNode[]
     */
    public function getNodes()
    {
        if ($this->hasSubNode !== true) {
            return [$this];
        }

        return $this->text;
    }

    /**
     * @param $styles
     */
    public function inheritStyles($styles)
    {
        $this->inheritedStyles = array_merge($this->inheritedStyles, $styles);
    }

    /**
     * @param $data
     */
    public function fillData($data)
    {
        $this->data = $data;
    }

    /**
     * @return TLine
     */
    public function render()
    {
        $tLine = new TLine([
            'styles'          => $this->styles,
            'text'            => $this->text,
            'inheritedStyles' => $this->inheritedStyles,
        ]);
        $text = $tLine->parsingText($this->data, $tLine->text);
        $tLine->setText($text);

        return $tLine;
    }

    /**
     * @return bool|string
     */
    public function hasLoopDirective()
    {
        return $this->hasDirective('each');
    }

    /**
     * @return bool|string
     */
    public function hasRepeatDirective()
    {
        return $this->hasDirective('repeat');
    }

    /**
     * @return bool|string
     */
    public function hasHiddenDirective()
    {
        return $this->hasDirective('hidden');
    }

    /**
     * remove loop directive
     */
    public function removeLoopDirective()
    {
        $this->removeDirective('each');
    }

    /**
     * remove repeat directive
     */
    public function removeRepeatDirective()
    {
        $this->removeDirective('repeat');
    }

    /**
     * remove hidden directive
     */
    public function removeHiddenDirective()
    {
        $this->removeDirective('hidden');
    }

    /**
     * @param string $name
     *
     * @return bool|string
     */
    private function hasDirective(string $name)
    {
        $length = strlen($name);
        foreach ($this->styles as $styles) {
            if (substr($styles, 0, $length) === $name) {
                if (strlen($styles) <= $length) {
                    return '';
                }

                return substr($styles, $length + 1);
            }
        }

        return false;
    }

    /**
     * @param string $name
     */
    private function removeDirective(string $name)
    {
        $result = [];
        $length = strlen($name);
        foreach ($this->styles as $styles) {
            if (substr($styles, 0, $length) === $name) {
                continue;
            }
            $result[] = $styles;
        }
        $this->styles = $result;
    }
}