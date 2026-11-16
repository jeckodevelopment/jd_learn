#!/usr/bin/env python3
"""FE-11 category and catalogue inherited-menu contract."""
from pathlib import Path
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
menu = ET.parse(root / "component/site/tmpl/category/default.xml").getroot()
config = ET.parse(root / "component/administrator/config.xml").getroot()
menu_fields = {f.get("name"): f for f in menu.findall(".//field")}
global_fields = {f.get("name"): f for f in config.findall(".//field")}
for key in ("category_show_courses", "category_show_subcategories"):
    assert key in menu_fields and key in global_fields
    assert menu_fields[key].get("default") == ""
    assert {v.get("value") for v in menu_fields[key].findall("option")} == {"", "0", "1"}
    assert global_fields[key].get("default") == "1"
for key in ("catalog_show_filters", "catalog_show_order", "catalog_page_size", "catalog_default_order"):
    assert key in menu_fields and key in global_fields
    assert menu_fields[key].get("default") == ""
    assert "" in {v.get("value") for v in menu_fields[key].findall("option")}
category = (root / "component/site/src/Helper/CategoryOptions.php").read_text()
catalogue = (root / "component/site/src/Helper/CatalogOptions.php").read_text()
view = (root / "component/site/src/View/Category/HtmlView.php").read_text()
layout = (root / "component/site/tmpl/category/default.php").read_text()
assert "ComponentHelper::getParams('com_jlms')" in category
assert "($menu->query['view'] ?? '') === 'category'" in category
assert "(int) ($menu->query['id'] ?? 0) === $categoryId" in category
assert "in_array($local, ['0', '1'], true)" in category
assert "Factory::getApplication()->getInput()->getInt('id', 0)" in catalogue
assert "CatalogOptions::enabled('catalog_show_filters')" in view
assert "CatalogOptions::enabled('catalog_show_order')" in view
assert "<?php if ($this->showFilters) : ?>" in layout
assert "<?php if ($this->showOrder) : ?>" in layout
assert "<?php if ($this->showSubcategories) : ?>" in layout
assert "<?php if ($this->showCourses) : ?>" in layout
assert "get('Items')" in view and "get('Pagination')" in view
print("FE-11 Joomla category menu inheritance contract OK")
