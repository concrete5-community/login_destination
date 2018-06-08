<?php

namespace Concrete\Package\LoginDestination\Controller\SinglePage\Dashboard\System\Registration;

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Asset\CssAsset;
use Concrete\Core\Asset\JavascriptAsset;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Filesystem\FileLocator;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\Utility\Service\Validation\Numbers;
use LoginDestination\Entity\Rule;
use LoginDestination\RuleRenderer;

defined('C5_EXECUTE') or die('Access Denied.');

class CustomPostlogin extends DashboardPageController
{
    public function on_start()
    {
        parent::on_start();
        $defaultsPage = t('Login Destination');
        $ldPage = Page::getByPath('/dashboard/system/registration/postlogin');
        if ($ldPage && !$ldPage->isError()) {
            $defaultsPage = h(t($ldPage->getCollectionName()));
            $checker = new Checker($ldPage);
            if ($checker->canRead()) {
                $defaultsPage = sprintf('<a href="%s">%s</a>', $this->app->make(ResolverManagerInterface::class)->resolve([$ldPage]), $defaultsPage);
            }
        }
        $this->app->make('help/dashboard')->registerMessageString(
            '/dashboard/system/registration/custom_postlogin',
            t('The first rule that satisfy the criteria will be executed: you can sort them by using the hamburger icon.')
            . '<br /><br />' .
            t('If no rule is satisfied, the standard rules defined at the %s page will be used.', $defaultsPage)
        );
    }

    public function view()
    {
        $this->configureCPLAssets();
        $rules = $this->getRules();
        $this->set('ruleRenderer', $this->app->make(RuleRenderer::class));
        $this->set('rules', $rules);
    }

    public function sort_rules()
    {
        if (!$this->token->validate('cpl-rules-sort')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $post = $this->request->request;
        $ruleIds = $post->get('ruleIds');
        if (!is_array($ruleIds)) {
            throw new UserMessageException('Bad parameter: ruleIds[]');
        }
        foreach ($ruleIds as $categoryId) {
            if (!$valn->integer($categoryId, 1)) {
                throw new UserMessageException('Bad parameter: ruleIds');
            }
        }
        $ruleIds = array_map('intval', $ruleIds);
        if ($ruleIds !== array_unique($ruleIds)) {
            throw new UserMessageException('Bad parameter: ruleIds');
        }
        $rules = [];
        foreach ($this->getRules() as $rule) {
            $rules[$rule->getRuleID()] = $rule;
        }
        if (count($rules) !== count($ruleIds) || count(array_diff($ruleIds, array_keys($rules))) !== 0) {
            throw new UserMessageException('Rules mismatch');
        }
        foreach ($rules as $rule) {
            $rule->setRuleOrder(array_search($rule->getRuleID(), $ruleIds));
        }
        $this->entityManager->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    /**
     * @return \LoginDestination\Entity\Rule[]
     */
    private function getRules()
    {
        $repo = $this->entityManager->getRepository(Rule::class);

        return $repo->findBy([], ['ruleOrder' => 'ASC', 'ruleID' => 'ASC']);
    }

    private function configureCPLAssets()
    {
        $assetList = AssetList::getInstance();
        $fl = $this->app->make(FileLocator::class);
        $fl->addPackageLocation('login_destination');

        $r = $fl->getRecord('js/dashboard/rules.js');
        $asset = new JavascriptAsset('login_destination/dashboard/rules');
        $asset->setAssetURL($r->url);
        $asset->setAssetPath($r->file);
        $assetList->registerAsset($asset);
        $this->requireAsset($asset);

        $r = $fl->getRecord('css/dashboard/rules.css');
        $asset = new CssAsset('login_destination/dashboard/rules');
        $asset->setAssetURL($r->url);
        $asset->setAssetPath($r->file);
        $assetList->registerAsset($asset);
        $this->requireAsset($asset);
    }
}
