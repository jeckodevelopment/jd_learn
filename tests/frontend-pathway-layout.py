#!/usr/bin/env python3
"""FE-07: pathway cover, existing ACL projection, and accessible ordered sequence."""
from pathlib import Path
from xml.etree import ElementTree as ET
root=Path(__file__).resolve().parents[1]
read=lambda path:(root/path).read_text(encoding='utf-8')
service=read('component/administrator/src/Application/PathwayService.php')
model=read('component/administrator/src/Model/PathwayModel.php')
art=read('component/administrator/src/Application/PathwayArtwork.php')
form=ET.parse(root/'component/administrator/forms/pathway.xml').getroot()
catalog=read('component/site/tmpl/pathways/default.php')
detail=read('component/site/tmpl/pathway/default.php')
css=read('component/media/css/site.css')
assert 'p.params_json' in service and 'p.state = 1' in service
assert 'getPathwayService()' in read('component/site/src/Model/PathwaysModel.php')
assert 'getPathwayService()' in read('component/site/src/Model/PathwayModel.php')
assert "->whereIn('p.access', $levels)" in service
assert "->where('e.user_id = :pathwayOwner')" in service
assert 'PathwayProgress::summarize($courses)' in service
assert "hasPersonalProgress ? $aggregate['percent'] : null" in service
assert "getAuthorisedViewLevels()" in service
assert 'CourseArtwork::path(' in model and 'CourseArtwork::validateLocal(' in model
assert "unset($data['cover_image'], $data['cover_alt'], $data['cover_decorative'])" in model
assert "json_encode($artwork" in model
assert "CourseArtwork::path(" in art
assert "catch (\\Throwable)" in art
for field in ('cover_image','cover_alt','cover_decorative'):
    assert form.find(f"./fieldset[@name='artwork']/field[@name='{field}']") is not None
assert "renderFieldset('artwork')" in read('component/administrator/tmpl/pathway/edit.php')
assert 'PathwayArtwork::fromParams' in catalog and 'PathwayArtwork::fromParams' in detail
assert 'jlms-pathway-card__cover' in catalog and 'jlms-pathway-card__title' in catalog
assert 'jlms-pathway-cover' in detail and 'jlms-pathway-sequence' in detail
assert 'jlms-pathway-timeline' in detail and '<ol' in detail and '<li' in detail
assert "if (!$this->isGuest && $this->item['progress'] !== null)" in detail
assert "if (!$this->isGuest)" in detail
assert "$course['percent'] !== null" in detail
assert "$course['id'] !== null" in detail
assert "COM_JLMS_PATHWAY_SEQUENCE_INFO" in detail
assert "!empty($course['delivery_suspended'])" in detail
assert "COM_JLMS_COURSE_DELIVERY_SUSPENDED" in detail
assert "COM_JLMS_PATHWAY_COURSE_RESTRICTED" in detail
assert "'delivery_suspended' => $deliverySuspended" in service
assert "task=enrollment.start" not in detail and 'task=enrollment.start' not in catalog
assert '#jlms-pathway .jlms-pathway-timeline' in css
assert '#jlms-pathways .jlms-pathway-card__cover' in css
metadata=ET.parse(root/'component/site/tmpl/pathway/default.xml').getroot()
assert metadata.find("./fields[@name='request']/fieldset/field[@name='id']") is not None
for lang in ('it-IT','en-GB'):
    strings=read(f'component/site/language/{lang}/com_jlms.ini')
    system=read(f'component/site/language/{lang}/com_jlms.sys.ini')
    admin=read(f'component/administrator/language/{lang}/com_jlms.ini')
    for key in ('COM_JLMS_PATHWAY_COURSE_COUNT','COM_JLMS_PATHWAY_STEP','COM_JLMS_PATHWAY_SEQUENCE_INFO'):
        assert strings.count(key+'=')==1
    for key in ('COM_JLMS_MENU_PATHWAY_TITLE','COM_JLMS_MENU_PATHWAY_DESC'):
        assert strings.count(key+'=')==1 and system.count(key+'=')==1
    for key in ('COM_JLMS_PATHWAY_COVER','COM_JLMS_PATHWAY_COVER_HELP'):
        assert admin.count(key+'=')==1
print('FE-07 pathway covers, ACL, timeline, progress, i18n and menu: OK')

# SCR-04: visible progress requires all course enrollment/rule bases.
assert "PathwayProgress::hasCompleteBasis($courses)" in service
assert "'progress_basis' => false" in service
assert "'progress_basis' => $enrollment !== null && $rules !== null" in service
