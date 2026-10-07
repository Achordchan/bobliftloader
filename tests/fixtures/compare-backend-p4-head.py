"""Compare every default route against HEAD without a server or HTML normalization."""
import io,json,subprocess,tarfile,tempfile
from pathlib import Path
root=Path(__file__).resolve().parents[2]
paths=json.loads(subprocess.check_output(['php','-r','require "inc/bootstrap.php"; echo json_encode([...bl_sitemap_paths(), "/thank-you/", "/p4-missing/"]);'], cwd=root))
with tempfile.TemporaryDirectory(prefix='bl-p4-baseline-') as d:
 base=Path(d)
 archive=subprocess.check_output(['git','archive','HEAD','inc','templates','index.php'], cwd=root)
 with tarfile.open(fileobj=io.BytesIO(archive)) as tar: tar.extractall(base,filter='data')
 (base/'assets').symlink_to(root/'assets',target_is_directory=True)
 renderer=base/'render.php'
 renderer.write_text('<?php $_SERVER["REQUEST_URI"]=$argv[2]; $_SERVER["HTTP_HOST"]="example.test"; $_SERVER["SCRIPT_NAME"]="/index.php"; require $argv[1]."/index.php";')
 for path in paths:
  outputs=[]
  for loc in (base,root):
   p=subprocess.run(['php',str(renderer),str(loc),path],capture_output=True)
   if p.returncode or p.stderr: raise RuntimeError(path+': '+p.stderr.decode())
   outputs.append(p.stdout)
  if outputs[0]!=outputs[1]: raise RuntimeError('Byte mismatch: '+path)
 print(f'Raw HTML byte comparison: {len(paths)} routes identical to HEAD (37 sitemap pages + thank-you + 404). No normalization.')
