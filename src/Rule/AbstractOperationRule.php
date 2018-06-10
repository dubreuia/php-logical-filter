<?php
namespace JClaveau\LogicalFilter\Rule;
use       JClaveau\VisibilityViolator\VisibilityViolator;

/**
 * Operation rules:
 * + Or
 * + And
 * + Not
 */
abstract class AbstractOperationRule extends AbstractRule
{
    /**
     * This property should never be null.
     *
     * @var array<AbstractRule> $operands
     */
    protected $operands = [];

    const remove_negations              = 'remove_negations';
    const remove_operation_duplicates   = 'remove_operation_duplicates';
    const rootify_disjunctions          = 'rootify_disjunctions';
    const unify_atomic_operands         = 'unify_atomic_operands';
    const remove_monooperand_operations = 'remove_monooperand_operations';
    const remove_invalid_branches       = 'remove_invalid_branches';    // simplified after this step

    const simplified                    = self::remove_invalid_branches;

    /**
     * The order is important!
     *
     * @var array $simplification_steps
     */
    const simplification_steps = [
        self::remove_negations,
        self::remove_operation_duplicates,
        self::rootify_disjunctions,
        self::unify_atomic_operands,
        self::remove_monooperand_operations,
        self::remove_invalid_branches,
    ];

    /**
     * @var null|string $simplified
     */
    protected $current_simplification_step = null;

    /**
     */
    public function __construct( array $operands=[] )
    {
        if ($nonRules = array_filter($operands, function($operand) {
            return !$operand instanceof AbstractRule;
        })) {
            throw new \InvalidArgumentException(
                "Operands must be instances of AbstractRule: \n"
                . var_export($nonRules, true)
            );
        }

        $this->operands = $operands;
    }

    /**
     * @return bool
     */
    public function isSimplified()
    {
        return $this->current_simplification_step == self::simplified;
    }

    /**
     * Adds an operand to the logical operation (&& or ||).
     *
     * @param  AbstractRule $new_operand
     *
     * @return $this
     */
    public function addOperand( AbstractRule $new_operand )
    {
        $this->operands[] = $new_operand;
        $this->current_simplification_step = null;
        return $this;
    }

    /**
     * @return array
     */
    public function getOperands()
    {
        return $this->operands;
    }

    /**
     * @return $this
     */
    public function setOperands(array $operands)
    {
        // keep the index start at 0
        $this->operands = array_values($operands);
        $this->current_simplification_step = null;
        return $this;
    }

    /**
     * @param string $step_to_go_to
     */
    public function moveSimplificationStepForward($step_to_go_to)
    {
        if (!in_array($step_to_go_to, self::simplification_steps)) {
            throw new \InvalidArgumentException(
                "Invalid simplification step to go to: ".$step_to_go_to
            );
        }

        $steps_indices = array_flip(self::simplification_steps);

        if ($this->current_simplification_step != null) {
            $current_index = $steps_indices[ $this->current_simplification_step ];
            $target_index  = $steps_indices[ $step_to_go_to ];

            if ( $current_index >= $target_index ) {
                // allow recall of previous step without going back
                return;
            }
            elseif ( $current_index < $target_index - 1 ) {
                throw new \LogicException(
                    "$step_to_go_to MUST be fullfilled after " . self::simplification_steps[$target_index - 1]
                    . " instead of the current step: " . $this->current_simplification_step
                    ."\nfor: " . $this
                );
            }
        }

        $this->current_simplification_step = $step_to_go_to;
    }

    /**
     * Checks if a simplification step is reached.
     *
     * @param  string step
     *
     * @return bool
     */
    public function simplicationStepReached($step)
    {
        if (!in_array($step, self::simplification_steps)) {
            throw new \InvalidArgumentException(
                "Invalid simplification step: ".$step
            );
        }

        if ($this->current_simplification_step == null)
            return false;

        $steps_indices = array_flip(self::simplification_steps);

        $current_index = $steps_indices[ $this->current_simplification_step ];
        $step_index    = $steps_indices[ $step ];

        return $current_index >= $step_index;
    }

    /**
     * Replace NotRule objects by the negation of their operands.
     *
     * @return $this
     */
    public function removeNegations()
    {
        $this->moveSimplificationStepForward(self::remove_negations);

        foreach ($this->operands as $i => $operand) {

            if ($operand instanceof NotRule) {
                $this->operands[$i] = $operand->negateOperand();
            }

            if ($this->operands[$i] instanceof AbstractOperationRule) {
                $this->operands[$i]->removeNegations();
            }
        }

        return $this;
    }

    /**
     */
    public function removeOperationDuplicates()
    {
        $this->moveSimplificationStepForward( self::remove_operation_duplicates );

        foreach ($this->operands as $i => $operand) {
            if ($operand instanceof AbstractOperationRule)
                $operand->removeOperationDuplicates();
        }

        if ($this instanceof NotRule)
            return $this;

        foreach ($this->operands as $i => $operand) {
            if (get_class($operand) == get_class($this)) {
                // Id AND is an operand on AND they can be merge (and the same with OR)
                foreach ($operand->getOperands() as $sub_operand) {
                    $this->addOperand( $sub_operand->copy() );
                }
                unset($this->operands[$i]);
            }

        }

        return $this;
    }

    /**
     * Simplify the current AbstractOperationRule.
     *
     * @return AbstractOperationRule the simplified rule
     */
    public function unifyOperands($unifyDifferentOperands = true)
    {
        $this->moveSimplificationStepForward( self::unify_atomic_operands );

        foreach ($this->operands as $operand) {
            if ($operand instanceof AbstractOperationRule) {
                $operand->unifyOperands();
            }
        }

        $operandsByFields = $this->groupOperandsByFieldAndOperator();

        // unifying same operands
        foreach ($operandsByFields as $field => $operandsByOperator) {
            foreach ($operandsByOperator as $operator => $operands) {
                if ($operator == AboveRule::operator) {
                    usort($operands, [$this, 'aboveRuleUnifySorter']);
                    $operands = [reset($operands)];
                }
                elseif ($operator == BelowRule::operator) {
                    usort($operands, [$this, 'belowRuleUnifySorter']);
                    $operands = [reset($operands)];
                }
                elseif ($operator == EqualRule::operator) {
                    $operandsTmp = array_map(function($operand) {
                        return serialize($operand);
                    }, $operands);

                    $operandsTmp = array_unique($operandsTmp);

                    $operands = array_map(function($operand) {
                        return unserialize($operand);
                    }, $operandsTmp);
                }

                $operandsByFields[ $field ][ $operator ] = $operands;
            }
        }

        if ($unifyDifferentOperands && $this instanceof AndRule) {
            // unifiying operands of different types
            $operandsByFields = $this->simplifyDifferentOperands($operandsByFields);
        }

        // Remove the index by fields and operators
        $unifiedOperands = [];
        foreach ($operandsByFields as $field => $operandsByOperator) {
            foreach ($operandsByOperator as $operator => $operands) {
                $unifiedOperands = array_merge($unifiedOperands, $operands);
            }
        }

        $this->operands = $unifiedOperands;

        return $this;
    }

    /**
     */
    public function removeMonooperandOperations()
    {
        $this->moveSimplificationStepForward( self::remove_monooperand_operations );

        if ($this instanceof NotRule) {
            throw new LogicException(
                "NotRule MUST have been already removed here"
            );
        }

        foreach ($this->operands as $i => $operand) {
            if ($operand instanceof AbstractOperationRule) {
                $this->operands[$i] = $operand->removeMonooperandOperations();
            }
        }

        if (count($this->operands) == 1)
            return reset($this->operands);

        return $this;
    }

    /**
     * Simplify the current OperationRule.
     * + If an OrRule or an AndRule contains only one operand, it's equivalent
     *   to it.
     * + If an OrRule has an other OrRule as operand, they can be merged
     * + If an AndRule has an other AndRule as operand, they can be merged
     *
     * @param  array $options stop_after | stop_before | force_logical_core
     *
     * @return AbstractRule the simplified rule
     */
    public final function simplify($options=[])
    {
        $step_to_stop_before = !empty($options['stop_before'])        ? $options['stop_before'] : null;
        $step_to_stop_after  = !empty($options['stop_after'])         ? $options['stop_after']  : null;
        $force_logical_core  = !empty($options['force_logical_core']) ? $options['force_logical_core'] : false;

        if ($step_to_stop_before && !in_array($step_to_stop_before, self::simplification_steps)) {
            throw new \InvalidArgumentException(
                "Invalid simplification step to stop at: ".$step_to_stop_before
            );
        }

        if ($step_to_stop_before == self::remove_negations)
            return $this;

        $this->removeNegations();

        // $this->dump(true);

        if ($step_to_stop_after  == self::remove_negations ||
            $step_to_stop_before == self::remove_operation_duplicates )
            return $this;

        // $this->dump(true);

        $this->removeOperationDuplicates();

        if ($step_to_stop_after  == self::remove_operation_duplicates ||
            $step_to_stop_before == self::rootify_disjunctions )
            return $this;

        // $this->dump(true);

        // FIXME return $this while RootifyingDisjunctions!
        if (method_exists($this, 'rootifyDisjunctions')) {
            $instance = $this->rootifyDisjunctions();
        }
        else {
            $instance = $this;
        }

        // $instance->dump(true, false);

        if ($step_to_stop_after  == self::rootify_disjunctions ||
            $step_to_stop_before == self::unify_atomic_operands )
            return $instance;

        $instance->unifyOperands();

        // $instance->dump(true, false);

        if ($step_to_stop_after  == self::unify_atomic_operands ||
            $step_to_stop_before == self::remove_monooperand_operations )
            return $instance;

        $instance = $instance->removeMonooperandOperations();

        // $instance->dump(!true);

        if ($step_to_stop_after  == self::remove_monooperand_operations ||
            $step_to_stop_before == self::remove_invalid_branches )
            return $instance;

        if (method_exists($instance, 'removeInvalidBranches')) {
            $instance->removeInvalidBranches();
            // Monooperands can be recreated by removeInvalidBranches
            $instance = $instance->removeMonooperandOperations();
        }

        // $instance->dump(true);

        if ($force_logical_core)
            $instance = $instance->forceLogicalCore();

        // $instance->dump(true);

        return $instance;
    }

    /**
     * Indexes operands by their fields and operators. This sorting is
     * used during the simplification step.
     *
     * @return array The 3 dimensions array of operands: field > operator > i
     */
    public function groupOperandsByFieldAndOperator()
    {
        $operandsByFields = [];
        foreach ($this->operands as $operand) {

            // Operation rules have no field but we need to keep them anyway
            $field = method_exists($operand, 'getField') ? $operand->getField() : '';

            if (!isset($operandsByFields[ $field ]))
                $operandsByFields[ $field ] = [];

            if (!isset($operandsByFields[ $field ][ $operand::operator ]))
                $operandsByFields[ $field ][ $operand::operator ] = [];

            $operandsByFields[ $field ][ $operand::operator ][] = $operand;
        }

        return $operandsByFields;
    }

    /**
     * Clones the rule and its operands.
     *
     * @return OperationRule A copy of the current instance with copied operands.
     */
    public function copy()
    {
        $copied_rule = clone $this;
        $copied_operands = [];
        foreach ($this->operands as $operand) {
            $copied_operands[] = $operand->copy();
        }

        VisibilityViolator::setHiddenProperty(
            $copied_rule,
            'operands',
            $copied_operands
        );

        return $copied_rule;
    }

    /**/
}
