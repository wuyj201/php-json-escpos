<?php

namespace Wuyj\Escpos;

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Wuyj\Escpos\Compiler\TCompiler;
use Wuyj\Escpos\Detector\PrinterStatusDetector;
use Wuyj\Escpos\Exceptions\PrinterConnectFailedException;
use Wuyj\Escpos\Parsers\ActionParser;
use Wuyj\Escpos\Parsers\LineParser;
use Wuyj\Escpos\Parsers\PrinterParser;

class EscposPrinter
{
    /**
     * @var int
     */
    protected $port = 9100;

    /**
     * @var int
     */
    protected $timeout = 30;

    /**
     * @var PrinterParser
     */
    protected $parser;

    /**
     * @var ActionParser[]
     */
    protected $actions;

    /**
     * @var TCompiler
     */
    protected $compiler;

    /**
     * EscposPrinter constructor.
     */
    public function __construct()
    {
        $this->parser = new PrinterParser();
        $this->compiler = new TCompiler();
    }

    /**
     * @var Printer
     */
    protected $printer;

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout = 30)
    {
        $this->timeout = + $timeout ?: 30;
    }

    /**
     * @param int $port
     */
    public function setPort($port = 9100)
    {
        $this->port = $port;
    }

    /**
     * @param        $ip
     *
     * @return Printer|null
     */
    public function getPrinter($ip)
    {
        $connector = $this->getConnector($ip);
        if (is_null($connector)) {
            return null;
        }

        return new Printer($connector);
    }

    /**
     * @param $ip
     *
     * @return $this
     * @throws PrinterConnectFailedException
     */
    public function connect($ip)
    {
        $this->printer = $this->getPrinter($ip);
        if (is_null($this->printer)) {
            throw new PrinterConnectFailedException();
        }

        return $this;
    }

    /**
     * @param $ip
     *
     * @return NetworkPrintConnector|null
     */
    public function getConnector($ip)
    {
        if (empty($ip)) {
            return null;
        }
        try {
            $connector = new NetworkPrintConnector($ip, $this->port, $this->timeout);
        } catch (\Exception $e) {
            return null;
        }

        return $connector;
    }

    /**
     * @param $template
     * @param $data
     */
    public function render($template, $data)
    {
        $template = json_decode($template, true);
        $this->parser->clearAction();
        $lines = [];
        foreach ($template as $line) {
            $this->compiler->handle($line, $data);
            $lines = array_merge($lines, $this->compiler->result());
        }
        $lineParser = new LineParser($this->parser);
        foreach ($lines as $line) {
            $lineParser->resolve($line);
        }
        $this->actions = $lineParser->getAllAction();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function printing()
    {
        $printer = $this->printer;
        if (is_null($printer)) {
            return false;
        }
        if (empty($this->actions)) {
            return false;
        }
        foreach ($this->actions as $action) {
            $action->handle($this->printer);
        }
        $this->close();

        return true;
    }

    /**
     * @return bool|int|mixed
     */
    public function detect()
    {
        $printer = $this->printer;
        if (is_null($printer)) {
            return - 1;
        }
        $detector = new PrinterStatusDetector($printer->getPrintConnector());
        $detectedStatus = $detector->detect();
        if ($detectedStatus !== true) {
            $this->close();

            return $detectedStatus;
        }

        return 1;
    }

    /**
     * @return \Mike42\Escpos\PrintConnectors\PrintConnector|null
     */
    public function getPrintConnector()
    {
        return $this->printer ? $this->printer->getPrintConnector() : null;
    }

    /**
     * @param int $pin
     * @param int $on_ms
     * @param int $off_ms
     *
     * @return bool
     */
    public function pulse($pin = 0, $on_ms = 120, $off_ms = 240)
    {
        $printer = $this->printer;
        if (is_null($printer)) {
            return false;
        }
        $printer->pulse($pin, $on_ms, $off_ms);
        $printer->close();

        return true;
    }

    public function close()
    {
        if ( ! is_null($this->printer)) {
            $this->printer->close();
            $this->printer = null;
        }
    }

}
