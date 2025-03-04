<?php

declare(strict_types=1);

namespace Browscap\Formatter;

use Browscap\Data\PropertyHolder;
use Exception;
use InvalidArgumentException;

use function str_replace;
use function trim;

/**
 * this formatter is responsible to format the output into the "csv" version of the browscap files
 */
class CsvFormatter implements FormatterInterface
{
    private PropertyHolder $propertyHolder;

    /**
     * @throws void
     */
    public function __construct(PropertyHolder $propertyHolder)
    {
        $this->propertyHolder = $propertyHolder;
    }

    /**
     * returns the Type of the formatter
     *
     * @throws void
     */
    public function getType(): string
    {
        return FormatterInterface::TYPE_CSV;
    }

    /**
     * formats the name of a property
     *
     * @throws void
     */
    public function formatPropertyName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /**
     * formats the name of a property
     *
     * @throws Exception
     */
    public function formatPropertyValue(bool|int|string $value, string $property): string
    {
        $valueOutput = (string) $value;

        switch ($this->propertyHolder->getPropertyType($property)) {
            case PropertyHolder::TYPE_STRING:
                $valueOutput = trim((string) $value);

                break;
            case PropertyHolder::TYPE_BOOLEAN:
                if ($value === true || $value === 'true') {
                    $valueOutput = 'true';
                } elseif ($value === false || $value === 'false') {
                    $valueOutput = 'false';
                } else {
                    $valueOutput = '';
                }

                break;
            case PropertyHolder::TYPE_IN_ARRAY:
                try {
                    $valueOutput = (string) $this->propertyHolder->checkValueInArray($property, (string) $value);
                } catch (InvalidArgumentException $ex) {
                    $valueOutput = '';
                }

                break;
            default:
                // nothing t do here
                break;
        }

        if ($valueOutput === 'unknown') {
            $valueOutput = '';
        }

        return '"' . str_replace('"', '""', $valueOutput) . '"';
    }
}
