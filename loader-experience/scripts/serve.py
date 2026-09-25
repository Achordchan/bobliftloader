"""Local preview server with single-range MP4 responses for video scrubbing."""
import argparse
import functools
import os
import re
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]

class PreviewHandler(SimpleHTTPRequestHandler):
    def send_head(self):
        self.remaining=None
        path=Path(self.translate_path(self.path))
        if path.suffix.lower()!='.mp4' or not path.is_file():
            return super().send_head()
        file=path.open('rb')
        stat=os.fstat(file.fileno());size=stat.st_size
        modified=self.date_time_string(stat.st_mtime)
        header=self.headers.get('Range')
        if self.headers.get('If-Range') not in (None,modified):header=None
        start,end=0,size-1
        if header:
            match=re.fullmatch(r'bytes=(\d*)-(\d*)',header.strip())
            try:
                if not match or not any(match.groups()):raise ValueError()
                first,last=match.groups()
                if first:
                    start=int(first);end=min(int(last),size-1) if last else size-1
                else:
                    length=int(last)
                    if length<=0:raise ValueError()
                    start=max(0,size-length)
                if start>=size or end<start:raise ValueError()
            except ValueError:
                file.close();self.send_response(416)
                self.send_header('Content-Range',f'bytes */{size}')
                self.send_header('Content-Length','0');self.end_headers();return None
        self.send_response(206 if header else 200)
        self.send_header('Content-Type','video/mp4')
        self.send_header('Accept-Ranges','bytes')
        self.send_header('Content-Length',str(end-start+1))
        self.send_header('Last-Modified',modified)
        if header:self.send_header('Content-Range',f'bytes {start}-{end}/{size}')
        self.end_headers();file.seek(start);self.remaining=end-start+1
        return file

    def copyfile(self, source, outputfile):
        try:
            if self.remaining is None:return super().copyfile(source,outputfile)
            while self.remaining>0:
                chunk=source.read(min(65536,self.remaining))
                if not chunk:break
                outputfile.write(chunk);self.remaining-=len(chunk)
        except (BrokenPipeError,ConnectionResetError):
            # Browsers cancel obsolete range requests during rapid seeking.
            return

if __name__=='__main__':
    parser=argparse.ArgumentParser()
    parser.add_argument('--host',default='0.0.0.0',help='监听地址；默认允许局域网访问')
    parser.add_argument('--port',type=int,default=4175)
    args=parser.parse_args()
    handler=functools.partial(PreviewHandler,directory=str(ROOT))
    with ThreadingHTTPServer((args.host,args.port),handler) as server:
        print(f'Preview listening on {args.host}:{args.port}',flush=True)
        try:server.serve_forever()
        except KeyboardInterrupt:pass
