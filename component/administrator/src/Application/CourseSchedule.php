<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

/** Typed author calendar: explicit IANA zone on input, UTC instants in storage. */
final class CourseSchedule
{
    public const FIELDS = ['calendar_timezone','calendar_publish_up','calendar_publish_down','calendar_starts_at','calendar_ends_at'];
    private const DATES = ['publish_up','publish_down','starts_at','ends_at'];

    public static function zone(mixed $zone): \DateTimeZone
    {
        if (!is_string($zone) || !in_array($zone,\DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC),true)) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        return new \DateTimeZone($zone);
    }
    private static function sql(mixed $date): ?string
    {
        if ($date === null || $date === '') { return null; }
        if (!is_string($date) || !preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]{1,6})?$/D',$date)) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s'.(str_contains($date,'.')?'.u':''),$date,new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$d || ($errors && ($errors['warning_count'] || $errors['error_count']))) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        return $date;
    }
    public static function toUtc(mixed $value, string $zone): ?string
    {
        if ($value === null || $value === '') { return null; }
        $tz=self::zone($zone); $wall=self::sql($value);
        if ($wall === null || str_contains($wall,'.')) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        $naive=new \DateTimeImmutable($wall,new \DateTimeZone('UTC')); $stamp=$naive->getTimestamp();
        $offsets=[$tz->getOffset($naive)];
        foreach ($tz->getTransitions($stamp-172800,$stamp+172800) ?: [] as $transition) { $offsets[]=$transition['offset']; }
        $candidates=[];
        foreach (array_unique($offsets) as $offset) {
            $candidate=(new \DateTimeImmutable('@'.($stamp-$offset)))->setTimezone($tz);
            if ($candidate->format('Y-m-d H:i:s')===$wall) { $candidates[$candidate->getTimestamp()]=$candidate; }
        }
        if (count($candidates)!==1) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_DST'); }
        return self::sql(reset($candidates)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
    }
    public static function display(?string $value,string $zone,bool $offset=false): string
    {
        if ($value===null || $value==='') { return ''; }
        self::sql($value);
        return (new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->setTimezone(self::zone($zone))->format('Y-m-d H:i:s'.($offset?' P':''));
    }
    private static function validate(array $data): array
    {
        self::zone($data['timezone'] ?? null);
        foreach (self::DATES as $key) { $data[$key]=self::sql($data[$key] ?? null); }
        foreach ([['publish_up','publish_down'],['starts_at','ends_at']] as [$start,$end]) {
            if ($data[$start]!==null && $data[$end]!==null && new \DateTimeImmutable($data[$start],new \DateTimeZone('UTC'))>=new \DateTimeImmutable($data[$end],new \DateTimeZone('UTC'))) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_RANGE'); }
        }
        $lower=array_filter([$data['publish_up'],$data['starts_at']],static fn($v)=>$v!==null);
        $upper=array_filter([$data['publish_down'],$data['ends_at']],static fn($v)=>$v!==null);
        if ($lower && $upper && max(array_map(static fn(string $v): \DateTimeImmutable => new \DateTimeImmutable($v,new \DateTimeZone('UTC')),$lower))>=min(array_map(static fn(string $v): \DateTimeImmutable => new \DateTimeImmutable($v,new \DateTimeZone('UTC')),$upper))) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_RANGE'); }
        return array_intersect_key($data,array_flip(array_merge(['_schema','timezone'],self::DATES)));
    }
    public static function read(object $course,string $defaultZone='UTC'): array
    {
        $params=json_decode(($course->params_json ?? '') ?: '{}',true,32,JSON_THROW_ON_ERROR);
        if (!is_array($params)) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        $data=$params['calendar'] ?? ['_schema'=>1,'timezone'=>$defaultZone];
        if (!is_array($data) || ($data['_schema'] ?? null)!==1) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        $data['publish_up']=$course->publish_up ?? null; $data['publish_down']=$course->publish_down ?? null;
        return self::validate($data);
    }
    public static function editor(object $course,string $defaultZone): array
    {
        $data=self::read($course,$defaultZone); $result=['calendar_timezone'=>$data['timezone']];
        foreach (self::DATES as $key) { $result['calendar_'.$key]=self::display($data[$key],$data['timezone']); }
        return $result;
    }
    public static function merge(array $input,object $course,string $params,string $defaultZone): array
    {
        if (!array_intersect(self::FIELDS,array_keys($input))) { return []; }
        $old=self::read($course,$defaultZone); $data=$old; $data['timezone']=$input['calendar_timezone'] ?? $old['timezone']; self::zone($data['timezone']);
        foreach (self::DATES as $key) {
            if (!array_key_exists('calendar_'.$key,$input)) { continue; }
            $value=$input['calendar_'.$key];
            // Preserve unchanged historical precision/ambiguous instants instead of reinterpreting them.
            if ($data['timezone']===$old['timezone'] && $value===self::display($old[$key],$old['timezone'])) { continue; }
            $data[$key]=self::toUtc($value,$data['timezone']);
        }
        $data=self::validate($data); $saved=json_decode($params,true,32,JSON_THROW_ON_ERROR);
        $saved['calendar']=['_schema'=>1,'timezone'=>$data['timezone'],'starts_at'=>$data['starts_at'],'ends_at'=>$data['ends_at']];
        return ['publish_up'=>$data['publish_up'],'publish_down'=>$data['publish_down'],'params_json'=>json_encode($saved,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)];
    }
    public static function snapshot(object $course): ?array
    {
        $data=self::read($course);
        foreach (self::DATES as $key) { if ($data[$key]!==null) { return $data; } }
        return null;
    }
    public static function within(?string $start,?string $end,?\DateTimeImmutable $now=null): bool
    {
        $now ??= new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
        $start=self::sql($start); $end=self::sql($end);
        return ($start===null || new \DateTimeImmutable($start,new \DateTimeZone('UTC')) <= $now)
            && ($end===null || new \DateTimeImmutable($end,new \DateTimeZone('UTC')) > $now);
    }
    public static function fromSnapshot(string $snapshot): ?array
    {
        $data=json_decode($snapshot,true,32,JSON_THROW_ON_ERROR);
        if (!isset($data['calendar'])) { return null; }
        if (!is_array($data['calendar']) || ($data['calendar']['_schema'] ?? null)!==1) { throw new \InvalidArgumentException('COM_JLMS_CALENDAR_INVALID'); }
        return self::validate($data['calendar']);
    }
}
