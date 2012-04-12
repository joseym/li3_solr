<?php

namespace li3_solr\vendors\QueryBuilder;

class Field {

    protected $_fieldName;
    protected $_terms = array();

    const RANGE_QUERY = 'RANGE_QUERY';

    const OPERATOR_RESTRICTED = '-';
    const OPERATOR_REQUIRED   = '+';
    
    private $_operators = array(self::OPERATOR_REQUIRED, self::OPERATOR_RESTRICTED);
    private $_modifiers = array('~', '^');


    function __construct($field = null, $value = null)
    {
        if(! empty($field)){
            $this->_fieldName = strval($field);
        }

        if(! empty($value)) {
            $this->addTerm($value);
        }

        return $this;
    }

    public function addTerm($value, $operator = null, array $metadata = null)
    {
        $term = array();

        $term['value'] = strval($value);

        if(! is_null($operator)) $term['operator'] = $operator;
        if(! is_null($metadata)) $term['metadata'] = $metadata;

        $this->_terms[] = $term;
        return $this;
    }

    public function setRange($from = null, $to = null, $operator = null)
    {
        $rangeTerm = array(
            'from' => (is_null($from))? '*' : strval($from),
            'to'   => (is_null($to))? '*' : strval($to),
        );

        $this->addTerm(self::RANGE_QUERY, $operator, $rangeTerm);

        return $this;
    }

    public function addBoostedTerm($value, $boostFactor, $operator = null)
    {
        $boost = array(
            'modifier' => '^',
            'factor'   => $boostFactor,
        );

        if(!is_numeric($boostFactor) || $boostFactor <= 0) {
            throw new \InvalidArgumentException("Supported boost factor is a number greater than 0, $boostFactor given");
        } else {
            $this->addTerm($value, $operator, (($boostFactor == 1)? null : $boost));
        }
        
        return $this;
    }

    public function addFuzzyTerm($value, $similarity = null, $operator = null)
    {
        $fuzzy = array('modifier' => '~');

        if(! is_null($similarity)) {
            if(!is_numeric($similarity) || $similarity <= 0 || $similarity >= 1) {
                throw new \InvalidArgumentException("Supported fuzzy similarity factor is a number between 0 to 1, $similarity given");
            }
            
            $fuzzy['factor']   = $similarity;
        }

        $this->addTerm($value, $operator, $fuzzy);

        return $this;
    }

    public function addProximityPhrase($value, $distance, $operator = null)
    {
        $proximity = array('modifier' => '~');

        if(! is_null($distance)) {
            if(!is_numeric($distance) || $distance <= 0 || is_float($distance)) {
                throw new \InvalidArgumentException("Supported proximity value is a number greater then 1, $distance given");
            }

            if(strpos(trim($value), ' ') < 1){
                throw new \InvalidArgumentException("Proximity cab be applied on a phrase of multiple words, $value given");
            }

            $proximity['factor'] = $distance;
        }

        $this->addTerm($value, $operator, $proximity);

        return $this;
    }

    public function __toString()
    {
        $output = $this->_processTerms();

        return (empty($this->_fieldName))? $output : $this->_fieldName . ':' . $output;
    }

    protected function _processTerms()
    {
        $termsOutput = (count($this->_terms) > 1)? '(' : '';

        $termSegments = array();
        foreach($this->_terms as $term){

            if(is_array($term)){

                if($term['value'] == self::RANGE_QUERY){
                    $segmentOutput = "[{$term['metadata']['from']} TO {$term['metadata']['to']}]";
                } else {
                    $segmentOutput = self::_escapeToken($term['value']);
                }

                // Add operator, required or restricted
                if(isset($term['operator']) && in_array($term['operator'], $this->_operators)) {
                    $segmentOutput = $term['operator'] . $segmentOutput;
                }
                
                // Add fuzzy, proximity and boost factor
                if(isset($term['metadata']['modifier']) && in_array($term['metadata']['modifier'], $this->_modifiers)) {
                    $segmentOutput .=  $term['metadata']['modifier'];
                    if(isset($term['metadata']['factor']) && is_numeric($term['metadata']['factor'])){
                        $segmentOutput .= $term['metadata']['factor'];
                    }
                }

                $termSegments[] = $segmentOutput;
            } else {
                $termSegments[] = self::_escapeToken($term);
            }
        }
        
        $termsOutput .= implode(' ', $termSegments);
        $termsOutput .= (count($this->_terms) > 1)? ')' : '';

        return $termsOutput;
    }

    /**
    * Add '"' chars if necessary to a token value.
    * @return unknown_type
    */
    protected static function _escapeToken($token)
    {
        if (preg_match("/[\., \-:]/", $token)) {
          return '"' . $token . '"';
        }
        else {
          return $token;
        }
    }
}
