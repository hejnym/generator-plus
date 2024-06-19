<?php declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
	->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
	->withRules([
		ArraySyntaxFixer::class,
	])
	->withCache(
		directory: __DIR__ . '/cache/ecs',
	)
	->withPhpCsFixerSets(perCS20: true);
