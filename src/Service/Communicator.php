<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Illuminate\Console\Command;

abstract class Communicator
{
    protected const string COMM_DIRECTION_IN = 'in';

    protected const string COMM_DIRECTION_OUT = 'out';

    protected const string COMM_DIRECTION_FLAT = 'flat';

    protected const string COMM_DIRECTION_NONE = 'none';

    protected const string HASH_ALGORITHM = 'sha512';

    protected const int VERBOSITY_HIGH = 3;

    protected const int VERBOSITY_MEDIUM = 2;

    protected const int VERBOSITY_LOW = 1;

    private const string COMM_SPACE = ' ';

    private const string COMM_SYMBOL_IN = '>>';

    private const string COMM_SYMBOL_OUT = '<<';

    private const string COMM_SYMBOL_FLAT = '--';

    private const string COMM_SYMBOL_NONE = '  ';

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
     * @param  string  $direction  in|out
     */
    final protected function tell(string $text, string $direction = self::COMM_DIRECTION_OUT): void
    {
        $direction = strtolower($direction);
        $nl = app()->runningInConsole() ? "\n" : '<br/>';
        $dirSymbol = match ($direction) {
            self::COMM_DIRECTION_IN => self::COMM_SYMBOL_IN,
            self::COMM_DIRECTION_FLAT => self::COMM_SYMBOL_FLAT,
            self::COMM_DIRECTION_OUT => self::COMM_SYMBOL_OUT,
            default => self::COMM_SYMBOL_NONE,
        };
        $dirSymbol .= self::COMM_SPACE;

        if ($this->callingCommand !== null && app()->runningInConsole()) {
            if ($direction === self::COMM_DIRECTION_OUT) {
                $this->callingCommand->line("<info>\\<\\< $text</info>");
            } else {
                $this->callingCommand->line($dirSymbol.$text);
            }
        } else {
            echo $nl.$dirSymbol.$text;
        }
    }
}
