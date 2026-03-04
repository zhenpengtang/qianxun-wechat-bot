# 🚀 WeChat Group Sender Pro

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)
[![Platform](https://img.shields.io/badge/platform-Windows%20%7C%20Linux-lightgrey.svg?style=flat-square)](https://github.com/)

**WeChat Group Sender Pro** 是一款专为“千寻微信框架”设计的轻量级管理后台。它集成了群组抓取、自定义分类、后台异步群发以及随机防封延迟等核心功能。



---

## ✨ 核心特性

* **📦 智能分类管理**: 支持从原始数据中精选群组，一键导出为 `.json` 分类表，实现精准营销。
* **📡 脱机异步发送**: 采用 `fastcgi_finish_request` 逻辑，点击发送后浏览器可直接关闭，任务在服务器后台静默运行。
* **⏱️ 随机防封算法**: 严格遵循 **5s - 60s** 的随机休眠策略，模拟真人发送频率，保护账号安全。
* **📜 实时透明日志**: 黑色控制台风格日志，精确记录每一个群组的发送时间、ID 及成功状态。
* **🎨 极简工业设计**: 基于 **Bootstrap 5** 开发，无需复杂配置，开箱即用。

---

## 🛠️ 技术架构

- **后端**: PHP (处理异步 I/O、CURL 请求、本地文件持久化)
- **前端**: JavaScript (Fetch API 异步交互) + Bootstrap 5
- **存储**: 基于文件系统的 JSON 存储（无需 MySQL 数据库）

---

## 🚀 快速开始

### 1. 环境准备
确保您的 Web 服务器拥有对以下文件夹的写入权限：
```bash
chmod -R 777 ./groups_config
touch history.log && chmod 777 history.log
