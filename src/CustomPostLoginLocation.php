<?php

namespace LoginDestination;

use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\PostLoginLocation;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use LoginDestination\Entity\Rule;

defined('C5_EXECUTE') or die('Access Denied.');

class CustomPostLoginLocation extends PostLoginLocation
{
    /**
     * @var \Concrete\Core\User\User|false|null
     */
    private $currentlyLoggedInUser = false;

    /**
     * @var int[]|false|null
     */
    private $groupsOfCurrentlyLoggedInUser = false;

    /**
     * @var \Concrete\Core\User\Group\Group[]
     */
    private $parentGroupsOf = [];

    /**
     * @var \LoginDestination\Entity\Rule[]|null
     */
    private $rules;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\User\PostLoginLocation::getSessionPostLoginUrl()
     */
    public function getSessionPostLoginUrl($resetSessionPostLoginUrl = false)
    {
        $parentResult = parent::getSessionPostLoginUrl($resetSessionPostLoginUrl);
        foreach ($this->getRules() as $rule) {
            if ($rule->isSessionValueOverwritten() && $this->isRuleApplicable($rule)) {
                $url = $this->getFinalUrl($rule);
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return $parentResult;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\User\PostLoginLocation::getDefaultPostLoginUrl()
     */
    public function getDefaultPostLoginUrl()
    {
        foreach ($this->getRules() as $rule) {
            if (!$rule->isSessionValueOverwritten() && $this->isRuleApplicable($rule)) {
                $url = $this->getFinalUrl($rule);
                if ($url !== '') {
                    return $url;
                }
            }
        }
        
        return parent::getDefaultPostLoginUrl();
    }

    /**
     * @return \Concrete\Core\User\User|null
     */
    private function getCurrentlyLoggedInUser()
    {
        if ($this->currentlyLoggedInUser === false) {
            $u = function_exists('\app') ? \app(User::class) : new User();
            $this->currentlyLoggedInUser = $u->isRegistered() ? $u : null;
        }

        return $this->currentlyLoggedInUser;
    }

    /**
     * @return int[]|null
     */
    private function getGroupsOfCurrentlyLoggedInUser()
    {
        if ($this->groupsOfCurrentlyLoggedInUser === false) {
            $me = $this->getCurrentlyLoggedInUser();
            if ($me === null) {
                $this->groupsOfCurrentlyLoggedInUser = null;
            } else {
                $groups = $me->getUserGroups();
                $this->groupsOfCurrentlyLoggedInUser = is_array($groups) ? array_map('intval', array_filter($groups)) : [];
            }
        }

        return $this->groupsOfCurrentlyLoggedInUser;
    }

    /**
     * @return \LoginDestination\Entity\Rule[]|\Generator
     */
    private function getRules()
    {
        if ($this->rules === null) {
            $app = Application::getFacadeApplication();
            $entityManager = $app->make(EntityManagerInterface::class);
            $repo = $entityManager->getRepository(Rule::class);
            $this->rules = $repo->findBy(['ruleEnabled' => 1], ['ruleOrder' => 'ASC', 'ruleID' => 'ASC']);
        }
        return $this->rules;
    }

    /**
     * @return bool
     */
    private function isRuleApplicable(Rule $rule)
    {
        switch ($rule->getRuleSubjectKind()) {
            case Rule::SUBJECTKIND_GROUP_IN_EXACT:
                return $this->isUserInGroup($rule->getRuleSubjectID(), false) === true;
            case Rule::SUBJECTKIND_GROUP_IN_WITHCHILD:
                return $this->isUserInGroup($rule->getRuleSubjectID(), true) === true;
            case Rule::SUBJECTKIND_GROUP_NOTIN_EXACT:
                return $this->isUserInGroup($rule->getRuleSubjectID(), false) === false;
            case Rule::SUBJECTKIND_GROUP_NOTIN_WITHCHILD:
                return $this->isUserInGroup($rule->getRuleSubjectID(), true) === false;
            case Rule::SUBJECTKIND_USER_IS:
                return $this->isUserID($rule->getRuleSubjectID()) === true;
            case Rule::SUBJECTKIND_USER_ISNOT:
                return $this->isUserID($rule->getRuleSubjectID()) === false;
        }

        return false;
    }

    /**
     * @param int $gID
     * @param bool $orChildGroups
     *
     * @return bool|null
     */
    private function isUserInGroup($gID, $orChildGroups)
    {
        $userGroups = $this->getGroupsOfCurrentlyLoggedInUser();
        if ($userGroups === null) {
            $result = null;
        } else {
            if (in_array($gID, $userGroups, true)) {
                $result = true;
            } else {
                $result = false;
                if ($orChildGroups && !empty($userGroups)) {
                    foreach ($userGroups as $userGroup) {
                        if ($this->isGroupInSubgroupsOf($gID, $userGroup)) {
                            $result = true;
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int $parentGroupID
     * @param int $childGroupID
     *
     * @return bool
     */
    private function isGroupInSubgroupsOf($parentGroupID, $childGroupID)
    {
        if (isset($this->parentGroupsOf[$childGroupID])) {
            $parentGroups = $this->parentGroupsOf[$childGroupID];
        } else {
            $parentGroups = [];
            $group = Group::getByID($childGroupID);
            if ($group) {
                foreach ($group->getParentGroups() as $group) {
                    $parentGroups[] = (int) $group->getGroupID();
                }
            }
            $this->parentGroupsOf[$childGroupID] = $parentGroups;
        }

        return in_array($parentGroupID, $parentGroups, true);
    }

    /**
     * @param int $uID
     *
     * @return bool|null
     */
    private function isUserID($uID)
    {
        $me = $this->getCurrentlyLoggedInUser();

        return $me === null ? null : (int) $me->getUserID() === $uID;
    }

    /**
     * @return string
     */
    private function getFinalUrl(Rule $rule)
    {
        $cID = $rule->getRuleDestinationPageID();
        if ($cID) {
            $page = Page::getByID($cID);
            if ($page && !$page->isError()) {
                $result = (string) $this->resolverManager->resolve([$page]);
            } else {
                $result = '';
            }
        } else {
            $result = $rule->getRuleDestinationExternalURL();
        }

        return $result;
    }
}
