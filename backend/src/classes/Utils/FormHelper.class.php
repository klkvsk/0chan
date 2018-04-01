<?php

class FormHelper {

    protected static $primitiveTypes = [
        PrimitiveString       ::class => 'string',
        PrimitiveInteger      ::class => 'int',
        PrimitiveDate         ::class => 'date',
        PrimitiveTimestamp    ::class => 'timestamp',
        PrimitiveTime         ::class => 'time',
        PrimitiveFloat        ::class => 'float',
        PrimitiveIdentifier   ::class => 'id',
        PrimitiveTernary      ::class => 'bool',
        PrimitiveBoolean      ::class => 'bool',
        PrimitiveFile         ::class => 'file',
        ListedPrimitive       ::class => 'list',
    ];

    /**
     * @param Form $form
     * @param array $titles
     * @param array $descriptions
     * @return array
     */
    public static function toClient(Form $form, $titles = array(), $descriptions = array()) {
        $schema = [];
        /** @var BasePrimitive[] $primitives */
        $primitives = $form->getPrimitiveList();
        foreach ($primitives as $primitive) {
            $item = [];
            $item['name'] = $primitive->getName();
            $item['required'] = $primitive->isRequired();
            foreach (self::$primitiveTypes as $primitiveClass => $primitiveType) {
                if (is_a($primitive, $primitiveClass)) {
                    $item['type'] = $primitiveType;
                    break;
                }
            }
            if (!isset($item['type'])) {
                throw new UnexpectedValueException('type is not mapped for ' . get_class($primitive));
            }

            if (isset($titles[$primitive->getName()])) {
                $item['title'] = $titles[$primitive->getName()];
            } else {
                $item['title'] = ucfirst($primitive->getName());
            }

            if (isset($descriptions[$primitive->getName()])) {
                $item['description'] = $descriptions[$primitive->getName()];
            }

            if ($primitive instanceof ListedPrimitive) {
                $item['options'] = [];
                foreach ($primitive->getList() as $primitiveOptionValue => $primitiveOptionChoiceValue) {
                    if (is_scalar($primitiveOptionChoiceValue)) {
                        $choiceName = $primitiveOptionChoiceValue;
                    } else if ($primitiveOptionChoiceValue instanceof NamedObject) {
                        $choiceName = $primitiveOptionChoiceValue->getName();
                    } else {
                        throw new UnexpectedValueException(
                            'choice name can not be guessed from ' . get_class($primitiveOptionChoiceValue)
                        );
                    }
                    $item['options'][$primitiveOptionChoiceValue] = $choiceName;
                }
            }

            if ($primitive instanceof RangedPrimitive) {
                $item['min'] = $primitive->getMin();
                $item['max'] = $primitive->getMax();
            }

            if ($primitive instanceof PrimitiveString) {
                $item['pattern'] = trim($primitive->getAllowedPattern(), '/') ?: null;
            }

            $error = $form->getError($primitive->getName());
            if ($error) {
                $errorText = $form->getTextualErrorFor($primitive->getName());
                if (!$errorText) {
                    if ($error == Form::WRONG) {
                        $errorText = 'Неправильно заполнено поле';
                    } else if ($error == Form::MISSING) {
                        $errorText = 'Не указано обязательное поле';
                    } else {
                        $errorText = 'Ошибка №' . $error;
                    }
                }
                $item['error'] = $errorText;
            }

            $schema []= $item;
        }

        return $schema;
    }

    public static function booleanToTernary(Form $form) {
        foreach ($form->getPrimitiveList() as $primitive) {
            if ($primitive instanceof PrimitiveBoolean) {
                $ternaryPrimitive = Primitive::ternary($primitive->getName())
                    ->setRequired($primitive->isRequired())
                    ->setDefault($primitive->getDefault());
                $form->set($ternaryPrimitive);
            }
        }
    }

    public static function makeStringFilter($multiline = false)
    {
        $chain = Filter::chain();
        $chain->add(Filter::safeUtf8());
        $chain->add(ZalgoTextFilter::me());
        $chain->add(Filter::trim());
        //$chain->add(Filter::htmlSpecialChars());

        if ($multiline) {
            $chain->add(Filter::nl2br());
        }

        return $chain;
    }

    /**
     * @param AbstractProtoClass $proto
     * @param $name
     * @return PrimitiveString
     */
    public static function stringPrimitive(AbstractProtoClass $proto, $name, $multiline = null)
    {
        $prop = $proto->getPropertyByName($name);
        $prm = $prop->makePrimitive($name);
        if ($prm instanceof PrimitiveString) {
            $prm->addImportFilter(FormHelper::makeStringFilter($multiline));
        } else {
            throw new UnexpectedValueException('$prm is ' . get_class($prm));
        }
        return $prm;
    }

}