<?php

namespace LoginDestination;

use Concrete\Core\Page\Page;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\User;
use Concrete\Core\View\View;
use LoginDestination\Entity\Rule;

defined('C5_EXECUTE') or die('Access Denied.');

class RuleRenderer
{
    /**
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    private $resolver;

    public function __construct(ResolverManagerInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param Rule $rule
     * @param View $view
     *
     * @return string
     */
    public function render(Rule $rule)
    {
        $ruleID = $rule->getRuleID();
        $editURL = (string) $this->resolver->resolve(['/dashboard/system/registration/custom_postlogin/rule', $ruleID]);
        $whoText = $this->getWhoText($rule);
        list($destinationText, $destinationURL) = $this->getDestinationTexts($rule);
        $stateClass = $rule->isRuleEnabled() ? 'label-success text-success' : 'label-danger text-danger';
        $stateText = $rule->isRuleEnabled() ? tc('Rule', 'enabled') : tc('Rule', 'disabled');

        return <<<EOT
<tr class="cpl-rule" data-rule-id="{$ruleID}">
    <td class="cpl-rule-move cpl-rule-move-drag">
        <i class="fa fa-bars"></i>
    </td>
    <td class="cpl-rule-move cpl-rule-move-arrow">
        <a href="#" data-move-delta="-1"><i class="fa fa-arrow-up"></i></a>
    </td>
    <td class="cpl-rule-move cpl-rule-move-arrow">
        <a href="#" data-move-delta="1"><i class="fa fa-arrow-down"></i></a>
    </td>
    <td class="cpl-rule-who">
        <a href="{$editURL}">{$whoText}</a>
    </td>
    <td class="cpl-rule-destination">
        <a href="{$destinationURL}" target="_blank">{$destinationText}</a>
    </td>
    <td class="cpl-rule-state">
        <span class="label {$stateClass}">{$stateText}</span>
    </td>
</tr>
EOT
        ;
    }

    /**
     * @param Rule $rule
     *
     * @return string
     */
    private function getWhoText(Rule $rule)
    {
        switch ($rule->getRuleSubjectKind()) {
            case Rule::SUBJECTKIND_GROUP_IN_EXACT:
                return t('Users in group %s', $this->getGroupName($rule->getRuleSubjectID()));
            case Rule::SUBJECTKIND_GROUP_IN_WITHCHILD:
                return t('Users in group (or sub-groups of) %s', $this->getGroupName($rule->getRuleSubjectID()));
            case Rule::SUBJECTKIND_GROUP_NOTIN_EXACT:
                return t('Users not in group %s', $this->getGroupName($rule->getRuleSubjectID()));
            case Rule::SUBJECTKIND_GROUP_NOTIN_WITHCHILD:
                return t('Users not in group (or sub-groups of) %s', $this->getGroupName($rule->getRuleSubjectID()));
            case Rule::SUBJECTKIND_USER_IS:
                return t('User is %s', $this->getUserName($rule->getRuleSubjectID()));
            case Rule::SUBJECTKIND_USER_ISNOT:
                return t('User is not %s', $this->getUserName($rule->getRuleSubjectID()));
        }

        return '?';
    }

    /**
     * @param int $gID
     *
     * @return string
     */
    private function getGroupName($gID)
    {
        $group = $gID ? Group::getByID($gID) : null;

        return $group === null ?
            ('<span class="label label-danger">' . t('GROUP NOT FOUND (id: %s)', $gID) . '</span>') :
            ('<span class="label label-default">' . $group->getGroupDisplayName(false) . '</span>')
        ;
    }

    /**
     * @param int $uID
     *
     * @return string
     */
    private function getUserName($uID)
    {
        $user = $uID ? User::getByUserID($uID) : null;

        return $user === null ?
            ('<span class="label label-danger">' . t('USER NOT FOUND (id: %s)', $uID) . '</span>') :
            ('<span class="label label-default">' . h($user->getUserName()) . '</span>')
        ;
    }

    /**
     * @param Rule $rule
     *
     * @return string[]
     */
    private function getDestinationTexts(Rule $rule)
    {
        $cID = $rule->getRuleDestinationPageID();
        if ($cID !== null) {
            $page = Page::getByID($cID);
            if ($page && !$page->isError()) {
                $result = [
                    h($page->getCollectionName()),
                    (string) $this->resolver->resolve([$page]),
                ];
            } else {
                $result = [
                    t('PAGE NOT FOUND (id: %s)', $cID),
                    '#',
                ];
            }
        } else {
            $externalURL = $rule->getRuleDestinationExternalURL();
            if ($externalURL !== '') {
                $result = [
                    h($externalURL),
                    h($externalURL),
                ];
            } else {
                $result = [
                    t('NOT CONFIGURED'),
                    '#',
                ];
            }
        }

        return $result;
    }
}
