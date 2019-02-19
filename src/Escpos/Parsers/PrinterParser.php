<?php

namespace Wuyj\Escpos\Parsers;

use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;
use Wuyj\Escpos\Actions\AlignSideAction;

class PrinterParser
{
    const ACTION_RESET = 'reset';

    const ACTION_TEXT = 'actionText';

    const ACTION_LINE_BREAK = 'lineBreak';

    const ACTION_LINE_CUT = 'actionCut';

    const FONTS_RADIO = [
        Printer::MODE_FONT_A => 48,
        Printer::MODE_FONT_B => 64,
    ];

    public $parsingMethods = [
        'fontSize',
        'fontBold',
        'alignSide',
        'alignCenter',
        'alignLeft',
        'alignRight',
        'strRepeat',
        'lineBreak',
        'actionCut',
        'actionFeed',
        'underLine',
        'actionText',
        'qrCode',
    ];

    public $fontWidth = 1;

    public $fontHeight = 1;

    public $fontSize = [1, 1];

    public $fontStyle = Printer::FONT_A;

    public $hasActionCut = false;

    private $printerActions = [];

    public function pushAction($action, array $param = [])
    {
        if ($action === 'cut') {
            $this->hasActionCut = true;
        }
        $action = new ActionParser($action, $param);
        $this->printerActions[] = $action;

        return $action;
    }

    public function clearAction()
    {
        $this->printerActions = [];
    }

    public function getAction()
    {
        return $this->printerActions;
    }

    public function hasAction($action)
    {

    }

    public function parseActionText(array $text)
    {
        $outPut = implode('', $text);
        $this->pushAction('selectPrintMode', [$this->fontStyle]);
        $this->pushAction('setTextSize', $this->fontSize);
        $this->pushAction('textChinese', [$outPut]);

        return $text;
    }

    public function parseActionFeed(array $text = [], $lines = 1)
    {
        $this->pushAction('feed', [+ $lines]);

        return $text;
    }

    public function parseActionCut(array $text = [])
    {
        $this->pushAction('cut');

        return $text;
    }

    public function parseStrRepeat(array $text, $multiplier = 48)
    {
        return array_map(function ($item) use ($multiplier) {
            return str_repeat($item, $multiplier);
        }, $text);
    }

    public function parseLineBreak(array $text)
    {
        return array_map(function ($text) {
            return "$text\n";
        }, $text);
    }

    public function parseFontSize(array $text, $width = 3, $height = 0)
    {
        if (empty($height)) {
            $height = $width;
        }
        $this->fontWidth = + $width;
        $this->fontHeight = + $height;
        $fontSize = [$this->fontWidth, $this->fontHeight];
        $fontStyle = Printer::MODE_FONT_A;
        if ($this->fontWidth === 1.5) {
            $fontStyle = Printer::MODE_FONT_B;
            $fontSize = [2, 2];
        }
        $this->fontStyle = $fontStyle;
        $this->fontSize = $fontSize;

        return $text;
    }

    /**
     * @param array $text
     * @param array ...$cols
     *
     * @return array
     */
    public function parseAlignSide(array $text, ...$cols)
    {
        $action = new AlignSideAction($text, $cols);
        $action->setFontWidth($this->fontWidth);

        return $action->parse();
    }

    public function parseFontBold(array $text)
    {
        $this->pushAction('setEmphasis', [true]);

        return $text;
    }

    public function parseUnderLine(array $text)
    {
        $this->pushAction('selectPrintMode', [Printer::MODE_UNDERLINE]);

        return $text;
    }

    public function parseAlignLeft(array $text)
    {
        $this->pushAction('setJustification', [Printer::JUSTIFY_LEFT]);

        return $text;
    }

    public function parseAlignCenter(array $text)
    {
        $this->pushAction('setJustification', [Printer::JUSTIFY_CENTER]);

        return $text;
    }

    public function parseAlignRight(array $text)
    {
        $this->pushAction('setJustification', [Printer::JUSTIFY_RIGHT]);

        return $text;
    }

    public function parseReset(array $text = [])
    {
        $this->pushAction('setEmphasis', [false]);
        $this->pushAction('setJustification', [Printer::JUSTIFY_LEFT]);
        $this->fontWidth = 1;
        $this->fontHeight = 1;
        $this->fontSize = [$this->fontWidth, $this->fontHeight];
        $this->fontStyle = Printer::FONT_A;

        return $text;
    }

    /**
     * @param array $text
     * @param       $filename
     *
     * @return array
     * @throws \Exception
     */
    public function parseQrCode(array $text, $filename)
    {
        if (file_exists($filename)) {
            $this->pushAction('bitImage', [EscposImage::load($filename)]);
        }

        return $text;
    }
}