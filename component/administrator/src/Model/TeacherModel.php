<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\CourseArtwork;

class TeacherModel extends AdminModel implements \Joomla\CMS\Versioning\VersionableModelInterface
{
    use \Joomla\CMS\Versioning\VersionableModelTrait;

    protected function versionHistoryEnabled(string $context): bool
    { return (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('save_history', 1); }

    public $typeAlias = 'com_jlms.teacher';
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_jlms.teacher', JPATH_ADMINISTRATOR . '/components/com_jlms/forms/teacher.xml',
            ['control' => 'jform', 'load_data' => $loadData]);
        if ($form && !$this->getCurrentUser()->authorise('jlms.teachers.manage', 'com_jlms')) {
            foreach (['user_id', 'state', 'access', 'language'] as $field) {
                $form->setFieldAttribute($field, 'disabled', 'true');
                $form->setFieldAttribute($field, 'required', 'false');
                $form->setFieldAttribute($field, 'filter', 'unset');
            }
        }
        return $form;
    }
    protected function loadFormData() { return Factory::getApplication()->getUserState('com_jlms.edit.teacher.data', []) ?: $this->getItem(); }
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item && !empty($item->id)) {
            foreach (json_decode($item->params_json, true, 16, JSON_THROW_ON_ERROR) as $key => $value) {
                if (in_array($key, ['image_alt', 'image_decorative', 'website'], true)) { $item->$key = $value; }
            }
        }
        return $item;
    }
    public function save($input)
    {
        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            $db->setQuery("SELECT id FROM #__assets WHERE name='com_jlms' FOR UPDATE")->loadResult() ?: throw new \DomainException('JERROR_ALERTNOAUTHOR');
            $actor = new User((int) $this->getCurrentUser()->id); $actor->clearAccessRights(); $this->setCurrentUser($actor);
            if ($actor->guest || $actor->block) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $allowed = ['id', 'revision', 'title', 'alias', 'catid', 'bio', 'image', 'image_alt', 'image_decorative', 'website', 'language', 'access', 'state', 'user_id', 'com_fields', 'version_note'];
            $data = array_intersect_key($input, array_flip($allowed));
            foreach (['id','revision','catid','access','state'] as $key) {
                if (array_key_exists($key,$data) && ((!is_int($data[$key]) && !is_string($data[$key])) || !preg_match('/^[0-9]{1,10}$/D',(string) $data[$key]))) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            }
            if (isset($data['state']) && !in_array((string) $data['state'],['0','1'],true)) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $id = (int) ($data['id'] ?? 0); $record = null;
            if ($id) {
                $q = $db->createQuery()->select('*')->from('#__jlms_teachers')->where('id=:id')->bind(':id', $id, ParameterType::INTEGER);
                $q->setQuery((string) $q . ' FOR UPDATE'); $record = $db->setQuery($q)->loadObject();
                if (!$record) { throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND'); }
                $scope = 'com_jlms.teacher.' . $id;
                if (!\Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($actor, $record)
                    || ($record->checked_out && (int) $record->checked_out !== (int) $actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
                if ((int) ($data['revision'] ?? 0) !== (int) $record->revision) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            } else {
                if (!$actor->authorise('jlms.teachers.manage', 'com_jlms')) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
                $query = $db->createQuery()->select('id')->from('#__categories')->where("extension='com_jlms.teacher' AND alias='jd-learn-teachers'");
                $data['catid'] = (int) ($data['catid'] ?? $db->setQuery($query)->loadResult());
                $scope = 'com_jlms';
            }
            if (!$actor->authorise('jlms.teachers.manage', 'com_jlms')) {
                foreach (['user_id', 'catid', 'state', 'access', 'language'] as $protected) { unset($data[$protected]); }
            }
            $catid = (int) ($data['catid'] ?? $record->catid ?? 0);
            if ((!$id || $catid !== (int) $record->catid) && !$actor->authorise('core.create', 'com_jlms.teacher.category.' . $catid)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            if (array_key_exists('state', $data) && (int) $data['state'] !== (int) ($record->state ?? 0) && !$actor->authorise('core.edit.state', $scope)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            foreach (['title', 'alias', 'bio', 'image', 'image_alt', 'website', 'language'] as $key) {
                if (isset($data[$key]) && (!is_string($data[$key]) || preg_match('//u', $data[$key]) !== 1)) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            }
            $params = $record ? json_decode($record->params_json, true, 16, JSON_THROW_ON_ERROR) : [];
            foreach (['image_alt', 'image_decorative', 'website'] as $key) { if (array_key_exists($key, $data)) { $params[$key] = $data[$key]; unset($data[$key]); } }
            $decorative = $params['image_decorative'] ?? '0';
            if (!in_array($decorative, ['0', '1', 0, 1, true, false], true)) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $params['image_decorative'] = in_array($decorative, ['1', 1, true], true);
            $params['image_alt'] = trim($params['image_alt'] ?? '');
            if (mb_strlen($params['image_alt']) > 500) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $image = CourseArtwork::path($data['image'] ?? (string) ($record->image ?? ''));
            CourseArtwork::validateLocal($image); $data['image'] = $image;
            if ($image !== '' && !$params['image_decorative'] && $params['image_alt'] === '') { throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_ALT_REQUIRED'); }
            if ($params['image_decorative']) { $params['image_alt'] = ''; }
            $website = trim($params['website'] ?? '');
            if (strlen($website) > 2048 || ($website !== '' && (!filter_var($website, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($website, PHP_URL_SCHEME)), ['http', 'https'], true)
                || parse_url($website, PHP_URL_USER) !== null || parse_url($website, PHP_URL_PASS) !== null || preg_match('/[\x00-\x20\x7f]/', $website)))) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
            $params['website'] = $website; $data['params_json'] = json_encode($params, JSON_THROW_ON_ERROR);
            if (array_key_exists('user_id', $data)) {
                if ($data['user_id'] === '' || $data['user_id'] === null || $data['user_id'] === '0' || $data['user_id'] === 0) { $data['user_id'] = null; }
                else {
                    if ((!is_int($data['user_id']) && !is_string($data['user_id'])) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $data['user_id'])) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
                    $user = new User((int) $data['user_id']); if (!$user->id) { throw new \InvalidArgumentException('COM_JLMS_TEACHER_INVALID'); }
                    $data['user_id'] = (int) $user->id;
                }
            }
            unset($data['revision']);
            if (!parent::save($data)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED'); }
            $db->transactionCommit(); return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $known = ['JERROR_ALERTNOAUTHOR', 'JERROR_LAYOUT_PAGE_NOT_FOUND', 'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_TEACHER_INVALID', 'COM_JLMS_COURSE_IMAGE_INVALID', 'COM_JLMS_COURSE_IMAGE_ALT_REQUIRED'];
            $this->setError(Text::_(in_array($error->getMessage(), $known, true) ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED')); return false;
        }
    }
    public function loadHistory(int $historyId)
    {
        $history = $this->getHistoryTable($historyId);
        if (!$history) { return false; }
        $data = json_decode($history->version_data, true, 64, JSON_THROW_ON_ERROR);
        $id = (int) ($data['id'] ?? 0);
        if (!$id || $history->item_id !== $this->typeAlias . '.' . $id) { return false; }
        $current = $this->getItem($id);
        if (!$current) { return false; }
        $data['revision'] = (int) $current->revision;
        $data = array_replace($data, array_intersect_key(json_decode($data['params_json'] ?? '{}', true), array_flip(['website', 'image_alt', 'image_decorative'])));
        return $this->save($data);
    }

    public function delete(&$pks) { $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE')); return false; }
    public function publish(&$pks, $value = 1) { $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE')); return false; }
}
