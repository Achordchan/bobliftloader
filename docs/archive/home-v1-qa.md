# 首页前端视觉与交互验收

日期：2026-09-20。范围：本地首页前端，不包含 WordPress 后台或生产服务。

## 设计来源与对照口径

- Source visual truth：`design-references/selected-direction.png`，原生 971 × 1619 像素。该图是用户授权自由设计后由本次实施采用的视觉方向，并非用户此前需要选择的三个原型。
- 实现：`http://127.0.0.1:4173`。
- 桌面 CSS viewport：1440 × 900；滚动条占用 15px，内容宽 1425px。
- 最终首屏：`docs/qa/desktop-hero-native.png`，工具输出 1425 × 891；截图工具将原生视口按密度进行标准化。比较板只等比缩放，不拉伸图片或文字。
- 首屏对照：`docs/qa/hero-comparison.png`，左侧为参考顶部 971 × 489 裁切、右侧为实际首屏，均按宽 720px 等比显示。首屏高度从参考概念适配为当前完整视口，是主动布局决定。
- 全局对照：`docs/qa/overview-comparison.png`，参考全图和实际四个主要分区截图放在同一画面比较。
- 分区截图：`desktop-equipment-final.png`、`desktop-applications-final.png`、`desktop-approach-final.png`、`desktop-contact-final.png`，位于 `docs/qa/`。
- 聚焦细节：产品弹窗 `equipment-dialog.png`、手机询盘核对 `mobile-inquiry-review.png`、手机应用 `mobile-applications.png`。
- 手机：390 × 844 viewport，`mobile-hero.png`。平板：768 × 1024 viewport，`tablet-equipment-final.png`。
- 整页自动拼接出现工具级密度 / 拼接失真；失真文件已移除。验收依据为正常渲染的逐区原生视口截图与实际 DOM 位置，不使用失真长图声称视觉通过。

参考图用于视觉方向，不要求逐像素克隆。根据本次用户授权，应用区改为“列表 + 大图”的可切换结构，新增机械细节 / 服务介绍与完整询盘表单；文案改成不依赖未确认规格、认证或工厂数据的内容。这些属于有意实现，不计为偏差。

## 检查发现与修复历史

1. **P1 / 回顶失效**：页头 sticky，直接对页头调用 `scrollIntoView` 不能可靠返回文档顶部。修改为 `window.scrollTo({top:0})`，保留键盘焦点逻辑。复验读取 `scrollY = 0`、首屏 top = 68；证据 `mobile-back-to-top-before.png` 与 `mobile-hero.png`。已修复。
2. **P2 / 正文字号过小**：首版部分正文 11–12px，在大标题旁阅读困难。主要正文提升到 13–14px，导航提升到 13px，表单标签提升到 12px；手机输入保持 16px。复验见桌面分区及手机截图。已修复。
3. **P2 / 平板产品裁切**：768px 双栏图像使用 cover 时丢失铲斗 / 车尾。平板断点改为 contain，手机独立单栏保持覆盖式展示。证据 `tablet-equipment.png` 与 `tablet-equipment-final.png`。复验机器完整可见，操作区域无溢出。已修复。
4. **P3 / 手机换行拼接**：隐藏 br 后句号与下一句之间无空格，已在 HTML 中保留空格。属于文案呈现修正。

## 五项视觉检查

- 字体与层级：Barlow Condensed 600/700 大标题、Manrope 正文与控件，字体全部本地加载。检查标题断行、按钮和表单阅读性；没有关键标题截断。细小分类标签为次要视觉层级，不承担核心操作说明。
- 间距与布局：统一侧边距、深浅分区、59/41 桌面产品比例、手机单栏；导航、图片标签、表单和弹窗对齐已检查。320 / 390 / 768 / 1024 / 1440px 均无横向溢出，结果在 `responsive-checks.json`。
- 配色：深蓝 #081c2b、米白 #f1f2ed、品牌强调蓝 #215bdb；正文与次要文本分别设置颜色。没有将灰色 placeholder 当作唯一字段标签。
- 图像：Logo 使用主站透明原图；翻斗车使用主站图；5 张新影像按统一风格制作后压缩成 WebP。已检查图片加载、裁切、文字遮挡与比例；生成影像没有被当作认证、工厂或客户实拍证明。
- 文案：产品类别、场景、服务和询盘语义相互对应。没有虚构参数、型号、认证、客户证言或统计。询盘核对明确告知尚未发送，邮件动作由用户完成。

## 实际浏览器验证

- 桌面设备切换 → Mini dumpers 详情弹窗 → Discuss this equipment → 询盘预选 Mini dumper，姓名框获得焦点。
- 设备 Tab 使用左右方向键切换，并同步 `aria-selected`、tabIndex、面板隐藏状态。
- 手机菜单展开 → Applications → 菜单自动关闭；Material transport → 对应图像 / 描述 / 询盘产品和项目文字同步。
- 未填写必填字段时无法进入核对弹窗。
- 使用 `qa@example.com` 等临时测试输入核对邮件草稿：收件人为 `info@boblift.com`，产品、姓名、国家、公司和项目内容正确编码。未点击 Open email 或真实发送。
- Escape 关闭核对弹窗，焦点返回 Prepare enquiry。
- 服务折叠组一次展开一项，打开第二项会关闭第一项。
- 5 个屏宽检查，无横向溢出；手机和桌面无关键控件被遮挡。
- 模拟 prefers-reduced-motion: reduce 时停用动画，内容保持可见。完成后恢复系统偏好与浏览器默认视口。
- 最终浏览器 error / warn 日志为空；可见图片均加载成功，站内锚点均有对应目标。
- 已清空所有测试输入，默认设备为 Skid steer loaders，默认应用为 Landscaping。
- Jev 完成两步产品切换 / 详情打开，随后由主代理独立验证；手机菜单辅助执行未继续时，使用原生浏览器接口完成验证。

## 命令与静态验证

- 对 6 个 PHP 文件逐个执行 `php -l`：通过。
- 对 4 个 JS 模块逐个执行 `node --check`：通过。
- Python HTMLParser 提取页面资源和 CSS 字体引用，使用本地直连请求检查 25 个路径：全部 HTTP 200。
- `rg` 检查 console.log/debug、debugger、TODO/FIXME、QA 测试输入、mockData/mockApi：源码无残留。
- 清理未使用的下载字体、图标；原始生成图移入设计参考目录，页面只请求压缩版。

第一次本地 HTTP 资源检查受到系统代理影响；改用仅针对此次本地检查的 `ProxyHandler({})` 后通过，没有更改用户系统代理配置。

## 未执行与剩余边界

- 没有进行真实邮件投递、收件验证或各邮件客户端兼容测试；复制到剪贴板分支完成实现，但未改写用户剪贴板来测试。
- 未在 Safari / Firefox 或真实手机硬件上测试；已通过当前 Chromium 浏览器的响应式检查。
- 未安装 / 接入 WordPress，也未实现后台字段、数据库、服务器询盘或正式部署；这是当前前端任务范围之外的工作。
- 所有 AI 生成产品及应用图仍需实物素材替换 / 确认，不能据此发布实际型号或能力承诺。
- 暂无未解决的 P0 / P1 / P2 前端问题。视觉方向、图库真实性和生产内容仍需用户业务验收。

final result: passed
