<?php declare (strict_types=1);

namespace Entrydo\RestRoute;

interface PathMatcherInterface
{
	public function match(string $route, string $path): ?array;

	public function createUrl(string $route, array $parameters): string;
}