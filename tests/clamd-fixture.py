"""Protocol fixture, not an antivirus: accepts ClamAV INSTREAM and recognises a test marker."""
import socket,struct,sys,os
path=sys.argv[1]
if os.path.exists(path):os.unlink(path)
server=socket.socket(socket.AF_UNIX);server.bind(path);server.listen(8)
print('ready',flush=True)
def exact(conn,size):
 out=b''
 while len(out)<size:
  chunk=conn.recv(size-len(out))
  if not chunk:raise EOFError()
  out+=chunk
 return out
try:
 while True:
  conn,_=server.accept()
  try:
   conn.settimeout(5)
   assert exact(conn,10)==b'zINSTREAM\0'
   infected=False
   while True:
    size=struct.unpack('!I',exact(conn,4))[0]
    if not size:break
    chunk=exact(conn,size);infected=infected or b'FIXTURE_INFECTED' in chunk
   conn.sendall(b'stream: Fixture.Test FOUND\0' if infected else b'stream: OK\0')
  except (OSError,EOFError,AssertionError):pass
  finally:conn.close()
finally:
 server.close()
 if os.path.exists(path):os.unlink(path)
