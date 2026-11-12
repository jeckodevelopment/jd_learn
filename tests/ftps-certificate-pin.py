#!/usr/bin/env python3
"""No-network tests for the FTPS SHA-256 trust policy."""
import hashlib
from pathlib import Path
import ssl
import sys
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "tools"))
from ftplib import FTP_TLS
from ftps_trust import (
    PinnedFTP_TLS, check_peer_certificate, create_ftps_client, parse_sha256_pin,
)


CERTIFICATE=b"synthetic DER bytes for test ONLY"
PIN=hashlib.sha256(CERTIFICATE).hexdigest()


class FakeSocket:
    def __init__(self, cert):
        self.cert=cert
        self.closed=False
    def getpeercert(self, binary_form=False):
        assert binary_form is True
        return self.cert
    def close(self):
        self.closed=True


class TestPinnedFTPS(unittest.TestCase):
    def test_accept_colon_separated_sha256(self):
        text=":".join(PIN[i:i+2] for i in range(0,64,2))
        self.assertEqual(parse_sha256_pin(text),hashlib.sha256(CERTIFICATE).digest())

    def test_reject_invalid_sha256(self):
        for pin in ("not-a-fingerprint", "AB:CD", PIN + "00", "sha1:" + PIN):
            with self.subTest(pin=pin):
                with self.assertRaises(ValueError):
                    parse_sha256_pin(pin)

    def test_default_context_preserves_public_ca_and_hostname_checks(self):
        class FakeClient:
            def __init__(self, context, timeout):
                self.context=context
        client, control = create_ftps_client({},FakeClient)
        self.assertIsNone(control)
        self.assertEqual(client.context.verify_mode, ssl.CERT_REQUIRED)
        self.assertTrue(client.context.check_hostname)

    def test_pinned_control_verified_before_login(self):
        class FakeClient:
            def __init__(self, context, timeout):
                self.context=context
                self.sock=FakeSocket(CERTIFICATE)
        client, control=create_ftps_client({"JDLEARN_FTPS_CERT_SHA256":PIN},FakeClient)
        self.assertEqual(client.context.verify_mode,ssl.CERT_NONE)
        self.assertFalse(client.context.check_hostname)
        control()
        client.sock=FakeSocket(b"different certificate")
        with self.assertRaises(ssl.SSLCertVerificationError):
            control()

    def test_production_pinning_verifies_control_and_data_certificates(self):
        client, verify_callback=create_ftps_client({"JDLEARN_FTPS_CERT_SHA256":PIN})
        self.assertIsNone(verify_callback)
        self.assertIsInstance(client,PinnedFTP_TLS)
        self.assertGreaterEqual(client.context.minimum_version,ssl.TLSVersion.TLSv1_2)
        client.sock=FakeSocket(CERTIFICATE)
        with patch.object(FTP_TLS,"auth",return_value="234"):
            self.assertEqual(client.auth(),"234")
        client.sock=FakeSocket(b"untrusted")
        with patch.object(FTP_TLS,"auth",return_value="234"):
            with self.assertRaises(ssl.SSLCertVerificationError):
                client.auth()

        good=FakeSocket(CERTIFICATE)
        with patch.object(FTP_TLS,"ntransfercmd",return_value=(good,42)):
            self.assertEqual(client.ntransfercmd("RETR example")[1],42)
        bad=FakeSocket(b"data-channel impostor")
        with patch.object(FTP_TLS,"ntransfercmd",return_value=(bad,42)):
            with self.assertRaises(ssl.SSLCertVerificationError):
                client.ntransfercmd("RETR example")
        self.assertTrue(bad.closed)

    def test_pin_does_not_match_empty_or_missing_certificate(self):
        expected=hashlib.sha256(CERTIFICATE).digest()
        for supplied in (None,b"",b"other"):
            with self.subTest(supplied=supplied):
                with self.assertRaises(ssl.SSLCertVerificationError):
                    check_peer_certificate(FakeSocket(supplied),expected)


if __name__=="__main__":
    unittest.main()
