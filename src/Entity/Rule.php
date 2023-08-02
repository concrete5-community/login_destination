<?php

namespace LoginDestination\Entity;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Represents a post-login location rule.
 *
 * @Doctrine\ORM\Mapping\Entity(
 * )
 * @Doctrine\ORM\Mapping\Table(
 *     name="LoginDestinationRules",
 *     indexes={
 *         @Doctrine\ORM\Mapping\Index(name="IX_LoginDestinationRules_Sort", columns={"ruleOrder", "ruleID"})
 *     },
 *     options={
 *         "comment": "Post-login location rules"
 *     }
 * )
 */
class Rule
{
    /**
     * Rule subject kind: in group.
     *
     * @var string
     */
    const SUBJECTKIND_GROUP_IN_EXACT = 'g1';

    /**
     * Rule subject kind: not in group.
     *
     * @var string
     */
    const SUBJECTKIND_GROUP_NOTIN_EXACT = 'g0';

    /**
     * Rule subject kind: in group on in child groups.
     *
     * @var string
     */
    const SUBJECTKIND_GROUP_IN_WITHCHILD = 'g3';

    /**
     * Rule subject kind: not in group in child groups.
     *
     * @var string
     */
    const SUBJECTKIND_GROUP_NOTIN_WITHCHILD = 'g2';

    /**
     * Rule subject kind: is user.
     *
     * @var string
     */
    const SUBJECTKIND_USER_IS = 'u1';

    /**
     * Rule subject kind: is not user.
     *
     * @var string
     */
    const SUBJECTKIND_USER_ISNOT = 'u0';

    /**
     * The rule ID.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", options={"unsigned": true, "comment": "Rule ID"})
     * @Doctrine\ORM\Mapping\Id
     * @Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $ruleID;

    /**
     * The rule order position.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=false, options={"unsigned": true, "comment": "Rule order position"})
     *
     * @var int
     */
    protected $ruleOrder;

    /**
     * Is the rule enabled?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is the rule enabled?"}))
     *
     * @var bool
     */
    protected $ruleEnabled;

    /**
     * Overwrite session value?
     *
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Overwrite session value?"}))
     *
     * @var bool
     */
    protected $overwriteSessionValue;

    /**
     * The rule subject kind.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=2, nullable=false, options={"comment": "Rule subject kind"})
     *
     * @var string
     */
    protected $ruleSubjectKind;

    /**
     * The rule subject ID.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=false, options={"unsigned": true, "comment": "Rule subject ID"})
     *
     * @var int
     */
    protected $ruleSubjectID;

    /**
     * The ID of the destination page.
     *
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=true, options={"unsigned": true, "comment": "ID of the destination page"})
     *
     * @var int|null
     */
    protected $ruleDestinationPageID;

    /**
     * The external URL destination.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "External URL destination"})
     *
     * @var string
     */
    protected $ruleDestinationExternalURL = '';

    /**
     * Get the rule ID.
     *
     * @return int|null
     */
    public function getRuleID()
    {
        return $this->ruleID;
    }

    /**
     * Get the rule order position.
     *
     * @return int
     */
    public function getRuleOrder()
    {
        return $this->ruleOrder;
    }

    /**
     * Set the rule order position.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setRuleOrder($value)
    {
        $this->ruleOrder = (int) $value;

        return $this;
    }

    /**
     * Is the rule enabled?
     *
     * @return bool
     */
    public function isRuleEnabled()
    {
        return $this->ruleEnabled;
    }

    /**
     * Is the rule enabled?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRuleEnabled($value)
    {
        $this->ruleEnabled = $value ? true : false;

        return $this;
    }


    /**
     * Overwrite session value?
     *
     * @return bool
     */
    public function isSessionValueOverwritten()
    {
        return $this->overwriteSessionValue;
    }

    /**
     * Overwrite session value?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setOverwriteSessionValue($value)
    {
        $this->overwriteSessionValue = $value ? true : false;

        return $this;
    }

    /**
     * Get the rule subject kind.
     *
     * @return string
     */
    public function getRuleSubjectKind()
    {
        return $this->ruleSubjectKind;
    }

    /**
     * Get the rule subject ID.
     *
     * @return int
     */
    public function getRuleSubjectID()
    {
        return $this->ruleSubjectID;
    }

    /**
     * Set the rule subject.
     *
     * @param string $kind
     * @param int $id
     *
     * @return $this
     */
    public function setRuleSubject($kind, $id)
    {
        $this->ruleSubjectKind = (string) $kind;
        $this->ruleSubjectID = (int) $id;

        return $this;
    }

    /**
     * Get the ID of the destination page.
     *
     * @return int|null
     */
    public function getRuleDestinationPageID()
    {
        return $this->ruleDestinationPageID;
    }

    /**
     * Set the ID of the destination page.
     *
     * @param int|null $value
     *
     * @return $this
     */
    public function setRuleDestinationPageID($value)
    {
        if (empty($value) || !is_scalar($value)) {
            $this->ruleDestinationPageID = null;
        } else {
            $value = (int) $value;
            $this->ruleDestinationPageID = $value > 0 ? $value : null;
        }

        return $this;
    }

    /**
     * Get the external URL destination.
     *
     * @return string
     */
    public function getRuleDestinationExternalURL()
    {
        return $this->ruleDestinationExternalURL;
    }

    /**
     * Set the external URL destination.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRuleDestinationExternalURL($value)
    {
        $this->ruleDestinationExternalURL = (string) $value;

        return $this;
    }
}
