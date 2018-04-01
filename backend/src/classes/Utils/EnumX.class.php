<?php

/**
 * Class EnumX
 * extended Enum with better phpdoc and helpers
 */
class EnumX extends Enum {

    /**
     * @param int $id
     * @return static
     */
    public static function create($id)
    {
        /** @var static $enum */
        $enum = parent::create($id);
        return $enum;
    }

    /**
     * @return static[]
     */
    public static function getList()
    {
        return parent::getList();
    }

    /**
     * @param $enum
     * @return bool
     * @throws WrongArgumentException
     */
    public function is($enum)
    {
        if ($enum instanceof static) {
            return $enum->getId() == $this->getId();
        } else if (is_scalar($enum)) {
            return $enum == $this->getId();
        } else {
            throw new WrongArgumentException(var_export($enum, true));
        }
    }

}