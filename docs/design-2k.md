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

## 2026-09-25：应用中心重做，选型并入系列介绍图

范围：`/applications/`、三个应用详情页，以及两个系列页的“系列介绍”图。场景仍是三个，没有新增场景或图片。

**先做后撤的方案**：最初在应用页做了一个独立选型工具（门宽 / 载荷 / 地面三个输入，加一张按比例画的门柱图）。用户指出了两个问题：一是列表页和详情页放了同一个工具；二是挑型号本来是产品页的活，系列页的宽度图（MADE FOR THE TIGHT SPOTS）已经在讲同一件事。所以这个工具已整体删除，不再保留。

- **系列介绍图改成可拖动**（`templates/partials/range-story.php`、`assets/js/ruler.js`，样式在 `pages.css` 的 `.bl-ruler-control`、`.bl-ruler-over`）：
  - 装载机图：原来固定在 1 米的虚线改成“你的最窄通道宽度”，可在 800–1,300 mm 之间拖动，默认 1,000 mm。机身能过的行显示蓝色，过不去的显示深色；机身能过、但铲斗比门宽的，铲斗线变橙，并写明宽出多少。
  - 翻斗车图：新增“每趟载荷”标记，默认不显示；拖动后，额定载荷低于这个值的型号变深色。
  - 两张图下方都有汇总行（比如“5 of 8 loaders fit through 1,000 mm · 1 with the bucket fitted”），还有一个带着数字进入联系表单的链接。
  - 不开 JS 时表单提交，由 PHP 算出同样的结果（参数为 `?opening=`、`?payload=`）。
- **应用列表页**：顶部三行标题；三张场景图全出血并排，鼠标悬停的一张会变宽。每张图上压着这个活最看重的数字，由产品数据实时计算：最窄装载机（含铲斗）930 mm，装载机额定载荷 200–500 kg，翻斗车 0.5–5 t。下面是“询盘前准备的四样信息”。
- **应用详情页**：大图下压参数条，最能决定这个活的一项标蓝；然后是三项询盘前检查。“适合这个活的机型”区块按这个活最看重的指标排序，列出 3 张产品卡：园林绿化按含铲斗的最窄宽度，场地平整按额定载荷从高到低，物料运输按额定载荷从低到高，并链接到系列页的宽度图、载荷图或对比表。最后是另外两个应用的入口和通用 CTA。
- 联系表单支持 `width`、`load`、`ground` 参数，会自动写进留言（只接受合理范围内的数值）。
- 应用页的样式在 `assets/css/applications.css`，只在应用页加载。旧版 `.bl-application-directory`、`.bl-application-wide`、`.bl-application-requirements` 已从 `pages.css` 和 `page-responsive.css` 删除。各应用的检查项已挪到 `inc/content.php`。
- 验证：`tests/test_site.py` 13 项全部通过（新增 `test_range_charts_take_the_visitors_number` 和 `test_applications_describe_the_job_and_point_to_the_range`）。在 1440 宽下截图检查；375 宽下 `scrollWidth` 等于视口宽度。WordPress Playground 本轮没有重跑。
- 注意：物料运输“路线”检查项里写的“轮式 1.5–1.8 m、履带式不到 0.9 m”是手写文案，翻斗车参数变了要同步改。

## 2026-09-25：新闻页重做

- **内容**：新增一篇紧凑设备新闻 `canton-fair-2026`（广交会）。
  - 事实来源：用户提供的易拉宝（小型滑移装载机、8 种快换属具、室外展位 12.0B34，欢迎现场试机）。
  - 届次、日期、展馆出自广交会官方《2026 Attendance Guide for Overseas Buyers》PDF：第 140 届第一期 10 月 15–19 日，工程机械在第一期。
  - 易拉宝上的机器是 BL-T360-H / BL-T460-H 共用的站驾履带机身，现场具体摆哪一款没有确认，文中没有写。
  - 易拉宝原图存为 `assets/images/news/canton-fair-2026-banner.webp`，从中裁出机器部分作封面 `canton-fair-2026-cover.webp`。
- **数据**：`news.json` 每篇新增 `line` 字段：`compact` 是本站紧凑设备新闻，`group` 是母公司的起重机新闻。可选字段有 `event`（活动名称、起止日期、场馆、地址、展位、日期来源）、`poster`、`products`、`products_note`。WordPress 后台的新闻编辑框加了“归属”和“展会 / 活动”字段；保存时保留其他字段。
- **列表页**：统一的单列列表，按时间倒序。每条都是“3:2 图 + 标签·分类·日期 + 标题 + 摘要”。活动新闻多一行：日期 | 展位 | 还有几天开幕（`bl_event_status()`，按北京时间计算）。删掉了分类筛选。
- **文章页**：正文列加右侧栏。
  - 活动新闻：侧栏放“Event details”细线框和易拉宝，正文末尾有到访联系方式。
  - 集团新闻：顶部放原图，侧栏放“About this story”（说明讲的是起重机业务）和原文链接（boblift.com 在正文区只出现一次）。
  - 下方是相关机型卡片和上一篇 / 下一篇导航。活动新闻额外输出 schema.org `Event`。
- **中途弃用的一版**：先做过一版头条大图、深色集团通栏、巨大标题和蓝色展位牌。用户认为是“为了设计而设计”，所以改成现在这版：规整、通用，靠对齐、细线、元信息排版和统一的图片比例来体现设计感，不靠大字号。
- 样式在 `assets/css/news.css`，只在新闻页加载；旧的 `.bl-journal`、`.bl-article-*`、`.bl-original-source` 已从 `pages.css` 和 `page-responsive.css` 删除。首页新闻区仍用 `.bl-news-card`，这次没有改。
- 验证：13 项测试通过（新闻测试覆盖了排序、归属标签、活动信息、Event 结构化数据、集团新闻外链次数）；在 1440 宽下截图检查；375 宽下没有横向溢出。

### 同日调整：栏目、侧栏模块、标题区

- **栏目**（2026-10-12 调整）：Product news（`product-news`）、Exhibition news（`exhibitions`）、Customer stories（`customer-stories`）、Guides（`industry-news`，旧名 Industry news，存储值不变），定义在 `BL_NEWS_CATEGORIES`；没有栏目的文章（集团新闻等）列在 Company news 标签下（`BL_NEWS_SECTIONS`），标签数加起来等于 All。第二排“Machine”只列 Skid steer loaders / Mini dumpers，没写产品的文章只出现在 All。列表页标题下是二级导航（沿用产品页的 `.bl-category-nav`，每项带篇数），没有文章的栏目不显示。栏目页地址是 `?category=` 形式，设为 noindex，未知的栏目值当作“全部”处理。
- **集团新闻**：母公司的三篇起重机新闻按用户要求保留，不归入任何栏目（`category` 为空），只在 All 里出现，标签显示为 “BOB-LIFT group”。广交会一篇归入 Product news。旧的 `Company news`、`Exhibitions` 值一律视为“不归入栏目”。
- **文章标题区**：面包屑 → 蓝色栏目链接（点击进入对应栏目页）→ 标题 → 导语 → 分隔线。
- **正文列**：每篇开头都放主图。易拉宝作为正文插图靠右浮动，插在 `poster.after` 指定的段落处；手机上改为居中通栏。
- **侧栏**：固定三个模块，按顺序排列，没有数据的不显示。
  - Written by：WordPress 下取文章作者的头像、显示名和个人资料里新增的“职位”（`bl_title`），不放联系方式；另有发布日期和更新日期，集团新闻附原文链接。
  - Event details：只有活动新闻才有。
  - Related products：在后台勾选，存为 `products`。
- 删掉了 “About this story” 文案框和文章底部的大产品卡片区。
- **WordPress 后台**：新闻分类改为固定下拉（三个栏目，外加“不归入栏目”），新增相关产品勾选；用户资料页新增“职位”字段。

## 2026-09-26：产品页默认进入滑移装载机

用户确认装载机是主推产品线。

- `/products/` 不再展示“全部设备”，而是 301 跳转到 `/products/skid-steer-loaders/`，旧的筛选链接也一样跳转。唯一例外是头部搜索（`?q=`）：这时仍在 `/products/` 显示两个系列的搜索结果。站点地图已去掉 `/products/`。
- 两个系列页顶部原来的“All / Skid steer loaders / Mini dumpers”小标签，换成了系列切换器（`.bl-family-switch`）：两张卡片，各有机器图、款数、系列名，以及一行关键数字（装载机“Rated 200–500 kg · from 930 mm wide”，翻斗车“Rated 0.5–5 t · tracked and wheeled”），数字取自 `bl_family_ranges()`。装载机卡片更宽，当前系列用蓝色标出。吸顶子导航改为在切换器滚出屏幕后出现。
- 头部 “Equipment” 直接链到装载机页；紧跟其后放了一个下拉面板（`.bl-nav-panel`），列出两个系列。鼠标悬停时显示；键盘 Tab 到 “Equipment” 时显示，再按 Tab 进入面板；手机菜单里显示为缩进的子列表。
- 面包屑去掉了 “Equipment” 一级，因为它会跳转。结构化数据的面包屑改为产品所属的系列页。首页“Explore the range”和 404 页的链接改为装载机页；页脚删掉了 “All equipment”。
- 验证：14 项测试通过（新增 `test_range_opens_on_the_loaders`，覆盖 301 跳转、搜索保留、导航面板、切换器的当前项、页脚和站点地图）；1440 宽截图检查；键盘展开面板实测通过；375 宽手机菜单检查通过。
