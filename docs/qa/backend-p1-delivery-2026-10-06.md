# 后台覆盖与接口闭环 P1 交付记录

基于 `claude/backend-coverage-p1` 的 P0 提交 `775bae726ea8e3e289ebe768284ad294a806fc8b`。未提交、未推送、未部署。原审查报告保持不变。

## 范围与实现

| 范围 | 实现 |
| --- | --- |
| A1 | `global.company` 统一公司、销售/商务邮箱、电话、销售/售后 WhatsApp、地址、营业时间、地图查询地址、公司官网及本站显示域名。页脚、首页联系、悬浮联系、产品询价、联系页、隐私占位符、结构化信息和公开发现文档共享读取。邮件草稿通过条件性 `data-email` 注入，默认 DOM 不增加属性；默认收件人从已有邮箱链接读取。 |
| A1 兼容 | 售后号码未覆盖时读取现有 `support.after.whatsapp_number`，明确清空后保持空值。插件已配置的通知收件人在没有全站邮箱覆盖时保留；显式修改全站邮箱后，仅主题询盘表单的主通知使用新地址，不改自动回复和其他表单，也不改插件存储。 |
| A2 | 主菜单和产品子菜单为可排序列表，稳定标识控制当前页状态；产品子菜单不依赖排序后仍居首。站点切换器为有序列表。Logo 和主题图标为媒体字段；原生 WordPress 站点图标优先。 |
| A3 | 页脚分组、链接、口号、版权及公共联系区块/悬浮按钮文字与地址进入全站设置；公司信息继续共用。 |
| A6 | 关于页首屏、集团关系、数据、业务链接、承诺、工厂照片与替代文字可编辑；公司介绍仍读取原生关于页面正文，原有缺省介绍不变。 |
| A7 | 联系页首屏、渠道说明、表单外标题、工厂拜访、照片、预约邮件主题/正文、商务合作区可编辑；联系方式引用全站设置，准备事项继续读取应用场景。 |
| A8 | 隐私页首屏与正文可编辑。站内提交和邮件草稿分别保存正文，保持原有插件条件切换，并显示当前模式。正文支持 `{company.name}`、`{company.domain}`、`{company.email}`；普通 HTML 使用 WordPress 清洗。 |
| A20 | 关于、装载机栏目、翻斗车栏目、新闻列表、联系、隐私增加 SEO 标题/描述/分享图；分享图保留首页回退。404 文案及按钮地址可编辑。 |
| B3 | 画册设置同时在后台和 REST 请求注册，`show_in_rest` 开启。`boblift/v1/catalogue` 的读写要求 `edit_pages`，避免降低核心 settings 接口的管理员权限；大小只读并由原有媒体文件钩子计算。 |
| B4/A19 | 新闻声明 `author` 支持；用户 `bl_title` 通过 `register_meta('user', …)` 注册，REST 开放，清洗文本，写入检查目标用户的 `edit_user`。 |
| B5 | 案例 `_bl_record.image` 的 schema 添加弃用标记和特色图片说明，历史值和原写入兼容保留。 |
| C1 | 仪表盘新增两个产品栏目、全站设置、关于、联系、隐私、新闻列表和 404 快捷入口；原生页面编辑指引区分关于正文与其他页面内容。菜单名称统一中文。 |
| 跟踪代码 | 置于全站设置末尾的折叠区，仅 `manage_options` 和 `unfiltered_html` 同时满足才显示和保存。独立子表单通过同一 P0 schema/版本/锁/合并保存流程写入新增 `bl_content_tracking`，不创建 REST 代理、不注册公开 setting。原文分别在 `wp_head(1)`、`wp_body_open(1)`、`wp_footer(99)` 输出；空值不输出。 |

原有 meta key、option key、页面 key、slug 和 URL 未更名。新内容仍使用 `bl_content_{page}`；跟踪代码虽独立储存和保存，入口仍在同一个全站设置页面，避免普通编辑者的整页保存或恢复默认触及原始脚本。

## 新菜单结构

```text
网站内容
├─ 首页
├─ 采购与售后
├─ 应用场景
├─ 滑移装载机
├─ 迷你翻斗车
├─ 全站设置
│  ├─ 公司与联系方式
│  ├─ 品牌图片
│  ├─ 页眉菜单、站点切换器
│  ├─ 页脚、公共联系区块、悬浮联系按钮
│  └─ 统计与跟踪代码（双权限，独立保存）
├─ 关于我们
├─ 联系我们
├─ 隐私说明
├─ 新闻列表
├─ 未找到页面
└─ 资料下载
```

将统计代码放在全站设置，便于与同样影响全部页面的公司和品牌内容一起管理；通过独立权限与保存版本隔离执行代码。

## 验证

- 执行所有无需数据库的 `tests/*.php`，排除 `tests/wp-smoke.php`；共 7 个脚本，全部通过。
- 新增 P1 回归验证默认值往返、旧字段保留、售后号码继承/清空、真实 REST 回调、全站联系方式多处读取、两种隐私模式及后台字段渲染、原生图标优先、统计代码空输出/原文三位置输出/越权拒绝/过期版本、画册权限与只读大小、作者权限、案例弃用标记及插件邮件收件人边界。
- 37 个站点页面与 404，共 38 个 CLI 渲染与 P0 基线一致。持久化快照只规范化 JS/CSS 的部署修改时间和自动版权年份，不规范化 HTML 空白、实体、文字或结构。另在资源时间戳对齐的两份工作树上逐字节比对 38 个页面，一致。
- 执行全部 47 个变更和新增 PHP 文件的 `php -l`，全部通过；132 个模板字段引用均能解析到已声明默认值。
- 执行 `node --check assets/js/main.js`、`node --check assets/js/inquiry.js`、`node tests/test_backend_p1_js.cjs`。交互回归覆盖重排菜单的鼠标进入/离开、键盘焦点、Escape、手机断点及默认顺序。
- 执行 `git diff --check`，通过。

```sh
for test in tests/*.php; do
  if [ "$test" != "tests/wp-smoke.php" ]; then php "$test" || exit 1; fi
done
node --check assets/js/main.js
node --check assets/js/inquiry.js
node tests/test_backend_p1_js.cjs
git diff --check
```

## 验证边界和风险

- `php -S 127.0.0.1:4175 -t .` 被沙箱拒绝：`Operation not permitted`。未执行 Python HTTP 测试、浏览器实屏、真实 WordPress 数据库、真实 REST HTTP/插件发信和媒体上传测试；未执行会写数据库的 `wp-smoke.php`。
- 布局检查为模板结构、已有 CSS 选择器、默认 HTML 快照和菜单交互状态测试；`assets/css` 未修改。没有声称完成浏览器布局验收，特别是任意超长菜单和大量菜单项。
- 主题本来使用文件修改时间生成脚本 URL。JS 修改后的真实部署版本号会变化，这是缓存失效所需；不能将不同部署时间下的原始 HTML 称为无条件逐字节相同。快照不忽略除此及自动年份之外的任何差异。
- 原文跟踪代码有意不清洗。启用统计后，应通过隐私编辑页同步修改原有“不使用统计跟踪”的声明；后台已有相应提示。
- WordPress 边界替身只测试主题逻辑，不代表验证了 WordPress 内核或第三方插件的实现。测试数据仅在 `tests/fixtures`；生产代码未加入 Mock、调试输出或演示入口。
- 未删除旧 Support 号码字段和案例历史图片；它们是兼容入口，不是重复运行的存储流程。其他新字段均读取 P0 内容解析/合并/保存流程。

## 文件清单

| 文件 | 修改内容 |
| --- | --- |
| `assets/js/inquiry.js` | 从页面注入信息读取草稿收件人，邮件链接和复制反馈同步。 |
| `assets/js/main.js` | 产品菜单移位后的悬停、键盘、Escape 和断点状态。 |
| `docs/qa/backend-p1-delivery-2026-10-06.md` | 本交付清单、菜单、验证边界和接口说明。 |
| `functions.php` | 加载跟踪钩子；站点图标过滤器尊重原生设置。 |
| `inc/content-404.php` | 404 文案与链接的默认值和编辑 schema。 |
| `inc/content-about.php` | 关于页内容与 SEO的默认值和编辑 schema。 |
| `inc/content-contact.php` | 联系页内容、预约邮件与 SEO的默认值和编辑 schema。 |
| `inc/content-dumpers.php` | 翻斗车栏目 SEO的默认值和编辑 schema。 |
| `inc/content-global.php` | 公司、品牌、页脚和公共联系的默认值和编辑 schema。 |
| `inc/content-loaders.php` | 装载机栏目 SEO的默认值和编辑 schema。 |
| `inc/content-navigation.php` | 有序菜单和站点切换列表的默认值和编辑 schema。 |
| `inc/content-news.php` | 新闻列表 SEO的默认值和编辑 schema。 |
| `inc/content-privacy.php` | 隐私首屏、两种模式正文与 SEO的默认值和编辑 schema。 |
| `inc/editable.php` | 新页面默认值接入 P0；兼容读取旧售后号码。 |
| `inc/llms.php` | 公开联系方式读取全站设置。 |
| `inc/routes.php` | 指定路由的 SEO 标题/描述读取字段。 |
| `inc/seo.php` | 公司结构化信息、Logo 和分页面分享图共用字段。 |
| `inc/site-content.php` | 页面定义加载、共享公司信息、品牌和文本读取函数。 |
| `inc/wordpress.php` | 新闻增加作者支持。 |
| `inc/wp-admin-ui.php` | 仪表盘入口和中文指引。 |
| `inc/wp-catalogue-rest.php` | REST 画册设置、编辑者专用读写接口和大小只读。 |
| `inc/wp-content-editor.php` | 新页面与字段类型、中文菜单、隐私状态、跟踪入口和原生页面指引。 |
| `inc/wp-content-state.php` | 跟踪子表单权限检查及恢复/保存返回全站设置，仍走原保存流程。 |
| `inc/wp-inquiry.php` | 主题询盘主通知读取显式全站邮箱覆盖，默认创建与邮件 Logo 共享设置。 |
| `inc/wp-rest.php` | 用户职位 meta 注册及案例封面弃用 schema；兼容数字页面标识。 |
| `inc/wp-support.php` | 画册注册调用新 REST 模块，保留大小计算。 |
| `inc/wp-tracking.php` | 双权限折叠子表单和三个原文输出钩子。 |
| `index.php` | 共享站点图标与关于页预加载图片。 |
| `templates/dialogs.php` | 原硬编码联系方式改为统一读取；默认文字和结构保留。 |
| `templates/footer.php` | 页脚文字、链接、品牌和联系方式读取全站字段。 |
| `templates/header.php` | 可排序导航、产品子菜单、联系按钮和 Logo。 |
| `templates/home.php` | 原硬编码联系方式改为统一读取；默认文字和结构保留。 |
| `templates/pages/404.php` | 404 文案和链接读取字段。 |
| `templates/pages/about.php` | 关于页各区块读取字段，原生公司介绍来源保留。 |
| `templates/pages/applications.php` | 原硬编码联系方式改为统一读取；默认文字和结构保留。 |
| `templates/pages/article.php` | 原硬编码联系方式改为统一读取；默认文字和结构保留。 |
| `templates/pages/contact.php` | 联系页内容、照片、地址与预约草稿读取字段。 |
| `templates/pages/privacy.php` | 按原插件条件读取两套可编辑正文。 |
| `templates/pages/product.php` | 原硬编码联系方式改为统一读取；默认文字和结构保留。 |
| `templates/pages/support.php` | 售后 WhatsApp 读取统一值，兼容旧字段。 |
| `templates/partials/inquiry-form.php` | 自定义邮件地址时注入 data-email；默认无新增属性。 |
| `templates/partials/page-cta.php` | 公共联系区块文字和按钮读取字段。 |
| `templates/partials/side-contact.php` | 悬浮按钮文字、链接和联系方式读取字段。 |
| `templates/partials/site-switch.php` | 有序站点列表读取字段。 |
| `tests/fixtures/backend-p1-default-html.json` | 从 P0 提交生成的不可变页面基线。 |
| `tests/fixtures/check-backend-p1-mail.php` | 不发信的 CF7 收件人边界测试。 |
| `tests/fixtures/register-backend-p1-types.php` | 无数据库新闻类型注册探针。 |
| `tests/fixtures/render-backend-p1.php` | 独立请求渲染器。 |
| `tests/fixtures/wp-memory.php` | 扩展内存 WordPress 边界，记录权限和注册；不写数据库。 |
| `tests/test_backend_p1.php` | P1 内容、权限、保存、REST、后台渲染和兼容回归。 |
| `tests/test_backend_p1_html.php` | 38 条路径的隔离 PHP 渲染与 P0 哈希比较。 |
| `tests/test_backend_p1_js.cjs` | 执行实际菜单交互代码的事件状态回归。 |
