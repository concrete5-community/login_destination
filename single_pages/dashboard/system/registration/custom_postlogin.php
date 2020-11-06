<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var LoginDestination\RuleRenderer $ruleRenderer
 * @var LoginDestination\Entity\Rule[] $rules
 */

if (empty($rules)) {
    ?>
    <div class="alert alert-info">
        <?= t('No rules defined.') ?><br />
        <?= t('You can add new rules by clicking the %s button.', sprintf('<code>%s</code>', t('New Rule')))?>
    </div>
    <?php
} else {
    ?>
    <table id="cpl-rules-table" class="table table-striped">
        <thead>
            <tr>
                <th class="cpl-rule-move"></th>
                <th class="cpl-rule-who"><?= t('Redirect Who') ?></th>
                <th class="cpl-rule-destination"><?= t('Redirect To') ?></th>
                <th class="cpl-rule-state"></th>
            </tr>
        </thead>
        <tbody id="cpl-rules">
            <?php
            foreach ($rules as $rule) {
                echo $ruleRenderer->render($rule);
            }
            ?>
        </tbody>
    </table>
    <script>
    $(document).ready(function() {
       window.initializeRuleList(<?= json_encode([
           'tokenName' => $token::DEFAULT_TOKEN_NAME,
           'actions' => [
               'sort' => [
                   'url' => (string) $view->action('sort_rules'),
                   'token' => $token->generate('cpl-rules-sort'),
               ],
           ],
       ]) ?>); 
    });
    </script>
    <?php
}
?>
<div class="ccm-dashboard-form-actions-wrapper">
    <div class="ccm-dashboard-form-actions">
        <a class="pull-right btn btn-primary" href="<?= $view->action('rule', 'new') ?>"><?= t('New Rule') ?></a>
    </div>
</div>
