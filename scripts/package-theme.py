"""Package only the installable theme, excluding research, test data and credentials."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root / 'dist' / 'bobliftloader-theme.zip'
output.parent.mkdir(exist_ok=True)
files = [root / name for name in ['style.css', 'functions.php', 'index.php', 'readme.txt', 'screenshot.png']]
for directory in ['inc', 'templates', 'assets']:
    files.extend(path for path in (root / directory).rglob('*') if path.is_file() and not path.name.startswith('.'))
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(set(files)):
        if path.exists(): archive.write(path, Path('bobliftloader') / path.relative_to(root))
with ZipFile(output) as archive:
    assert archive.testzip() is None
    assert 'bobliftloader/style.css' in archive.namelist()
    assert 'bobliftloader/functions.php' in archive.namelist()
    assert not any('/tests/' in name or '/docs/' in name or '.env' in name for name in archive.namelist())
print(f'{output}\n{output.stat().st_size:,} bytes; {len(set(files))} theme files checked')
