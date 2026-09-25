# 本次逐文件改动

## 入口、路由与 SEO

| 文件 | 改动 |
| --- | --- |
| `index.php` | 从单页入口改为共享文档外壳；按明确路由加载页面；输出每页 SEO；支持 WordPress hooks |
| `router.php` | 独立预览路由；只开放发布资源，源码 / 测试路径返回 404 |
| `style.css` | WordPress 主题声明 |
| `functions.php` | 注册主题、内容类型、管理字段和初始化工具 |
| `inc/bootstrap.php` | 共用加载入口 |
| `inc/helpers.php` | URL / 资源、数据读取、转义、卡片、面包屑、询盘链接帮助函数 |
| `inc/routes.php` | 28 个页面入口、真实 404、预览 Sitemap / robots；支持 WordPress 标准内容页 |
| `inc/seo.php` | title / description / canonical / OG / JSON-LD；将本站 publisher 关联到主公司 |

## 内容与 WordPress

| 文件 | 改动 |
| --- | --- |
| `inc/content.php` | 首页分类与应用图改为真实产品；统一资源路径 |
| `inc/data/products.json` | 5 款装载机、8 款迷你翻斗车的真实记录、图片、来源和已核实资料 |
| `inc/data/news.json` | 广交会 2026 产品新闻，及 3 篇有原始日期与来源的集团新闻摘要 |
| `inc/wordpress.php` | 产品 / 新闻内容类型；从已发布 WordPress 内容读取展示数据 |
| `inc/wp-admin.php` | 中文产品 / 新闻字段；nonce、权限、清洗；载荷筛选值从参数同步 |
| `inc/wp-import.php` | 显式、幂等的初始导入；不覆盖已有同路径内容 |

## 页面与共享模板

| 文件 | 改动 |
| --- | --- |
| `templates/header.php` | 导航改为真实多页面链接及当前页状态 |
| `templates/footer.php` | 完整导航、主公司法定名称、主站链接、电话和独立隐私页 |
| `templates/home.php` | 产品入口跳转分类页；公司 / 新闻入口；移除错误概念机和旧详情弹窗入口 |
| `templates/dialogs.php` | 仅保留询盘核对弹窗，删除旧设备 / 隐私弹窗 |
| `templates/pages/about.php` | 2026-09-24 重做：公司信息条（徐州 / 37,000 m² / 200+ / ISO 9001）、与集团的关系（结构树，boblift.com 只出现这一处）、“徐州工厂”板块（三图拼贴，链到装载机生产流程）、证书；产品内容和生产流程移到分类页；支持 WP 正文覆盖关系段落 |
| `assets/css/about.css` | 关于页专用样式，只在 `/about/` 加载 |
| `templates/partials/range-story.php` | 分类页“系列介绍”板块：用途、参数图（装载机机身宽度 / 翻斗车额定载重）、按单配置；装载机页另有颜色定制卡片 |
| `assets/js/making.js` | 装载机分类页生产流程的步骤高亮；窗口过矮时按底边吸附步骤列表 |
| `templates/partials/making-story.php` | 装载机生产流程（徐州工厂 6 步），只在滑移装载机分类页显示 |
| `templates/pages/products.php` | 总目录 / 分类、筛选标签（带每项数量）、结果与空状态；输出范围内全部型号，不符合的加 `hidden`；搜索框移到顶部，带 `?q=` 进来时显示为可清除的搜索词 |
| `assets/js/catalog.js` | 筛选即时生效：点标签时就地过滤、更新各标签数量和网址；无脚本时表单照常提交，由 PHP 过滤 |
| `templates/partials/site-search.php` | 顶部搜索面板和搜索索引（型号、系列、应用、页面、新闻；装载机附旧写法 BL-390 / BL-T360） |
| `assets/js/search.js` | 顶部搜索：按钮、`/`、Ctrl/⌘+K 打开，边输入边出结果，方向键选择、回车打开、Esc 关闭；旧型号给出说明 |
| `templates/partials/section-nav.php`、`assets/js/section-nav.js` | 分类页区块导航：分类标签滚出屏幕后吸顶在主导航下方，高亮当前区块，顶边显示阅读进度，右侧“Get a quote”带上设备类别 |
| `templates/partials/product-card.php` | 卡片瘦身：4:3 图片，标题只写型号，下面一行写名称其余部分，三项关键参数；图片链接不再单独占一个 Tab 位置 |
| `assets/icons/magnifying-glass.svg` | Phosphor regular 放大镜图标，与其他图标同源 |
| `templates/pages/product.php` | 独立详情、5 张装载机图库、真实参数 / 结构信息、来源和相关推荐 |
| `templates/pages/news.php` | 新闻列表及分类筛选 |
| `templates/pages/article.php` | 新闻正文、日期、图片说明、原始来源；读出 WP 编辑器正文 |
| `templates/pages/contact.php` | 公司联系信息、跨页型号 / 应用带入、共用询盘表单 |
| `templates/pages/applications.php` | 3 类应用目录 |
| `templates/pages/application.php` | 应用说明、需求整理、对应产品入口 |
| `templates/pages/privacy.php` | 对当前实际信息处理流程的说明 |
| `templates/pages/404.php` | 404 页面与返回入口 |
| `templates/pages/page.php` | WordPress 新建标准内容页的通用模板 |
| `templates/partials/inquiry-form.php` | 首页 / 联系页复用同一表单，保留选中型号 |
| `templates/partials/product-card.php` | 统一真实产品卡片，无图时显示真实空状态而不借用其他型号 |
| `templates/partials/news-card.php` | 统一新闻卡片、日期和来源图 |
| `templates/partials/page-cta.php` | 内页询盘入口 |

## 样式、交互与资源

| 文件 | 改动 |
| --- | --- |
| `assets/css/components.css` | 清理旧设备 / 隐私弹窗样式 |
| `assets/css/responsive.css` | 清理旧弹窗的响应式规则，保留当前共用控件 |
| `assets/css/pages.css` | 多页面设计、产品图库、文章和公司排版、实际产品首页构图 |
| `assets/css/page-responsive.css` | 新页面的手机 / 平板适配 |
| `assets/js/equipment.js` | 删除旧详情弹窗逻辑，保留首页切换并新增独立产品图库 |
| `assets/js/dialogs.js` | 删除旧隐私弹窗绑定，保留共用询盘焦点管理 |
| `assets/js/inquiry.js` | 可选表单初始化；型号跟随正确品类进入核对和邮件 |
| `assets/images/products/` | 下载并压缩各型号真实图片：25 张装载机图、8 张翻斗车图 |
| `assets/images/company/` / `assets/images/news/` | 主站真实公司图与对应新闻素材 |
| `design-references/retired-concept-assets/` | 原先不对应实际产品的概念图归档，发布目录不再使用 |
| `screenshot.png` | 当前 WordPress 首页的主题预览图 |

`assets/css/base.css`、`assets/css/home.css`、`assets/js/main.js` 沿用原有公共实现；图片资源和页面逻辑在明确作用域内扩展，没有新增前端运行时依赖。

## 验证与交付

| 文件 | 改动 |
| --- | --- |
| `tests/test_site.py` | 28 页面、SEO、实际产品资料、过滤、跨页询盘、转义、404 和资源检查 |
| `tests/wp-smoke.php` | WordPress 初始化、编辑读出、nonce / 权限、草稿隐藏及测试恢复 |
| `tests/wordpress-blueprint.json` | 隔离 WordPress 验证实例配置 |
| `scripts/package-theme.py` | 只打包主题代码与发布资源，排除测试、研究和凭据 |
| `readme.txt` | 主题安装 / 初始化说明 |
| `README.md` | 多页面结构、预览、WordPress 运行和边界 |
| `docs/content-verification.md` | 公司 / 产品 / 新闻来源、SEO 和生成图失败情况 |
| `docs/multipage-source-assets.json` | 下载素材的来源记录 |
| `docs/image-prompts/` | codex-image2 正式提示词；失败请求没有伪造成图片产物 |
| `docs/qa-multipage/` | 桌面、手机、WordPress、布局检查证据 |
| `design-qa.md` | 当前多页面验收报告，上一版存于 `docs/archive/home-v1-qa.md` |
| `dist/bobliftloader-theme.zip` | 可安装 WordPress 主题包 |
