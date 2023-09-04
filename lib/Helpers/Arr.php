<?php

namespace TracezillaSDK\Helpers;

class Arr {
    /**
     * Variable to hold array
     */
    protected $arr;

    public function __construct(array $arr) {
        $this->arr = $arr;
        return $this;
    }

    /**
     * Helper function to create instance
     */
    public static function from(array $arr) {
        return new self($arr);
    }

    /**
     * Get array value by path
     */
    public function get(string $path, mixed $defaultValue = null) {
        $parts      = explode('.', $path);
        $pointer    = $this->arr;

        $nextPart   = array_shift($parts);

        while ($nextPart && is_array($pointer) && isset($pointer[$nextPart])) {
            $pointer    =& $pointer[$nextPart];
            $nextPart   = array_shift($parts);
        }

        return $nextPart || !$pointer ? $defaultValue : $pointer;
    }
}