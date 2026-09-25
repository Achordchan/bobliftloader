# 素材与设计来源

日期：2026-09-20。

本次使用内置 Image Gen 工具，没有使用 API / CLI 图像生成。用户授权脱离前几套原型，由实施方确定设计方向并完成前端。`design-references/selected-direction.png` 是本次内部采用的视觉方向，前三轮原型没有被拼接成页面。

## 主站素材

- `assets/images/logo.png`：来自 `https://ikrorwxhjqoplm5m-static.micyjz.com/cloud/lmBpoKqnlnSRkkkqppkojp/4.png`，主站页头原始透明 Logo，显示时通过 CSS 单色反白适配深色背景。
- `assets/images/dumper-source.webp`：来自 `https://ikrorwxhjqoplm5m-static.micyjz.com/cloud/ljBpoKqnlnSRnknrqjqnjp/touming.png`，主站 1 Ton Mini Dumper 配图。仅用于系列展示，没有把同图标为其他型号。
- 产品范围与邮箱来源：`https://www.boblift.com/Mini-Dumper-pl45958257.html` 与 `https://www.boblift.com/`。未把主站起重机经验或工厂数据套用为新滑移装载机业务事实。

## 生成图像

原始 PNG 保留于 `design-references/source-images/`，原始工具输出也保留于 Codex 生成图片目录。前端使用 `cwebp -q 86` 压缩的 WebP。以下均为概念影像，不代表已确认的实物型号或客户案例。

| 页面文件 | 原生尺寸 | 用途 |
| --- | --- | --- |
| `assets/images/hero-quarry.webp` | 1586 × 992 | 首屏与场地准备场景 |
| `assets/images/loader-studio.webp` | 1422 × 1106 | 装载机系列展示与详情 |
| `assets/images/application-landscape.webp` | 1601 × 983 | 园林场景 |
| `assets/images/application-transport.webp` | 1586 × 992 | 物料运输场景，参考主站翻斗车轮廓 |
| `assets/images/engineering-detail.webp` | 1475 × 1067 | 机械细节编辑图片 |

工具没有按提示词输出精确请求像素尺寸，保留原生尺寸，没有插值放大。页面按照实际图像比例、主体位置进行响应式裁切。

### 生成提示词记录

**首屏**：Premium automotive-style industrial photograph; cobalt blue compact wheeled skid steer with black bucket grounded on dark basalt quarry, dramatic desaturated blue-gray storm sky. Full machine on the right 60–65 percent facing left in three-quarter view; left 40 percent quiet dark negative space for HTML headline. Physically plausible hydraulics, rubber and steel detail. No text, logos, UI, people, watermark or other vehicles. Requested 2400 × 1500.

**产品棚拍**：Premium realistic studio industrial photograph of a cobalt blue compact wheeled skid steer, black bucket and enclosed mesh cab, front-left three-quarter view, complete machine filling about 75 percent. Seamless warm gray concrete studio centered on #e7e8e3, soft overcast lighting and contact shadows. No UI, labels, specs, brand text, watermark or people. Requested 1800 × 1400.

**园林**：Premium documentary-style editorial photo; cobalt blue wheeled skid steer with low black bucket working beside a warm limestone retaining wall, mature greenery, fresh gravel and earth in a country estate garden. Full machine in right-center occupying about 45 percent, gentle natural light, grounded tires and coherent hydraulic connections. No text, logos, UI, watermark or people. Requested 2200 × 1350.

**运输**：Use the supplied main-site mini dumper only as a vehicle-shape reference. Yellow four-wheel front-tip dumper with fully lowered horizontal hopper containing a shallow gravel load; large front wheels, smaller rear wheels, open seat and steering wheel, no cab or loader arms. Full machine center-right on rural construction gravel road, aggregate piles and beige low-rise building behind, soft overcast light. No poster text, UI, people, specs or watermark. Concept operating context, not a documentary customer case. Requested 2000 × 1250.

**机械细节**：Extreme cinematic editorial close-up of cobalt blue loader lift arm, brushed steel hydraulic piston, black hose and connected pivot bolts. Tight coherent assembly, tactile wear, low-key navy shadows and narrow grazing natural light, selective focus. No whole vehicle, labels, fake specifications, diagrams, floating parts, people, logos or watermark. Requested 1800 × 1300.

## 车间实拍（2026-09-24）

来自用户提供的 `~/Desktop/工作/滑移装载机(1)/车间生产/`（厂1、厂2，原图 6960 × 4640）。用户确认：两处都是徐州工厂的**滑移装载机**生产车间；翻斗车的生产照片还没有提供，页面上不要把这些照片说成翻斗车生产线。转 WebP q72，保留原比例 3:2：

| 页面文件 | 原图 | 用途 |
| --- | --- | --- |
| `assets/images/about/hall-2560.webp`、`hall-1200.webp` | 厂2 IMG_4909 | 关于页首屏：成品车间 |
| `assets/images/about/paint-1600.webp`、`-900` | 厂2 IMG_4770 | 装载机流程 03 按单喷涂（蓝色车架） |
| `assets/images/about/people-2560.webp`、`-1600`、`-900` | 厂1 IMG_4070 | 首页“People behind the machine”横幅（工人装配履带装载机） |
| `assets/images/about/weld-1600.webp`、`-900` | 厂1 IMG_4012 | 装载机流程 01 焊接；关于页工厂板块 |
| `assets/images/about/bucket-1600.webp`、`-900` | 厂1 IMG_4018 | 装载机流程 02 铲斗 |
| `assets/images/about/hydraulics-1600.webp`、`-900` | 厂1 IMG_4121 | 流程 04 液压（装载机动臂） |
| `assets/images/about/assembly-1600.webp`、`-900` | 厂2 IMG_4898 | 装载机流程 05 总装；关于页工厂板块主图 |
| `assets/images/about/ready-1600.webp`、`-900` | 厂2 IMG_4920 | 装载机流程 06 待发货；装载机页颜色卡片 |

用户确认（2026-09-24）：照片均摄于徐州工厂，车间里各种颜色的机器是按订单定制的涂装。页面据此写“our factory in Xuzhou”和“painted to order”。`ready-900.webp` 同时用在滑移装载机分类页的“Painted to order”卡片。

## 字体、图标

- Barlow Condensed：600、700；Manrope：400、500、600、700。来自 Google Fonts 官方分发，SIL Open Font License；许可文件保存在 `assets/fonts/`。
- 图标：Phosphor Icons Core 官方仓库 `https://github.com/phosphor-icons/core` 的 regular 图标。未手绘 SVG；使用源文件 `<img>` 引用。MIT License 保存在 `assets/icons/LICENSE`。
- 页面没有从外部 CDN 加载字体、脚本或图标。
