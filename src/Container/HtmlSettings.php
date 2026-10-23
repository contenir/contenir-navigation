<?php

declare(strict_types=1);

namespace Contenir\Navigation\Container;

use Contenir\Navigation\Exception\ConfigurationException;

use function is_array;
use function is_string;

/**
 * Reads the elements a text item's HTML may hold from settings: a list of
 * attribute names by element.
 *
 * @internal
 */
final class HtmlSettings
{
    /**
     * @return array<string, list<string>>
     *
     * @throws ConfigurationException
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; each element's attributes are checked here.
     */
    public static function allowed(mixed $html, string $key): array
    {
        if (! is_array($html)) {
            throw ConfigurationException::invalidSetting($key, 'a list of attributes by element');
        }

        $allowed = [];
        foreach ($html as $element => $attributes) {
            if (! is_string($element) || ! is_array($attributes)) {
                throw ConfigurationException::invalidSetting($key, 'a list of attributes by element');
            }

            $allowed[$element] = [];
            foreach ($attributes as $attribute) {
                $allowed[$element][] = is_string($attribute)
                    ? $attribute
                    : throw ConfigurationException::invalidSetting("{$key}.{$element}", 'a list of attribute names');
            }
        }

        return $allowed;
    }
}
