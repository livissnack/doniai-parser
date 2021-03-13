<h1 align="center">Doniai Parser</h1>

<p align="center">:tada: 基于Manaphp开发的短视频解析工具站。</p>

![Build Status](https://img.shields.io/travis/livissnack/doniai-parser)
[![Issues](https://img.shields.io/github/issues/livissnack/doniai-parser.svg)](https://github.com/livissnack/doniai-parser/issues)
![Forks](https://img.shields.io/github/forks/livissnack/doniai-parser.svg)

## 说明

```
说明注意事项：
1、一款短视频解析站
2、目前仅支持解析抖音短视频（后期会慢慢支持其他平台）
3、需要了解短视频解析原理或直接购买本人的解析服务，请联系本人，非诚勿扰
```

## 安装

```sh
$ git clone git@github.com:livissnack/doniai-parser.git new_name
$ cd new_name
$ composer install
```

## 配置

在使用本应用之前，你需要去 config 目录下进行相应的配置

## 使用

```shell
$ cd new_name/config
$ cp .env.example .env //配置正确的参数
$ cd .. && cd docker
$ vim .env //配置docker配置
$ docker-compose -f swoole.yml up -d
```

## nginx反向代理配置

请自行百度配置


## 页面效果

#### 解析效果
![effect](/example/images/1.png)


## 所用依赖，感谢这些好用的扩展包

- [Manaphp](https://github.com/manaphp)
- [VueJs](https://vuejs.org/)
- [Buefy](https://buefy.org/)

## License

the MIT License, http://livissnack.mit-license.org
