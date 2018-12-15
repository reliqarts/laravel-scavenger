<?php

namespace ReliQArts\Scavenger\Contracts;

interface Paraphraser
{
    /**
     * Paraphrase text.
     *
     * @param string $text text to paraphrase
     *
     * @return string paraphrased text.
     */
    public function paraphrase(string $text): string;
}
