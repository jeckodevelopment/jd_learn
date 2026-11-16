#!/usr/bin/env python3
"""FE-09 static regression contract: public Joomla teachers directory."""
from pathlib import Path
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
model = (root / "component/site/src/Model/TeachersModel.php").read_text()
view = (root / "component/site/src/View/Teachers/HtmlView.php").read_text()
layout = (root / "component/site/tmpl/teachers/default.php").read_text()
router = (root / "component/site/src/Service/Router.php").read_text()
metadata = root / "component/site/tmpl/teachers/default.xml"
ET.parse(metadata)
assert "TeacherProfiles::available" in model, "Directory must use canonical visibility ACL"
assert "getIdentity()" in model and "getTag()" in model
assert "getStoreId" in model and "getPagination" not in model
assert "t.bio,t.image,t.params_json" in model
assert "user_id" not in model and "email" not in model and "username" not in model
assert "get('Items')" in view and "get('Pagination')" in view
assert "getWebAssetManager()->useStyle('com_jlms.site')" in view
assert "new RouterViewConfiguration('teachers')" in router
assert "new RouterViewConfiguration('teacher')" in router
assert "setParent($teachers)" not in router, "Existing teacher routes must be preserved"
assert "view=teacher&id=" in layout and "Route::_(" in layout
assert "strip_tags" in layout and "$this->escape" in layout
assert "jlms-course-grid" in layout and "jlms-teacher-card" in layout
assert "getPagesLinks()" in layout
assert "teacher->user_id" not in layout and "teacher->email" not in layout
for lang in ("it-IT", "en-GB"):
    catalog = (root / f"component/site/language/{lang}/com_jlms.ini").read_text()
    for key in ("COM_JLMS_TEACHERS", "COM_JLMS_TEACHERS_EMPTY",
                "COM_JLMS_MENU_TEACHERS_TITLE", "COM_JLMS_MENU_TEACHERS_DESC"):
        assert key + "=" in catalog, (lang, key)
print("FE-09 directory static contract OK")
