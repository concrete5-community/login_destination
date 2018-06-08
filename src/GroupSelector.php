<?php

namespace LoginDestination;

use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\Group;
use Concrete\Core\View\View;

defined('C5_EXECUTE') or die('Access Denied.');

class GroupSelector
{
    protected static $idCounter = 0;

    /**
     * The application container instance.
     *
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $resolverManager;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Application\Application $app
     * @param ResolverManagerInterface $resolverManager
     */
    public function __construct(ResolverManagerInterface $resolverManager)
    {
        $this->resolverManager = $resolverManager;
    }

    /**
     * Build the HTML to be placed in a page to choose a group using a popup dialog.
     *
     * @param string $fieldName the name of the field
     * @param int|\Concrete\Core\User\Group\Group|null $gID the ID of the group (or a Group instance) to be initially selected
     *
     * @return string
     *
     * @example
     * <code>
     *     $groupSelector->selectSingleGroup('groupID', 123);
     * </code>.
     */
    public function selectSingleGroup($fieldName, $gID = null)
    {
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

        $permissions = new Checker();
        if ($permissions->canAccessGroupSearch()) {
            $pickURL = (string) $this->resolverManager->resolve(['/ccm/system/dialogs/group/search']);
            $dialogTitle = t('Choose a Group');
            $unselectedStyle = $selectedGroup ? ' style="display: none"' : '';
            $selectedStyle = $selectedGroup ? '' : ' style="display: none"';
            $selectedGroupName = $selectedGroup ? $selectedGroup->getGroupDisplayName() : '';
            $result = <<<EOT
<input type="hidden" name="{$fieldName}" id="{$fieldID}--value" value="{$selectedGroupIP}" />
<div class="ccm-item-selector">
    <a id="{$fieldID}--unselected" href="{$pickURL}" dialog-modal="true" dialog-title="{$dialogTitle}" dialog-on-open="window.__currentGroupSelector = &quot;{$fieldID}&quot;" dialog-on-destroy="delete window.__currentGroupSelector" {$unselectedStyle}>{$dialogTitle}</a>
    <div id="{$fieldID}--selected" class="ccm-item-selector-item-selected"{$selectedStyle}>
        <a id="{$fieldID}--unselect" href="#" class="ccm-item-selector-clear"><i class="fa fa-close"></i></a>
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

        return $result;
    }
}
