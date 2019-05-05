<?php

/*
 * @author    Reliq <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliqArts\Scavenger\Helpers;

use Symfony\Component\DomCrawler\Crawler;

class NodeProximityAssistant
{
    private const CURRENT_NODE_LIST_EMPTY = 'The current node list is empty.';

    /**
     * @param string  $selector
     * @param Crawler $crawler
     *
     * @return Crawler
     */
    public function closest(string $selector, Crawler $crawler): Crawler
    {
        if (!count($crawler)) {
            throw new \InvalidArgumentException(self::CURRENT_NODE_LIST_EMPTY);
        }

        $node = $crawler->getNode(0);
        while ($node = $node->parentNode) {
            if (XML_ELEMENT_NODE === $node->nodeType) {
                $parentCrawler = new Crawler($node, $crawler->getUri(), $crawler->getBaseHref());
                $descendantMatchingSelector = $parentCrawler->filter($selector);
                if ($descendantMatchingSelector->count()) {
                    return $descendantMatchingSelector;
                }
            }
        }

        return null;
    }
}
