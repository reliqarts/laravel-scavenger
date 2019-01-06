<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Schema;
use ReliQArts\Scavenger\Helpers\Config;

/**
 * Scavenger Scrap.
 *
 * @property string      $hash
 * @property string      $model
 * @property string      $source
 * @property bool|string $data
 * @property string      $title
 * @property mixed       $related
 *
 * @method static firstOrCreate(array $array, array $array1 = [])
 * @method static firstOrNew(array $array, array $array1 = [])
 */
class Scrap extends EloquentModel
{
    private const SOFT_DELETES_TRAIT = 'Illuminate\Database\Eloquent\SoftDeletes';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the scraps table.
     *
     * @return string
     */
    public function getTable()
    {
        return Config::getScrapsTable();
    }

    /**
     * Convert scrap to target model.
     *
     * @param bool $convertDuplicates     whether to force conversion even if model already exists
     * @param bool $storeRelatedReference Whether to update relation field on scrap (self)
     *                                    N.B. if reference is stored the scrap will be saved.
     *
     * @return null|EloquentModel
     */
    public function convert(bool $convertDuplicates = false, bool $storeRelatedReference = false): ?EloquentModel
    {
        $targetObject = null;
        $convert = true;

        if (!empty($this->model)) {
            $existingRelated = $this->getRelated();

            if ($existingRelated && !$convertDuplicates) {
                return $existingRelated;
            }

            if ($convert) {
                /** @var EloquentModel $targetObject */
                $targetObject = new $this->model();
                $targetTable = $targetObject->getTable();

                // Fill model data with scrap data if attributes exist
                foreach (json_decode($this->data, true) as $attr => $val) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    if (!Config::isSpecialKey($attr) && Schema::hasColumn($targetTable, $attr)) {
                        $targetObject->{$attr} = $val;
                    }
                }

                // save related model
                $targetObject->save();

                if ($storeRelatedReference) {
                    $this->related = $targetObject->getKey();
                    $this->save();
                }
            }
        }

        return $targetObject;
    }

    /**
     * Convert scrap to target model.
     *
     * @return null|EloquentModel
     */
    public function getRelated(): ?EloquentModel
    {
        $related = null;

        if ($this->model && $this->related) {
            // find relation
            if ($this->relatedModelUsesSoftDeletes()) {
                /** @noinspection PhpUndefinedMethodInspection */
                $related = $this->model::withTrashed()->find($this->related);
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                $related = $this->model::find($this->related);
            }
        }

        return $related;
    }

    /**
     * Whether related model uses eloquent's SoftDeletes trait.
     *
     * @return bool
     *
     * @see \Illuminate\Database\Eloquent\SoftDeletes
     */
    public function relatedModelUsesSoftDeletes(): bool
    {
        return in_array(self::SOFT_DELETES_TRAIT, class_uses($this->model, true), true);
    }
}
