<?php

namespace ReliQArts\Scavenger\Contracts;

interface Paraphraser
{
    /**
     * Paraprase text.
     *
     * @param string $text text to paraphase
     *
     * @return string paraphrased text.
     */
    public function paraphrase($text);
}
