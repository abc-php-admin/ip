# think-ip
The ThinkPHP 6 ip Package

## 安装
> composer require abcphp/think-ip @dev

## 获取ip
> \abc\Ip::find("10.82.245.13")

```
array(7) {
  ["ip"]=>
  string(12) "10.82.245.13"
  ["country"]=>
  string(9) "局域网"
  ["province"]=>
  string(0) ""
  ["city"]=>
  string(0) ""
  ["county"]=>
  string(0) ""
  ["isp"]=>
  string(0) ""
  ["area"]=>
  string(11) "局域网IP"
}
```

##更新数据
> \abc\Ip::udpate()