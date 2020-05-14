<?php

/**
 * UserAgent string builder.
 *
 * @category  Payment
 */
class Coin_Api_UserAgent
{
    /**
     * Components of the user-agent.
     *
     * @var array
     */
    private $_fields;

    /**
     * Initialise user-agent with default fields.
     */
    public function __construct()
    {
        $this->_fields = array(
            'Library' => array(
                'name' => 'Prestaworks.Coin.ApiWrapper',
                'version' => '1.0.0',
            ),
            'OS' => array(
                'name' => php_uname('s'),
                'version' => php_uname('r'),
            ),
            'Language' => array(
                'name' => 'PHP',
                'version' => phpversion(),
            ),
        );
    }

    /**
     * Add a new field to the user agent.
     *
     * @param string $field Name of field
     * @param array  $data  data array with name, version and possibly options
     */
    public function addField($field, array $data)
    {
        if (array_key_exists($field, $this->_fields)) {
            throw new Coin_Api_Exception(
                "Unable to redefine field {$field}"
            );
        }
        $this->_fields[$field] = $data;
    }

    /**
     * Serialise fields to a user agent string.
     *
     * @return string
     */
    public function __toString()
    {
        $parts = array();
        foreach ($this->_fields as $key => $value) {
            $parts[] = "$key/{$value['name']}_{$value['version']}";
            if (array_key_exists('options', $value)) {
                $parts[] = '('.implode(' ; ', $value['options']).')';
            }
        }

        return implode(' ', $parts);
    }
}
