<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Factories;

use ReliQArts\Scavenger\DTOs\Result;
use ReliQArts\Scavenger\Exceptions\Exception;
use ReliQArts\Scavenger\Exceptions\InvalidTargetDefinition;
use ReliQArts\Scavenger\Helpers\FormattedMessage;
use ReliQArts\Scavenger\Helpers\TargetKey;
use ReliQArts\Scavenger\Models\Target;
use ReliQArts\Scavenger\Services\Scanner;

final class TargetBuilder
{
    /**
     * @var array
     */
    private $globalKeywords;

    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * TargetBuilder constructor.
     *
     * @param array        $globalKeywords
     * @param null|Scanner $scanner
     */
    public function __construct(array $globalKeywords = [], ?Scanner $scanner = null)
    {
        $this->globalKeywords = $globalKeywords;
        $this->scanner = $scanner ?: new Scanner();
    }

    /**
     * @param array $definition
     *
     * @throws Exception
     *
     * @return Target
     */
    public function createFromDefinition(array $definition): Target
    {
        $validityResult = $this->validateDefinition($definition);
        if (!$validityResult->isSuccess()) {
            throw InvalidTargetDefinition::fromResult($validityResult);
        }

        // aggregate keywords
        if (!(empty($this->globalKeywords) || empty($definition[TargetKey::SEARCH]))) {
            $definition[TargetKey::SEARCH][TargetKey::SEARCH_KEYWORDS] = array_merge(
                $this->globalKeywords,
                $definition[TargetKey::SEARCH][TargetKey::SEARCH_KEYWORDS]
            );
        }

        return new Target(
            $definition[TargetKey::NAME],
            $definition[TargetKey::MODEL],
            $definition[TargetKey::SOURCE],
            $definition[TargetKey::MARKUP],
            $definition[TargetKey::PAGER] ?? [],
            $definition[TargetKey::PAGES] ?? 0,
            $definition[TargetKey::DISSECT] ?? [],
            $definition[TargetKey::PREPROCESS] ?? [],
            $definition[TargetKey::REMAP] ?? [],
            $definition[TargetKey::BAD_WORDS] ?? [],
            $definition[TargetKey::SEARCH] ?? [],
            $definition[TargetKey::SEARCH_ENGINE_REQUEST_PAGES] ?? false,
            $definition[TargetKey::EXAMPLE] ?? false
        );
    }

    /**
     * @param array $definition
     *
     * @return Result
     */
    private function validateDefinition(array $definition): Result
    {
        $result = new Result(true);

        if (empty($definition[TargetKey::NAME])) {
            return $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::TARGET_NAME_MISSING,
                    sprintf('[%s]', TargetKey::NAME)
                )
            );
        }

        $targetName = $definition[TargetKey::NAME];

        if (!empty($definition['example']) && $definition['example']) {
            return $result->setSuccess(false)->addError(
                FormattedMessage::get(FormattedMessage::EXAMPLE_TARGET, $targetName)
            );
        }

        // ensure model can be resolved
        try {
            $definition[TargetKey::MODEL] = resolve($definition[TargetKey::MODEL]);
        } catch (Exception $e) {
            return $result->setSuccess(false)->addError(
                FormattedMessage::get(FormattedMessage::TARGET_MODEL_RESOLUTION_FAILED, $targetName, $e->getMessage())
            );
        }

        if (empty($definition[TargetKey::SOURCE])) {
            return $result->setSuccess(false)->addError(
                FormattedMessage::get(FormattedMessage::TARGET_SOURCE_MISSING, $targetName)
            );
        }

        // validate markup definition
        $markupValidityResult = $this->validateMarkupDefinition($definition[TargetKey::MARKUP], $targetName);
        if ($markupValidityResult->hasErrors()) {
            $result = $result->setSuccess(false)->addErrors($markupValidityResult->getErrors());
        }

        // validate pager definition
        $pagerValidityResult = $this->validatePagerDefinition($definition[TargetKey::PAGER]);
        if ($pagerValidityResult->hasErrors()) {
            $result = $result->setSuccess(false)->addErrors($pagerValidityResult->getErrors());
        }

        // validate search definition
        $searchValidityResult = $this->validateSearchDefinition($definition[TargetKey::SEARCH], $targetName);
        if ($searchValidityResult->hasErrors()) {
            $result = $result->setSuccess(false)->addErrors($searchValidityResult->getErrors());
        }

        return $result;
    }

    /**
     * @param null|array $markupDefinition
     * @param string     $targetName
     *
     * @return Result
     */
    private function validateMarkupDefinition(?array $markupDefinition, string $targetName): Result
    {
        $result = new Result(true);

        if (empty($markupDefinition)) {
            return $result;
        }

        $titleOrLinkExists = !empty(
            Scanner::firstNonEmpty($markupDefinition, [TargetKey::MARKUP_TITLE, TargetKey::MARKUP_LINK])
        );
        if (!$titleOrLinkExists) {
            $result = $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::TARGET_MISSING_TITLE_LINK,
                    $targetName
                )
            );
        }

        if (empty($markupDefinition[TargetKey::special(TargetKey::MARKUP_INSIDE)])
            && empty(
                Scanner::firstNonEmpty($markupDefinition, [
                    TargetKey::special(TargetKey::ITEM_WRAPPER),
                    TargetKey::special(TargetKey::RESULT),
                    TargetKey::special(TargetKey::ITEM),
                    TargetKey::special(TargetKey::WRAPPER),
                ])
            )
        ) {
            $result = $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::TARGET_MISSING_ITEM_WRAPPER,
                    $targetName
                )
            );
        }

        return $result;
    }

    /**
     * @param null|array $pagerDefinition
     *
     * @return Result
     */
    private function validatePagerDefinition(?array $pagerDefinition): Result
    {
        $result = new Result(true);

        if (empty($pagerDefinition)) {
            return $result;
        }

        if (empty($pagerDefinition[TargetKey::PAGER_SELECTOR])) {
            $result = $result->setSuccess(false);
        }

        return $result;
    }

    /**
     * @param null|array $searchDefinition
     * @param string     $targetName
     *
     * @return Result
     */
    private function validateSearchDefinition(?array $searchDefinition, string $targetName): Result
    {
        $result = new Result(true);

        if (empty($searchDefinition)) {
            return $result->setSuccess(true);
        }

        // ensure form requirements are set
        if (empty($searchDefinition[TargetKey::SEARCH_FORM][TargetKey::SEARCH_FORM_SELECTOR])) {
            // form selector
            $result = $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::MISSING_SEARCH_KEY,
                    'form selector config',
                    sprintf('[%s][%s][%s]', TargetKey::SEARCH, TargetKey::SEARCH_FORM, TargetKey::SEARCH_FORM_SELECTOR),
                    $targetName
                )
            );
        } elseif (empty($searchDefinition[TargetKey::SEARCH_FORM][TargetKey::SEARCH_FORM_INPUT])) {
            // form keyword input name
            $result = $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::MISSING_SEARCH_KEY,
                    'keyword input name',
                    sprintf('[%s][%s][%s]', TargetKey::SEARCH, TargetKey::SEARCH_FORM, TargetKey::SEARCH_FORM_INPUT),
                    $targetName
                )
            );
        } elseif (empty($this->globalKeywords) && empty($searchDefinition[TargetKey::SEARCH_KEYWORDS])) {
            // search keyword list empty
            $result = $result->setSuccess(false)->addError(
                FormattedMessage::get(
                    FormattedMessage::MISSING_SEARCH_KEY,
                    'keywords',
                    sprintf('[%s][%s]', TargetKey::SEARCH, TargetKey::SEARCH_KEYWORDS),
                    $targetName
                )
            );
        }

        return $result;
    }
}
