"""Connect completed mechanical clips to the existing product chapters."""
from pathlib import Path
import re
ROOT=Path(__file__).resolve().parents[1]
page=(ROOT/'index.html').read_text()
def panel(name,classes,zoom=False):
 title='履带运行' if name=='tracks' else '举升与翻斗'
 video=f'<video data-src="assets/motion/{name}.mp4" poster="assets/motion/{name}-poster.jpg" preload="none" muted playsinline aria-label="{title}三维动作演示"></video>'
 if zoom:video='<div class="exp-chassis-zoom">'+video+'</div>'
 return f'''        <div class="exp-motion-panel {classes}" data-motion="{name}">
          {video}
          <div class="exp-motion-status" data-motion-status role="status" hidden><span>正在准备机械动作</span><button type="button" data-motion-retry hidden>重试</button></div>
          <div class="exp-motion-tools"><button type="button" data-motion-play aria-pressed="false"><span class="exp-play-symbol" aria-hidden="true"></span><span>播放动作</span></button><label class="exp-sr" for="{name}-progress">{title}动作进度</label><input id="{name}-progress" data-motion-progress type="range" min="0" max="1000" value="0" step="1" disabled><span class="exp-motion-tag">{title}</span></div>
        </div>'''
page=re.sub(r'        <div class="exp-chassis-media">.*?</div>\n        <div class="exp-chapter-note">',panel('tracks','exp-chassis-media',True)+'\n        <div class="exp-chapter-note">',page,flags=re.S)
page=re.sub(r'        <div class="exp-hydraulic-media">.*?</div>',panel('operation','exp-hydraulic-media'),page,count=1,flags=re.S)
page=page.replace('<span class="exp-giant-word"','<button class="exp-reference-button" type="button" data-real="drive">查看实机运行 ↗</button>\n        <span class="exp-giant-word"')
page=page.replace('<div class="exp-detail-caption">从整体，到连接的每一处。</div>','<button class="exp-reference-button" type="button" data-real="lift">实机动作对照 ↗</button><div class="exp-detail-caption">滚动控制举升与翻斗，也可点击播放。</div>')
page=page.replace('<h3>举升臂</h3><p>从车架延伸至作业前端。</p>','<h3>举升</h3><p>臂架抬升，油缸随铰点伸展。</p>').replace('<h3>液压油缸</h3><p>近看缸筒、活塞杆与铰接位置。</p>','<h3>翻斗</h3><p>铲斗绕前端铰点翻转。</p>').replace('<h3>连接接口</h3><p>管路、接头与快换结构，一眼看清。</p>','<h3>回落</h3><p>收回铲斗，臂架回到起始位置。</p>')
page=page.replace('沿着举升臂，<br>靠近液压油缸与连接结构。','跟着实际动作，<br>看举升、翻斗与回落。')
page=page.replace('01 — 06','滚动探索').replace('部分模型细节仍在还原，以实物为准。','模型细节和动作行程仍在还原，非实机参数标定。')
page=page.replace('<link rel="stylesheet" href="gallery.css?v=3">','<link rel="stylesheet" href="gallery.css?v=3">\n  <link rel="stylesheet" href="motion.css?v=4">')
page=page.replace('</body>', '''  <dialog class="exp-gallery exp-real-dialog" id="real-dialog" aria-labelledby="real-title">
    <div class="exp-gallery-head"><div><h2 id="real-title">实机，动起来。</h2><p id="real-caption">用户提供的实际运行视频</p></div><button class="exp-close" id="real-close" type="button" aria-label="关闭实机视频">×</button></div>
    <video class="exp-real-video" id="real-video" controls playsinline preload="none" aria-label="实机操作视频"></video>
    <div class="exp-gallery-tabs" role="group" aria-label="选择实机动作"><button type="button" data-real-choice="drive" aria-pressed="false">行驶转向</button><button type="button" data-real-choice="lift" aria-pressed="false">举升回落</button><button type="button" data-real-choice="bucket" aria-pressed="false">铲斗翻转</button></div>
    <p class="exp-gallery-note">实拍参考；三维演示中的行程、速比和管路变形尚未完成工程标定。</p>
  </dialog>
</body>''')
page=page.replace('?v=3','?v=4')
(ROOT/'index.html').write_text(page)
