# ESC/POS Print JSON formatted receipts on ESC/POS printers.

基于[mike42/escpos-php](https://github.com/mike42/escpos-php) 封装一层类库，可以根据模板来打印小票。 在x-printer 80mm热敏纸上运行正常。

[English](./README.md) | 简体中文

## 使用说明

### 安装
```bash
composer require wuyj/php-json-escpos
```

### 例子
```php
<?php
require __DIR__ . '/vendor/autoload.php';
use Wuyj\Escpos\EscposPrinter;
$printer = new EscposPrinter();

/** 打印小票。 **/
$json = '[{"styles":"alignCenter;fontBold;fontSize:2,2","text":["TITLE TITLE TITLE","","标题标题标题标题",""]},{"styles":"fontSize:1,1","text":["订单号: ${order_sn}"]},{"styles":"fontSize:1,1","text":["订单时间: ${add_time}"]},{"styles":"alignSide:12,12","text":["座位号: ${table_sn}","用餐人数: ${cover}"]},{"styles":"hidden:hide_refund_text","text":["原因:${refund_reason}"]},{"styles":"strRepeat","text":["-"]},{"styles":"each:foods;alignSide:17,-2,-5","text":[{"styles":"","text":[["${item_name_en}","${item_quantity}","${item_price}"],["${item_name_zh}","",""]]},{"styles":"each:specs_items","text":[["* ${item_attr_en} ${item_attr}","",""]]},{"styles":"hidden:hide_remark","text":["* ${remark}","",""]}]},{"styles":"strRepeat","text":["-"]},{"styles":"alignSide:-14,-10;fontSize:1,1","text":["小计:","${sub_total}"]},{"styles":"alignSide:-14,-10;fontSize:1,1","text":["折扣:","${discount}"]},{"styles":"alignSide:-14,-10;fontSize:1,1;fontBold","text":["总计:","${total}"]},{"styles":"alignSide:8,8,-8;fontSize:1,1;fontBold","text":["支付方式","","金额"]},{"styles":"each:payTypeItems;alignSide:8,8,-8","text":["${pay_name}","${en_name}","${amount}"]}]';
$data = '{"order_sn":"0123456789","add_time":"2019/02/01","table_sn":"T100","cover":4,"refund_reason":"TOO EXPENSIVE","hide_refund_text":true,"sub_total":600,"total":540,"discount":"90%","payTypeItems":[{"pay_name":"支付宝","en_name":"AliPay","amount":540}],"hide_pay_type_list":false,"foods":[{"remark":"remark test","hide_remark":true,"item_name_en":"foods","item_quantity":2,"item_price":100.00,"item_name_zh":"食物","specs_items":[]},{"remark":"remark test","hide_remark":false,"item_name_en":"juice","item_quantity":2,"item_price":200.00,"item_name_zh":"饮料","specs_items":[{"item_attr_en":"No Ice","item_attr":"不加冰"},{"item_attr_en":"No Sugar","item_attr":"不加糖"}]}]}';
$printer->render($json, json_encode($data, true));
$printer->connect('10.10.10.201');
$printer->printing();

/** 打开钱箱。 **/
$printer->connect('10.10.10.201');
$printer->pulse();

/** 监测小票机状态 **/
$printer->connect('10.10.10.201');
$status = $printer->detect();
switch ($status) {
    case 1: 
        echo 'OK';
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

## 支持的文本样式
### underLine, 下划线
* add underline for text
### qrCode, 二维码
* print qrCode
### actionCut, 切纸
* cut receipts paper
### actionFeed, 喂纸
* actionFeed: 1 (feed 1 line)
* feed paper
### fontSize, 字体大小
* fontSize: width, height
* FONT_A: 1, 2, 3
* FONT_B: 1.5
### alignSide, 文本排列
* alignSide: 8, 8, -8 (共24格,代表3列文本, 每列占8格, -8代表向右对齐, 默认向左对齐)
* 按照表格打印文本
* 24 for FONT_A, 32 for FONT_B
### strRepeat
* strRepeat (默认打印48个字符)
### alignLeft
### alignRight
### alignCenter
### fontBold
### lineBreak
### actionText


## 支持的指令
### each, 循环打印
* each: data_items (循环数组data_items打印文本)
### hidden, 按照条件隐藏文本
* hidden: hidden_value (按照`hidden_value`的值为条件来隐藏文本)
### repeat, 重复打印
* repeat: repeat_times (重复打印 `repeat_times` 次)