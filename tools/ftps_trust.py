#!/usr/bin/env python3
"""FTPS trust policy: public PKI by default, opt-in SHA-256 certificate pin.

A pin MUST be independently verified with the hosting provider before enabling.
The same exact certificate is required on the FTPS control AND data channels.
No plaintext FTP or silent fallback is possible.
"""
import hashlib
import hmac
import re
import ssl
from ftplib import FTP_TLS


def parse_sha256_pin(value):
    if not value:
        return None
    value = value.strip()
    if value.lower().startswith("sha256 fingerprint="):
        value = value.partition("=")[2]
    normalized = value.replace(":", "")
    if not re.fullmatch(r"[0-9a-fA-F]{64}", normalized):
        raise ValueError("JDLEARN_FTPS_CERT_SHA256 must contain 64 SHA-256 hex digits")
    return bytes.fromhex(normalized)


def check_peer_certificate(sock, expected):
    cert = sock.getpeercert(binary_form=True)
    if not cert or not hmac.compare_digest(hashlib.sha256(cert).digest(), expected):
        raise ssl.SSLCertVerificationError(
            "FTPS certificate SHA-256 does not match the explicitly trusted fingerprint"
        )


class PinnedFTP_TLS(FTP_TLS):
    """Pins both the encrypted control socket and every protected data socket."""
    expected_sha256 = None

    def auth(self):
        result = super().auth()
        if self.expected_sha256 is not None:
            check_peer_certificate(self.sock, self.expected_sha256)
        return result

    def ntransfercmd(self, cmd, rest=None):
        conn, size = super().ntransfercmd(cmd, rest)
        try:
            if self.expected_sha256 is not None:
                check_peer_certificate(conn, self.expected_sha256)
        except BaseException:
            conn.close()
            raise
        return conn, size


def create_ftps_client(env, ftp_factory=FTP_TLS):
    expected = parse_sha256_pin(env.get("JDLEARN_FTPS_CERT_SHA256", ""))
    if expected is None:
        context = ssl.create_default_context()
        context.check_hostname = True
        context.verify_mode = ssl.CERT_REQUIRED
        client = ftp_factory(context=context, timeout=30)
        return client, None

    # A pinned certificate replaces CA path verification, not identity checking:
    # each TLS connection must prove possession of exactly the pinned certificate.
    # The SHA-256 pin is checked before sending username/password or FTP data.
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    context.check_hostname = False
    context.verify_mode = ssl.CERT_NONE
    if ftp_factory is FTP_TLS:
        client = PinnedFTP_TLS(context=context, timeout=30)
        client.expected_sha256 = expected
        return client, None
    # Test fakes inject a factory, so perform the control pin explicitly.
    client = ftp_factory(context=context, timeout=30)
    def verify_control():
        check_peer_certificate(client.sock, expected)
    return client, verify_control
