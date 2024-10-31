<?php

namespace PayPecker\WooPaypecker\Api;

use PayPecker\WooPaypecker\Setup;
use illuminate\Validation\Validator as IllumateValidator;
use PayPecker\WooPaypecker\Api\ValidatorFactoryMaker;

class Validator
{
    public static function tokenIsValid(string $token)
    {
        $options = get_option(Setup::OPTIONS_IDENTIFIER);
        $tokenSavedInOption = isset($options['token']) ? $options['token'] : null;
        if ($tokenSavedInOption) {
            return "Bearer $tokenSavedInOption" === $token;
        } else {
            return false;
        }
    }

    public static function userIsSet()
    {
        $options = get_option(Setup::OPTIONS_IDENTIFIER);
        if (isset($options['user_id'])) {
            return true;
        }
        return false;
    }

    public static function categoryIsValid($categories)
    {
        $categoriesAreValid = true;
        foreach ($categories as $category) {
            $categoriesAreValid = $categoriesAreValid && self::validateCategory($category);
        }
        return $categoriesAreValid;
    }

    public static function validateCategory($category)
    {
        $isParentValid = true;
        if (!isset($category['value'])) {
            return false;
        }

        if (!is_string($category['value'])) {
            return false;
        }

        if (isset($category['parent']) && !is_array($category['parent'])) {
            return false;
        }

        if (isset($category['parent'])) {
            $isParentValid = self::validateCategory($category['parent']);
        }


        return true && $isParentValid;
    }

    public static function make(array $params, array $rules, array $customMessages = [], array $customAttributes = []): IllumateValidator
    {
        $factory = new ValidatorFactoryMaker();
        return $factory->make($params, $rules, $customMessages, $customAttributes);
    }
}
