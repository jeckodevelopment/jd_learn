<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Language\Text;

final class CourseMessageVariableException extends \RuntimeException
{
    /** @param list<string> $names Names extracted by the placeholder validator. */
    public function __construct(public readonly array $names)
    {
        parent::__construct('COM_JLMS_COMMUNICATION_TEMPLATE_VARIABLES', 422);
    }

    public function translatedMessage(): string
    {
        $tokens = array_map(
            static fn (string $name): string => '{' . substr($name, 0, 64) . (strlen($name) > 64 ? '…' : '') . '}',
            array_slice($this->names, 0, 8)
        );
        if (count($this->names) > count($tokens)) {
            $tokens[] = '…';
        }
        return Text::sprintf('COM_JLMS_COMMUNICATION_TEMPLATE_VARIABLE_NAMES', implode(', ', $tokens));
    }
}
