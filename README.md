# BOB-LIFT Compact Equipment · 多页面 WordPress 网站

同一套原生 PHP 模板同时支持独立前端预览与 WordPress 运行，保留深蓝、米白和大字号排版，并使用真实产品素材。

## 当前站点

共 33 个独立页面入口：

- 首页 `/`。
- 公司介绍 `/about/`，明确事业站归属及主站链接。
- 产品中心 `/products/`，滑移装载机 / 迷你翻斗车分类页。
- 18 个 `/product/{slug}/` 详情：8 款滑移装载机、10 款迷你翻斗车（含履带翻斗车和履带搅拌车）。装载机型号写作 BL + T（履带）/ S（轮式）+ 数字，对照见 `/Users/a1234/Desktop/工作/滑移装载机(1)/型号对照说明.md`。
- 应用中心 `/applications/` 及 3 个应用详情。按门宽 / 载荷挑型号在系列页的“系列介绍”图里，图上的标记可以拖动。
- 新闻 `/news/` 及 3 篇有来源的公司文章。
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

## 安装到 WordPress

1. 运行 `python3 scripts/package-theme.py` 得到 `dist/bobliftloader-theme.zip`。
2. 在 WordPress 的“外观 → 主题 → 安装主题”上传 ZIP 并启用。
3. 在“工具 → BOB-LIFT 站点初始化”明确执行导入。
4. 初始化补齐缺失的 18 个产品、3 篇新闻和 8 个页面，配置静态首页；已有同路径内容不覆盖，空固定链接配置才会改为文章名形式。
5. 在“设备产品”和“企业新闻”管理标题、正文、摘要、特色图片与来源；关于页正文可通过页面编辑器替换。

编辑产品的 `Rated load | 1,000 kg` 参数时，载荷筛选值自动同步，避免展示参数与筛选字段各自维护。未确认的定量参数不要根据型号名称推断。

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

另外对 PHP 文件执行 `php -l`，对四个 JS 模块执行 `node --check`。WordPress Blueprint 会验证初始化幂等、产品保存 / 读出、新闻正文编辑读出、权限 / nonce 拒绝和草稿隐藏，最后恢复测试数据；仅在隔离实例中移除 WordPress 自带示例内容。

详见 `design-qa.md`、`docs/design-2k.md`、`docs/qa-2k/`、`docs/qa-multipage/`、`docs/content-verification.md` 和 `docs/file-changes.md`。

## 边界

- 当前未部署正式域名，预览保持 noindex；WordPress 收录开关由站点设置控制。
- 公司 / 产品资料来自用户指定的两个官网。装载机和两款履带翻斗车的定量参数来自供应商参数表（`滑移装载机参数表.xlsx`），见 `docs/content-verification.md`。
- 视觉以 2560 × 1440 为设计基准、其余分辨率自适应：流体 token、2240px 内容框、全出血首屏、双机展台、三联应用图、翻斗车对比表、详情页直达联系与常驻侧栏，详见 `docs/design-2k.md`。产品详情保留官网实拍，场景图标为示意。
- 未验证生产 MySQL、邮件实际收件、第三方 SEO 插件及 Google 实际收录。
- 本次运行验证环境为 WordPress 7.1.1 / PHP 8.3.33；没有宣称完成其他版本、浏览器或生产环境的验证。
