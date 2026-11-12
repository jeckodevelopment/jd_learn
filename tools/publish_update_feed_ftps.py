#!/usr/bin/env python3
"""Publish a Joomla update feed using explicit FTPS (FTP over verified TLS).

No plaintext FTP fallback. Files are uploaded to a temporary name and renamed
only once their contents are verified. FTP servers differ on whether rename
may replace an existing target: an unsupported replacement fails closed.
"""
import hashlib
import os
from ftplib import FTP_TLS
from ftps_trust import create_ftps_client
from pathlib import Path
import posixpath
import re
import ssl
import uuid


def required(env, name):
    value = env.get(name, "")
    if not value or not value.strip():
        raise ValueError(f"Missing required environment setting: {name}")
    return value


def remote_directory(value):
    """Accept FTP account roots (/) and absolute subdirectories only."""
    if not value.startswith("/") or "\\" in value:
        raise ValueError("FTPS directory must be an absolute POSIX path")
    segments = value.split("/")
    if any(segment in ("..", ".") for segment in segments):
        raise ValueError("FTPS directory cannot traverse parent/current directories")
    if any(ord(c) < 32 for c in value):
        raise ValueError("FTPS directory contains control characters")
    return posixpath.normpath(value)


def publish(env=os.environ, ftp_factory=FTP_TLS):
    host = required(env, "JDLEARN_FTPS_HOST")
    user = required(env, "JDLEARN_FTPS_USER")
    password = required(env, "JDLEARN_FTPS_PASSWORD")
    directory = remote_directory(required(env, "JDLEARN_FTPS_DIRECTORY"))
    file_path = Path(required(env, "JDLEARN_FEED_FILE"))
    if not file_path.is_file():
        raise ValueError("The local update feed does not exist")
    data = file_path.read_bytes()
    if not data:
        raise ValueError("Refusing to publish an empty update feed")
    port = int(env.get("JDLEARN_FTPS_PORT") or "21")
    if not 1 <= port <= 65535:
        raise ValueError("Invalid FTPS port")
    if not re.fullmatch(r"[A-Za-z0-9.-]+", host):
        raise ValueError("FTPS host must be a DNS hostname without a protocol or path")

    # Validates CA trust chain AND hostname. Do not disable certificate checks.
    ftp, verify_control = create_ftps_client(env, ftp_factory)
    temporary = ".jdlearn.xml.upload-" + uuid.uuid4().hex
    uploaded = False
    try:
        ftp.connect(host=host, port=port, timeout=30)
        ftp.auth()                # Explicit AUTH TLS, before sending credentials.
        ftp.login(user=user, passwd=password)
        ftp.prot_p()              # TLS is mandatory for data transfers (PROT P).
        ftp.set_pasv(True)
        ftp.cwd(directory)        # "/" works for accounts jailed inside updates.
        with file_path.open("rb") as stream:
            ftp.storbinary("STOR " + temporary, stream)
        uploaded = True

        received = bytearray()
        ftp.retrbinary("RETR " + temporary, received.extend)
        if hashlib.sha256(received).digest() != hashlib.sha256(data).digest():
            raise RuntimeError("Uploaded feed differs from the local file")
        # Rename may fail when target already exists: never delete the old feed
        # as an automatic fallback. Atomic replacement is server-dependent.
        ftp.rename(temporary, "jdlearn.xml")
        uploaded = False
        received = bytearray()
        ftp.retrbinary("RETR jdlearn.xml", received.extend)
        if bytes(received) != data:
            raise RuntimeError("Published feed verification failed")
    finally:
        if uploaded:
            try:
                ftp.delete(temporary)
            except Exception:
                pass  # Preserve original exception; remote cleanup is best effort.
        try:
            ftp.quit()
        except Exception:
            ftp.close()
    print("Feed published and verified over explicit FTPS with certificate validation")


if __name__ == "__main__":
    publish()
