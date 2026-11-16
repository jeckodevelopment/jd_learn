#!/usr/bin/env python3
"""FE-08: per-menu category section visibility preserving Joomla ACL projections."""
from pathlib import Path
from xml.etree import ElementTree as ET
root=Path(__file__).resolve().parents[1]
read=lambda p:(root/p).read_text(encoding="utf-8")
layout=read("component/site/tmpl/category/default.php")
view=read("component/site/src/View/Category/HtmlView.php")
opt=read("component/site/src/Helper/CategoryOptions.php")
meta=ET.parse(root/"component/site/tmpl/category/default.xml").getroot()
for key in ("category_show_subcategories","category_show_courses"):
    field=meta.find("./fields[@name='params']/fieldset/field[@name='"+key+"']")
    assert field is not None and field.get("default")=="1"
    assert {(v.get("value"),v.text) for v in field.findall("option")}=={("1","JYES"),("0","JNO")}
    assert key in opt
assert "getMenu()->getActive()" in opt
assert "($menu->query['option'] ?? '') !== 'com_jlms'" in opt
assert "($menu->query['view'] ?? '') !== 'category'" in opt
assert "(int) ($menu->query['id'] ?? 0) !== $categoryId" in opt
assert "return $options;" in opt and "'subcategories' => true" in opt and "'courses' => true" in opt
assert "CategoryOptions::sections((int) $this->category->id)" in view
assert "$this->showSubcategories = $options['subcategories'];" in view
assert "$this->showCourses = $options['courses'];" in view
assert "$this->items = $this->showCourses ? $this->get('Items') : [];" in view
assert "if ($this->showSubcategories)" in view
assert "if ($this->showSubcategories) : " in layout or "if ($this->showSubcategories) :" in layout
assert "if ($this->showCourses) :" in layout
assert "id=\"jlms-subcategories\"" in layout and "id=\"jlms-category-courses\"" in layout
assert "subcategories_limitstart" in view and "pagination.prefix" in view
assert "jlms-subcategories-pagination" in layout and "jlms-category-courses-pagination" in layout
assert "getPagesLinks()" in layout
assert "getCatalogService()->category(" in read("component/site/src/Model/CategoryModel.php")
assert "getCatalogService()->categoryQuery(" in read("component/site/src/Model/CategoriesModel.php")
assert 'class="jlms-card jlms-category-card jlms-subcategory"' in layout
assert 'class="jlms-card jlms-course-card"' in layout
for lang in ("it-IT","en-GB"):
    ini=read(f"component/site/language/{lang}/com_jlms.ini")
    for key in ("COM_JLMS_CATEGORY_MENU_SETTINGS","COM_JLMS_CATEGORY_SHOW_SUBCATEGORIES",
                "COM_JLMS_CATEGORY_SHOW_SUBCATEGORIES_DESC","COM_JLMS_CATEGORY_SHOW_COURSES",
                "COM_JLMS_CATEGORY_SHOW_COURSES_DESC"):
        assert ini.count(key+"=")==1, (lang,key)
print("FE-08 independent category sections and ACL projection: OK")
