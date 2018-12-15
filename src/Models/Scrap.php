<?php

namespace ReliQArts\Scavenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ReliQArts\Scavenger\Helpers\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Scavenger Scrap
 *
 * @property string      $hash
 * @property string      $model
 * @property string      $source
 * @property string|bool $data
 * @property string      $title
 * @property mixed       $related
 * @method static Builder whereHash($specialKey)
 */
class Scrap extends Model
{
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
     * @param bool $convertDuplicates whether to force conversion even if model already exists
     *
     * @return Model
     */
    public function convert($convertDuplicates = false)
    {
        $targetObject = false;
        $convert = true;

        if ($this->model) {
            if ($existingRelated = $this->getRelated()) {
                $targetObject = $existingRelated;
                if (!$convertDuplicates) {
                    $convert = false;
                }
            }

            if ($convert) {
                /** @var Model $targetObject */
                $targetObject = new $this->model();

                // Fill model data with scrap data if attributes exist
                foreach (json_decode($this->data, true) as $attr => $val) {
                    $targetTable = $targetObject->getTable();
                    /** @noinspection PhpUndefinedMethodInspection */
                    if (!Config::isSpecialKey($attr) && Schema::hasColumn($targetTable, $attr)) {
                        $targetObject->{$attr} = $val;
                    }
                }

                // Save model
                $targetObject->save();

                // Update relation
                $this->related = $targetObject->getKey();
                $this->save();
            }
        }

        return $targetObject;
    }

    /**
     * Convert scrap to target model.
     *
     * @return Model|bool
     */
    public function getRelated()
    {
        $related = false;
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
     * @see \Illuminate\Database\Eloquent\SoftDeletes
     *
     * @return bool
     */
    public function relatedModelUsesSoftDeletes(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model, true), true);
    }
}
