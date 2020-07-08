<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Illuminate\Console\Command;

abstract class Communicator
{
    protected const COMM_DIRECTION_IN = 'in';
    protected const COMM_DIRECTION_OUT = 'out';
    protected const COMM_DIRECTION_FLAT = 'flat';
    protected const COMM_DIRECTION_NONE = 'none';
    protected const HASH_ALGORITHM = 'sha512';
    protected const VERBOSITY_HIGH = 3;
    protected const VERBOSITY_MEDIUM = 2;
    protected const VERBOSITY_LOW = 1;

    private const COMM_SPACE = ' ';
    private const COMM_SYMBOL_IN = '>>';
    private const COMM_SYMBOL_OUT = '<<';
    private const COMM_SYMBOL_FLAT = '--';
    private const COMM_SYMBOL_NONE = '  ';

    /**
     * Calling command if running in console.
     */
    protected ?Command $callingCommand;

    /**
     * Hashing algorithm in use.
     */
    protected string $hashAlgorithm;

    /**
     * Level of detail.
     */
    protected int $verbosity;

    /**
     * Communicator constructor.
     */
    public function __construct(?Command $callingCommand = null)
    {
        $this->callingCommand = $callingCommand;
    }

    final protected function printBlankLine(): void
    {
        $this->tell("\n", 'none');
    }

    /**
     * Print to console or screen.
     *
     * @param string $direction in|out
     */
    final protected function tell(string $text, string $direction = self::COMM_DIRECTION_OUT): void
    {
        $direction = strtolower($direction);
        $nl = app()->runningInConsole() ? "\n" : '<br/>';

        switch ($direction) {
            case self::COMM_DIRECTION_IN:
                $dirSymbol = self::COMM_SYMBOL_IN;

                break;
            case self::COMM_DIRECTION_FLAT:
                $dirSymbol = self::COMM_SYMBOL_FLAT;

                break;
            case self::COMM_DIRECTION_OUT:
                $dirSymbol = self::COMM_SYMBOL_OUT;

                break;
            default:
                $dirSymbol = self::COMM_SYMBOL_NONE;
        }
        $dirSymbol .= self::COMM_SPACE;

        if ($this->callingCommand !== null && app()->runningInConsole()) {
            if ($direction === self::COMM_DIRECTION_OUT) {
                $this->callingCommand->line("<info>\\<\\< {$text}</info>");
            } else {
                $this->callingCommand->line($dirSymbol . $text);
            }
        } else {
            echo $nl . $dirSymbol . $text;
        }
    }
}
