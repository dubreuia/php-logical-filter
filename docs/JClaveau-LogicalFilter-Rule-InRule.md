JClaveau\LogicalFilter\Rule\InRule
===============

This class represents a rule that expect a value to belong to a list of others.

This class represents a rule that expect a value to be one of the list of
possibilities only.


* Class name: InRule
* Namespace: JClaveau\LogicalFilter\Rule
* Parent class: [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)



Constants
----------


### operator

    const operator = 'or'







Methods
-------


### __construct

    mixed JClaveau\LogicalFilter\Rule\InRule::__construct(string $field, array $possibilities)





* Visibility: **public**


#### Arguments
* $field **string** - &lt;p&gt;The field to apply the rule on.&lt;/p&gt;
* $possibilities **array** - &lt;p&gt;The values the field can belong to.&lt;/p&gt;



### getPossibilities

    array JClaveau\LogicalFilter\Rule\InRule::getPossibilities()





* Visibility: **public**




### addPossibilities

    array JClaveau\LogicalFilter\Rule\InRule::addPossibilities(array $possibilities)





* Visibility: **public**


#### Arguments
* $possibilities **array**



### rootifyDisjunctions

    \JClaveau\LogicalFilter\Rule\OrRule JClaveau\LogicalFilter\Rule\OrRule::rootifyDisjunctions()

Replace all the OrRules of the RuleTree by one OrRule at its root.



* Visibility: **public**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)




### toArray

    mixed JClaveau\LogicalFilter\Rule\OrRule::toArray($debug)





* Visibility: **public**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)


#### Arguments
* $debug **mixed**



### aboveRuleUnifySorter

    integer JClaveau\LogicalFilter\Rule\OrRule::aboveRuleUnifySorter(\JClaveau\LogicalFilter\Rule\AboveRule $a, \JClaveau\LogicalFilter\Rule\AboveRule $b)

This is called by the unifyOperands() method to choose which AboveRule
to keep for a given field.

It's used as a usort() parameter.

* Visibility: **protected**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)


#### Arguments
* $a **[JClaveau\LogicalFilter\Rule\AboveRule](JClaveau-LogicalFilter-Rule-AboveRule.md)**
* $b **[JClaveau\LogicalFilter\Rule\AboveRule](JClaveau-LogicalFilter-Rule-AboveRule.md)**



### belowRuleUnifySorter

    integer JClaveau\LogicalFilter\Rule\OrRule::belowRuleUnifySorter(\JClaveau\LogicalFilter\Rule\BelowRule $a, \JClaveau\LogicalFilter\Rule\BelowRule $b)

This is called by the unifyOperands() method to choose which BelowRule
to keep for a given field.

It's used as a usort() parameter.

* Visibility: **protected**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)


#### Arguments
* $a **[JClaveau\LogicalFilter\Rule\BelowRule](JClaveau-LogicalFilter-Rule-BelowRule.md)**
* $b **[JClaveau\LogicalFilter\Rule\BelowRule](JClaveau-LogicalFilter-Rule-BelowRule.md)**



### removeInvalidBranches

    \JClaveau\LogicalFilter\Rule\OrRule JClaveau\LogicalFilter\Rule\OrRule::removeInvalidBranches()

Removes rule branches that cannot produce result like:
A = 1 && ( (B < 2 && B > 3) || (C = 8 && C = 10) ) <=> A = 1



* Visibility: **public**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)




### hasSolution

    boolean JClaveau\LogicalFilter\Rule\OrRule::hasSolution()

Checks if the tree below the current OperationRule can have solutions
or if it contains contradictory rules.



* Visibility: **public**
* This method is defined by [JClaveau\LogicalFilter\Rule\OrRule](JClaveau-LogicalFilter-Rule-OrRule.md)


