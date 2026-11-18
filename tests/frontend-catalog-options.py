#!/usr/bin/env python3
"""FE-01: inspect Joomla catalogue menu/global options and safe view contracts.

Structural gate; acceptance in a real Joomla site is still required.
"""
from pathlib import Path
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
catalog = root / "component/site/tmpl/courses"
menu = ET.parse(catalog / "default.xml").getroot()
component = ET.parse(root / "component/administrator/config.xml").getroot()
names = {
    "catalog_show_filters", "catalog_show_order", "catalog_show_cover",
    "catalog_show_summary", "catalog_show_category", "catalog_page_size", "catalog_default_order",
}
for options in (menu, component):
    fields = {x.attrib["name"]: x for x in options.findall(".//field") if "name" in x.attrib}
    assert names <= set(fields), ("missing catalogue options", names - set(fields))
    for key in names:
        assert fields[key].attrib.get("default") is not None, ("missing explicit default", key)

for folder in ("component/site/language", "component/administrator/language"):
    for language in ("it-IT", "en-GB"):
        content = (root / folder / language / "com_jlms.ini").read_text()
        for key in ("COM_JLMS_CATALOG_MENU_SETTINGS", "COM_JLMS_CATALOG_SHOW_FILTERS",
                    "COM_JLMS_CATALOG_SHOW_ORDER", "COM_JLMS_CATALOG_SHOW_COVER",
                    "COM_JLMS_CATALOG_SHOW_SUMMARY", "COM_JLMS_CATALOG_PAGE_SIZE",
                    "COM_JLMS_CATALOG_DEFAULT_ORDER", "COM_JLMS_CATALOG_SHOW_CATEGORY",
                    "COM_JLMS_CATALOG_CATEGORY", "COM_JLMS_CATALOG_ALL_CATEGORIES"):
            assert key + "=" in content, (language, key)

helper = (root / "component/site/src/Helper/CatalogOptions.php").read_text()
model = (root / "component/site/src/Model/CoursesModel.php").read_text()
view = (root / "component/site/src/View/Courses/HtmlView.php").read_text()
template = (catalog / "default.php").read_text()
css = (root / "component/media/css/site.css").read_text()
assert "ComponentHelper::getParams('com_jlms')" in helper
assert "getMenu()->getActive()" in helper
assert "($menu->query['view'] ?? '') === 'courses'" in helper
assert "($menu->query['view'] ?? '') === 'category'" in helper
assert "CatalogOptions::enabled('catalog_show_filters')" in model
assert "Factory::getApplication()->getInput()->get('filter'" in model
assert "CatalogOptions::get('catalog_page_size')" in model
assert "v.presentation_json AS catalog_presentation_json" in model
assert "$this->showFilters = CatalogOptions::enabled" in view
assert "$this->showCategory = $this->showFilters && CatalogOptions::enabled" in view
assert "getCategoryChoice()" in view and "getCategories()" in view
assert "<?php if ($this->showFilters) : ?>" in template
assert "<?php if ($this->showCategory) : ?>" in template
assert 'name="filter[category_id]"' in template
assert "COM_JLMS_CATALOG_ALL_CATEGORIES" in template
assert "catalog_show_category" in helper
assert "filter.category_choice" in model
assert "['recent', 'title', 'duration']" in model
assert "['recent', 'title', 'duration']" in helper
assert "v.presentation_json, '$.details.estimated_minutes'" in model
assert "($minutes IS NULL) ASC, $minutes ASC, c.id ASC" in model
assert 'value="duration"' in template
for fieldset in (menu, component):
    order_field = fieldset.find(".//field[@name='catalog_default_order']")
    assert "duration" in [o.get("value") for o in order_field.findall("option")]
for language in ("it-IT", "en-GB"):
    ini = (root / "component/site/language" / language / "com_jlms.ini").read_text()
    assert "COM_JLMS_CATALOG_ORDER_DURATION=" in ini

assert "COM_JLMS_CATALOG_CLEAR_FILTERS" in template
assert 'id="jlms-catalog-clear-filters"' in template
assert "$this->showFilters && ($this->search !== ''" in template
assert "($this->categoryId ? '&catid=' . (int) $this->categoryId : '')" in template
assert "filter[search]" not in template.split('id="jlms-catalog-clear-filters"')[1].split("<?php endif; ?>")[0]
for language in ("it-IT", "en-GB"):
    ini = (root / "component/site/language" / language / "com_jlms.ini").read_text()
    assert "COM_JLMS_CATALOG_CLEAR_FILTERS=" in ini

assert "isset($this->getCategories()[$categoryChoice])" in model
service = (root / "component/administrator/src/Application/CatalogService.php").read_text()
assert "publicCourseCategories" in service
assert "DISTINCT cat.id, cat.title" in service
assert "courseQuery($actor, $language, $scopeCategoryId, false)" in service
assert "<?php if ($this->showCover) : ?>" in template
assert "<?php if ($this->showSummary" in template
assert "CoursePresentation::fromSnapshot" in template
assert 'class="jlms-card jlms-course-card"' in template
assert "container-name: jlms-catalog" in css
assert ".jlms-course-grid--list" in css
assert "jlms-course-card__cover-placeholder" in css
print("FE-01 menu configuration, i18n and catalogue display contracts: OK")
