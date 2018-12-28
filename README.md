# PHPCLB
PHP封装的腾讯云负载均衡 SDK 及 CLI（负载均衡 API 2017）

> 目前阶段腾讯云还未提供负载均衡中PHP的 SDK 便自己封装了一个，提供了 CLI 的调用方式，在 CICD 中的多种发布模式中起到关键作用。

请配合 [腾讯云负载均衡API文档](https://cloud.tencent.com/document/product/214/888) 使用。

[![Latest Stable Version](https://poser.pugx.org/gengxiankun/phpclb/v/stable)](https://packagist.org/packages/gengxiankun/phpclb)
[![Total Downloads](https://poser.pugx.org/gengxiankun/phpclb/downloads)](https://packagist.org/packages/gengxiankun/phpclb)
[![License](https://poser.pugx.org/gengxiankun/phpclb/license)](https://packagist.org/packages/gengxiankun/phpclb)

## 安装

安装此扩展程序的首选方法是通过 [composer](http://getcomposer.org/download/).

执行命令
```bash
php composer.phar require --prefer-dist gengxiankun/phpclb "~1.0.0"
```

或添加配置到项目目录下的composer.json文件的require部分

`"gengxiankun/phpclb": "~1.0.0"`

## 使用

### SDK 用法

```php
use gengxiankun\phpclb\TencentCLB;

$clb = new TencentCLB([
    'secretId' => $secretId,
    'secretKey' => $secretKey,
    'region' => $region
]);

$response = $clb->modifyForwardFourthBackendsWeight($loadBalancerId, $listenerId, $backends_n_instanceId, $backends_n_port, $backends_n_weight);
```

### CLI 用法

```bash
phpclb [ Action ] [ paramter1 value1 ] [ paramter2 value2 ] ... [ paramterN valueN ]
```
*ex*
```bash
vendor/bin/phpclb \
 modifyForwardFourthBackendsWeight\
 secretId xxx\
 secretKey xxx\
 region xxx\
 loadBalancerId xxx\
 listenerId xxx\
 backends_n_instanceId xxx\
 backends_n_port xxx\
 backends_n_weight xxx
```

## 支持的 Actions LIST
- `modifyForwardSeventhBackends` 修改应用型七层监听器转发规则上云服务器的权重
- `modifyForwardFourthBackendsWeight` 修改应用型四层监听器转发规则上云服务器的权重
- `modifyForwardSeventhBackendsPort` 修改应用型七层监听器转发规则上云服务器的端口
- `modifyForwardFourthBackendsPort` 修改应用型四层监听器转发规则上云服务器的端口
- `describeForwardLBBackends` 查询应用型负载均衡云服务器列表
- `registerInstancesWithForwardLBSeventhListener` 绑定云服务器到应用型负载均衡七层监听器的转发规则上
- `registerInstancesWithForwardLBFourthListener` 绑定云服务器到应用型负载均衡四层监听器的转发规则上
- `deregisterInstancesFromForwardLB` 解绑应用型负载均衡七层监听器转发规则上的云服务器
- `deregisterInstancesFromForwardLBFourthListener` 解绑应用型负载均衡四层监听器转发规则上的云服务器

## API 参数对照表
原生参数 | 现参数
------------ | -------------
locationIds.n | locationIds_n
backends.n.instanceId | backends_n_instanceId
backends.n.port | backends_n_port
backends.n.weight | backends_n_weight

> 其他参数
backends.n.port | backends_n_port参数
backends.n.port | backends_n_port
