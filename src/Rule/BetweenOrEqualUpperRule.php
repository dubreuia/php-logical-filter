<?php
/**
 * BetweenOrEqualUpperRule
 *
 * @package php-logical-filter
 * @author  Jean Claveau
 */
namespace JClaveau\LogicalFilter\Rule;

class BetweenOrEqualUpperRule extends BetweenRule
{
    /** @var string operator */
    const operator = '><=';

    /**
     */
    public function __construct( $field, array $limits )
    {
        $this->addOperand( new AboveRule($field, $limits[0]) );
        $this->addOperand( new BelowOrEqualRule($field, $limits[1]) );
    }

    /**
     * @param bool $debug=false
     */
    public function toArray($debug=false)
    {
        $description = [
            $this->getField(),
            $debug ? $this->getInstanceId() : self::operator,
            [
                $this->getMinimum(),
                $this->getMaximum(),
            ]
        ];

        return $description;
    }

    /**/
}