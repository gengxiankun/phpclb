# PHPCLB

## 安装

安装此扩展程序的首选方法是通过 [composer](http://getcomposer.org/download/).

执行命令

`php composer.phar require --prefer-dist gengxiankun/phpclb "~1.0.0"`

或添加配置到项目目录下的composer.json文件的require部分

`"gengxiankun/phpclb": "~1.0.0"`

## 使用

```bash
phpclb [ Action ] [ paramter1 value1 ] [ paramter2 value2 ] ... [ paramterN valueN ]
```
*ex*
```bash
phpclb \
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
