# Blender 装载机产品渲染

## 打开工程

修复版工程：`loader-product-fixed.blend`。

`loader-product-final.blend` 为修复前版本，原文件完整保留。

在 Blender 中按 `⌘O`，或使用 **File → Open（文件 → 打开）** 选择该文件。也可以在 Finder 中双击工程。

- `loader-product.blend` 是先前提供的初稿，保留用于对照。
- 最终工程为原生可编辑场景，包含网格、曲线、布尔开孔、倒角、材质节点、灯光和 3 台相机。
- 打开后看到的是模型视图。通过 **Render → Render Image（渲染 → 渲染图像）** 查看正式渲染；无需重新建模。
- 已有图片位于 `renders/hero.png`、`renders/detail.png`、`renders/side.png`。
- 环境贴图已打包进最终 `.blend`，单独移动工程也可以继续查看和渲染。
- 场景里的 Text 数据块保存了脚本副本，供查阅；重新生成时使用下述外部脚本命令。

## 这版制作了什么

以用户提供的整机、侧面和局部特写为参考，原生制作车架、折弯机罩、真实镂空蜂窝网、玻璃灯罩、操纵杆防尘套、双侧三角履带、轮组、举升臂、液压缸、快接头及卷弯钢制铲斗。

材质区分烤漆、粉末喷涂、橡胶、镀锌件、抛光活塞杆和玻璃。使用 Cycles、Metal GPU、摄影棚环境光及面光源进行路径追踪渲染。最大采样 160，自适应噪声阈值 0.018，开启降噪；正式图片为 1800×1600。

## 文件职责

| 文件 | 职责 |
| --- | --- |
| `scripts/materials.py` | 原生材质节点和微表面纹理 |
| `scripts/geometry.py` | 网格、板件、圆柱、软管、紧固件和布尔切孔工具 |
| `scripts/body.py` | 车架、机罩、格栅、灯具、操纵台与后部踏板 |
| `scripts/tracks.py` | 履带带体、胎纹、轮组和侧护板 |
| `scripts/attachment.py` | 举升臂、液压结构、快接头、快换架和铲斗 |
| `scripts/build.py` | 组装模型、摄影棚和相机，保存工程并渲染 |
| `scripts/render.py` | 从已保存的工程切换相机并出图，不改写工程 |
| `scripts/validate.py` | 检查空网格、非法坐标、贴图打包和最终场景设置 |
| `assets/` | 环境贴图及原作者、许可说明 |
| `references/` | 本次可从临时目录读取到的用户产品参考图片 |
| `renders/` | 实际 Cycles 渲染图 |
| `logs/` | 建模、渲染与验证记录 |
| `previous-versions.sha256.json` | 两个 HTML 版本的原始文件校验清单 |

## 命令行重建与渲染

以下命令从 `loader-blender` 的父目录执行。

重建最终工程并渲染整机图（会覆盖生成的最终工程；手动修改后请另存文件再执行）：

```sh
/Applications/Blender.app/Contents/MacOS/Blender \
  --background --factory-startup --python-exit-code 1 \
  --python loader-blender/scripts/build.py -- --render hero
```

只从保存的工程渲染细节图，不覆盖工程：

```sh
/Applications/Blender.app/Contents/MacOS/Blender \
  --background loader-blender/loader-product-fixed.blend --python-exit-code 1 \
  --python loader-blender/scripts/render.py -- --camera detail
```

侧视图把 `detail` 改为 `side`；加 `--preview` 可生成较小的低采样预览。

只读验证：

```sh
/Applications/Blender.app/Contents/MacOS/Blender \
  --background loader-blender/loader-product-fixed.blend --python-exit-code 1 \
  --python loader-blender/scripts/validate.py
```

## 制作与验证边界

本机为 Blender 5.2.2 LTS，使用 Apple M1 Max（24 核 GPU）的 Metal 设备完成渲染。初次运行经历了 Metal 内核编译，之后低采样预览已经可以正常快速出图。

这是照片参考下的展示模型，没有原厂 CAD 或尺寸图，部件比例、内部机构与背面经过简化。举升臂和铲斗已按铰点设置父子层级，但液压管路和油缸没有完整动画绑定；直接转动铰点不会自动求解所有机械联动。

已执行脚本语法检查、真实 Cycles 渲染和原生网格检查；检查了成片构图与可见装配。未做工程尺寸校准、制造可行性、完整机构碰撞或动画验收；没有本轮新增的网页界面，因此网页布局检查不适用。

## 本次实际验收结果

- 950 个可见产品网格均非空，坐标检查无非法数值。
- 蜂窝格栅布尔结果为 455 个面，开孔已在细节渲染中核对。
- 1 张环境贴图已打包，3 台相机和 8 个脚本文本块已保存。
- 两个 HTML 版本的 39 个原有文件逐一通过 SHA-256 校验，内容未变。
- `python3 -m py_compile loader-blender/scripts/*.py` 通过。
- 通过 Blender `--background --python` 执行建模、渲染和只读验证，无鼠标自动操作。
- 未发现 Mock、TODO、断点或演示接口；保留命令行生成报告用于复现与验收。

命令行渲染脚本会为当前后台进程启用 Metal；未修改 Blender 的全局用户偏好设置。

## 机罩黑纹修复（2026-09-20）

- 修复 `body.py` 中上下端面与两侧折板的共面重叠，保留面板位置与其他部件。
- `build.py` 关闭所有保存的三维视口的关系线，默认输出修复版文件。
- `repair_hood.py` 对已保存工程执行局部修复、校验原坐标并另存，不重建整机。
- `hood_checks.py` 对四块机罩面板做逐三角形共面面积检查，分别覆盖原始网格与修饰器结果；`validate.py` 已接入此检查。
- 修复后两种检查中 6 组面板对的重叠面积均为 0，10 个保存视口已关闭关系线。
- `render.py --output` 可另存修复对照图，不覆盖前一版渲染。
- 修复版局部渲染：`renders/detail-fixed.png`；修复记录：`logs/hood-repair.json`。

重新生成时，请先另存你手动修改过的修复版工程，避免生成命令覆盖它。
