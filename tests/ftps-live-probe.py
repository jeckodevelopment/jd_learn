#!/usr/bin/env python3
"""Offline tests of FTPS live-check safety; no network or secrets required."""
import importlib.util
from pathlib import Path
import ssl
import sys
import unittest

source=Path(__file__).resolve().parents[1]/"tools/check_ftps_connection.py"
sys.path.insert(0,str(source.parent))
spec=importlib.util.spec_from_file_location("check_ftps_connection",source)
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class FakeFTPS:
    def __init__(self,context,timeout,deny_overwrite=False):
        assert context.verify_mode == ssl.CERT_REQUIRED
        assert context.check_hostname is True
        self.files={"jdlearn.xml":b"NEVER REPLACE ME"}
        self.calls=[]
        self.deny_overwrite=deny_overwrite
    def connect(self,**kwargs): self.calls.append("connect")
    def auth(self): self.calls.append("auth")
    def login(self,**kwargs):
        assert self.calls==["connect","auth"],"Must AUTH TLS before credentials"
        self.calls.append("login")
    def prot_p(self):self.calls.append("prot_p")
    def set_pasv(self,value):assert value
    def cwd(self,value):assert value=="/";self.calls.append("cwd")
    def storbinary(self,cmd,body):
        assert self.calls.index("prot_p")<len(self.calls)
        name=cmd[5:]
        assert name.startswith(".jdlearn-ftps-probe-")
        self.files[name]=body.read()
    def retrbinary(self,cmd,callback):callback(self.files[cmd[5:]])
    def rename(self,src,dest):
        assert src.startswith(".jdlearn-ftps-probe-")
        assert dest.startswith(".jdlearn-ftps-probe-")
        if self.deny_overwrite:raise OSError("Destination exists")
        self.files[dest]=self.files.pop(src)
    def delete(self,name):
        assert name.startswith(".jdlearn-ftps-probe-")
        self.files.pop(name,None)
    def quit(self):pass
    def close(self):pass


class ProbeTests(unittest.TestCase):
    def setUp(self):
        self.ftp=None
        self.env={"JDLEARN_FTPS_HOST":"ftps.example.org",
                  "JDLEARN_FTPS_USER":"user",
                  "JDLEARN_FTPS_PASSWORD":"fake-test-only",
                  "JDLEARN_FTPS_DIRECTORY":"/"}
    def factory(self,**kwargs):
        self.ftp=FakeFTPS(**kwargs)
        return self.ftp
    def test_probe_cleans_and_preserves_live_feed(self):
        module.probe(self.env,self.factory)
        self.assertEqual(self.ftp.files,{"jdlearn.xml":b"NEVER REPLACE ME"})
        self.assertEqual(self.ftp.calls[:4],["connect","auth","login","prot_p"])
    def test_refused_overwrite_is_fail_closed_and_cleans(self):
        def factory(**kwargs):
            self.ftp=FakeFTPS(deny_overwrite=True,**kwargs)
            return self.ftp
        with self.assertRaisesRegex(RuntimeError,"production feed updates remain blocked"):
            module.probe(self.env,factory)
        self.assertEqual(self.ftp.files,{"jdlearn.xml":b"NEVER REPLACE ME"})
    def test_missing_credentials_prevent_connection(self):
        env=dict(self.env)
        env.pop("JDLEARN_FTPS_PASSWORD")
        with self.assertRaisesRegex(ValueError,"JDLEARN_FTPS_PASSWORD"):
            module.probe(env,self.factory)
        self.assertIsNone(self.ftp)


if __name__=="__main__":
    unittest.main()
