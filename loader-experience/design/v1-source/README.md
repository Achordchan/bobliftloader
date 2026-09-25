# BOB-LIFT 360 / 460 网页动效样片

独立静态页面，不改动原 WordPress 网站、已有展示页或 Blender 工程。使用实际 Blender 渲染的连续图像，按滚动进度呈现镜头环绕和细节靠近。

## 查看

本地地址：`http://127.0.0.1:4175/`，当前已启动服务。服务关闭后，在项目根目录执行：

```sh
python3 -m http.server 4175 --bind 127.0.0.1 --directory loader-experience
```

需要HTTP服务，不能通过file协议双击运行，因为序列帧通过fetch加载。

- 滚动浏览：整机概览 → 履带底盘 → 操纵细节 → 回到整机。
- “播放演示”自动推进，再次点击暂停；手动滚轮、触摸或方向键停止自动推进。
- 底部章节可直接跳转，也可以返回首屏。
- 最后一章可打开四张用户产品实拍，切换分类；Esc关闭并恢复按钮焦点。
- 系统“减少动态效果”开启时，改为静态章节视角，播放按钮变为“逐段查看”。

## 素材与边界

动画来自修复踏板后的 `../loader-cad/loader-cad-restored.blend`，没有使用概念生成图替代产品动画。120帧，1080×1080，Cycles/Metal，20采样与降噪，透明背景及实际渲染阴影。WebP序列14657194字节，约14.0MiB。四张照片来自用户主图目录。

首帧和章节关键帧优先，最多四路下载、两路解码；桌面缓存16张解码图，手机缓存10张，其余以压缩数据保留。失败资源由用户点击重试，不进行无限轮询。

这是镜头、滚动与网页观感样片，不是最终产品精度验收。模型的估算部件及CAD/实物差异仍保留；没有编造发动机参数。未部署公网，未验证真实iPhone/Safari、低端设备或移动网络，移动端使用Chromium视口模拟。本次较低采样序列放大后可能有柔化和细部噪声。

## 文件职责

| 文件 | 用途 |
| --- | --- |
| `index.html` | 四章节、导航、加载状态与实拍弹窗 |
| `styles.css` | 独立页面桌面版式和公共组件，使用exp前缀 |
| `responsive.css` | 手机、窄屏、短屏及减少动态效果样式 |
| `gallery.css` | 实拍弹窗样式 |
| `app.js` | 滚动、文字切换、播放暂停、章节和弹窗交互 |
| `sequence.js` | 下载、重试、解码、有限缓存和画布绘制 |
| `assets/sequence/0000.webp`–`0119.webp` | 120张实际渲染帧 |
| `assets/photos/` | 四张用户实拍的压缩版本 |
| `assets/vendor/` | GSAP 3.13.0和ScrollTrigger，保留原版权及许可头 |
| `scripts/render_sequence.py` | 渲染连续镜头，不覆盖Blender工程 |
| `scripts/encode_frame.py` | 转换WebP，通过临时文件原子替换避免读取半文件 |
| `scripts/verify_assets.py` | 验证帧数、尺寸、透明通道、重复帧与本地依赖 |
| `design/concept.png` | 版式概念，不是网页产品素材 |
| `design/spec.md` | 版式、文案和交互约束 |
| `design/rendered-frames/` | 原始PNG序列，可用于重新编码 |
| `design/render-manifest.json` | 渲染参数与镜头关键点 |
| `design/asset-validation.json` | 素材校验记录 |
| `design/fidelity.md` | 浏览器与概念对照记录 |
| `preview.png` | 最终网页首屏截图 |

## 检查和重渲染

```sh
node --check loader-experience/app.js
node --check loader-experience/sequence.js
python3 -m py_compile loader-experience/scripts/*.py
loader-cad/.venv/bin/python loader-experience/scripts/verify_assets.py

/Applications/Blender.app/Contents/MacOS/Blender \
  -b loader-cad/loader-cad-restored.blend --python-exit-code 1 \
  --python loader-experience/scripts/render_sequence.py
```

渲染脚本跳过已有PNG/WebP，可断点续作。修改镜头或材质后应先另存旧帧再重渲染，避免混入不同版本。没有对未验收的机械机构制作动作。
