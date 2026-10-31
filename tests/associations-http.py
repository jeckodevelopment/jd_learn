"""Native language switcher, routing and association manager on a disposable Joomla site."""
from pathlib import Path
from urllib.request import build_opener, HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin, urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import json, os, re, subprocess, sys, time

root = Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE') != '1':
    raise RuntimeError('Disposable Joomla site required')
fixture = json.loads((root.parent / 'associations-fixture.json').read_text())
config = root / 'configuration.php'
original = config.read_text()
base = 'http://127.0.0.1:8093'
log = open(root.parent / 'associations-http.log', 'w+')
server = None
checks = 0

class Page(HTMLParser):
    def __init__(self, text):
        super().__init__(convert_charrefs=True)
        self.links = []
        self.forms = []
        self.form = None
        self.feed(text)
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'a' and 'href' in attrs: self.links.append(attrs)
        if tag == 'form':
            self.form = {'action': attrs.get('action', ''), 'fields': {}}
            self.forms.append(self.form)
        if tag == 'input' and self.form is not None and 'name' in attrs:
            self.form['fields'][attrs['name']] = attrs.get('value', '')
    def handle_endtag(self, tag):
        if tag == 'form': self.form = None

http = build_opener(HTTPCookieProcessor(CookieJar()))
def get(path):
    with http.open(urljoin(base, path), timeout=20) as response:
        return response.geturl(), response.read().decode('utf-8')
def expect(value, message):
    global checks
    if not value: raise RuntimeError(message)
    checks += 1
try:
    subprocess.run(['php', 'tests/associations-http-fixture.php', str(root)], check=True)
    server = subprocess.Popen(['php', '-S', '127.0.0.1:8093', '-t', str(root)], stdout=log, stderr=log)
    for _ in range(40):
        try: get('/administrator/index.php'); break
        except OSError: time.sleep(.2)
    for sef in [0, 1]:
        changed, count = re.subn(r'public\s+\$sef\s*=\s*[^;]+;', f'public $sef = {sef};', original)
        if count != 1: raise RuntimeError('SEF configuration fixture missing')
        config.write_text(changed)
        _, html = get('/index.php?option=com_jlms&view=course&id=' + str(fixture['en']) + '&lang=en')
        expect('id="jlms-course"' in html and 'association-en' in html, 'English course sheet missing')
        page = Page(html)
        italian = next((a['href'] for a in page.links if a.get('lang') == 'it-IT' or a.get('hreflang') == 'it-IT'), None)
        expect(italian is not None, 'Native mod_languages Italian link absent')
        _, translated = get(italian)
        expect('id="jlms-course"' in translated and 'association-it' in translated, 'Native language switch must resolve associated course, not catalog home')
        expect('PRIVATE_TRANSLATION_BODY' not in translated and 'data-course-id="'+str(fixture['it'])+'"' in translated, 'Public translation contains no protected lesson body')
        _, untranslated = get('/index.php?option=com_jlms&view=course&id='+str(fixture['all'])+'&lang=en')
        target = next(a['href'] for a in Page(untranslated).links if a.get('lang') == 'it-IT' or a.get('hreflang') == 'it-IT')
        _, fallback = get(target)
        expect('id="jlms-courses"' in fallback and 'id="jlms-course"' not in fallback, 'Missing translation uses native language home fallback')
    url, html = get('/administrator/index.php')
    login = next(f for f in Page(html).forms if 'username' in f['fields'])
    data = login['fields'] | {'username':'cioperator','passwd':'Ci-only-Long-Password-2026'}
    with http.open(urljoin(url, login['action']), data=urlencode(data).encode(), timeout=20) as response: response.read()
    _, html = get('/administrator/index.php?option=com_associations&view=associations&itemtype=com_jlms.course')
    expect('association-en' in html and 'association-it' in html, 'Core associations manager must list courses with mapped access/category fields')
    _, html = get('/administrator/index.php?option=com_jlms&task=course.edit&id='+str(fixture['other_en']))
    expect('id="jlms-course-associations"' in html and 'jform[associations][it-IT]' in html, 'Native course editor association controls missing')
    print(json.dumps({'native_multilingual_http_checks':checks,'sef_modes':[0,1],'status':'passed'}))
except Exception:
    log.flush(); log.seek(0); print(log.read()[-12000:], file=sys.stderr)
    raise
finally:
    config.write_text(original)
    if server is not None:
        server.terminate()
        try: server.wait(timeout=5)
        except subprocess.TimeoutExpired: server.kill(); server.wait()
    subprocess.run(['php', 'tests/associations-http-fixture.php', str(root), 'disable'], check=True)
    log.close()
