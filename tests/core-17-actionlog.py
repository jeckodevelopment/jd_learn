#!/usr/bin/env python3
"""CORE-17: verify Joomla actionlog packaging and post-commit event contract."""
from pathlib import Path
import xml.etree.ElementTree as ET
root=Path(__file__).resolve().parents[1]
manifest=ET.parse(root/'plugins/actionlog/jdlearn/jdlearn.xml').getroot()
assert manifest.get('type')=='plugin' and manifest.get('group')=='actionlog'
assert manifest.find('namespace') is not None
package=ET.parse(root/'packages/pkg_jlms.xml').getroot()
children=package.findall('./files/file')
assert len(children)==12
assert sum(x.get('group')=='actionlog' and x.get('id')=='jdlearn'
               and x.text=='plg_actionlog_jdlearn.zip' for x in children)==1
build=(root/'tools/build.py').read_text()
assert "plg_actionlog_jdlearn.zip" in build and "'constituents':12" in build
plugin=(root/'plugins/actionlog/jdlearn/src/Extension/Jdlearn.php').read_text()
service=(root/'component/administrator/src/Application/PublicationService.php').read_text()
assert "onJlmsCourseTransition" in plugin and "getSubscribedEvents()" in plugin
assert "AssessmentActionlogModel" in plugin and "com_jlms.course" in plugin
assert "['review', 'return', 'publish', 'unpublish', 'archive', 'restore']" in plugin
assert "addLog([$message]" in plugin
assert "onJlmsCourseTransition" in service
assert service.index("$this->db->transactionCommit();",service.index("public function transition(")) < service.index("onJlmsCourseTransition",service.index("public function transition("))
assert "PluginHelper::importPlugin('actionlog')" in service
assert "catch (\\Throwable)" in service
assert "assessment" in plugin.lower() and "enrollment" in plugin.lower()
installer=(root/'plugins/actionlog/jdlearn/installer.php').read_text()
assert "if ($type !== 'install')" in installer and "enabled" in installer
for lang in ('it-IT','en-GB'):
    folder=root/'plugins/actionlog/jdlearn/language'/lang
    sys=(folder/'plg_actionlog_jdlearn.sys.ini').read_text()
    ini=(folder/'plg_actionlog_jdlearn.ini').read_text()
    assert 'PLG_ACTIONLOG_JDLEARN=' in sys
    assert 'PLG_ACTIONLOG_JDLEARN_COURSE_TRANSITION=' in ini
print('CORE-17 native actionlog plugin, events, minimal payload, packaging IT/EN: OK')
