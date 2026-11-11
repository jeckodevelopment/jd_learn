#!/usr/bin/env python3
"""Offline contract/security tests for the FTPS update-feed publisher."""
import importlib.util
from pathlib import Path
import ssl
import tempfile
import unittest

module_path=Path(__file__).resolve().parents[1] / "tools/publish_update_feed_ftps.py"
spec=importlib.util.spec_from_file_location("ftps_publisher",module_path)
pub=importlib.util.module_from_spec(spec)
spec.loader.exec_module(pub)


class FakeFTPS:
    def __init__(self, context, timeout):
        assert isinstance(context, ssl.SSLContext)
        assert context.verify_mode == ssl.CERT_REQUIRED
        assert context.check_hostname is True
        self.calls=[]
        self.files={"jdlearn.xml": b"prior release"}
        self.dir=None
        self.reject_rename=False

    def connect(self, **kwargs):
        self.calls.append("connect")
        assert kwargs["port"]==21

    def auth(self):
        self.calls.append("auth")

    def login(self, **kwargs):
        assert self.calls == ["connect","auth"], "Credentials must follow TLS"
        assert kwargs["user"]=="dedicated-user"
        self.calls.append("login")

    def prot_p(self):
        self.calls.append("prot_p")

    def set_pasv(self, enabled):
        assert enabled is True

    def cwd(self, directory):
        assert self.calls[-1]=="prot_p", "Protected data channel required before use"
        self.dir=directory
        self.calls.append("cwd")

    def storbinary(self, command, stream):
        assert self.dir=="/"
        self.files[command.removeprefix("STOR ")]=stream.read()
        self.calls.append("STOR")

    def retrbinary(self, command, callback):
        callback(self.files[command.removeprefix("RETR ")])
        self.calls.append("RETR")

    def rename(self, old, new):
        assert self.calls[-1]=="RETR"
        if self.reject_rename:
            raise OSError("550 existing destination")
        self.files[new]=self.files.pop(old)
        self.calls.append("RNTO")

    def delete(self, name):
        self.files.pop(name,None)

    def quit(self):
        self.calls.append("quit")

    def close(self):
        self.calls.append("close")


class FTPSContract(unittest.TestCase):
    def setUp(self):
        self.directory=tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.feed=Path(self.directory.name)/"jdlearn.xml"
        self.feed.write_bytes(b"<updates><update /></updates>")
        self.config={
            "JDLEARN_FTPS_HOST":"ftp.example.org",
            "JDLEARN_FTPS_USER":"dedicated-user",
            "JDLEARN_FTPS_PASSWORD":"not-a-real-secret",
            "JDLEARN_FTPS_DIRECTORY":"/",
            "JDLEARN_FEED_FILE":str(self.feed)
        }
        self.ftp=None

    def factory(self, **kwargs):
        self.ftp=FakeFTPS(**kwargs)
        return self.ftp

    def test_jailed_account_root_and_protected_transfer(self):
        pub.publish(self.config,self.factory)
        self.assertEqual(self.ftp.dir,"/")
        self.assertEqual(self.ftp.files["jdlearn.xml"],self.feed.read_bytes())
        self.assertEqual(self.ftp.calls[:4],["connect","auth","login","prot_p"])
        self.assertNotIn("jdlearn.xml.upload",self.ftp.files)

    def test_failed_rename_does_not_delete_previous_feed(self):
        def fail_factory(**kwargs):
            ftp=self.factory(**kwargs)
            ftp.reject_rename=True
            return ftp
        with self.assertRaises(OSError):
            pub.publish(self.config,fail_factory)
        self.assertEqual(self.ftp.files["jdlearn.xml"],b"prior release")
        self.assertEqual(list(self.ftp.files),["jdlearn.xml"])

    def test_missing_password_blocks_connection(self):
        config=dict(self.config)
        del config["JDLEARN_FTPS_PASSWORD"]
        with self.assertRaisesRegex(ValueError,"JDLEARN_FTPS_PASSWORD"):
            pub.publish(config,self.factory)
        self.assertIsNone(self.ftp)

    def test_invalid_directory_is_refused(self):
        for path in ("/../other","relative","/updates/./other"):
            with self.subTest(path=path):
                config=dict(self.config, JDLEARN_FTPS_DIRECTORY=path)
                with self.assertRaises(ValueError):
                    pub.publish(config,self.factory)

    def test_no_plaintext_downgrade_when_auth_tls_fails(self):
        def factory(**kwargs):
            ftp=self.factory(**kwargs)
            def reject():
                raise OSError("AUTH TLS unsupported")
            ftp.auth=reject
            return ftp
        with self.assertRaisesRegex(OSError,"AUTH TLS"):
            pub.publish(self.config,factory)
        self.assertNotIn("login",self.ftp.calls)
        self.assertNotIn("STOR",self.ftp.calls)


if __name__=="__main__":
    unittest.main()
