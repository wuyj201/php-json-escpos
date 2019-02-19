<?php

namespace Wuyj\Escpos\Test;


use PHPUnit\Framework\TestCase;
use Wuyj\Escpos\EscposPrinter;

class PrinterTest extends TestCase
{

    public function testGetInstance()
    {
        $printer = new EscposPrinter();
        $this->assertInstanceOf(EscposPrinter::class, $printer);

        return $printer;
    }

    /**
     * @depends      testGetInstance
     * @dataProvider receiptsDataProvider
     *
     * @param               $template
     * @param               $data
     * @param EscposPrinter $printer
     *
     * @throws \Exception
     * @throws \Wuyj\Escpos\Exceptions\PrinterConnectFailedException
     */
    public function testPrint($template, $data, EscposPrinter $printer)
    {
        $printer->render($template, $data);
        $printer->connect('10.10.10.202');
        $printer->detect();
        $data = json_decode($data, true);
        $this->assertTrue(is_string($template));
        $this->assertTrue(is_array($data));
        $printer->printing();
    }

    public function receiptsDataProvider()
    {
        return [
            'pos' => [
                $this->loadResource('pos-receipt'),
                $this->loadResource('pos-receipt', 'data'),
            ],
        ];
    }

    private function loadResource($name, $type = 'template')
    {
        $path = dirname(__FILE__) . "/resources/$type/$name.json";

        return file_get_contents($path);
    }
}