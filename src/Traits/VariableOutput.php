<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Traits;

use Illuminate\Console\Command;

trait VariableOutput
{
    /**
     * Calling command if running in console.
     *
     * @var Command
     */
    protected $callingCommand = null;

    /**
     * Print to console or screen.
     *
     * @param string $text
     * @param string $direction in|out
     *
     * @return string
     */
    protected function tell(string $text, string $direction = 'out'): string
    {
        $direction = strtolower($direction);
        $nl = app()->runningInConsole() ? "\n" : '<br/>';
        $dirSymbol = ($direction == 'in' ? '>> ' : ($direction == 'flat' ? '-- ' : '<< '));
        if ($direction == 'none') {
            $dirSymbol = '';
        }

        if (app()->runningInConsole() && $this->callingCommand) {
            if ($direction == 'out') {
                $this->callingCommand->line("<info>\<\< {$text}</info>");
            } else {
                $this->callingCommand->line("$dirSymbol$text");
            }
        } else {
            print "$nl$dirSymbol$text";
        }

        return $text;
    }
}