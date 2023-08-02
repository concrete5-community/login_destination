<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Form\Service\Widget\UserSelector $userSelector
 * @var LoginDestination\GroupSelector $groupSelector
 * @var LoginDestination\Entity\Rule $rule
 * @var LoginDestination\DestinationPicker $destinationPicker
 * @var array $subjectKinds
 */

?>
<form method="POST" action="<?= $view->action('save', $rule->getRuleID() ?: 'new') ?>">
    <?php $token->output('ld-edit-' . ($rule->getRuleID() ?: 'new')) ?>

    <div class="form-group">
        <?= $form->label('', t('Rule is enabled?')) ?>
        <div class="radio">
            <label>
                <?= $form->radio('enabled', 'Y', (bool) $rule->isRuleEnabled()) ?>
                <?= t('Yes')?>
            </label>
        </div>
        <div class="radio">
            <label>
                <?= $form->radio('enabled', 'N', !(bool) $rule->isRuleEnabled()) ?>
                <?= t('No')?>
            </label>
        </div>
    </div>

    <div class="form-group">
        <?= $form->label('', t("Override destination stored in users' session?")) ?>
        <div class="radio">
            <label>
                <?= $form->radio('overwriteSessionValue', 'Y', (bool) $rule->isSessionValueOverwritten()) ?>
                <?= t('Yes')?>
            </label>
        </div>
        <div class="radio">
            <label>
                <?= $form->radio('overwriteSessionValue', 'N', !(bool) $rule->isSessionValueOverwritten()) ?>
                <?= t('No')?>
            </label>
        </div>
    </div>
    
    <div class="form-group">
        <?= $form->label('subjectKind', t('Rule Kind')) ?>
        <?= $form->select('subjectKind', $subjectKinds, (string) $rule->getRuleSubjectKind(), ['required' => 'required']) ?>
        <div class="ld-pick" id="ld-pick-g"<?= strpos((string) $rule->getRuleSubjectKind(), 'g') === 0 ? '' : ' style="display: none"' ?>>
            <?php $groupSelector->selectSingleGroup('selectedGroup', strpos((string) $rule->getRuleSubjectKind(), 'g') === 0 ? $rule->getRuleSubjectID() : null) ?>
        </div>
        <div class="ld-pick" id="ld-pick-u"<?= strpos((string) $rule->getRuleSubjectKind(), 'u') === 0 ? '' : ' style="display: none"' ?>>
            <?= $userSelector->selectUser('selectedUser', strpos((string) $rule->getRuleSubjectKind(), 'u') === 0 ? $rule->getRuleSubjectID() : null) ?>
        </div>
    </div>

    <div class="form-group">
        <?= $form->label('destination', t('Destination')) ?>
        <?= $destinationPicker->destination('destination', $rule->getRuleDestinationPageID(), $rule->getRuleDestinationExternalURL(), false, ['required' => true]) ?>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a class="btn btn-default" href="<?= URL::to('/dashboard/system/registration/custom_postlogin') ?>"><?= t('Cancel') ?></a>
            <?php
            if ($rule->getRuleID()) {
                ?>
                <button class="btn btn-danger" id="ld-delete-rule"><?= t('Delete') ?></button>
                <?php
            }
            ?>
            <button class="btn btn-primary" type="submit"><?= t('Save') ?></button>
        </div>
    </div>

</form>

<?php
if ($rule->getRuleID()) {
    ?>
    <div id="ld-delete-rule-dialog" style="display: none">
        <form method="POST" class="form-stacked" style="padding-left: 0px" action="<?= $view->action('delete', $rule->getRuleID()) ?>">
            <?php $token->output('ld-delete-' . $rule->getRuleID()) ?>
            <p><?= t('Are you sure? This action cannot be undone.') ?></p>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-danger pull-right" onclick="$('#ld-delete-rule-dialog form').submit()"><?= t('Delete') ?></button>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        $('#ld-delete-rule').on('click', function(e) {
            e.preventDefault();
            jQuery.fn.dialog.open({
                element: '#ld-delete-rule-dialog',
                modal: true,
                width: 320,
                title: <?= json_encode(t('Delete Rule')) ?>,
                height: 'auto'
            });
        });
    });
    </script>
    <?php
}
?>
<script>
$(document).ready(function() {
    $('#subjectKind')
        .on('change', function() {
            var v = $(this).val(), firstChar = v ? v.charAt(0) : '';
            $('.ld-pick').hide();
            $('.ld-pick#ld-pick-' + firstChar).show();
        })
        .trigger('change')
    ;
});

</script>
