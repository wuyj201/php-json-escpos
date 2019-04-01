# ESC/POS Print JSON formatted receipts on ESC/POS printers.

Based on [mike42/escpos-php](https://github.com/mike42/escpos-php) project, works properly on x-printer 80mm.

English | [简体中文](./README.zh-CN.md)

## Quick Start

### Include the library
```bash
composer require wuyj/php-json-escpos
```

### Examples
```php
<?php
require __DIR__ . '/vendor/autoload.php';
use Wuyj\Escpos\EscposPrinter;
$printer = new EscposPrinter();

/** Print receipts. **/
$json = '[{"styles":"alignCenter;fontBold;fontSize:2,2","text":["TITLE TITLE TITLE","","标题标题标题标题",""]},{"styles":"fontSize:1,1","text":["订单号: ${order_sn}"]},{"styles":"fontSize:1,1","text":["订单时间: ${add_time}"]},{"styles":"alignSide:12,12","text":["座位号: ${table_sn}","用餐人数: ${cover}"]},{"styles":"hidden:hide_refund_text","text":["原因:${refund_reason}"]},{"styles":"strRepeat","text":["-"]},{"styles":"each:foods;alignSide:17,-2,-5","text":[{"styles":"","text":[["${item_name_en}","${item_quantity}","${item_price}"],["${item_name_zh}","",""]]},{"styles":"each:specs_items","text":[["* ${item_attr_en} ${item_attr}","",""]]},{"styles":"hidden:hide_remark","text":["* ${remark}","",""]}]},{"styles":"strRepeat","text":["-"]},{"styles":"alignSide:-14,-10;fontSize:1,1","text":["小计:","${sub_total}"]},{"styles":"alignSide:-14,-10;fontSize:1,1","text":["折扣:","${discount}"]},{"styles":"alignSide:-14,-10;fontSize:1,1;fontBold","text":["总计:","${total}"]},{"styles":"alignSide:8,8,-8;fontSize:1,1;fontBold","text":["支付方式","","金额"]},{"styles":"each:payTypeItems;alignSide:8,8,-8","text":["${pay_name}","${en_name}","${amount}"]}]';
$data = '{"order_sn":"0123456789","add_time":"2019/02/01","table_sn":"T100","cover":4,"refund_reason":"TOO EXPENSIVE","hide_refund_text":true,"sub_total":600,"total":540,"discount":"90%","payTypeItems":[{"pay_name":"支付宝","en_name":"AliPay","amount":540}],"hide_pay_type_list":false,"foods":[{"remark":"remark test","hide_remark":true,"item_name_en":"foods","item_quantity":2,"item_price":100.00,"item_name_zh":"食物","specs_items":[]},{"remark":"remark test","hide_remark":false,"item_name_en":"juice","item_quantity":2,"item_price":200.00,"item_name_zh":"饮料","specs_items":[{"item_attr_en":"No Ice","item_attr":"不加冰"},{"item_attr_en":"No Sugar","item_attr":"不加糖"}]}]}';
$printer->render($json, json_encode($data, true));
$printer->connect('10.10.10.201');
$printer->printing();

/** Open cashier drawer. **/
$printer->connect('10.10.10.201');
$printer->pulse();

/** Detect printer device status. **/
$printer->connect('10.10.10.201');
$status = $printer->detect();
switch ($status) {
	case 1: 
		echo 'OK';
		$printer->close();
		break;
	case -1:
		echo 'Not Connected';
		break;
	case 0x20:
		echo 'BTN PRESSED';
		break;
	case 0x08:
		echo 'BTN PRESSED';
		break;
	case 0x64:
		echo 'HARDWARE ERROR';
		break;
}
```

## Supported styles
### underLine
* add underline for text
### qrCode
* print qrCode
### actionCut
* cut receipts paper
### actionFeed
* actionFeed: 1 (feed 1 line)
* feed paper
### fontSize
* fontSize: width, height
* FONT_A: 1, 2, 3
* FONT_B: 1.5
### alignSide
* alignSide: 8, 8, -8 (print text as table, total 3 columns, each column will display 8 cell, -8 means align text by right, default to align text to left)
* 24 for FONT_A, 32 for FONT_B
### strRepeat
* strRepeat (default char `_` will be repeated 48 times)
### alignLeft
### alignRight
### alignCenter
### fontBold
### lineBreak
### actionText


## Supported directives
### each
* each: data_items (loop `data_items` to print text line)
### hidden
* hidden: hidden_value (hidden text line if hidden_value is true)
### repeat
* repeat: repeat_times (repeat the specified line with `repeat_times` times)