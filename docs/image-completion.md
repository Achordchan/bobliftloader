# 图片补全与大屏横幅修复

日期：2026-09-20。

## 图片工具修复

- `/Users/a1234/.codex/skills/codex-image2/src/image_gen.go`：上传前读取真实图片头，设置 image/png、image/jpeg 或 image/webp，保持原始图片字节不变；错误信息保留脱敏后的服务端说明。
- `/Users/a1234/.codex/skills/codex-image2/src/image_gen_test.go`：覆盖 PNG/JPEG/WebP、非图片拒绝、错误详情保留与密钥脱敏。
- `/Users/a1234/.codex/skills/codex-image2/bin/codex-image2-darwin-arm64`：重新编译当前 Mac 使用的程序；launcher、模型和凭据配置没有改变。
- 源码和原二进制备份：`/Users/a1234/.codex/skills/codex-image2/.backups/20260920-142526/`。
- 其他平台二进制未重新编译。

## 交付素材

均使用 codex-image2-local、模型 gpt-image-2.5-flare、quality=high，通过真实官网机型图片编辑生成。产品详情图库仍保留原始实拍。

| 发布图片 | 实际像素 | 使用位置 |
| --- | --- | --- |
| `assets/images/hero-banner-wide.webp` | 1942 × 809（约 2.4:1） | 大于 1000px 的桌面首页横幅 |
| `assets/images/hero-real-loader.webp` | 1536 × 1024 | 手机 / 平板首页、场地准备示意 |
| `assets/images/application-real-landscape.webp` | 1536 × 1024 | 园林场景 |
| `assets/images/application-real-transport.webp` | 1536 × 1024 | 翻斗车运输场景 |

PNG 原图在 `design-references/generated-scenes/`；完整提示词在 `docs/image-prompts/`。生成场景是应用示意，不冒充实际客户现场。

曾请求 3840 × 2160，但接口实际返回 1672 × 941；该研究稿没有作为原生 4K 图片发布。最终桌面横幅请求 2400 × 1000，实际返回 1942 × 809。报告以文件真实尺寸为准，没有插值放大后冒充高分辨率。

## 网站文件改动

- `index.php`：桌面 / 小屏按媒体条件分别预加载横幅和小屏构图。
- `templates/home.php`：使用 picture 选择桌面横幅或小屏图片；更新图像说明和场景标识。
- `inc/content.php`：接入园林、场地准备与运输三类场景。
- `templates/pages/application.php`：标明场景为基于实拍制作的应用示意。
- `assets/css/base.css`：定义统一 1920px 最大内容框。
- `assets/css/components.css`：导航与正文使用相同的大屏对齐边界。
- `assets/css/home.css`：产品展台统一使用公共内容宽度。
- `assets/css/pages.css`：约束横幅、公司和应用大图；桌面横幅按 2.4:1 槽位显示，大屏最大 1920 × 800；调整大屏正文可读性。
- `assets/css/page-responsive.css`：独立处理手机、平板构图，避免为大屏修复而破坏小屏显示。
- `screenshot.png`、`dist/bobliftloader-theme.zip`：随最终前端重新生成。
- `README.md`、`docs/content-verification.md`、`design-qa.md`：更新已解决的生图故障和当前图片使用状态。

## 2K / 4K 原因与修复证据

原实现同时使用全宽图片、1920px 正文以及贴边导航；4K 图片被强制覆盖到 3825 × 870 的区域，放大后截断机器。

最终：

- 2560 × 1440 CSS viewport：媒体框 1920 × 800，Logo 与标题左边界均为 400.5px。
- 3840 × 2160 CSS viewport：媒体框 1920 × 800，Logo 与标题左边界均为 1040.5px。
- 两种视口实际加载的是 1942 × 809 的宽屏横幅，图片与槽位比例一致，没有靠大幅裁切放入横幅。
- 公司、目录、产品详情、新闻、联系、应用页在 2K / 4K 的 12 个组合没有横向溢出，图像宽度受控。
- 手机仍使用单独的 3:2 构图，并将文字与图片分区；平板限制首屏高度。

证据：`docs/qa-images/banner-checks.json`、`widescreen-layout.json`、各视口截图。高分辨率截图工具存在显示密度差异，尺寸与对齐以同时采集的浏览器 CSS 边界验证；不宣称已在所有物理显示器上完成测试。

## 验证与边界

- `go test image_gen.go image_gen_test.go` 通过；真实图片请求成功，原来的 MIME 400 已消失。
- `php -l` 检查 index.php、home.php、application.php、content.php 通过。
- 对 WordPress 预览执行 `python3 tests/test_site.py http://127.0.0.1:9402`，7 项测试通过。
- 检查桌面、平板、手机图片布局以及 2K / 4K；生产域名发布未执行。
- 未添加 Mock、假参数或假提交逻辑；被弃用的研究稿不进入发布资源。
- 生成图仍可能存在细微机械细节偏差，不能替代实拍和技术资料；图库与产品参数保持来源数据。
