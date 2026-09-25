# 装载机 CAD 外观还原工程

## 直接使用

打开 `loader-cad-restored.blend`。这是本次基于用户 STEP 原厂结构、补充照片外观部件的可编辑 Blender 工程。通过“渲染 → 渲染图像”生成主视图，场景中另外保存了侧视和后侧相机。

`loader-cad-original.blend` 保留 STEP 原始装配姿态，仅做单位、坐标系转换和基础材质分配，适合对照原厂结构。原始 STEP、原照片、已有 `loader-blender/` 工程和网站文件均未修改。

## 来源与精度

- 源文件：`/Users/a1234/Desktop/工作/滑移装载机(1)/照片/红色 360-460 站 /滑移装载机结构图.STEP`
- STEP 文件头：SolidWorks 2026，AP203；文件标注导出时间为 2026-09-21。
- 源文件 SHA-256：`93597d0a7ded7ac4bd8494f4a9e0ec1c731d936aea1803807c1768c6f9eb8824`。
- 实际读取 160 个装配叶子零件，转换为 159970 个三角面。保留原厂名称、装配路径和各零件局部网格，未把整机合并成一个对象。
- 网格化线性偏差参数为 0.25 mm，角度偏差为 0.20 rad。它是 CAD 曲面的渲染网格，不替代源 STEP 的工程曲面。
- 单位由毫米换为米；Blender 的 X 为车宽、Y 为前方、Z 为上方。
- 还原工程的大臂绕原厂后铰点降低 25°；铲斗、快换架绕原厂前铰点调平。此变换是静态展示姿态，不是动画机械绑定。
- 摄影参考使用同目录“滑移装载机主图”中的整机、侧视、仪表和局部照片。

## 还原范围与边界

原厂 CAD 提供车架、举升臂、铲斗、快换结构、履带架、油箱、机罩、仪表板和部分内部件。补充件包括橡胶履带、轮组、油缸、软硬管路、接头、操纵杆、防尘套、扶手、仪表、开关、灯具、发动机外观、散热器与紧固件。材质区分喷漆、橡胶、镀锌钢和镀铬活塞杆。

在 Blender 集合中，原始零件使用 `CAD ·` 前缀，补充件使用 `照片补充 ·` 前缀；对象自定义属性记录来源。可以单独隐藏或编辑照片估算件。

照片与 STEP 存在可见结构差异：STEP 侧板为长圆孔，照片为扇形护罩；STEP 后盖和照片打开状态也不完全相同。本工程以 STEP 为结构依据，没有为了外观一致而改造原厂板件。照片补充件没有供应商尺寸，尤其发动机、履带轮组及管路只能用于展示；不承诺制造、装配或运动学精度。标签仅保留底板外观，不编造模糊的警示文字、制造商标识或规格。未执行完整碰撞验收、实物尺寸校准和动作绑定。

## 文件清单

| 文件 | 用途 |
| --- | --- |
| `loader-cad-restored.blend` | CAD 与照片补充件、材质、灯光、三台相机的展示工程 |
| `loader-cad-original.blend` | 原始 CAD 装配姿态工程，供比对 |
| `renders/loader-cad-restored.png` | 还原工程前侧主图 |
| `renders/side.png` | 侧视检查图 |
| `renders/rear.png` | 后侧检查图 |
| `renders/loader-cad-original.png` | 原始 CAD 结构渲染 |
| `scripts/convert_step.py` | 使用 OpenCascade 读取装配树、变换、孔轴和网格 |
| `scripts/build_scene.py` | 创建原厂零件、材质、摄影棚、相机，保存并渲染 |
| `scripts/detail_geometry.py` | 仅供照片补充件使用的几何创建工具 |
| `scripts/track_details.py` | 履带、驱动轮、承重轮与紧固件 |
| `scripts/photo_details.py` | 根据原厂铰点设置姿态，补充液压、操纵台与动力外观 |
| `scripts/validate_scene.py` | 源文件哈希、160 零件、边界精度、空网格及贴图依赖检查 |
| `scripts/render_views.py` | 从已保存工程渲染侧面与后侧，不覆盖工程 |
| `data/assembly.json` | 含装配位置的 CAD 网格中间数据 |
| `data/assembly-report.json` | 零件清单、原始毫米边界、面数和孔轴信息 |
| `data/final-validation.json` | 最终工程的机器可读校验结果 |
| `data/*.log` | 真实转换、构建、渲染与检查日志 |
| `references/contact.jpg` | 27 张产品主图的联系表 |
| `references/6.jpg`、`13.jpg`、`17.jpg`、`19.jpg`、`21.jpg` | 用于局部核对的缩略参考副本 |
| `requirements.txt` | CAD 转换环境依赖；无需安装即可打开成品 Blender 文件 |
| `.gitignore` | 忽略本地 Python 环境、缓存与 Blender 自动备份 |

## 重建与验证

以下命令在本项目根目录执行。重建会覆盖本次生成的同名工程，请先另存手工编辑过的文件。

```sh
loader-cad/.venv/bin/python loader-cad/scripts/convert_step.py \
  '/Users/a1234/Desktop/工作/滑移装载机(1)/照片/红色 360-460 站 /滑移装载机结构图.STEP' \
  loader-cad/data/assembly.json

/Applications/Blender.app/Contents/MacOS/Blender -b --factory-startup \
  --python-exit-code 1 --python loader-cad/scripts/build_scene.py -- --complete

python3 -m py_compile loader-cad/scripts/*.py

/Applications/Blender.app/Contents/MacOS/Blender -b loader-cad/loader-cad-restored.blend \
  --python-exit-code 1 --python loader-cad/scripts/validate_scene.py

/Applications/Blender.app/Contents/MacOS/Blender -b loader-cad/loader-cad-restored.blend \
  --python-exit-code 1 --python loader-cad/scripts/render_views.py
```

运行环境为本机 Blender 5.2.2 LTS，渲染使用 Cycles、Metal GPU 和降噪。主图 1800×1500、96 采样，侧面与后侧 64 采样。成品无外部贴图依赖，移动 `.blend` 后仍可查看和渲染；转换脚本依赖保留在本目录 `.venv`，不影响网站生产依赖。

## 验收

已运行语法检查、真实 Blender 构建、保存文件后重新载入校验，以及前侧、侧面、后侧渲染目视核对。CAD 局部边界最大误差约 0.000058 mm，属于网格坐标浮点精度；这项检查不代表制造精度验收。不存在空网格、非有限坐标和未打包外部图片。未发现 Mock、断点或临时测试入口。

没有网页界面改动；布局检查针对模型构图、仪表孔位、履带护板关系及落斗姿态。旧的照片建模工程作为独立历史文件保留，没有作为重复几何叠加到这份新场景中。

## 2026-09-22 踏板防滑纹贴合修复

原厂站立踏板倾角约 6.674°，补建防滑纹原先使用固定世界高度，导致底部离踏板 24.67–55.59 mm。现改为读取原厂踏板的真实承载面，将 48 条凸纹逐条沿表面法线贴合，并嵌入 0.4 mm；保留凸纹的交错排列及原有材质。每条凸纹检查中心、四角和边中点共 9 个支撑位置，同时检查倒角后的实际网格底部。

- `scripts/footplate.py`：识别 CAD 踏板承载面、对齐凸纹并校验接触。
- `scripts/photo_details.py`：移除固定高度摆放逻辑，重建时调用踏板贴合函数。
- `scripts/repair_footplate.py`：局部修复、修复前备份及其他对象不变检查。
- `scripts/render_footplate.py`：载入保存后的工程，重新校验并渲染近景。
- `loader-cad-footplate-fixed.blend`：独立修复副本。
- `renders/footplate-after.png`：修复后踏板近景。
- `data/footplate-repair.json`：修复及其他对象不变校验记录。

本轮不调整其他部件与颜色。语法检查、保存后重新加载、48 条凸纹实际网格接触校验及近景渲染已通过；没有新增网页改动或 Mock 逻辑，未重做整机颜色、碰撞和机械动作验收。

经用户授权，修复已同步覆盖 `loader-cad-restored.blend`，当前 Blender 场景也已更新；覆盖前的实时场景另存于 `backups/`。局部修复校验确认其他 898 个对象的变换、网格与材质槽未变。
