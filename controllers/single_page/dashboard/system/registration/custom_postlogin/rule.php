<?php

namespace Concrete\Package\LoginDestination\Controller\SinglePage\Dashboard\System\Registration\CustomPostlogin;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\Widget\UserSelector;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\User;
use Concrete\Core\Utility\Service\Validation\Numbers;
use LoginDestination\DestinationPicker;
use LoginDestination\Entity\Rule as RuleEntity;
use LoginDestination\GroupSelector;

defined('C5_EXECUTE') or die('Access Denied.');

class Rule extends DashboardPageController
{
    public function view($id = '')
    {
        $subjectKinds = [];
        if ($id === 'new') {
            $rule = new RuleEntity();
            $rule->setRuleEnabled(true);
            $subjectKinds[''] = t('Please Select');
        } else {
            $id = $id && is_scalar($id) ? (int) $id : null;
            $rule = $id ? $this->entityManager->find(RuleEntity::class, $id) : null;
            if ($rule === null) {
                $this->flash('error', t('Unable to find the specified rule.'));

                return $this->app->make(ResponseFactoryInterface::class)->redirect(
                    $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/registration/custom_postlogin']),
                    302
                );
            }
        }
        $this->set('rule', $rule);
        $subjectKinds += [
            RuleEntity::SUBJECTKIND_GROUP_IN_EXACT => t('If the user is in the following group'),
            RuleEntity::SUBJECTKIND_GROUP_IN_WITHCHILD => t('If the user is in the following group or in its sub-groups'),
            RuleEntity::SUBJECTKIND_GROUP_NOTIN_EXACT => t('If the user is in not the following group'),
            RuleEntity::SUBJECTKIND_GROUP_NOTIN_WITHCHILD => t('If the user is in not the following group or in its sub-groups'),
            RuleEntity::SUBJECTKIND_USER_IS => t('If the user is the following one'),
            RuleEntity::SUBJECTKIND_USER_ISNOT => t('If the user is not the following one'),
        ];
        $this->set('subjectKinds', $subjectKinds);
        $this->set('userSelector', $this->app->make(UserSelector::class));
        $this->set('groupSelector', $this->app->make(GroupSelector::class));
        $this->set('destinationPicker', $this->app->make(DestinationPicker::class));
    }

    public function save($id = '')
    {
        try {
            if (!$this->token->validate('ld-edit-' . $id)) {
                throw new UserMessageException($this->token->getErrorMessage());
            }
            $post = $this->request->request;
            if ($id === 'new') {
                $rule = new RuleEntity();
            } else {
                $id = $id && is_scalar($id) ? (int) $id : null;
                $rule = $id ? $this->entityManager->find(RuleEntity::class, $id) : null;
                if ($rule === null) {
                    throw new UserMessageException(t('Unable to find the specified rule.'));
                }
            }
            $valn = $this->app->make(Numbers::class);
            $subjectKind = $post->get('subjectKind');
            if (
                $subjectKind === RuleEntity::SUBJECTKIND_GROUP_IN_EXACT
                || $subjectKind === RuleEntity::SUBJECTKIND_GROUP_IN_WITHCHILD
                || $subjectKind === RuleEntity::SUBJECTKIND_GROUP_NOTIN_EXACT
                || $subjectKind === RuleEntity::SUBJECTKIND_GROUP_NOTIN_WITHCHILD
            ) {
                $subjectID = $post->get('selectedGroup');
                $group = $valn->integer($subjectID, 1) ? Group::getByID($subjectID) : null;
                if ($group === null) {
                    throw new UserMessageException(t('Please specify a group.'));
                }
            } elseif ($subjectKind === RuleEntity::SUBJECTKIND_USER_IS || $subjectKind === RuleEntity::SUBJECTKIND_USER_ISNOT) {
                $subjectID = $post->get('selectedUser');
                $user = $valn->integer($subjectID, 1) ? User::getByUserID($subjectID) : null;
                if ($user === null) {
                    throw new UserMessageException(t('Please specify a user.'));
                }
            } else {
                throw new UserMessageException(t('Please specify the rule kind.'));
            }
            $errors = null;
            list(, $cid, $url) = $this->app->make(DestinationPicker::class)->decodeDestination('destination', DestinationPicker::DESTINATIONKIND_PAGE | DestinationPicker::DESTINATIONKIND_EXTERNALURL, $errors, ['required' => true]);
            if ($errors->has()) {
                throw new UserMessageException($errors->toText());
            }
            $rule
                ->setRuleEnabled($post->get('enabled') === 'Y')
                ->setRuleSubject($subjectKind, $subjectID)
                ->setRuleDestinationPageID($cid)
                ->setRuleDestinationExternalURL($url)
            ;
            if ($id === 'new') {
                $cn = $this->entityManager->getConnection();
                $rule->setRuleOrder(1 + (int) $cn->fetchColumn('SELECT MAX(ruleOrder) FROM LoginDestinationRules'));
                $this->entityManager->persist($rule);
            }
            $this->entityManager->flush($rule);
            $this->set('success', $id === 'new' ? t('The new rule has been added.') : t('The rule has been updated.'));

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/registration/custom_postlogin']),
                302
            );
        } catch (UserMessageException $x) {
            $this->error->add($x);

            return $this->view($id);
        }
    }

    public function delete($id = '')
    {
        try {
            if (!$this->token->validate('ld-delete-' . $id)) {
                throw new UserMessageException($this->token->getErrorMessage());
            }
            $id = $id && is_scalar($id) ? (int) $id : null;
            $rule = $id ? $this->entityManager->find(RuleEntity::class, $id) : null;
            if ($rule === null) {
                throw new UserMessageException(t('Unable to find the specified rule.'));
            }
            $this->entityManager->remove($rule);
            $this->entityManager->flush($rule);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/registration/custom_postlogin']),
                302
            );
        } catch (UserMessageException $x) {
            $this->error->add($x);

            return $this->view($id);
        }
    }
}
