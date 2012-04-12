<?php

namespace li3_solr\vendors\QueryBuilder;

class Querystring
{

    /**
     * Queries storage.
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Sub-queries storage.
     *
     * @var array
     */
    protected $_subQueries = array();

    /**
     * Default operator for joining fields
     *
     * @var string
     */
    protected $_fieldSeparator = 'AND';

    /**
     * Add a field to Querystring
     *
     * @param Field $field
     * @param String $key  OPTIONAL identifier to use later for removing or overwriting
     * @return Querystring
     */
    public function addField(Field $field, $key = null)
    {
        if ($key) {
            $this->_fields[$key] = $field;
        }
        else {
            $this->_fields[] = $field;
        }

        return $this;
    }

    /**
     * Add another Querystring object to current query
     *
     * @param Querystring $query
     * @param String $key  OPTIONAL identifier to use later for removing or overwriting
     * @return Querystring
     */
    public function addSubQuery(Querystring $query, $key = null)
    {
        if ($key) {
            $this->_subQueries[$key] = $query;
        }
        else {
            $this->_subQueries[] = $query;
        }
    }

    /**
     * Set the operator for joining Fields
     *
     * @param  String $separator  AND|OR
     * @return String Querystring
     */
    public function setFieldSeparator($separator)
    {
        if ($separator == 'AND' || $separator == 'OR') {
            $this->_fieldSeparator = $separator;
        }

        return $this;
    }

    /**
     * Removes a sub-Query using key
     *
     * @param  $key
     * @return Querystring
     */
    public function removeSubQuery($key)
    {
        unset($this->_subQueries[$key]);
        return $this;
    }

    /**
     * Removes a Field using key
     *
     * @param  $key
     * @return Querystring
     */
    public function removeField($key)
    {
        unset($this->_fields[$key]);
        return $this;
    }


    /**
     * Build RAW query string
     *
     * @return string
     */
    public final function __toString()
    {
        $output = trim($this->_renderRawOutput());

        if (!empty($this->_subQueries)) {
            $subOutput = ' ('. implode(') ' . $this->_fieldSeparator . ' (', $this->_subQueries) . ') ';
            if (!empty($output)) {
                $output .= ' ' . $this->_fieldSeparator . $subOutput;
            }
            else {
                $output = $subOutput;
            }
        }
        return $output;
    }

    protected function _renderRawOutput()
    {
        if (!empty($this->_fields)) {
            return implode(' ' . $this->_fieldSeparator . ' ', $this->_fields);
        }

        return "";
    }
}

