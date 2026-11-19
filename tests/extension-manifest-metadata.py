#!/usr/bin/env python3
"""Validate every nested Joomla manifest shipped in a JD Learn package."""
from pathlib import Path
import argparse
import io
import re
import zipfile
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[1]
NAMES={
    'lib_jlms':'JD Learn - Contracts',
    'com_jlms':'COM_JLMS',
    'plg_jlmsvideo_youtube':'PLG_JLMSVIDEO_YOUTUBE',
    'plg_jlmsvideo_vimeo':'PLG_JLMSVIDEO_VIMEO',
    'plg_jlmsvideo_cloudflarestream':'PLG_JLMSVIDEO_CLOUDFLARESTREAM',
    'plg_jlmsvideo_html5':'PLG_JLMSVIDEO_HTML5',
    'plg_task_jlms':'PLG_TASK_JLMS',
    'plg_privacy_jdlearn':'PLG_PRIVACY_JDLEARN',
    'plg_system_jlmsmenu':'PLG_SYSTEM_JLMSMENU',
    'plg_finder_jlms':'PLG_FINDER_JLMS',
    'plg_user_jdlearnconsent':'PLG_USER_JDLEARNCONSENT',
    'plg_actionlog_jdlearn':'PLG_ACTIONLOG_JDLEARN',
}
DISPLAY={
    'plg_jlmsvideo_youtube':'JD Learn - YouTube',
    'plg_jlmsvideo_vimeo':'JD Learn - Vimeo',
    'plg_jlmsvideo_cloudflarestream':'JD Learn - Cloudflare Stream',
    'plg_jlmsvideo_html5':'JD Learn - HTML5',
    'plg_task_jlms':'JD Learn - Scheduled Tasks',
    'plg_privacy_jdlearn':'JD Learn - Privacy',
    'plg_system_jlmsmenu':'JD Learn - Menu Navigation',
    'plg_finder_jlms':'JD Learn - Smart Search',
    'plg_user_jdlearnconsent':'JD Learn - User Consent',
    'plg_actionlog_jdlearn':'JD Learn - Action Log',
}
p=argparse.ArgumentParser()
p.add_argument('--zip',type=Path,required=True)
p.add_argument('--version',required=True)
p.add_argument('--date',required=True)
args=p.parse_args()
assert re.fullmatch(r'\d{4}-\d{2}-\d{2}',args.date), 'Date not YYYY-MM-DD'
with zipfile.ZipFile(args.zip) as package:
    main=ET.fromstring(package.read('pkg_jlms.xml'))
    assert main.tag=='extension' and main.get('type')=='package'
    assert main.findtext('version')==args.version
    assert main.findtext('creationDate')==args.date
    assert main.findtext('name')=='JD Learn'
    children=main.findall('./files/file')
    assert len(children)==len(NAMES),(len(children),len(NAMES))
    seen=set()
    for child in children:
        filename=child.text
        key=filename.removesuffix('.zip')
        assert key in NAMES and key not in seen,(key,seen)
        seen.add(key)
        with zipfile.ZipFile(io.BytesIO(package.read('constituents/'+filename))) as inner:
            manifest='jlms.xml' if key in ('com_jlms','lib_jlms') else key.split('_')[-1]+'.xml'
            root=ET.fromstring(inner.read(manifest))
            assert root.tag=='extension'
            assert root.findtext('version')==args.version,(key,'version',root.findtext('version'))
            assert root.findtext('creationDate')==args.date,(key,'date',root.findtext('creationDate'))
            assert root.findtext('name')==NAMES[key],(key,'name',root.findtext('name'))
            if key.startswith('plg_'):
                assert root.get('type')=='plugin'
                assert root.get('group')==key.split('_')[1]
                for lang in ('it-IT','en-GB'):
                    sys_path=f'language/{lang}/{key}.sys.ini'
                    sys_data=inner.read(sys_path).decode('utf-8')
                    assert f'{NAMES[key]}="{DISPLAY[key]}"' in sys_data.splitlines(),(key,lang,'display translation')
            elif key=='com_jlms':
                assert root.get('type')=='component'
            elif key=='lib_jlms':
                assert root.get('type')=='library'
assert seen==set(NAMES)
print(f'Metadata aligned across JD Learn package and {len(seen)} constituents: {args.version} / {args.date}')
