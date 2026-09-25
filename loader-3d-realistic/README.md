# 履带装载机 · Three.js 写实版

打开本目录 `index.html` 即可离线使用。新版本在 `loader-3d-realistic` 中；原来的 `../loader-3d` 完整保留。页头“查看保留版”可直接跳回原版本。

## 本轮改进

- 重新区分橙色漆面、粉末喷涂钢板、橡胶与机加工钢件；增加透明罩光和细微表面纹理。
- 引入本地摄影棚 HDR 环境光，提供真实环境中的亮暗反射形状。
- 调整几何倒角，修正板件平面的法线；铲斗内壁改为连续曲面与连续纹理坐标，去掉分段条纹。
- 加入环境遮蔽、柔化实时阴影、地面接触衰减及输出抗锯齿。
- 使用更长焦的取景，保留旋转、缩放、四种视角、举升、翻斗、线框与重置。
- 合并不会相对运动的零件，保留动态关节；停止操作时不重复绘制，不持续消耗 GPU。

## 文件职责

| 文件 | 本版本职责 |
| --- | --- |
| `index.html`、`style.css` | 新版入口、中文界面、响应式布局与返回原版的入口 |
| `geometry.js` | 物理材质、程序纹理、圆角几何、板件平面与倒角法线 |
| `body.js` | 沿用原结构，增加机罩倒角与工作灯透明罩 |
| `attachment.js` | 连续铲斗曲面、金属刃口、原有关节与地面限位 |
| `tracks.js`、`model.js` | 从原版复制；履带结构和子系统组装本轮未修改 |
| `studio.js` | 环境光、柔化阴影、地面、环境遮蔽、后处理与颜色输出 |
| `optimize.js` | 按材质合并静态零件，不合并关节与动态液压缸 |
| `viewer.js` | 初始化写实渲染、相机、交互和按需绘制 |
| `assets/environment.js`、`assets/README.md` | 内嵌环境光数据及作者、来源、许可说明 |
| `vendor/three-studio.min.js`、`vendor/studio-entry.js` | Three.js 0.160.1 与同版本官方附加组件的打包文件及入口源码 |
| `vendor/*LICENSE*`、`vendor/*.LEGAL.txt` | 第三方许可声明 |
| `original-version.sha256.json` | 修改开始前原版本所有文件的 SHA-256 清单 |
| `preview.png`、`side-preview.png`、`mobile-preview.png`、`articulation-preview.png` | 新版实际浏览器截图 |

Three.js 版本维持 0.160.1。运行依赖已本地打包；新增的渲染组件来自同版本 Three.js 官方附加组件。esbuild 0.25.10 仅在临时构建目录使用，运行本页面无需 Node.js 或安装包。

## 验证（2026-09-20）

语法检查：

```sh
for file in loader-3d-realistic/{geometry,tracks,body,attachment,model,viewer,studio,optimize}.js; do
  node --check "$file" || exit 1
done
```

通过 `ego-browser nodejs` 执行的实际检查：

- `file://` 直接打开成功；WebGL 错误码为 0，着色器编译失败数为 0。
- 桌面 1500×1100、手机模拟视口 390×844、侧面和举升状态已截图检查，手机无横向溢出。
- 鼠标拖拽、滚轮缩放、自动旋转、线框、视角切换、键盘操作滑块与重置通过。
- 举升 0/12/24/48° × 请求翻斗 -12/0/45°，共 12 组姿态的实际铲斗顶点检查通过，最低点不低于地面 0.024。
- 停止操作后跨 12 个浏览器动画帧，新增渲染帧数为 0。
- 当前浏览器一次自动旋转采样中，30 个动画帧耗时 968ms；这是本环境的一次结果，不代表所有硬件性能。
- 页面返回保留版的链接已实际点击，原版可正常加载。
- 静态部件合并后模型包含 48 个网格对象，约 40 万个三角形。
- 已扫描自有代码中的 Mock、TODO、debugger 和 console.log，无残留；保留初始化错误日志。

原版完整性校验（从两个版本的父文件夹执行）：

```sh
python3 - <<'PY'
import hashlib, json
from pathlib import Path
root = Path('loader-3d')
expected = json.loads(Path('loader-3d-realistic/original-version.sha256.json').read_text())
actual = {str(p.relative_to(root)): hashlib.sha256(p.read_bytes()).hexdigest()
          for p in root.rglob('*') if p.is_file()}
assert actual == expected, '原版文件发生变化'
print(f'原版 {len(actual)} 个文件全部保持不变')
PY
```

## 边界

这是程序化几何重建的实时写实版本，改善了材质、曲面、反射和阴影，但没有改成摄影扫描或精确 CAD 模型。尺寸、内部零件和运动学仍是照片估计，不用于制造或完整机械碰撞判断。

未进行真实触屏设备、Safari/Firefox 兼容性和低端 GPU 验证。后处理需要支持对应纹理格式的 WebGL 环境；性能与显卡、窗口尺寸以及同时打开的 3D 页面数量有关。未使用离线路径追踪，不能将当前画面视为照片级效果的保证。
