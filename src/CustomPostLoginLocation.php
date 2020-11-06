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
     * {@inheritdoc}
     *
     * @see \Concrete\Core\User\PostLoginLocation::getDefaultPostLoginUrl()
     */
    public function getDefaultPostLoginUrl()
    {
        $result = '';
        foreach ($this->getRules() as $rule) {
            if ($this->isRuleApplicable($rule)) {
                $result = $this->getFinalUrl($rule);
                if ($result !== '') {
                    break;
                }
            }
        }
        if ($result === '') {
            $result = parent::getDefaultPostLoginUrl();
        }

        return $result;
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    private function getEntityManager()
    {
        if ($this->entityManager === null) {
        }

        return $this->entityManager;
    }

    /**
     * @return \Concrete\Core\Database\Connection\Connection
     */
    private function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->getEntityManager()->getConnection();
        }

        return $this->connection;
    }

    /**
     * @return \Concrete\Core\User\User|null
     */
    private function getCurrentlyLoggedInUser()
    {
        if ($this->currentlyLoggedInUser === false) {
            $u = new User();
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
     * @return Rule[]|\Generator
     */
    private function getRules()
    {
        $app = Application::getFacadeApplication();
        $entityManager = $app->make(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $ids = $connection->fetchAll('SELECT ruleID FROM LoginDestinationRules WHERE ruleEnabled = 1 ORDER BY ruleOrder, ruleID');
        foreach ($ids as $id) {
            $rule = $entityManager->find(Rule::class, $id);
            if ($rule !== null) {
                yield $rule;
            }
        }
    }

    /**
     * @param Rule $rule
     *
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
     * @param Rule $rule
     *
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
