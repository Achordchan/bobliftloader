"""Compare raw default HTML with the current HEAD, sharing unchanged assets to keep version URLs identical."""
import io,json,subprocess,tarfile,tempfile
from pathlib import Path
root=Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='backend-p2-baseline-') as directory:
 base=Path(directory)
 archive=subprocess.check_output(['git','archive','HEAD','inc','templates','index.php','tests/fixtures/render-backend-p1.php'])
 with tarfile.open(fileobj=io.BytesIO(archive)) as tar:tar.extractall(base,filter='data')
 (base/'assets').symlink_to(root/'assets',target_is_directory=True)
 paths=json.loads((root/'tests/fixtures/backend-p1-default-html.json').read_text())['sha256']
 for path in paths:
  outputs=[]
  for location in [base,root]:
   result=subprocess.run(['php',str(location/'tests/fixtures/render-backend-p1.php'),path],capture_output=True)
   if result.returncode or result.stderr:raise RuntimeError(result.stderr.decode())
   outputs.append(result.stdout)
  if outputs[0]!=outputs[1]:raise RuntimeError(f'Byte mismatch: {path}')
 print(f'Raw byte comparison: {len(paths)} paths equal to HEAD; no normalization; same unmodified assets')
