# BOB-LIFT Compact Equipment · 多页面 WordPress 网站

同一套原生 PHP 模板同时支持独立前端预览与 WordPress 运行，保留深蓝、米白和大字号排版，并使用真实产品素材。

## 当前站点

共 34 个独立页面入口：

- 首页 `/`。
- 公司介绍 `/about/`，明确事业站归属及主站链接。
- 产品中心 `/products/`，滑移装载机 / 迷你翻斗车分类页。
- 18 个 `/product/{slug}/` 详情：8 款滑移装载机、10 款迷你翻斗车（含履带翻斗车和履带搅拌车）。装载机型号写作 BL + T（履带）/ S（轮式）+ 数字，对照见 `/Users/a1234/Desktop/工作/滑移装载机(1)/型号对照说明.md`。
- 应用中心 `/applications/` 及 3 个应用详情。按门宽 / 载荷挑型号在系列页的“系列介绍”图里，图上的标记可以拖动。
- 采购与售后 `/support/`：采购流程与装柜数、质保与报修、配件、资料下载、常见问题。条款（起订 1 台、T/T、8–30 个工作日、整机质保 12 个月）只在 `inc/support.php` 定义，产品页和 FAQ 都从这里读取。
- 属具在滑移装载机系列页 `#attachments`，随整机报价，不单独成目录；产品视频在详情页图库下方。
- 新闻 `/news/`：产品新闻、应用案例、行业新闻三个栏目；另有 3 篇有来源的集团文章，只在“全部”中显示。
- 联系 `/contact/`、隐私 `/privacy/`。

WordPress 还支持通过标准页面编辑器新建的内容页。未知地址返回 404。

## 本地预览

独立 PHP 预览，无数据库依赖：

```sh
php -S 127.0.0.1:4173 -t . router.php
```

完整 WordPress 隔离验证（Node 20.18+；运行时依赖仅用于开发）：

```sh
npx --yes @wp-playground/cli@latest server --auto-mount="$PWD" --blueprint="$PWD/tests/wordpress-blueprint.json" --port=9402 --workers=1
```

本次预览：PHP `http://127.0.0.1:4173/`；WordPress `http://127.0.0.1:9402/`。已有进程运行时不要重复占用端口。WordPress Playground 使用隔离的 SQLite 临时数据，退出后不作为持久生产数据保存。

## 本地 WordPress 后台

持久的本地 WordPress（MySQL 数据库 `bobliftloader_wp`，中文后台），用来在 `/wp-admin/` 管理产品、新闻和页面。需要 Homebrew 的 `php`、`wp-cli`、`mysql`，并先 `brew services start mysql`。

```sh
scripts/setup-local-wordpress.sh   # 首次安装；重复运行只补缺，不覆盖已有内容
./start.sh                         # 前台运行，默认 http://127.0.0.1:8090/ ，Ctrl+C 停止；./start.sh 8091 换端口
```

- WordPress 装在仓库内的 `.wordpress/`（已忽略，不进 Git）；主题目录是指向本仓库的软链接，改代码刷新即生效。
- 首次安装生成管理员 `bobadmin` 和随机密码，写在 `.wordpress/admin-login.txt`（仅本机可读）。
- 安装后自动启用主题、导入 18 个产品 / 4 篇新闻 / 9 个页面、静态首页、文章名固定链接、上海时区，并关闭搜索引擎收录。
- 访问地址跟随浏览器打开的主机和端口，换端口不需要改数据库。
- 调试日志在 `.wordpress/wp-content/debug.log`。

## 安装到 WordPress

1. 运行 `python3 scripts/package-theme.py` 得到 `dist/bobliftloader-theme.zip`。
2. 在 WordPress 的“外观 → 主题 → 安装主题”上传 ZIP 并启用。
3. 在“工具 → BOB-LIFT 站点初始化”明确执行导入。
4. 初始化补齐缺失的 18 个产品、4 篇新闻和 9 个页面，配置静态首页；已有同路径内容不覆盖，空固定链接配置才会改为文章名形式。
5. 在“设备产品”和“企业新闻”管理标题、正文、摘要、特色图片与来源；关于页正文可通过页面编辑器替换。产品用经典编辑界面，依次是基本信息、规格参数、图片、视频、来源；新闻用块编辑器，栏目、归属、相关产品在右侧栏，展会信息在正文下方。
6. 首页首屏有装载机、迷你翻斗车两张，右上角切换，约 7 秒自动轮换（悬停、聚焦时暂停，点切换或暂停键后停止，减少动画偏好下不轮换）；翻斗车一张在“网站内容 → 首页 → 首屏 · 迷你翻斗车”修改。首页和 Support 页的文字、图片、列表在“网站内容 → 首页 / 采购与售后 Support”修改（含搜索结果标题与描述、采购条款、装柜数量、常见问题）。只保存改过的字段，其余跟随主题默认值（`inc/editable.php`）；“整页恢复默认”可撤销全部修改。采购条款改一处，产品页同步。
7. 产品视频：在产品编辑页的“视频”面板逐个添加（视频、封面、说明，可从媒体库选）。画册：在“网站内容 → 资料下载”从媒体库选择 PDF，留空则使用主题自带的占位画册；能编辑页面的用户都可以改。
8. 后台菜单隐藏了本站不用的“文章”和“评论”，全站评论与 pingback 关闭（已有数据保留）。

编辑产品规格里参数名为 `Rated load`、值为 `1,000 kg` 的一行时，载荷筛选值自动同步，避免展示参数与筛选字段各自维护。未确认的定量参数不要根据型号名称推断。

## 部署到 WordPress.com

线上站托管在 WordPress.com（Business，Atomic）。GitHub 部署连接用「高级」模式，工作流 `.github/workflows/wpcom.yml`，目标目录 `/wp-content/themes/bobliftloader`。工作流只打包主题文件（与 `scripts/package-theme.py` 同一清单，约 28MB），推送 main 自动部署。不要用「简单」模式，它会把整个仓库（600MB+，含设计参考和 3D 源文件）原样放到公开目录。

## 主要交互

- 产品分类、关键词搜索、载荷筛选与空结果反馈。
- 原始产品图库、配置 / 参数表、相关产品推荐。
- 产品型号和应用信息跨页带入联系表单。
- 表单校验 → 核对 → 邮件软件草稿，保留编辑和复制入口。
- 新闻分类、文章正文、来源与公司介绍互链。
- 手机菜单、键盘 Tab 切换、弹窗焦点恢复、减少动画偏好。

询盘当前通过访客自己的邮件软件发送，没有服务器提交、CRM 持久化或“发送成功”模拟状态。

## 验证

```sh
python3 tests/test_site.py http://127.0.0.1:4173
python3 tests/test_site.py http://127.0.0.1:9402
```

另外对 PHP 文件执行 `php -l`，对四个 JS 模块执行 `node --check`，并运行 `php tests/test_dumper_models.php`（翻斗车适用场景里的型号新旧写法都能找到产品）。WordPress Blueprint 会验证初始化幂等、产品保存 / 读出、新闻正文编辑读出、权限 / nonce 拒绝和草稿隐藏，最后恢复测试数据；仅在隔离实例中移除 WordPress 自带示例内容。

详见 `design-qa.md`、`docs/design-2k.md`、`docs/qa-2k/`、`docs/qa-multipage/`、`docs/content-verification.md` 和 `docs/file-changes.md`。

## 边界

- 当前未部署正式域名，预览保持 noindex；WordPress 收录开关由站点设置控制。
- 公司 / 产品资料来自用户指定的两个官网。装载机和两款履带翻斗车的定量参数来自供应商参数表（`滑移装载机参数表.xlsx`），见 `docs/content-verification.md`。
- 视觉以 2560 × 1440 为设计基准、其余分辨率自适应：流体 token、2240px 内容框、全出血首屏、双机展台、三联应用图、翻斗车对比表、详情页直达联系与常驻侧栏，详见 `docs/design-2k.md`。产品详情保留官网实拍，场景图标为示意。
- 未验证生产 MySQL、邮件实际收件、第三方 SEO 插件及 Google 实际收录。
- 本次运行验证环境为 WordPress 7.1.1 / PHP 8.3.33；没有宣称完成其他版本、浏览器或生产环境的验证。
