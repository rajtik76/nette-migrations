<?php declare(strict_types=1);

namespace Rajtik\Migrations\Support;


final class Inflector
{
	public static function snake(string $value): string
	{
		$value = (string) preg_replace('~(?<=[a-z0-9])(?=[A-Z])|[\s\-]+~', '_', trim($value));

		return strtolower((string) preg_replace('~_+~', '_', $value));
	}


	public static function plural(string $word): string
	{
		return match (true) {
			(bool) preg_match('~[^aeiou]y$~i', $word) => substr($word, 0, -1) . 'ies',
			(bool) preg_match('~(s|x|z|ch|sh)$~i', $word) => $word . 'es',
			default => $word . 's',
		};
	}
}
