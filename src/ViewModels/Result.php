<?php

namespace ReliQArts\Scavenger\ViewModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 *	Result view model.
 */
class Result implements Arrayable, Jsonable
{
    public $success = false;
    public $error = null;
    public $extra = null;
    public $message = null;
    public $reused = null;

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $options 
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}
