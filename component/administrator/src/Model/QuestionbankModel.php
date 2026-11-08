<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
final class QuestionbankModel extends ListModel
{
    public function getFilterForm($data = [], $loadData = true) { return $this->loadForm('com_jlms.filter_questionbank','filter_questionbank',['control'=>'filter','load_data'=>false]); }
    public function getBank(): array
    {
        $app=Factory::getApplication();$input=$app->getInput();$filters=$input->get('filter',[],'array');
        foreach (['search','published','catid','language'] as $key) { if (!is_scalar($filters[$key]??'')) { $filters[$key]='_invalid_'; } }
        $list=$input->get('list',[],'array');
        foreach (['fullordering','ordering','direction','limit'] as $key) { if (isset($list[$key])&&!is_scalar($list[$key])) { $list[$key]=''; } }
        $ordering=(string)($list['ordering']??'r.prompt');$direction=(string)($list['direction']??'ASC');
        if (isset($list['fullordering'])&&preg_match('/\A(.+)\s+(ASC|DESC)\z/iD',(string)$list['fullordering'],$match)) { $ordering=$match[1];$direction=$match[2]; }
        $limit=(int)($list['limit']??20);$limit=$limit===0?100:max(1,min(100,$limit));$start=max(0,$input->getInt('limitstart',0));
        foreach ($filters as $key=>$value) { $this->setState('filter.'.$key,(string)$value); }
        $this->setState('list.ordering',$ordering);$this->setState('list.direction',strtoupper($direction));$this->setState('list.limit',$limit);$this->setState('list.start',$start);
        $result=$app->bootComponent('com_jlms')->getQuestionBankService()->adminListing($app->getIdentity(),$filters,$start,$limit,$ordering,$direction);
        $result['pagination']=new \Joomla\CMS\Pagination\Pagination($result['total'],$start,$limit);
        return $result;
    }
}
