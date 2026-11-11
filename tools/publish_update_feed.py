#!/usr/bin/env python3
"""Deploy a Joomla update feed via SFTP using server-verified host keys.

Requires paramiko. Fails closed if the server cannot atomically replace the feed.
"""
import io
import os
from pathlib import Path
import posixpath
import uuid

import paramiko

host=os.environ['JDLEARN_SFTP_HOST']
user=os.environ['JDLEARN_SFTP_USER']
remote_dir=os.environ['JDLEARN_SFTP_DIRECTORY'].rstrip('/')
known=os.environ['JDLEARN_SFTP_KNOWN_HOSTS']
key_text=os.environ['JDLEARN_SFTP_PRIVATE_KEY']
source=Path(os.environ['JDLEARN_FEED_FILE'])
if not source.is_file() or source.stat().st_size==0:
    raise RuntimeError('missing feed')
if not remote_dir.startswith('/') or '..' in remote_dir.split('/'):
    raise RuntimeError('remote SFTP directory must be an absolute, pre-created directory')
hostkeys=paramiko.HostKeys()
with io.StringIO(known) as lines:
    for line in lines:
        line=line.strip()
        if line and not line.startswith('#'):
            fields=line.split()
            if len(fields)<3: raise RuntimeError('invalid known_hosts entry')
            key=paramiko.PKey.from_type_string(fields[1],__import__('base64').b64decode(fields[2]))
            hostkeys.add(fields[0],fields[1],key)
def load_key():
    for klass in (paramiko.Ed25519Key,paramiko.RSAKey,paramiko.ECDSAKey):
        try:
            return klass.from_private_key(io.StringIO(key_text))
        except paramiko.SSHException:
            pass
    raise RuntimeError('unreadable SSH private key')
ssh=paramiko.SSHClient()
ssh.load_system_host_keys()
# Known hosts supplied via environment, not auto-trusted.
ssh.get_host_keys().clear()
for hostname,keys in hostkeys.items():
    for keytype,key in keys.items():
        ssh.get_host_keys().add(hostname,keytype,key)
ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
port=int(os.environ.get('JDLEARN_SFTP_PORT','22'))
remote=posixpath.join(remote_dir,'jdlearn.xml')
temporary=remote+'.upload-'+uuid.uuid4().hex
try:
    ssh.connect(hostname=host,port=port,username=user,pkey=load_key(),
                look_for_keys=False,allow_agent=False,timeout=20,auth_timeout=20)
    sftp=ssh.open_sftp()
    try:
        with sftp.file(temporary,'wb') as dst:
            dst.write(source.read_bytes())
        if sftp.stat(temporary).st_size!=source.stat().st_size:
            raise RuntimeError('SFTP upload size mismatch')
        # POSIX rename must be supported; never delete the old published feed first.
        sftp.posix_rename(temporary,remote)
        with sftp.file(remote,'rb') as actual:
            if actual.read()!=source.read_bytes():
                raise RuntimeError('remote feed verification failed')
    finally:
        try: sftp.remove(temporary)
        except OSError: pass
        sftp.close()
finally:
    ssh.close()
print('Feed published and verified over authenticated SFTP')
