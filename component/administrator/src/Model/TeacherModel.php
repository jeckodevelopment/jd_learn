<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\CourseArtwork;

final class TeacherModel extends AdminModel
{
    public $typeAlias = 'com_jlms.teacher';
    public function getForm($data = [], $loadData = true) { return $this->loadForm('com_jlms.teacher', 'teacher', ['control' => 'jform', 'load_data' => $loadData]); }
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
            if ($actor->guest || $actor->block || !$actor->authorise('jlms.teachers.manage', 'com_jlms')) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $allowed = ['id', 'revision', 'title', 'alias', 'catid', 'bio', 'image', 'image_alt', 'image_decorative', 'website', 'language', 'access', 'state', 'user_id'];
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
                if ((!$actor->authorise('core.edit', $scope) && !((int) $record->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $scope)))
                    || ($record->checked_out && (int) $record->checked_out !== (int) $actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
                if ((int) ($data['revision'] ?? 0) !== (int) $record->revision) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            } else { $scope = 'com_jlms.teacher.category.' . (int) ($data['catid'] ?? 0); }
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
    public function delete(&$pks) { $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE')); return false; }
    public function publish(&$pks, $value = 1) { $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE')); return false; }
}
