<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

final class CourseModel extends AdminModel implements \Joomla\CMS\Versioning\VersionableModelInterface
{
    use \Joomla\CMS\Versioning\VersionableModelTrait { saveHistory as private persistHistory; }

    public $typeAlias = 'com_jlms.course';
    protected $associationsContext = 'com_jlms.item';
    private bool $associationWrite = false;
    private int $preparedAsset = 0;
    private ?array $copyPlan = null;

    public function getTable($name = '', $prefix = '', $options = [])
    {
        $table = parent::getTable($name, $prefix, $options);
        if ($table instanceof \Jlms\Component\Jlms\Administrator\Table\CourseTable) {
            $table->requireStableAsset($this->associationWrite);
            if ($this->preparedAsset) { $table->usePreparedAsset($this->preparedAsset); }
        }
        return $table;
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.course', 'course', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        return Factory::getApplication()->getUserState('com_jlms.edit.course.data', []) ?: $this->getItem();
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            foreach (\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::editor($item, $this->siteTimezone()) as $key=>$value) { $item->$key=$value; }
            // Keep the editor on Joomla's global option for a new course; the site IANA zone
            // is still used above to interpret/display dates and is persisted on save.
            if (empty($item->id)) { $item->calendar_timezone = ''; }
        }
        if ($item && !empty($item->id)) {
            foreach (\Jlms\Component\Jlms\Administrator\Application\CoursePresentation::details($item->params_json) ?? [] as $field => $value) {
                if ($field !== '_schema') { $item->{'presentation_' . $field} = $value; }
            }
            $mailPreferences = json_decode((string) ($item->params_json ?: '{}'), true, 32, JSON_THROW_ON_ERROR)['mail_preferences'] ?? [];
            foreach (\Jlms\Component\Jlms\Administrator\Application\MailPreferences::EVENTS as $event) {
                $item->{'mail_' . $event} = $mailPreferences[$event] ?? 'inherit';
            }
            $item->certificate_enabled = json_decode((string) ($item->params_json ?: '{}'), true, 32, JSON_THROW_ON_ERROR)['certificate_enabled'] ?? 'inherit';
            $item->teacher_ids = \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::selected($this->getDatabase(),(int) $item->id);
            $item->tags = new \Joomla\CMS\Helper\TagsHelper();
            $item->tags->getTagIds((int) $item->id, 'com_jlms.course');
            $item->associations = [];
            foreach ($this->associationMembers((int) $item->id) as $member) {
                if ((int) $member->id !== (int) $item->id) {
                    $item->associations[$member->language] = (int) $member->id;
                }
            }
        }
        return $item;
    }

    private function siteTimezone(): string
    {
        return (string) Factory::getApplication()->getConfig()->get('offset', 'UTC');
    }

    protected function preprocessForm(\Joomla\CMS\Form\Form $form, $data, $group = 'content')
    {
        if (\Joomla\CMS\Language\Associations::isEnabled()) {
            $xml = new \SimpleXMLElement('<form><fields name="associations"><fieldset name="item_associations" label="COM_JLMS_ASSOCIATIONS" /></fields></form>');
            foreach (\Joomla\CMS\Language\LanguageHelper::getContentLanguages(false, false) as $language) {
                $field = $xml->fields->fieldset->addChild('field');
                foreach (['name' => $language->lang_code, 'type' => 'Courseassociation',
                    'addfieldprefix' => 'Jlms\Component\Jlms\Administrator\Field',
                    'language' => $language->lang_code, 'label' => $language->title, 'translate_label' => 'false',
                    'filter' => 'integer', 'default' => '0'] as $name => $value) {
                    $field->addAttribute($name, $value);
                }
            }
            $form->load($xml, false);
        }
        parent::preprocessForm($form, $data, $group);
        $form->setFieldAttribute('calendar_timezone', 'default', '');
    }

    private function associationMembers(int $id): array
    {
        $db = $this->getDatabase();
        $context = $this->associationsContext;
        $query = $db->createQuery()->select('c.*, a.' . $db->quoteName('key') . ' AS association_key')->from('#__associations AS a')
            ->innerJoin('#__associations AS b ON b.' . $db->quoteName('key') . ' = a.' . $db->quoteName('key') . ' AND b.context = a.context')
            ->innerJoin('#__jlms_courses AS c ON c.id = b.id')
            ->where('a.id = :id')->where('a.context = :context')
            ->bind(':id', $id, ParameterType::INTEGER)->bind(':context', $context);
        return $db->setQuery($query)->loadObjectList();
    }

    /** Called under the component asset lock and the source course lock. Core performs persistence. */
    private function validateAssociations(array &$data, \Joomla\CMS\User\User $actor, int $id, ?object $record): void
    {
        $members = $id ? $this->associationMembers($id) : [];
        $language = (string) ($data['language'] ?? $record->language ?? '*');
        if ($members && $language !== (string) $record->language) {
            throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
        }
        if (!array_key_exists('associations', $data)) { return; }
        if (!\Joomla\CMS\Language\Associations::isEnabled() || !is_array($data['associations'])) {
            throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
        }
        $languages = \Joomla\CMS\Language\LanguageHelper::getLanguages('lang_code');
        $targets = [];
        foreach ($data['associations'] as $tag => $target) {
            if (filter_var($target, FILTER_VALIDATE_INT) === false || (int) $target < 0) {
                throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
            }
            $target = (int) $target;
            if (!$target) { continue; }
            if (!isset($languages[$tag]) || $tag === $language || $language === '*' || $target === $id || in_array($target, $targets, true)) {
                throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
            }
            $targets[$tag] = $target;
        }
        $currentTargets = [];
        foreach ($members as $member) {
            if ((int) $member->id !== $id) { $currentTargets[$member->language] = (int) $member->id; }
        }
        ksort($targets); ksort($currentTargets);
        if ($targets === $currentTargets) {
            // Metadata-only save: leave the shared graph entirely untouched.
            unset($data['associations']);
            return;
        }
        $db = $this->getDatabase();
        $ids = array_unique(array_merge(array_values($targets), array_map(static fn ($m): int => (int) $m->id, $members)));
        sort($ids, SORT_NUMERIC);
        foreach ($ids as $target) {
            $query = $db->createQuery()->select('*')->from('#__jlms_courses')->where('id = :id')->bind(':id', $target, ParameterType::INTEGER);
            $query->setQuery((string) $query . ' FOR UPDATE');
            $item = $db->setQuery($query)->loadObject();
            $asset = 'com_jlms.course.' . $target;
            if (!$item || $actor->guest || $actor->block
                || !($actor->authorise('core.edit', $asset) || ((int) $item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset)))
                || ($item->checked_out && (int) $item->checked_out !== (int) $actor->id)) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if (!(((int) $item->state === 0 && $item->current_version_id === null)
                || ((int) $item->state === 1 && $item->current_version_id !== null))) {
                throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
            }
            $tag = array_search($target, $targets, true);
            if ($tag !== false && $item->language !== $tag) {
                throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
            }
            // Do not steal a member of another translation group. Detach it there first.
            foreach ($this->associationMembers($target) as $peer) {
                if (!$members || $peer->association_key !== $members[0]->association_key) {
                    throw new \DomainException('COM_JLMS_INVALID_ASSOCIATION');
                }
            }
        }
        // A nonempty zero field instructs native AdminModel to clear the old group too.
        $data['associations'] = $targets ?: [$language => 0];
        if (($targets || $members) && (!$record
            || (string) ($data['title'] ?? $record->title) !== (string) $record->title
            || (int) ($data['catid'] ?? $record->catid) !== (int) $record->catid)) {
            throw new \DomainException('COM_JLMS_ASSOCIATION_SAVE_SEPARATELY');
        }
        // Even an all-zero request on an existing course must retain its native asset.
        $this->associationWrite = $record !== null;
    }

    /** New course identity with a complete draft; native asset tree writes occur before the transaction. */
    public function duplicate(array $input): int
    {
        $db = $this->getDatabase();
        $actor = \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->getCurrentUser());
        $id = \Jlms\Component\Jlms\Administrator\Application\CurriculumService::id($input['course_id'] ?? null);
        $versionId = \Jlms\Component\Jlms\Administrator\Application\CurriculumService::id($input['version_id'] ?? null);
        $revision = \Jlms\Component\Jlms\Administrator\Application\CurriculumService::id($input['revision'] ?? null);
        $versionRevision = \Jlms\Component\Jlms\Administrator\Application\CurriculumService::id($input['expected_version_revision'] ?? null);
        $source = $this->getItem($id);
        if (!$source || !\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($actor, $source)
            || !\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate($db, $actor, (int) $source->catid)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        $title = $input['title'] ?? null;
        if (!is_string($title) || preg_match('/\A[^\r\n]{1,255}\z/u', trim($title)) !== 1) { throw new \InvalidArgumentException('COM_JLMS_INVALID_COURSE'); }
        $title = trim($title);
        $category = new \Joomla\CMS\Table\Category($db);
        if (!$category->load((int) $source->catid) || $category->extension !== 'com_jlms.course' || (int) $category->published === -2) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        $asset = new \Joomla\CMS\Table\Asset($db);
        $asset->setLocation((int) $category->asset_id, 'last-child');
        $asset->bind(['name' => 'com_jlms.copy.' . bin2hex(random_bytes(16)), 'title' => \Joomla\String\StringHelper::substr($title, 0, 100), 'rules' => '{}']);
        if (!$asset->check() || !$asset->store()) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED'); }
        $this->setState('course.copy_complete', false);
        $this->preparedAsset = (int) $asset->id;
        $this->copyPlan = ['course' => $id, 'version' => $versionId, 'revision' => $revision, 'version_revision' => $versionRevision];
        try {
            $data = (array) $source;
            $data['id'] = 0; $data['revision'] = 0; $data['title'] = $title;
            $data['alias'] = 'copy-' . bin2hex(random_bytes(12));
            unset($data['associations'], $data['teacher_ids']);
            if (!$this->save($data)) { throw new \RuntimeException($this->getError()); }
            return (int) $this->getState('course.id');
        } finally {
            // Failed authoring writes roll back before removing the unused reserved native asset.
            if (!$this->getState('course.copy_complete', false)) { $asset->delete((int) $asset->id); }
            $this->preparedAsset = 0; $this->copyPlan = null;
        }
    }

    private function copySource(\Joomla\CMS\User\User $actor): ?object
    {
        if ($this->copyPlan === null) { return null; }
        $db = $this->getDatabase();
        $id = $this->copyPlan['course']; $version = $this->copyPlan['version'];
        $q = $db->createQuery()->select('*')->from('#__jlms_courses')->where('id=:id')->bind(':id', $id, ParameterType::INTEGER);
        $q->setQuery((string) $q . ' FOR UPDATE'); $source = $db->setQuery($q)->loadObject();
        if (!$source || !\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($actor, $source)
            || ($source->checked_out && (int) $source->checked_out !== (int) $actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        if ((int) $source->revision !== $this->copyPlan['revision']) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        $q = $db->createQuery()->select('*')->from('#__jlms_course_versions')->where('id=:id')->bind(':id', $version, ParameterType::INTEGER);
        $q->setQuery((string) $q . ' FOR UPDATE'); $programme = $db->setQuery($q)->loadObject();
        if (!$programme || (int) $programme->course_id !== $id) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
        if ((int) $programme->revision !== $this->copyPlan['version_revision']) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        if ($programme->checked_out && (int) $programme->checked_out !== (int) $actor->id) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        return (object) ['course' => $source, 'version' => $programme];
    }

    public function save($data)
    {
        $db = $this->getDatabase();
        $this->associationWrite = false;
        $allowed = ['id', 'title', 'alias', 'catid', 'language', 'catalog_access', 'content_access', 'enrollment_mode', 'summary', 'revision', 'associations', 'teacher_ids', 'com_fields', 'tags', 'mail_enrollment', 'mail_completion', 'mail_suspension', 'mail_reactivation', 'mail_certificate', 'certificate_enabled'];
        foreach (\Jlms\Component\Jlms\Administrator\Application\CoursePresentation::FIELDS as $field) { $allowed[] = 'presentation_' . $field; }
        $allowed=array_merge($allowed,\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::FIELDS);
        $data = array_intersect_key($data, array_flip($allowed));
        $id = (int) ($data['id'] ?? 0);
        $expected = (int) ($data['revision'] ?? 0);
        unset($data['revision']);
        $db->transactionStart();
        try {
            // Serialize translation graph edits only in this short authoring transaction.
            // The row exists in native Joomla; no parallel association table or global site lock.
            $db->setQuery("SELECT id FROM #__assets WHERE name = 'com_jlms' FOR UPDATE")->loadResult()
                ?: throw new \DomainException('JERROR_ALERTNOAUTHOR');
            $user = new \Joomla\CMS\User\User((int) $this->getCurrentUser()->id);
            $user->clearAccessRights();
            if ($user->guest || $user->block || !$user->authorise('core.manage','com_jlms')) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $this->setCurrentUser($user);
            $copySource = $this->copySource($user);
            if ($copySource !== null && !array_key_exists('tags', $data)) {
                $tagIds = (new \Joomla\CMS\Helper\TagsHelper())->getTagIds((int) $copySource->course->id, 'com_jlms.course');
                $data['tags'] = $tagIds ? explode(',', $tagIds) : [];
            }
            $record = null;
            if ($id > 0) {
                $q = $db->createQuery()->select('*')->from($db->quoteName('#__jlms_courses'))
                    ->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
                // Joomla's query object keeps the bound parameters when setting a raw SQL suffix.
                // DatabaseQuery does not expose a forUpdate() builder method.
                $q->setQuery((string) $q . ' FOR UPDATE');
                $record = $db->setQuery($q)->loadObject();
                if (!$record) {
                    throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
                }
                $asset = 'com_jlms.course.' . $id;
                $canEdit = $user->authorise('core.edit', $asset)
                    || ((int) $record->created_by === (int) $user->id && $user->authorise('core.edit.own', $asset));
                if (!$canEdit) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                if ($record->checked_out && (int)$record->checked_out!==(int)$user->id) { throw new \DomainException('COM_JLMS_COURSE_CHECKED_OUT'); }
                if ((int) $record->revision !== $expected) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
                // Teaching records remain immutable; this model edits live editorial metadata.
                if ((int) $record->catid !== (int) ($data['catid'] ?? $record->catid)
                    && !$user->authorise('core.create', 'com_jlms.course.category.' . (int) $data['catid'])) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
            } elseif (!$user->authorise('core.create', 'com_jlms.course.category.' . (int) ($data['catid'] ?? 0))) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if (array_key_exists('summary', $data)) {
                $summary = $data['summary'];
                $previous = $record->summary ?? null;
                if (is_string($summary) && is_string($previous)
                    && str_replace(["\r\n", "\r"], "\n", $summary) === str_replace(["\r\n", "\r"], "\n", $previous)) {
                    // Preserve the original bytes when the browser only changed line endings.
                    unset($data['summary']);
                } elseif (!is_string($summary) || \Joomla\String\StringHelper::strlen($summary) > 500) {
                    throw new \DomainException('COM_JLMS_COURSE_SUMMARY_TOO_LONG');
                }
            }
            $this->validateAssociations($data, $user, $id, $record);
            $teachers = null;
            if (array_key_exists('teacher_ids', $data)) {
                $teachers = \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::assignment($db,$user,(string) ($data['language'] ?? $record->language ?? '*'),$data['teacher_ids']);
                if (!$id && $teachers) { throw new \DomainException('COM_JLMS_TEACHER_SAVE_FIRST'); }
                if ($teachers === \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::selected($db,$id)) { $teachers = null; }
                elseif ($id) { $this->associationWrite = true; } // Native asset must stay stable for atomic relation writes.
                unset($data['teacher_ids']);
            }
            $presentation = \Jlms\Component\Jlms\Administrator\Application\CoursePresentation::merge(
                $data, $record->params_json ?? '{}', static fn (string $html): string => \Joomla\CMS\Component\ComponentHelper::filterText($html),
            );
            foreach (\Jlms\Component\Jlms\Administrator\Application\CoursePresentation::FIELDS as $field) { unset($data['presentation_' . $field]); }
            if ($presentation !== null) { $data['params_json'] = $presentation; }
            $mailPreferences = \Jlms\Component\Jlms\Administrator\Application\MailPreferences::merge(
                $data, $data['params_json'] ?? $record->params_json ?? '{}',
            );
            foreach (\Jlms\Component\Jlms\Administrator\Application\MailPreferences::EVENTS as $event) { unset($data['mail_' . $event]); }
            if ($mailPreferences !== null) { $data['params_json'] = $mailPreferences; }
            $certificatePreferences = \Jlms\Component\Jlms\Administrator\Application\CertificatePreferences::merge(
                $data, $data['params_json'] ?? $record->params_json ?? '{}',
            );
            unset($data['certificate_enabled']);
            if ($certificatePreferences !== null) { $data['params_json'] = $certificatePreferences; }
            if (($data['calendar_timezone'] ?? null) === '') { $data['calendar_timezone'] = $this->siteTimezone(); }
            $calendar=\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::merge($data,$record ?? (object) [],$data['params_json'] ?? $record->params_json ?? '{}',$this->siteTimezone());
            foreach (\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::FIELDS as $field) { unset($data[$field]); }
            $data=array_replace($data,$calendar);
            if ($copySource !== null) {
                // Trusted persisted source metadata, never raw JSON from the request.
                foreach (['params_json', 'metadata_json', 'image'] as $field) { $data[$field] = $copySource->course->$field; }
            }
            if (!parent::save($data)) {
                $error = $this->getError();
                $validationErrors = [
                    'COM_JLMS_INVALID_COURSE', 'COM_JLMS_INVALID_ACCESS', 'COM_JLMS_COURSE_ALIAS_DUPLICATE',
                    'JLIB_DATABASE_ERROR_CATEGORY_REQUIRED', 'COM_JLMS_ASSOCIATION_SAVE_SEPARATELY',
                ];
                throw new \DomainException(in_array($error, $validationErrors, true) ? $error : 'COM_JLMS_SAVE_FAILED');
            }
            if ($teachers !== null) {
                $q = $db->createQuery()->delete('#__jlms_course_teachers')->where('course_id=:course')->bind(':course',$id,ParameterType::INTEGER); $db->setQuery($q)->execute();
                foreach ($teachers as $ordering => $teacher) {
                    $relation = (object) ['course_id'=>$id,'teacher_id'=>$teacher,'ordering'=>$ordering]; $db->insertObject('#__jlms_course_teachers',$relation);
                }
            }
            if ($copySource !== null) {
                $newId = (int) $this->getState('course.id');
                $target = $this->getItem($newId);
                $user->clearAccessRights();
                if (!\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($user, $target)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
                $workflow = new \Joomla\CMS\Workflow\Workflow('com_jlms.course', Factory::getApplication(), $db);
                $stage = (int) $workflow->getDefaultStageByCategory((int) $target->catid);
                if (!$stage || !$workflow->createAssociation($newId, $stage)) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
                $selected = \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::assignment($db, $user, (string) $target->language,
                    \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::selected($db, (int) $copySource->course->id));
                foreach ($selected as $ordering => $teacher) { $relation = (object) ['course_id' => $newId, 'teacher_id' => $teacher, 'ordering' => $ordering]; $db->insertObject('#__jlms_course_teachers', $relation); }
                $fieldModel = Factory::getApplication()->bootComponent('com_fields')->getMVCFactory()->createModel('Field', 'Administrator', ['ignore_request' => true]);
                $sourceItem = (string) $copySource->course->id;
                $q = $db->createQuery()->select('v.field_id,v.value')->from('#__fields_values AS v')
                    ->innerJoin('#__fields AS f ON f.id=v.field_id')->where("f.context='com_jlms.course'")
                    ->where('v.item_id=:item')->bind(':item', $sourceItem);
                $values = [];
                foreach ($db->setQuery($q)->loadObjectList() as $value) { $values[(int) $value->field_id][] = $value->value; }
                foreach ($values as $fieldId => $fieldValues) {
                    $field = $fieldModel->getItem($fieldId);
                    if (!$field || !$user->authorise('core.edit.value', 'com_jlms.field.' . $fieldId)
                        || !in_array((int) $field->access, $user->getAuthorisedViewLevels(), true)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
                    if (!$fieldModel->setFieldValue($fieldId, $newId, $fieldValues)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED'); }
                }
                $programme = clone $copySource->version;
                $programme->course_id = $newId;
                $programme->title_snapshot = $target->title;
                $programme->presentation_json = \Jlms\Component\Jlms\Administrator\Application\CoursePresentation::snapshot($target);
                (new \Jlms\Component\Jlms\Administrator\Application\CourseVersionCopy($db))->copy($user, $programme, 1, true, (int) $copySource->version->id);
            }
            $history = $data;
            $history['teacher_ids'] = \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::selected($db, (int) $this->getState('course.id'));
            $history['version_note'] = is_string($inputNote = Factory::getApplication()->getInput()->post->get('jform', [], 'array')['version_note'] ?? '') ? strip_tags($inputNote) : '';
            $this->persistHistory($history, $this->typeAlias);
            $db->transactionCommit();
            if ($copySource !== null) { $this->setState('course.copy_complete', true); }
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $known = ['JERROR_LAYOUT_PAGE_NOT_FOUND', 'JERROR_ALERTNOAUTHOR', 'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_VERSION_IMMUTABLE', 'COM_JLMS_INVALID_ASSOCIATION', 'COM_JLMS_ASSOCIATION_SAVE_SEPARATELY', 'COM_JLMS_PRESENTATION_INVALID', 'COM_JLMS_TEACHER_INVALID', 'COM_JLMS_TEACHER_SAVE_FIRST', 'COM_JLMS_CALENDAR_INVALID', 'COM_JLMS_CALENDAR_RANGE', 'COM_JLMS_CALENDAR_DST','COM_JLMS_COURSE_CHECKED_OUT','COM_JLMS_COURSE_IMAGE_INVALID','COM_JLMS_COURSE_IMAGE_ALT_REQUIRED',
                'COM_JLMS_COURSE_SUMMARY_TOO_LONG', 'COM_JLMS_COURSE_ALIAS_DUPLICATE', 'COM_JLMS_INVALID_COURSE',
                'COM_JLMS_INVALID_ACCESS', 'JLIB_DATABASE_ERROR_CATEGORY_REQUIRED', 'COM_JLMS_SAVE_FAILED'];
            if (!in_array($error->getMessage(), $known, true)) {
                // Private diagnostics omit SQL, values and exception messages.
                try { \Joomla\CMS\Log\Log::add($error::class . ' at ' . basename($error->getFile()) . ':' . $error->getLine(), \Joomla\CMS\Log\Log::ERROR, 'com_jlms'); } catch (\Throwable) {}
            }
            $this->setError(Text::_(in_array($error->getMessage(), $known, true) ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            return false;
        } finally {
            $this->associationWrite = false;
        }
    }

    protected function versionHistoryEnabled(string $context): bool
    {
        return (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('save_history', 1);
    }

    public function saveHistory(array $data, string $context)
    {
        // Save once after course relations have been persisted by this model.
        return true;
    }

    public function loadHistory(int $historyId)
    {
        $history = $this->getHistoryTable($historyId);
        if (!$history) { return false; }
        $data = json_decode($history->version_data, true, 64, JSON_THROW_ON_ERROR);
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1 || $history->item_id !== $this->typeAlias . '.' . $id) { return false; }
        $current = $this->getItem($id);
        if (!$current) { return false; }
        $data['revision'] = (int) $current->revision;
        foreach (\Jlms\Component\Jlms\Administrator\Application\CoursePresentation::details($data['params_json'] ?? '{}') ?? [] as $name => $value) {
            if ($name !== '_schema') { $data['presentation_' . $name] = $value; }
        }
        $data = array_replace($data, \Jlms\Component\Jlms\Administrator\Application\CourseSchedule::editor((object) $data, Factory::getApplication()->getIdentity()->getTimezone()->getName()));
        return $this->save($data);
    }

    public function delete(&$pks)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }

    protected function canEditState($record)
    {
        return $this->getCurrentUser()->authorise('core.edit.state', 'com_jlms.course.' . (int) $record->id);
    }

    public function publish(&$pks, $value = 1)
    {
        $service = Factory::getApplication()->bootComponent('com_jlms')->getPublicationService();
        if (!$service->permitsStateChange($pks, (int) $value)) {
            $this->setError(Text::_('COM_JLMS_PUBLICATION_NOT_AVAILABLE'));
            return false;
        }
        $historyIds = array_map('intval', $pks);
        $success = parent::publish($pks, $value);
        if ($success && $historyIds) {
            $this->setState('course.id', $historyIds[0]);
            $this->persistHistory(['teacher_ids' => \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::selected($this->getDatabase(), $historyIds[0])], $this->typeAlias);
        }
        $service->recordStateChangeResult($success);
        return $success;
    }
}
