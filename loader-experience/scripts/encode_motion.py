"""Encode completed mechanical renders with all-intra H.264 for responsive seeking."""
import subprocess
import sys
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
for name,count,bg in [('operation',160,'0x121314'),('tracks',64,'0xf5f5f7')]:
 if len(sys.argv)>1 and name not in sys.argv[1:]:continue
 folder=ROOT/'design/motion-frames'/name
 for i in range(count):
  if not (folder/f'{i:04d}.png').is_file():raise RuntimeError(f'Missing rendered frame: {name}/{i:04d}')
 destination=ROOT/'assets/motion'/f'{name}.mp4'
 subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i',f'color=c={bg}:s=960x720:r=24','-framerate','24','-i',str(folder/'%04d.png'),'-filter_complex','[0:v][1:v]overlay=shortest=1:format=auto,format=yuv420p','-frames:v',str(count),'-an','-c:v','libx264','-crf','20','-preset','fast','-g','1','-movflags','+faststart','-y',str(destination)],check=True)
 subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-i',str(destination),'-frames:v','1','-q:v','2','-y',str(ROOT/'assets/motion'/f'{name}-poster.jpg')],check=True)
 print(f'Encoded {name}',flush=True)
