<?php

namespace Wuyj\Escpos\Parsers;

use Wuyj\Escpos\Compiler\TLine;

class LineParser
{
    protected $vars = [];

    protected $template = [];

    protected $parser = null;

    public function __construct(PrinterParser $parser)
    {
        $this->parser = $parser;
    }

    public function resolve(TLine $line)
    {
        $styles = array_filter($line->styles, function ($style) {
            return in_array($style, $this->parser->parsingMethods);
        });
        if ( ! in_array(PrinterParser::ACTION_LINE_CUT, $styles)) {
            array_unshift($styles, PrinterParser::ACTION_RESET);
            array_push($styles, PrinterParser::ACTION_LINE_BREAK);
            array_push($styles, PrinterParser::ACTION_TEXT);
        }
        array_reduce($styles, function ($carry, $style) use ($line) {
            $parserType = 'parse' . ucfirst($style);
            if (method_exists($this->parser, $parserType)) {
                $param = array_merge([$carry], $line->getOption($style));
                $carry = call_user_func_array([
                    $this->parser,
                    $parserType,
                ], $param);
            }

            return $carry;
        }, $line->text);
    }

    public function getAllAction()
    {
        if ($this->parser->hasActionCut === false) {
            $this->parser->pushAction('feed', [4]);
            $this->parser->pushAction('cut');
        }
        $actions = $this->parser->getAction();

        return $actions;
    }

}