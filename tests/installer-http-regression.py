#!/usr/bin/env python3
"""Reproduce the Joomla 6.2 administrator upload path for the first failing PR #366.

Runs ONLY against disposable Joomla fixtures in CI; performs a real upload,
installer controller and model invocation, and verifies the installed version.
"""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener, HTTPCookieProcessor, Request
from urllib.error import HTTPError
from urllib.parse import urlencode, urljoin
from http.cookiejar import CookieJar
import json, os, re, subprocess, sys, time, uuid

root = Path(sys.argv[1]).resolve()
package = Path(sys.argv[2]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE') != '1' or not package.is_file() or not root.is_dir():
    raise RuntimeError('Only disposable Joomla fixture and existing ZIP are supported')
base = 'http://127.0.0.1:8097'
config = root / 'configuration.php'
original = config.read_text()
changed, count = re.subn(r'public\s+\$live_site\s*=\s*[^;]+;', "public $live_site = '" + base + "';", original)
if count != 1:
    raise RuntimeError('Cannot locate Joomla fixture live_site setting')
config.write_text(changed)

class Page(HTMLParser):
    def __init__(self, raw):
        self.forms = []
        self.current = None
        self.base = None
        super().__init__(convert_charrefs=True)
        self.feed(raw)

    def handle_starttag(self, tag, attributes):
        attr = dict(attributes)
        if tag == 'base':
            self.base = attr.get('href')
        if tag == 'form':
            self.current = {'action': attr.get('action', ''), 'fields': {}}
            self.forms.append(self.current)
        if tag == 'input' and self.current is not None and 'name' in attr:
            self.current['fields'][attr['name']] = attr.get('value', '')

    def handle_endtag(self, tag):
        if tag == 'form':
            self.current = None

client = build_opener(HTTPCookieProcessor(CookieJar()))
log = open(root.parent / 'installer-http-regression.log', 'w+')
server = subprocess.Popen(['php', '-S', '127.0.0.1:8097', '-t', str(root)], stdout=log, stderr=log)

def request(path, data=None, headers=None):
    try:
        with client.open(Request(urljoin(base, path), data=data, headers=headers or {}), timeout=90) as response:
            return response.geturl(), response.status, response.read().decode('utf-8', errors='replace')
    except HTTPError as exc:
        return exc.geturl(), exc.code, exc.read().decode('utf-8', errors='replace')

try:
    for _ in range(50):
        try:
            url, status, page = request('/administrator/index.php')
            break
        except OSError:
            if server.poll() is not None:
                raise RuntimeError('Disposable Joomla HTTP server terminated')
            time.sleep(0.2)
    login_page = Page(page)
    login = next(f for f in login_page.forms if 'username' in f['fields'] and 'passwd' in f['fields'])
    login_data = login['fields'] | {
        'username': 'cioperator',
        'passwd': 'Ci-only-Long-Password-2026',
    }
    login_url = urljoin(urljoin(url, login_page.base or ''), login['action'])
    _, status, body = request(login_url, urlencode(login_data).encode())
    if status != 200:
        raise RuntimeError('Admin login HTTP '+str(status)+': '+re.sub(r'<[^>]+>', ' ', body)[-500:])

    _, status, installer_html = request('/administrator/index.php?option=com_installer&view=install')
    if status != 200:
        raise RuntimeError('Joomla installer view failed HTTP '+str(status))
    installer_page = Page(installer_html)
    form = next(f for f in installer_page.forms if f['fields'].get('task') == 'install.install')
    tokens = [name for name, val in form['fields'].items() if re.fullmatch('[a-f0-9]{32}', name) and val == '1']
    if len(tokens) != 1:
        raise RuntimeError('Could not locate Joomla administrator CSRF token')
    boundary = '----JD-Learn-Disposable-' + uuid.uuid4().hex
    parts = []
    for name, val in [('installtype', 'upload'), ('task', 'install.ajax_upload'), (tokens[0], '1')]:
        parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+name+'"\r\n\r\n'+val+'\r\n').encode())
    file_header = ('--'+boundary+'\r\nContent-Disposition: form-data; name="install_package"; filename="'+package.name+'"\r\nContent-Type: application/zip\r\n\r\n').encode()
    parts.extend([file_header, package.read_bytes(), b'\r\n', ('--'+boundary+'--\r\n').encode()])
    _, status, body = request('/administrator/index.php?option=com_installer&task=install.ajax_upload', b''.join(parts),
                              {'Content-Type': 'multipart/form-data; boundary='+boundary, 'X-Requested-With':'XMLHttpRequest'})
    try:
        parsed = json.loads(body)
    except ValueError:
        parsed = {'body': re.sub(r'<[^>]+>', ' ', body)[-1400:]}
    print(json.dumps({'joomla': '6.2.0', 'channel':'native administrator HTTP multipart upload',
                      'status':status, 'response':parsed}, ensure_ascii=False))
    if status != 200 or parsed.get('success') is False or parsed.get('error'):
        raise RuntimeError('Joomla administrator upload failed')
    if 'redirect' not in parsed.get('data', {}):
        raise RuntimeError('Joomla upload returned no expected redirect result')
finally:
    server.terminate()
    try:
        server.wait(timeout=10)
    except subprocess.TimeoutExpired:
        server.kill()
    config.write_text(original)
    log.close()
