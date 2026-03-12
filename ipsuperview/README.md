# IP SuperView

GLPI 10 plugin for subnet overview, fixed IP auditing, live scan comparison, and bilingual Chinese/English UI.

## 中文

`IP SuperView` 是一个面向 GLPI 10 的轻量插件，用来把一个 IPv4 网段整理成适合日常排查和核对的工作台。

### 功能

- 网段占用总览
- 冲突 IP 检查
- `/24` 及更小网段热力图
- 固定 IP 对应表，支持陈旧资料识别
- 在线但未登记设备视图
- 人工别名与保留地址管理
- CSV 报表导出
- 跟随 GLPI 会话语言的中英双语界面

### 适用范围

- 支持网段范围：`/20` 到 `/30`
- 热力图：`/24` 或更小
- 明细表：最多 `1024` 个可用地址
- 在线实扫：当前 PHP 环境允许 `shell_exec` 时可用

### 安装

1. 把 `ipsuperview` 目录复制到 GLPI 的 `plugins/` 目录。
2. 在 GLPI 中打开 `Setup > Plugins`。
3. 安装并启用 `IP SuperView`。
4. 给目标 Profile 授予 `View IP subnet overview` 权限。

### 说明

- 插件不会自动修改 GLPI 里的 IP 绑定关系。
- 实扫结果只用于对比和排查，不会自动写回资产。
- 导出内容包含汇总、固定 IP 对应表、冲突地址、未登记设备和人工标记。

## English

`IP SuperView` is a lightweight plugin for GLPI 10 that turns an IPv4 subnet into an operator-friendly workspace.

### Features

- Occupancy summary for a subnet
- Duplicate IP detection
- Heatmap for `/24` and smaller ranges
- Fixed IP mapping with stale record highlighting
- Live but unregistered device view
- Manual aliases and reserved IP markers
- CSV export
- Bilingual Chinese/English UI based on the active GLPI session language

### Scope

- Supported subnet sizes: `/20` to `/30`
- Heatmap: `/24` or smaller
- Detail table: up to `1024` usable addresses
- Live scan: available when `shell_exec` is enabled in PHP

### Install

1. Copy the `ipsuperview` directory into your GLPI `plugins/` directory.
2. Open `Setup > Plugins` in GLPI.
3. Install and enable `IP SuperView`.
4. Grant the `View IP subnet overview` right to the target profile.

### Notes

- The plugin does not modify GLPI IP assignments automatically.
- Live scan data is used for operator comparison and troubleshooting.
- Export covers summary, fixed IP mapping, conflicts, unregistered devices, and manual labels.

## Tested

- GLPI `10.0.18`
- Production-style `/24` subnet validation
