<?php

namespace Wuyj\Escpos\Parsers;

use Mike42\Escpos\Printer;

class ActionParser
{
    /**
     * @var string
     */
    public $action = '';

    /**
     * @var array
     */
    public $param = [];

    /**
     * ActionParser constructor.
     *
     * @param $action
     * @param $param
     */
    public function __construct($action, $param)
    {
        $this->action = $action;
        $this->param = $param;
    }

    /**
     * @param Printer $printer
     *
     * @throws \Exception
     */
    public function handle(Printer $printer)
    {
        if (method_exists($printer, $this->action)) {
            try {
                call_user_func_array([$printer, $this->action], $this->param);
            } catch (\Error $e) {
                throw new \Exception('printer' . $this->action . ' execute action error.' . $e);
            } catch (\Exception $e) {
                throw new \Exception('printer' . $this->action . ' execute action exception.' . $e);
            }
        } else {
            throw new \Exception('printer action ' . $this->action . ' not found.');
        }
    }
}