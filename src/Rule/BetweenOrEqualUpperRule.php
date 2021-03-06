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
        if ($limits[0] == $limits[1]) {
            // A > 1 && (A = 1 || A < 1) <=> no sens
            // So if the two limits are equal we only consider the equality
            $this->addOperand( new EqualRule($field, $limits[0]) );
            $this->addOperand( new EqualRule($field, $limits[0]) );
        }
        else {
            $this->addOperand( new AboveRule($field, $limits[0]) );
            $this->addOperand( new BelowOrEqualRule($field, $limits[1]) );
        }
    }

    /**
     * @return mixed
     */
    public function getMinimum()
    {
        if ($this->getOperandAt(0) instanceof EqualRule)
            return $this->getOperandAt(0)->getValue();

        return $this->getOperandAt(0)->getMinimum();
    }

    /**
     * @return mixed
     */
    public function getMaximum()
    {
        if (!$this->getOperandAt(1))
            return $this->getOperandAt(0)->getValue();

        if ($this->getOperandAt(1) instanceof EqualRule)
            return $this->getOperandAt(1)->getValue();

        return $this->getOperandAt(1)->getMaximum();
    }

    /**
     */
    public function getValues()
    {
        return [
            $this->getMinimum(),
            $this->getMaximum(),
        ];
    }

    /**/
}
