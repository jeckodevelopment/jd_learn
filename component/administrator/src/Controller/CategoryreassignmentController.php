<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

final class CategoryreassignmentController extends BaseController
{
    public function reassign(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $id = $this->input->post->getInt('category_id');
        $destinations = $this->input->post->get('destination', [], 'array');
        try {
            $component = $this->app->bootComponent('com_jlms');
            $component->getCategoryReassignmentService()->reassign($this->app->getIdentity(), $id, $destinations);
            $this->setMessage(Text::_('COM_JLMS_CATEGORY_REASSIGN_SUCCESS'));
            $this->setRedirect(Route::_('index.php?option=com_jlms&view=categoryreassignment&category_id=' . $id, false));
        } catch (\Throwable $error) {
            $key = $error->getMessage();
            $code = (int) $error->getCode();
            if ($error instanceof \InvalidArgumentException) {
                $code = 422;
            }
            if (preg_match('/\ACOM_JLMS_[A-Z_]+\z/D', $key)) {
                throw new \RuntimeException(Text::_($key), in_array($code, [400, 403, 404, 409, 422], true) ? $code : 422);
            }
            throw $error;
        }
    }
}
