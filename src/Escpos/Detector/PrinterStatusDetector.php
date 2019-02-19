<?php

namespace Wuyj\Escpos\Detector;

use Mike42\Escpos\PrintConnectors\PrintConnector;

class PrinterStatusDetector
{
    const STATUS_PAPER_ROLL = 0x20;

    const STATUS_BTN_PRESSED = 0x08;

    const STATUS_HARDWARE_ERROR = 0x64;

    const OFF_LINE_ERRORS = [
        self::STATUS_PAPER_ROLL,
        self::STATUS_BTN_PRESSED,
        self::STATUS_HARDWARE_ERROR,
    ];

    protected $connector;

    public function __construct(PrintConnector $connector)
    {
        $this->connector = $connector;
    }

    public function detect()
    {
        $queryStatusCommand = "\x10\x04\x02";
        $this->connector->write($queryStatusCommand);
        $reply = $this->connector->read(1);
        foreach (self::OFF_LINE_ERRORS as $status) {
            if ($this->checkStatus($reply, $status)) {
                return $status;
            }
        }

        return true;
    }

    private function checkStatus($data, $status)
    {
        return (ord($data) & $status) === $status;
    }
}