<img align="right" width="100" src="https://avatars.githubusercontent.com/u/100565733?s=200" alt="Lsky Pro Logo"/>

<h1 align="left"><a href="https://www.lsky.pro">Lsky Pro</a></h1>

☁ Your photo album on the cloud.

[![PHP](https://img.shields.io/badge/PHP->=8.0-orange.svg)](http://php.net)
[![Release](https://img.shields.io/github/v/release/lsky-org/lsky-pro)](https://github.com/lsky-org/lsky-pro/releases)
[![Issues](https://img.shields.io/github/issues/lsky-org/lsky-pro)](https://github.com/lsky-org/lsky-pro/issues)
[![Code size](https://img.shields.io/github/languages/code-size/lsky-org/lsky-pro?color=blueviolet)](https://github.com/lsky-org/lsky-pro)
[![Repo size](https://img.shields.io/github/repo-size/lsky-org/lsky-pro?color=eb56fd)](https://github.com/lsky-org/lsky-pro)
[![Last commit](https://img.shields.io/github/last-commit/lsky-org/lsky-pro/dev)](https://github.com/lsky-org/lsky-pro/commits/dev)
[![License](https://img.shields.io/badge/license-GPL_V3.0-yellowgreen.svg)](https://github.com/lsky-org/lsky-pro/blob/master/LICENSE)

[官网](https://www.lsky.pro) &middot;
[文档](https://docs.lsky.pro) &middot;
[社区](https://github.com/lsky-org/lsky-pro/discussions) &middot;
[演示](https://pic.vv1234.cn) &middot;
[Telegram 群组](https://t.me/lsky_pro)

> [!WARNING]
> 开源版本已停止维护，不再进行新特性更新和 bug 修复。

> master 分支为未安装三方拓展的版本，通常包含了最新未发布版本的一些实验性新特性和修复补丁，正式版本请点击 [这里](https://github.com/lsky-org/lsky-pro/releases) 下载。  
> 发现 bug 请提交 [issues](https://github.com/lsky-org/lsky-pro/issues) (提问前建议阅读[提问的智慧](https://github.com/ryanhanwu/How-To-Ask-Questions-The-Smart-Way/blob/main/README-zh_CN.md))  
> 有任何想法、建议、或分享，请移步 [社区](https://github.com/lsky-org/lsky-pro/discussions)

![看不见图片请使用科学上网](https://user-images.githubusercontent.com/22728201/157242302-bfbd04a0-fb30-4241-800e-cc2b1dad9b19.png)
![看不见图片请使用科学上网](https://user-images.githubusercontent.com/22728201/157242314-5716d578-fee5-4083-8d91-0d98cb2545d9.png)

### 🧩 本仓库扩展：Asset Router Control Plane

本仓库在 Lsky Pro 原有能力之上增加了一套 **Asset Router sidecar 控制面**。扩展目标是让 Lsky Pro 除了继续保留 legacy 图床能力之外，也可以管理一套面向多存储后端的统一资源路由入口。

扩展遵循 sidecar 方式实现：不移除 Lsky 原有上传、图片管理、相册、储存策略、API 等功能；新增的 Asset Router 模式作为默认工作区，Lsky 原功能作为 legacy 模式保留在侧边栏独立分组中。

#### 功能概览

- Asset Router 模式 Web 管理页：
  - 仪表盘：查看 Asset Router 资源概览与趋势。
  - 上传图片：支持拖拽上传，并可选择公开或非公开资源。
  - 我的图片：使用与 Lsky legacy 类似的 Justified Gallery 布局、图片预览和右键菜单。
  - 图片资源：管理员视角的全局资源管理。
  - Provider 状态：查看各存储 provider 的资源记录与导入入口。
  - 接口：展示 Asset Router API 与 CLI 接入方式。
- Legacy Lsky 模式：
  - 原有仪表盘、上传图片、我的图片、图片管理、接口、画廊、设置、储存策略等继续保留。
  - 本地储存策略的 `public/i` 链接在 Docker 镜像中保持兼容。
- 公共管理功能：
  - 控制台、角色组、用户管理、系统设置作为公共功能保留。
- 资源链接复制：
  - Asset Router 图片页支持复制 URL、HTML、BBCode、Markdown、Markdown with link。
- Provider 导入：
  - 可从已存在的 provider 对象列表导入资源元数据到 Asset Router 控制面，不复制或删除原始对象。

#### 支持的资源后端

当前 Asset Router 控制面支持以下 provider 语义：

- `r2`：主对象存储。
- `github-jsdelivr`：公开资源镜像，用于低成本公共分发。
- `lsky`：legacy Lsky 兼容/备用入口。
- `local`：开发或未启用远端存储时的本地回退。

公开资源通常会写入主存储并排队镜像到公开 provider；非公开资源只使用主存储或本地回退。具体 provider 是否启用由环境变量决定。

#### Web 路由

Asset Router Web UI：

- `GET /ar/dashboard`
- `GET /ar/upload`
- `POST /ar/upload`
- `GET /ar/images`
- `GET /ar/images/{asset}`
- `GET /ar/api`

管理员 UI：

- `GET /admin/asset-router/assets`
- `GET /admin/asset-router/assets/{asset}`
- `GET /admin/asset-router/providers`
- `POST /admin/asset-router/providers/import`

#### API

Asset Router API 位于 `/api/asset-router/v1`：

- `GET /status`
- `GET /providers`
- `GET /assets`
- `POST /assets`
- `GET /assets/{asset}`
- `PUT|PATCH /assets/{asset}`
- `DELETE /assets/{asset}`
- `GET /assets/{asset}/links`
- `POST /assets/{asset}/mirror`
- `POST /assets/{asset}/probe`
- `GET /jobs`
- `GET /jobs/{job}`
- `POST /picgo/upload`

API 使用 Laravel Sanctum token 进行认证，返回结构沿用 Lsky Pro 的 `{status, message, data}` 风格。`/picgo/upload` 提供 PicGo 兼容上传入口。

#### CLI

仓库包含一个轻量 Asset Router CLI 原型：

```bash
tools/asset-router-cli status
tools/asset-router-cli list
tools/asset-router-cli upload ./image.png --visibility=public
tools/asset-router-cli get <asset-id>
tools/asset-router-cli links <asset-id>
tools/asset-router-cli mirror <asset-id>
tools/asset-router-cli probe <asset-id>
tools/asset-router-cli jobs
```

CLI 通过 HTTP API 工作，需要设置：

```bash
ASSET_ROUTER_BASE_URL=https://your-lsky-host.example
ASSET_ROUTER_TOKEN=your-sanctum-token
```

请勿将真实 token 写入仓库。

#### Artisan 命令

```bash
php artisan asset-router:import-providers --source=all --prefix= --limit=0
php artisan asset-router:run-jobs --limit=20
```

- `asset-router:import-providers`：从 provider 列表导入元数据。
- `asset-router:run-jobs`：处理镜像、同步等后台任务。

#### Docker 镜像

本仓库包含 Docker 构建文件：

- `Dockerfile`
- `.docker/apache-vhost.conf`
- `.github/workflows/docker-image.yml`

镜像会构建 PHP/Apache 运行环境、Composer vendor、前端静态资源，并创建 legacy Lsky 本地储存所需的 `public/i -> ../storage/app/uploads` 符号链接。

#### 常用环境变量

以下为公开可描述的配置项名称。真实值应通过 `.env`、容器环境变量或 CI/CD secret 注入，不能提交到仓库。

```dotenv
ASSET_ROUTER_ENABLED=true
ASSET_ROUTER_PUBLIC_BASE_URL=https://assets.example.com
ASSET_ROUTER_MEMBERS_BASE_URL=https://assets.example.com/m
ASSET_ROUTER_DEFAULT_VISIBILITY=public

ASSET_ROUTER_R2_ENABLED=false
ASSET_ROUTER_R2_BUCKET=
ASSET_ROUTER_R2_ENDPOINT=
ASSET_ROUTER_R2_REGION=auto
ASSET_ROUTER_R2_ACCESS_KEY_ID=
ASSET_ROUTER_R2_SECRET_ACCESS_KEY=
ASSET_ROUTER_R2_ACCOUNT_ID=
ASSET_ROUTER_R2_API_TOKEN=

ASSET_ROUTER_GITHUB_REPO=owner/repo
ASSET_ROUTER_GITHUB_BRANCH=main
ASSET_ROUTER_GITHUB_TOKEN=
ASSET_ROUTER_GITHUB_JSDELIVR_BASE_URL=https://cdn.jsdelivr.net/gh/owner/repo@main

ASSET_ROUTER_LSKY_FALLBACK_BASE_URL=
ASSET_ROUTER_MIRROR_AUTO_DISPATCH=false
ASSET_ROUTER_SECOND_BRAIN_SYNC_URL=
ASSET_ROUTER_SECOND_BRAIN_SYNC_TOKEN=
```

#### 数据表

Asset Router 扩展新增以下主要表：

- `asset_router_assets`
- `asset_router_provider_objects`
- `asset_router_jobs`

这些表用于保存资源元数据、provider 对象状态和异步任务状态，不替代 Lsky legacy 的 `images`、`strategies`、`albums` 等原有表。

### 📌 TODO
* [x] 支持`本地`等多种第三方云储存 `AWS S3`、`阿里云 OSS`、`腾讯云 COS`、`七牛云`、`又拍云`、`SFTP`、`FTP`、`WebDav`、`Minio`
* [x] 多种数据库驱动支持，`MySQL 5.7+`、`PostgreSQL 9.6+`、`SQLite 3.8.8+`、`SQL Server 2017+`
* [x] 支持配置使用多种缓存驱动，`Memcached`、`Redis`、`DynamoDB`、等其他关系型数据库，默认以文件的方式缓存
* [x] 多图上传、拖拽上传、粘贴上传、动态设置策略上传、复制、一键复制链接
* [x] 强大的图片管理功能，瀑布流展示，支持鼠标右键、单选多选、重命名等操作
* [x] 自由度极高的角色组配置，可以为每个组配置多个储存策略，同时储存策略可以配置多个角色组
* [x] 可针对角色组设置上传文件、文件夹路径命名规则、上传频率限制、图片审核等功能
* [x] 支持图片水印、文字水印、水印平铺、设置水印位置、X/y 轴偏移量设置、旋转角度等
* [x] 支持通过接口上传、管理图片、管理相册
* [x] 支持在线增量更新、跨版本更新
* [x] 图片广场
* [x] Asset Router sidecar 控制面、多 provider 元数据管理、PicGo 兼容接口与 CLI 接入

### 🛠 安装要求
- PHP >= 8.0.2
- BCMath PHP 扩展
- Ctype PHP 扩展
- DOM PHP 拓展
- Fileinfo PHP 扩展
- JSON PHP 扩展
- Mbstring PHP 扩展
- OpenSSL PHP 扩展
- PDO PHP 扩展
- Tokenizer PHP 扩展
- XML PHP 扩展
- Imagick 拓展
- exec、shell_exec 函数
- readlink、symlink 函数
- putenv、getenv 函数
- chmod、chown、fileperms 函数

### 😋 鸣谢
- [Laravel](https://laravel.com)
- [Tailwindcss](https://tailwindcss.com)
- [Fontawesome](https://fontawesome.com)
- [Echarts](https://echarts.apache.org)
- [Intervention/image](https://github.com/Intervention/image)
- [league/flysystem](https://flysystem.thephpleague.com)
- [overtrue](https://github.com/overtrue)
- [Jquery](https://jquery.com)
- [jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload)
- [Alpinejs](https://alpinejs.dev/)
- [Viewer.js](https://github.com/fengyuanchen/viewerjs)
- [DragSelect](https://github.com/ThibaultJanBeyer/DragSelect)
- [Justified-Gallery](https://github.com/miromannino/Justified-Gallery)
- [Clipboard.js](https://github.com/zenorocha/clipboard.js)

### 💰 捐赠
Lsky Pro 的开发和更新等，都是作者在业余时间独立开发，并免费开源使用，如果您认可我的作品，并且觉得对你有所帮助我愿意接受来自各方面的捐赠😃。
<table width="100%">
    <tr>
        <th>支付宝</th>
        <th>微信</th>
    </tr>
    <tr>
        <td><img alt="看不见图片请使用科学上网" src="https://raw.githubusercontent.com/lsky-org/lsky-pro/82988ebe2edd32264d609b26bf9132b3dce7c39e/public/static/app/images/demo/alipay.png"></td>
        <td><img alt="看不见图片请使用科学上网" src="https://raw.githubusercontent.com/lsky-org/lsky-pro/82988ebe2edd32264d609b26bf9132b3dce7c39e/public/static/app/images/demo/wechat.jpeg"></td>
    </tr>
</table>

### 🤩 Stargazers over time
[![Stargazers over time](https://starchart.cc/lsky-org/lsky-pro.svg)](https://starchart.cc/lsky-org/lsky-pro)

### 📧 联系我
- Email: i@wispx.cn

### 📃 开源许可
[GPL 3.0](https://opensource.org/licenses/GPL-3.0)

Copyright (c) 2018-present Lsky Pro.
