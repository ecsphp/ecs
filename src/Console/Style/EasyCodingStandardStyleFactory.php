<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Style;

use ECSPrefix202610\Entropy\Console\Output\OutputColorizer;
use ECSPrefix202610\Entropy\Console\Output\OutputPrinter;
use ECSPrefix202610\Entropy\Console\Output\ProgressBar;
/**
 * @api
 */
final class EasyCodingStandardStyleFactory
{
    /**
     * @api
     */
    public function create(): \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $progressBar = new ProgressBar();
        $isDebug = in_array('--debug', $_SERVER['argv'] ?? [], \true);
        return new \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle($outputPrinter, $progressBar, $isDebug);
    }
}
