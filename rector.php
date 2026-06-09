<?php
declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

// Rector rules we intentionally skip (CakePHP-friendly)
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;

$cacheDir = getenv('RECTOR_CACHE_DIR') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rector';

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])

    // Use file-based cache for faster runs
    ->withCache(
        cacheClass: FileCacheStorage::class,
        cacheDirectory: $cacheDir,
    )

    // Enable modern PHP syntax and attribute-related improvements
    ->withPhpSets()
    ->withAttributesSets()

    // Safe and useful Rector sets for CRM/NMS projects
    ->withSets([
        SetList::CODE_QUALITY,      // Improve readability and simplify logic
        SetList::CODING_STYLE,      // Consistent coding style
        SetList::DEAD_CODE,         // Remove unused code
        SetList::EARLY_RETURN,      // Replace nested conditions with early returns
        SetList::INSTANCEOF,        // Modern instanceof usage
        SetList::TYPE_DECLARATION,  // Add missing type declarations
        SetList::PRIVATIZATION,     // Safely tighten visibility where possible
    ])

    // Skip rules that are unsafe or undesirable for CakePHP-style projects
    ->withSkip([
        __DIR__ . '/tests/comparisons',

        // Do not convert properties to constructor promotion
        ClassPropertyAssignToConstructorPromotionRector::class,

        // Do not convert closures to arrow functions
        ClosureToArrowFunctionRector::class,

        // Do not enforce catch variable naming rules
        CatchExceptionNameMatchingTypeRector::class,

        // Do not split chained assignments
        SplitDoubleAssignRector::class,

        // Do not enforce blank lines after statements
        NewlineAfterStatementRector::class,

        // Do not rewrite compact() calls
        CompactToVariablesRector::class,

        // Do not remove return tags from docblocks
        RemoveUselessReturnTagRector::class,

        // Do not add strict return types to fluent APIs
        ReturnTypeFromStrictFluentReturnRector::class,

        // Do not rewrite boolean comparisons
        ExplicitBoolCompareRector::class,

        // Do not add typed properties for mocks in tests
        TypedPropertyFromCreateMockAssignRector::class,
    ]);
