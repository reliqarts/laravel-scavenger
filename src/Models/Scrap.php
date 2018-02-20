<?php

namespace ReliQArts\Scavenger\Models;

use Schema;
use Illuminate\Database\Eloquent\Model;
use ReliQArts\Scavenger\Helpers\SchemaHelper;
/**
 *  Scavenger Scrap model.
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
        return SchemaHelper::getScrapsTable();
    }

    /**
     * Convert scrap to target model.
     * 
     * @param boolean $convertDuplicates Whether to force conversion even if model already exists.
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
                $targetObject = new $this->model;

                // Fill model data with scrap data if attributes exist
                foreach (json_decode($this->data, true) as $attr => $val) {
                    $targetTable = $targetObject->getTable();
                    if (!SchemaHelper::isSpecialKey($attr) && Schema::hasColumn($targetTable, $attr)) {
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
     * @param boolean $force Whether to force conversion even if model already exists.
     *
     * @return Model
     */
    public function getRelated()
    {
        $related = false;
        if ($this->model && $this->related) {
            // find relation
            if ($this->relatedModelUsesSoftDeletes()) {
                $related = $this->model::withTrashed()->find($this->related);
            } else {
                $related = $this->model::find($this->related);
            }
        }
        return $related;
    }

    /**
     * Whether related model uses eloquent's SoftDeletes trait.
     *
     * @see Illuminate\Database\Eloquent\SoftDeletes
     * @return bool
     */
    public function relatedModelUsesSoftDeletes()
    {
        $result = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model, true));
        return $result;
    }
}
