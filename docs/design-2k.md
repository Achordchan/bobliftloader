# 2K 优先的视觉重构

日期：2026-09-20。

## 目标

设计基准从 1440 改为 2560 × 1440，其余分辨率向下自适应。此前的版式是按 1440 设计再"放到"2K 上：主视觉锁在 1920px 框内两侧留空、正文 15px 与大量 8–11px 标签、六个 CSS 文件里 254 处硬编码字号只靠 `≥1920` 的零星补丁。

## 做法

### 流体 token 系统（`assets/css/base.css`）

- 字号 `--fs-micro … --fs-hero` 全部用 `clamp()`，在 2560 达到上限（正文 19px、标签 14px、H2 78px、主标题 216px），在笔记本上保留可读下限。
- 间距 `--sp-section` / `--sp-block`、`--gutter`、`--header-height` 同样流体化。
- 内容框 `--frame-width` 由 1920 改为 2240，2K 下两侧各留约 250px。
- 六个 CSS 文件中桌面规则的固定字号全部换成 token；原 `clamp()` 标题改为"1440 结果不变、2560 放大 1.32 倍"的线性表达式；区块级 padding / gap 同理放大 1.3 倍。旧的 `≥1700` / `≥1920` 补丁块删除。
- 手机 / 平板断点内小于 12px 的字号统一抬到 11–13px。

### 首页三个板块（`templates/home.php`、`assets/css/home.css`）

- **首屏**：`hero-banner-2k.webp` 全出血 `object-fit: cover`，左侧叠加深蓝渐变压标题，三行 216px 标题；底部改为两张真实入口卡（滑移装载机 5 款、迷你翻斗车 8 款 · 1–5 t），型号数与载荷范围由产品数据计算。
- **The Range**：去掉 tab，两台机型并排棚拍展台（`range-loader-studio.webp`、`range-dumper-studio.webp`），下方各三格事实（型号数 / 载重或行走方式 / 斗容或操控）。
- **Applications**：改为三张 4:5 竖幅照片横排（`app-landscaping.webp`、`app-site-prep.webp`、`app-transport.webp`），悬停展开说明文字。

### 目录、详情、对比表、侧栏（`assets/css/pages.css`）

- 目录 ≥1500 宽 4 列，卡片固定三格参数（载重 / 斗容 / 功率）；装载机无数值参数时显示"Spec sheet: On request"，不再用定性词充数。
- 新增 `templates/partials/compare-table.php`：8 款翻斗车 7 项已发布参数横向对比，首列吸附，每列可直接进详情或询价；显示在 `/products/` 和 `/products/mini-dumpers/`（无筛选时）。
- 详情页 55/45 分栏，缩略图竖排在主图左侧，关键参数 60px，"Request a quote" 下直接给 WhatsApp（带机型预填文案）与邮箱两张入口卡；分区导航条改为吸顶。
- 新增 `templates/partials/side-contact.php`：全站常驻侧边联系条（WhatsApp / 邮件 / 询盘 / 回顶），≤800 宽收成底部条。

### 手机端

- 首屏改为背景图 + 深色渐变、文字覆盖，标题 `clamp(56px,16.5vw,84px)`，修复原先 390 宽下 "CAPABILITY." 被裁切的问题。
- 真实浏览器 375 宽复验：`scrollWidth === 375`，没有元素超出视口。

## 图片

| 文件 | 实际像素 | 用途 |
| --- | --- | --- |
| `assets/images/hero-banner-2k.webp` | 1942 × 809 | 桌面首屏（≥801px） |
| `assets/images/hero-real-loader.webp` | 1536 × 1024 | 手机首屏 |
| `assets/images/range-loader-studio.webp` / `range-dumper-studio.webp` | 1374 × 1145 | 首页展台、首屏入口卡 |
| `assets/images/app-landscaping.webp` / `app-site-prep.webp` / `app-transport.webp` | 1586 × 992 | 首页应用三联、应用页 |

生成接口返回的像素低于请求值（首屏要求 3840 × 1600，实际 1942 × 809），2K 全出血时约放大 1.3 倍，场景类照片可接受；若后续拿到更高分辨率原图，直接替换同名文件即可。旧的 `hero-banner-wide.webp`、`application-real-*.webp` 已删除。

产品实拍图仍为 800–1000px 原图，2K 下卡片显示约 500px（Retina 需 1000px），是当前最明显的清晰度短板，需向工厂索取 ≥2000px 原图并沿用现有文件名替换。

## 验证

- `python3 tests/test_site.py http://127.0.0.1:4173`：7 项通过。
- 全部 PHP `php -l`、4 个 JS `node --check` 通过。
- 截图：`docs/qa-2k/`（首页 2560 首屏与整页、翻斗车目录 2560、FY-30 / BL-1000 详情 2560、首页 1440）。
- 未验证：WordPress Playground 实例本轮未重跑；真实移动设备与 Safari 未测。

## About 页内容补充（同日下午）

素材全部来自 www.boblift.com（公司自有站点）：

- 车间航拍 `assets/images/company/boblift-workshop-aerial.webp`（原图 4032 × 2268，转 2560 宽 WebP；另有 1200 宽手机版），用于 About 页全出血大图和首页"One company"板块，替换原 800px 通用车间图。
- 五张证书 `assets/images/company/cert-*.webp`（ISO 9001、CE ×2、EAC、随车吊型式试验制造许可证），452 × 640。
- 事实条：37,000 m² 厂区、200+ 国家与地区、ISO 9001 / CE / EAC、与意大利和瑞典企业技术合作；正文按主站 About 页转写。主站首页写"20 多年"、起重机站写"14 年"，两者矛盾，页面上未使用年限数字。
- 品牌关系块改为三站并列：boblift.com / bobliftcrane.com / bobliftloader.com（YOU ARE HERE）。
- 三条价值点改为买家关心的内容：自有工厂生产、出口文件齐全、同一团队服务整个车队。
- WordPress 下仍可通过页面编辑器替换正文段落，事实条和证书由模板维护。

## 2026-09-24：About 页重做

用户要求讲清楚本站和 boblift.com 的关系，并把重点放在装载设备上。新页面从上到下：

1. 首屏：三行大标题“SMALL MACHINES. / BACKED BY / BOB-LIFT.”，下面是成品车间全出血大图。滚动时图片从页边距展开到满宽（CSS view timeline，不支持的浏览器直接显示满宽）。图片底部压一条深色“铭牌”：装载机 / 翻斗车款数、最窄机身、额定载荷，数据实时来自 `products.json`。
2. 01 How we fit：深色区，结构树。根节点是集团（航拍、法定名称、三项事实、boblift.com 链接），下分两支：起重设备用描边字，表示不是本站重点；紧凑设备用蓝底，标 YOU ARE HERE，并链到两个分类页。连线在滚动进入时依次画出。下面三条说明这层关系对买家意味着什么。
3. 02 What we build：两个系列的大字索引行、机身宽度标尺（机身实心条、铲斗细线、1 米虚线）、按单配置（T/S、H/C、发动机品牌）。
4. 03 How they are made：左侧步骤索引吸顶，并随滚动高亮；右侧六张车间照片，首尾两张满宽，中间四张错位成两列。
5. 04 Paperwork：集团证书四张。之后是通用 CTA。

样式在 `assets/css/about.css`，只在 `/about/` 加载；旧关于页的样式已从 `pages.css` 和 `page-responsive.css` 删除。

同日调整：产品相关的三块（系列索引、宽度标尺、按单配置）从 About 页移出，About 页改为四段：首屏（公司信息条）→ 01 关系 → 02 车间流程 → 03 证书。分类页在产品列表和对比表之间新增“系列介绍”板块（`range-story.php`，样式在 `pages.css` 的 `.bl-range-story` / `.bl-ruler`）。
