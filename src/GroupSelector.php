<?php

namespace LoginDestination;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Form\Service\Widget\GroupSelector as CoreGroupSelector;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\Group;
use Concrete\Core\View\View;

defined('C5_EXECUTE') or die('Access Denied.');

class GroupSelector
{
    protected static $idCounter = 0;

    /**
     * The URL resolver manager.
     *
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;
    
    /**
     * The URL resolver manager.
     *
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $resolverManager;

    /**
     * The application instance used to create the core group selector for Concrete 9.2.0+
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * Initialize the instance.
     */
    public function __construct(Repository $config, ResolverManagerInterface $resolverManager, Application $app)
    {
        $this->config = $config;
        $this->resolverManager = $resolverManager;
        $this->app = $app;
    }

    /**
     * Build the HTML to be placed in a page to choose a group using a popup dialog.
     *
     * @param string $fieldName the name of the field
     * @param int|\Concrete\Core\User\Group\Group|null $gID the ID of the group (or a Group instance) to be initially selected
     *
     * @example
     * <code>
     *     $groupSelector->selectSingleGroup('groupID', 123);
     * </code>.
     */
    public function selectSingleGroup($fieldName, $gID = null)
    {
        $permissions = new Checker();
        $canAccessGroupSearch = $permissions->canAccessGroupSearch();
            
        if ($canAccessGroupSearch && version_compare($this->config->get('concrete.version'), '9.2.0a3') >= 0) {
            $this->app->make(CoreGroupSelector::class)->selectGroup($fieldName, $gID);
            return;
        }
        $v = View::getRequestInstance();
        $v->requireAsset('core/groups');

        if (is_object($gID)) {
            $selectedGroup = $gID;
        } elseif (!empty($gID) && is_scalar($gID)) {
            $selectedGroup = Group::getByID((int) $gID);
        } else {
            $selectedGroup = null;
        }
        $selectedGroupIP = $selectedGroup ? (int) $selectedGroup->getGroupID() : 0;
        if ($selectedGroupIP === 0) {
            $selectedGroupIP = null;
            $selectedGroup = null;
        }
        $fieldID = $fieldName;
        if (strpos($fieldID, '[') !== false) {
            $fieldID = str_replace(['[', ']', ''], $fieldName) . '_' . self::$idCounter++;
        }

        if ($canAccessGroupSearch) {
            if (version_compare($this->config->get('concrete.version'), '9') >= 0) {
                $pickURL = (string) $this->resolverManager->resolve(['/ccm/system/dialogs/groups/search']);
                $clearClass = 'fa fa-times-circle';
            } else {
                $pickURL = (string) $this->resolverManager->resolve(['/ccm/system/dialogs/group/search']);
                $clearClass = 'fa fa-close';
            }
            $dialogTitle = t('Choose a Group');
            $unselectedStyle = $selectedGroup ? ' style="display: none"' : '';
            $selectedStyle = $selectedGroup ? '' : ' style="display: none"';
            $selectedGroupName = $selectedGroup ? $selectedGroup->getGroupDisplayName() : '';
            $result = <<<EOT
<input type="hidden" name="{$fieldName}" id="{$fieldID}--value" value="{$selectedGroupIP}" />
<div class="ccm-item-selector">
    <a id="{$fieldID}--unselected" href="{$pickURL}" dialog-modal="true" dialog-title="{$dialogTitle}" dialog-on-open="window.__currentGroupSelector = &quot;{$fieldID}&quot;" dialog-on-destroy="delete window.__currentGroupSelector" {$unselectedStyle}>{$dialogTitle}</a>
    <div id="{$fieldID}--selected" class="ccm-item-selector-item-selected"{$selectedStyle}>
        <a id="{$fieldID}--unselect" href="#" class="ccm-item-selector-clear"><i class="{$clearClass}"></i></a>
        <div id="{$fieldID}--selected-name">{$selectedGroupName}</div>
    </div>
</div>
<script>
$(document).ready(function() {
    function setGroup(data) {
        $('#{$fieldID}--value').val(data ? data.gID : '');
        $('#{$fieldID}--selected-name').text(data ? data.gName : '');
        $('#{$fieldID}--unselected').toggle(data ? false : true);
        $('#{$fieldID}--selected').toggle(data ? true : false);
    }
    ConcreteEvent.subscribe('SelectGroup', function (e, data) {
        if (window.__currentGroupSelector === "{$fieldID}" && data && data.gID) {
            setGroup(data);
            jQuery.fn.dialog.closeTop();
        }
    });
    $('#{$fieldID}--unselected').dialog();
    $('#{$fieldID}--unselect').on('click', function (e) {
        e.preventDefault();
        setGroup(null);
    });
});
</script>
EOT
            ;
        } else {
            // Read only
            $selectedGroupName = $selectedGroup ? $selectedGroup->getGroupDisplayName() : tc('Group', 'None Selected');
            $result = <<<EOT
<div class="ccm-item-selector">
    <div class="ccm-item-selector-item-selected">
        <input type="hidden" name="{$fieldName}" value="{$selectedGroupIP}">
        <div class="ccm-item-selector-item-selected-title">{$selectedGroupName}</div>
    </div>
</div>
EOT
            ;
        }

        echo $result;
    }
}
