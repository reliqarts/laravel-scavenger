<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Contract;

interface Paraphraser
{
    /**
     * Paraphrase text.
     *
     * @param string $text text to paraphrase
     *
     * @return string paraphrased text
     */
    public function paraphrase(string $text): string;
}
