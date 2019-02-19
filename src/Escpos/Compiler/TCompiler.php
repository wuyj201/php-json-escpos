<?php

namespace Wuyj\Escpos\Compiler;

class TCompiler
{
    /**
     * @var TLine[]
     */
    protected $stack = [];

    /**
     * @var array
     */
    protected $data;

    /**
     * @param $line
     * @param $data
     */
    public function handle($line, $data)
    {
        $this->stack = [];
        $this->data = $data;
        $this->traverse(new TNode($line), $this->data);
    }

    /**
     * @param TNode $tNode
     * @param       $data
     */
    private function traverse(TNode $tNode, $data)
    {
        if (($dataKeyName = $tNode->hasLoopDirective()) !== false) {
            $items = $this->getData($data, $dataKeyName);
            $tNode = clone $tNode;
            $tNode->removeLoopDirective();
            foreach ($items as $item) {
                $this->traverse($tNode, $item);
            }

            return;
        }
        if (($dataKeyName = $tNode->hasRepeatDirective()) !== false) {
            $times = $this->getData($data, $dataKeyName);
            if (is_numeric($times)) {
                $times = + $times;
                $tNode = clone $tNode;
                $tNode->removeRepeatDirective();
                for ($i = 1; $i <= $times; $i ++) {
                    $this->traverse($tNode, $data);
                }

                return;
            }
        }
        foreach ($tNode->getNodes() as $node) {
            if ($tNode === $node) {
                $this->pushIntoStack($node, $this->getData($data));
            } else {
                $node->inheritStyles($tNode->inheritedStyles);
                $node->inheritStyles($tNode->styles);
                $this->traverse($node, $data);
            }
        }
    }

    /**
     * @param TNode $tNode
     * @param array $data
     */
    private function pushIntoStack(TNode $tNode, array $data)
    {
        if (($keyName = $tNode->hasHiddenDirective()) !== false) {
            $tNode = clone $tNode;
            $tNode->removeHiddenDirective();
            $negative = true;
            if ($keyName[0] === '!') {
                $keyName = substr($keyName, 1);
                $negative = false;
            }
            if ($data[$keyName] === $negative) {
                return;
            }
        }
        $tNode->fillData($data);
        $this->stack[] = $tNode->render();
    }

    /**
     * @return TLine[]
     */
    public function result()
    {
        return $this->stack;
    }

    /**
     * @param      $data
     * @param null $key
     *
     * @return array
     */
    private function getData($data, $key = null)
    {
        if (is_null($key)) {
            return $data;
        }
        return $data[$key] ?? [];
    }
}