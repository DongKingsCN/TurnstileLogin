# Typecho Cloudflare Turnstile 登录验证插件 (TurnstileLogin)

![Typecho Version](https://img.shields.io/badge/Typecho-1.2%2B-blue)

为 Typecho 和 Typerenew（社区二改typecho） 提供 Cloudflare Turnstile 人机验证支持，无缝替换传统的登录验证码，防爆破、防自动化脚本攻击。

## 🌟 特性

* **直接填入密钥**：后台集成可视化配置界面，轻松设置 Site Key 与 Secret Key。
* **主题模式切换**：支持自动 / 浅色 / 暗色 主题适配，完美契合你的后台风格。
* **无感与高安全**：基于 Cloudflare 智能风控，大部分情况下用户免手动点击即可完成登录验证。

## 🛠️ 安装方法

1. 点击本仓库右上角 **Code -> Download ZIP** 下载源码包（或在 [Releases](../../releases) 页面下载）。
2. 将解压后的文件夹重命名为 `TurnstileLogin`（**必须精准匹配，大小写敏感**）。
3. 将 `TurnstileLogin` 文件夹上传至 Typecho 插件目录 `/usr/plugins/` 下。
4. 登录 Typecho 管理后台 -> **控制台** -> **插件** -> 激活 **TurnstileLogin** 插件。

## ⚙️ 配置与使用说明

### 1. 配置密钥
1. 登录 [Cloudflare Dashboard](https://dash.cloudflare.com/)，进入 **Turnstile** 菜单创建站点。
2. 获取 **Site Key（公钥）** 和 **Secret Key（私钥）**。
3. 进入 Typecho 后台 -> **设置插件 TurnstileLogin**，填入对应的公钥与私钥并选择主题模式，最后保存设置。

### 2. 后端设置
*由于 Typecho 原生登录页面未提供钩子，如需开启后台登录验证，需自行在后台模板中插入组件代码，可以将提示词发送给AI工具进行编写。*

