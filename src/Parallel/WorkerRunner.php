<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Parallel;

use ECSPrefix202610\Clue\React\NDJson\Decoder;
use ECSPrefix202610\Clue\React\NDJson\Encoder;
use Symplify\EasyCodingStandard\Application\SingleFileProcessor;
use Symplify\EasyCodingStandard\Parallel\Enum\Action;
use Symplify\EasyCodingStandard\Parallel\Enum\Content;
use Symplify\EasyCodingStandard\Parallel\Enum\ReactCommand;
use Symplify\EasyCodingStandard\Parallel\Enum\ReactEvent;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\Utils\ParametersMerger;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\SystemError;
use Throwable;
final class WorkerRunner
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Application\SingleFileProcessor
     */
    private $singleFileProcessor;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Utils\ParametersMerger
     */
    private $parametersMerger;
    public function __construct(SingleFileProcessor $singleFileProcessor, ParametersMerger $parametersMerger)
    {
        $this->singleFileProcessor = $singleFileProcessor;
        $this->parametersMerger = $parametersMerger;
    }
    public function run(Encoder $encoder, Decoder $decoder, Configuration $configuration): void
    {
        // 1. handle system error
        $handleErrorCallback = static function (Throwable $throwable) use ($encoder): void {
            $systemErrors = new SystemError($throwable->getLine(), $throwable->getMessage(), $throwable->getFile());
            $encoder->write([ReactCommand::ACTION => Action::RESULT, Content::RESULT => [Bridge::SYSTEM_ERRORS => [$systemErrors], Bridge::FILES_COUNT => 0, Bridge::SYSTEM_ERRORS_COUNT => 1]]);
            $encoder->end();
        };
        $encoder->on(ReactEvent::ERROR, $handleErrorCallback);
        // 2. collect diffs + errors from file processor
        $decoder->on(ReactEvent::DATA, function (array $json) use ($encoder, $configuration): void {
            $action = $json[ReactCommand::ACTION];
            if ($action !== Action::MAIN) {
                return;
            }
            $systemErrorsCount = 0;
            /** @var string[] $filePaths */
            $filePaths = $json[Content::FILES] ?? [];
            $errorAndFileDiffs = [];
            $systemErrors = [];
            foreach ($filePaths as $filePath) {
                try {
                    $currentErrorsAndFileDiffs = $this->singleFileProcessor->processFilePath($filePath, $configuration);
                    $errorAndFileDiffs = $this->parametersMerger->merge($errorAndFileDiffs, $currentErrorsAndFileDiffs);
                } catch (Throwable $throwable) {
                    ++$systemErrorsCount;
                    $errorMessage = sprintf('System error: "%s"', $throwable->getMessage());
                    $errorMessage .= 'Run ECS with "--debug" option and post the report here: https://github.com/easy-coding-standard/easy-coding-standard/issues/new';
                    $systemErrors[] = new SystemError($throwable->getLine(), $errorMessage, $filePath);
                }
            }
            /**
             * this invokes all listeners listening $decoder->on(...) @see ReactEvent::DATA
             */
            $encoder->write([ReactCommand::ACTION => Action::RESULT, Content::RESULT => [Bridge::CODING_STANDARD_ERRORS => $errorAndFileDiffs[Bridge::CODING_STANDARD_ERRORS] ?? [], Bridge::FILE_DIFFS => $errorAndFileDiffs[Bridge::FILE_DIFFS] ?? [], Bridge::FILES_COUNT => count($filePaths), Bridge::SYSTEM_ERRORS => $systemErrors, Bridge::SYSTEM_ERRORS_COUNT => $systemErrorsCount]]);
        });
        $decoder->on(ReactEvent::ERROR, $handleErrorCallback);
    }
}
