#!/usr/bin/env python3
"""Safe live FTPS check: disposable files only; never touches jdlearn.xml.

This command tests explicit AUTH TLS, PROT P, write, read, rename, overwrite of
another disposable file, and deletion. It must run only with dedicated account
credentials and a previously created updates directory.
"""
import hashlib
import io
import os
import secrets
import ssl
from ftplib import FTP_TLS
from publish_update_feed_ftps import required, remote_directory


def probe(env=os.environ, ftp_factory=FTP_TLS):
    host = required(env, "JDLEARN_FTPS_HOST")
    user = required(env, "JDLEARN_FTPS_USER")
    password = required(env, "JDLEARN_FTPS_PASSWORD")
    directory = remote_directory(required(env, "JDLEARN_FTPS_DIRECTORY"))
    port = int(env.get("JDLEARN_FTPS_PORT") or "21")
    if not 1 <= port <= 65535:
        raise ValueError("Invalid FTPS port")
    import re
    if not re.fullmatch(r"[A-Za-z0-9.-]+", host):
        raise ValueError("FTPS host must be a DNS hostname")

    context = ssl.create_default_context()
    # No custom CA override, no unverified TLS, no plaintext FTP fallback.
    ftp = ftp_factory(context=context, timeout=30)
    token = secrets.token_hex(12)
    source = ".jdlearn-ftps-probe-" + token + ".upload"
    target = ".jdlearn-ftps-probe-" + token + ".target"
    payload = ("JD Learn FTPS check: " + token).encode("ascii")
    old = b"JD Learn FTPS pre-existing disposable target"
    connected = False
    created = set()
    overwrite_ok = False
    try:
        ftp.connect(host=host, port=port, timeout=30)
        connected = True
        ftp.auth()
        ftp.login(user=user, passwd=password)
        ftp.prot_p()
        ftp.set_pasv(True)
        ftp.cwd(directory)

        # Allocate ONLY randomly named disposable files. Never use jdlearn.xml.
        ftp.storbinary("STOR " + source, io.BytesIO(payload))
        created.add(source)
        ftp.storbinary("STOR " + target, io.BytesIO(old))
        created.add(target)

        received = bytearray()
        ftp.retrbinary("RETR " + source, received.extend)
        if hashlib.sha256(received).digest() != hashlib.sha256(payload).digest():
            raise RuntimeError("FTPS probe file was not transferred intact")

        # An existing target must be replaceable for future release updates.
        # This is confined to two disposable names, never a production feed.
        try:
            ftp.rename(source, target)
            created.discard(source)
            overwrite_ok = True
        except Exception as exc:
            raise RuntimeError(
                "FTPS server refused to rename over an existing disposable file; "
                "production feed updates remain blocked"
            ) from exc

        received = bytearray()
        ftp.retrbinary("RETR " + target, received.extend)
        if bytes(received) != payload:
            raise RuntimeError("The replaced disposable FTPS file has wrong contents")
    finally:
        if connected:
            # Delete only files created by this exact probe invocation.
            for name in sorted(created):
                try:
                    ftp.delete(name)
                except Exception:
                    pass
            try:
                ftp.quit()
            except Exception:
                ftp.close()
    if not overwrite_ok:
        raise RuntimeError("FTP rename-overwrite test did not pass")
    print("FTPS LIVE PROBE OK: AUTH TLS, certificate validation, PROT P, upload, "
          "readback, rename-overwrite and cleanup; jdlearn.xml untouched")


if __name__ == "__main__":
    probe()
