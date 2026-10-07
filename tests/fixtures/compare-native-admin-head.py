"""Compare every default route byte-for-byte with HEAD without a web server or database."""
import io
import json
from pathlib import Path
import subprocess
import tarfile
import tempfile

root = Path(__file__).resolve().parents[2]
paths = json.loads(subprocess.check_output([
    'php', '-r', "require 'inc/bootstrap.php'; echo json_encode(array_values(array_unique([...bl_sitemap_paths(), '/thank-you/', '/404/'])));"
], cwd=root))
with tempfile.TemporaryDirectory(prefix='native-admin-baseline-') as directory:
    base = Path(directory)
    archive = subprocess.check_output(['git', 'archive', 'HEAD', 'inc', 'templates', 'index.php'], cwd=root)
    with tarfile.open(fileobj=io.BytesIO(archive)) as tar:
        tar.extractall(base, filter='data')
    (base / 'assets').symlink_to(root / 'assets', target_is_directory=True)
    for path in paths:
        outputs = []
        for location in [base, root]:
            result = subprocess.run([
                'php', '-r', '$_SERVER["REQUEST_URI"]=$argv[1]; require "index.php";', path
            ], cwd=location, capture_output=True)
            if result.returncode or result.stderr:
                raise RuntimeError(result.stderr.decode())
            outputs.append(result.stdout)
        if outputs[0] != outputs[1]:
            raise RuntimeError(f'Byte mismatch: {path}')
    print(f'{len(paths)} default routes equal to HEAD byte-for-byte; shared unchanged assets; no normalization')
