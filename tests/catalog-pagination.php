<?php

declare(strict_types=1);

namespace Joomla\CMS\Pagination {
    final class Pagination
    {
        public array $params = [];
        public function setAdditionalUrlParam(string $key, mixed $value): void
        {
            $this->params[$key] = $value;
        }
    }
}

namespace {
    require __DIR__ . '/../component/site/src/Helper/CatalogPagination.php';

    use Joomla\CMS\Pagination\Pagination;
    use Jlms\Component\Jlms\Site\Helper\CatalogPagination;

    $pages = new Pagination();
    CatalogPagination::apply($pages, [
        'search' => 'opera & voice',
        'order' => 'published',
        'category_id' => 7,
        'tag_id' => 5,
        'duration' => '30-60',
        'difficulty' => 'beginner',
        'available' => '1',
        'layout' => 'list',
        'teacher_id' => 22,
        'pathway_id' => 31,
        'limitstart' => '900',
        'secret' => 'LEAK-ME',
    ], true, 18);
    if ($pages->params['catid'] !== 18
        || count($pages->params['filter']) !== 10
        || $pages->params['filter']['search'] !== 'opera & voice'
        || $pages->params['filter']['order'] !== 'published'
        || array_key_exists('secret', $pages->params['filter'])
        || array_key_exists('limitstart', $pages->params['filter'])) {
        throw new RuntimeException('SCR-01 filters or catalogue scope lost or arbitrary query leaked');
    }

    $disabled = new Pagination();
    CatalogPagination::apply($disabled, ['search' => 'private', 'order' => 'recent'], false, 18);
    if ($disabled->params !== ['catid' => 18]) {
        throw new RuntimeException('Hidden filter options must not be persisted');
    }

    $category = new Pagination();
    CatalogPagination::apply($category, [
        'search' => '',
        'tag_id' => 0,
        'teacher_id' => 0,
        'pathway_id' => null,
        'available' => '1',
        'layout' => 'grid',
        'order' => 'editorial',
        'untrusted' => ['key' => 'value'],
    ], true);
    if ($category->params !== ['filter' => ['order' => 'editorial', 'available' => '1', 'layout' => 'grid']]) {
        throw new RuntimeException('SCR-01 category pagination must retain only recognized scalar filters');
    }
    echo "SCR-01 normalized pagination filters and category scope: OK\n";
}
